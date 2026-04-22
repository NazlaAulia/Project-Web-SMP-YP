<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function formatNomorWa($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    if (substr($nomor, 0, 1) === '0') {
        $nomor = '62' . substr($nomor, 1);
    } elseif (substr($nomor, 0, 2) !== '62') {
        return false;
    }

    return $nomor;
}

if (!isset($conn)) {
    respon('error', 'conn tidak terbaca');
}

if ($conn->connect_error) {
    respon('error', 'Koneksi database gagal: ' . $conn->connect_error);
}

$nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
$nisn            = trim($_POST['nisn'] ?? '');
$jenis_kelamin   = trim($_POST['jenis_kelamin'] ?? '');
$tanggal_lahir   = trim($_POST['tanggal_lahir'] ?? '');
$alamat          = trim($_POST['alamat'] ?? '');
$asal_sekolah    = trim($_POST['asal_sekolah'] ?? '');
$no_hp           = trim($_POST['no_hp'] ?? '');
$email           = trim($_POST['email'] ?? '');
$pendapatan_ortu = trim($_POST['pendapatan_ortu'] ?? '');
$nama_wali       = trim($_POST['nama_wali'] ?? '');

if (
    $nama_lengkap === '' ||
    $nisn === '' ||
    $jenis_kelamin === '' ||
    $tanggal_lahir === '' ||
    $alamat === '' ||
    $asal_sekolah === '' ||
    $no_hp === '' ||
    $pendapatan_ortu === '' ||
    $nama_wali === ''
) {
    respon('error', 'Semua field wajib diisi kecuali email.');
}

if (!in_array($jenis_kelamin, ['L', 'P'])) {
    respon('error', 'Jenis kelamin tidak valid.');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respon('error', 'Format email tidak valid.');
}

if (!preg_match('/^[0-9]{10}$/', $nisn)) {
    respon('error', 'NISN harus terdiri dari 10 digit angka.');
}

if (!preg_match('/^(08|62)[0-9]{8,13}$/', $no_hp)) {
    respon('error', 'Nomor HP orang tua / wali tidak valid. Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.');
}

$no_hp = formatNomorWa($no_hp);

if ($no_hp === false) {
    respon('error', 'Nomor HP orang tua / wali gagal diformat.');
}

/* cek NISN ganda */
$cek = $conn->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE nisn = ?");
if (!$cek) {
    respon('error', 'Prepare cek gagal: ' . $conn->error);
}

$cek->bind_param("s", $nisn);

if (!$cek->execute()) {
    respon('error', 'Execute cek gagal: ' . $cek->error);
}

$cek->store_result();

if ($cek->num_rows > 0) {
    $cek->close();
    respon('error', 'NISN sudah pernah didaftarkan.');
}
$cek->close();

/* cek kuota maksimal 60 siswa */
$kuota_max = 60;

$qJumlah = $conn->query("SELECT COUNT(*) AS total FROM pendaftaran");
if (!$qJumlah) {
    respon('error', 'Gagal menghitung jumlah pendaftar: ' . $conn->error);
}

$dataJumlah = $qJumlah->fetch_assoc();
$jumlah_pendaftar = (int)($dataJumlah['total'] ?? 0);
$kuota_tersisa = $kuota_max - $jumlah_pendaftar;

if ($kuota_tersisa <= 0) {
    respon('error', 'Kuota pendaftaran sudah penuh. Maksimal 60 siswa.');
}

$status = 'menunggu';
$tanggal_daftar = date('Y-m-d');
$pendapatan_ortu = (float)$pendapatan_ortu;

$stmt = $conn->prepare("
    INSERT INTO pendaftaran
    (nama_lengkap, nisn, jenis_kelamin, tanggal_lahir, alamat, asal_sekolah, no_hp, email, tanggal_daftar, status, pendapatan_ortu, nama_wali)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    respon('error', 'Prepare insert gagal: ' . $conn->error);
}

$stmt->bind_param(
    "ssssssssssds",
    $nama_lengkap,
    $nisn,
    $jenis_kelamin,
    $tanggal_lahir,
    $alamat,
    $asal_sekolah,
    $no_hp,
    $email,
    $tanggal_daftar,
    $status,
    $pendapatan_ortu,
    $nama_wali
);

if ($stmt->execute()) {
    $id_pendaftaran = $stmt->insert_id;

    $jumlah_pendaftar_baru = $jumlah_pendaftar + 1;
    $kuota_tersisa_baru = $kuota_max - $jumlah_pendaftar_baru;

    respon('success', 'Pendaftaran berhasil dikirim.', [
        'id_pendaftaran'   => $id_pendaftaran,
        'nama_lengkap'     => $nama_lengkap,
        'nisn'             => $nisn,
        'asal_sekolah'     => $asal_sekolah,
        'no_hp'            => $no_hp,
        'nama_wali'        => $nama_wali,
        'jumlah_pendaftar' => $jumlah_pendaftar_baru,
        'kuota_max'        => $kuota_max,
        'kuota_tersisa'    => $kuota_tersisa_baru
    ]);
} else {
    respon('error', 'Gagal menyimpan data: ' . $stmt->error);
}
?>