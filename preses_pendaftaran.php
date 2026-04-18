<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'pendaftaran.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
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

if (
    $nama_lengkap === '' ||
    $nisn === '' ||
    $jenis_kelamin === '' ||
    $tanggal_lahir === '' ||
    $alamat === '' ||
    $asal_sekolah === '' ||
    $no_hp === '' ||
    $pendapatan_ortu === ''
) {
    respon('error', 'Semua field wajib diisi kecuali email.');
}

if (!in_array($jenis_kelamin, ['L', 'P'])) {
    respon('error', 'Jenis kelamin tidak valid.');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respon('error', 'Format email tidak valid.');
}

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

$status = 'menunggu';
$tanggal_daftar = date('Y-m-d');

$stmt = $conn->prepare("
    INSERT INTO pendaftaran
    (nama_lengkap, nisn, jenis_kelamin, tanggal_lahir, alamat, asal_sekolah, no_hp, email, tanggal_daftar, status, pendapatan_ortu)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    respon('error', 'Prepare insert gagal: ' . $conn->error);
}

$stmt->bind_param(
    "ssssssssssd",
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
    $pendapatan_ortu
);

if ($stmt->execute()) {
    $id_pendaftaran = $stmt->insert_id;

    respon('success', 'Pendaftaran berhasil dikirim, silakan tunggu verifikasi admin.', [
        'id_pendaftaran' => $id_pendaftaran,
        'nama_lengkap' => $nama_lengkap,
        'nisn' => $nisn,
        'asal_sekolah' => $asal_sekolah,
        'no_hp' => $no_hp
    ]);
} else {
    respon('error', 'Gagal menyimpan data ke database: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>