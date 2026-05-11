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
    respon('error', 'Koneksi database tidak terbaca.');
}
if ($conn->connect_error) {
    respon('error', 'Koneksi database gagal: ' . $conn->connect_error);
}

// Ambil tahun ajaran aktif
$query_ta = "SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$result_ta = mysqli_query($conn, $query_ta);
$ta_aktif = mysqli_fetch_assoc($result_ta);

if (!$ta_aktif) {
    respon('error', 'Tahun ajaran aktif tidak ditemukan.');
}

// 1. Cek apakah pendaftaran sudah ditutup
$sekarang = date('Y-m-d');
if ($sekarang > $ta_aktif['tgl_tutup']) {
    respon('error', 'Pendaftaran sudah ditutup untuk tahun ajaran ' . $ta_aktif['tahun_ajaran']);
}

// Ambil data dari POST
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

// Validasi wajib
if (empty($nama_lengkap) || empty($nisn) || empty($jenis_kelamin) || empty($tanggal_lahir) || empty($alamat) || empty($asal_sekolah) || empty($no_hp) || empty($pendapatan_ortu) || empty($nama_wali)) {
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

$no_hp_raw = $no_hp;
$no_hp = formatNomorWa($no_hp);
if ($no_hp === false) {
    respon('error', 'Nomor HP gagal diformat.');
}

// 2. Cek NISN duplikat
$cek = $conn->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE nisn = ?");
if (!$cek) respon('error', 'Prepare gagal: ' . $conn->error);
$cek->bind_param("s", $nisn);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    respon('error', 'NISN sudah pernah didaftarkan.');
}
$cek->close();

// 3. Cek kuota berdasarkan tahun ajaran aktif
$diterima = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = {$ta_aktif['id_tahun_ajaran']} AND status='diterima'"))['total'];
$kuota_tersisa = $ta_aktif['kuota'] - $diterima;
if ($kuota_tersisa <= 0) {
    respon('error', 'Kuota pendaftaran untuk tahun ajaran ini sudah penuh.');
}

// 4. Insert data dengan id_tahun_ajaran
$status = 'menunggu';
$tanggal_daftar = date('Y-m-d H:i:s');
$id_ta = $ta_aktif['id_tahun_ajaran'];
$pendapatan_ortu_float = (float) $pendapatan_ortu;

$query = "INSERT INTO pendaftaran (nama_lengkap, nisn, jenis_kelamin, tanggal_lahir, alamat, asal_sekolah, no_hp, email, nama_wali, pendapatan_ortu, tanggal_daftar, status, id_tahun_ajaran) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
if (!$stmt) respon('error', 'Prepare insert gagal: ' . $conn->error);

$stmt->bind_param(
    "sssssssssdssi",
    $nama_lengkap, $nisn, $jenis_kelamin, $tanggal_lahir, $alamat, $asal_sekolah, $no_hp, $email, $nama_wali, $pendapatan_ortu_float, $tanggal_daftar, $status, $id_ta
);

if ($stmt->execute()) {
    $id_pendaftaran = $stmt->insert_id;

    // Hitung ulang jumlah pendaftar untuk response
    $jumlah_pendaftar = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_ta"))['total'];
    $diterima_baru = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_ta AND status='diterima'"))['total'];
    $kuota_tersisa_baru = $ta_aktif['kuota'] - $diterima_baru;

    respon('success', 'Pendaftaran berhasil dikirim.', [
        'id_pendaftaran'   => $id_pendaftaran,
        'nama_lengkap'     => $nama_lengkap,
        'nisn'             => $nisn,
        'asal_sekolah'     => $asal_sekolah,
        'no_hp'            => $no_hp_raw,   // asli untuk tampilan
        'nama_wali'        => $nama_wali,
        'jumlah_pendaftar' => $jumlah_pendaftar,
        'kuota_max'        => $ta_aktif['kuota'],
        'kuota_tersisa'    => $kuota_tersisa_baru
    ]);
} else {
    respon('error', 'Gagal menyimpan data: ' . $stmt->error);
}
?>