<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';
require_once 'fungsi_siswa.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    respon("error", "Koneksi database gagal.");
}

$id_siswa = (int)($_POST['id_siswa'] ?? 0);
$nisn = trim($_POST['nisn'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$id_kelas = (int)($_POST['id_kelas'] ?? 0);

if ($id_siswa <= 0 || $nisn === '' || $nama === '' || $jenis_kelamin === '' || $id_kelas <= 0) {
    respon("error", "Data tidak lengkap.");
}

if (!in_array($jenis_kelamin, ['L', 'P'])) {
    respon("error", "Jenis kelamin tidak valid.");
}

// Ambil kelas lama siswa
$query_kelas_lama = mysqli_query($conn, "SELECT id_kelas FROM siswa WHERE id_siswa = $id_siswa");
$kelas_lama = mysqli_fetch_assoc($query_kelas_lama);
$id_kelas_lama = $kelas_lama ? $kelas_lama['id_kelas'] : 0;

// Cek kapasitas kelas baru jika berbeda
if ($id_kelas != $id_kelas_lama) {
    $cekKapasitas = cekKapasitasKelas($conn, $id_kelas, $id_siswa);
    if (!$cekKapasitas['tersedia']) {
        respon("error", "Kelas tujuan sudah penuh! ({$cekKapasitas['jumlah_saat_ini']}/{$cekKapasitas['kapasitas']}) siswa. Sisa kuota: 0");
    }
}

$cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ? AND id_siswa != ?");
if (!$cek) {
    respon("error", "Prepare cek gagal: " . $conn->error);
}

$cek->bind_param("si", $nisn, $id_siswa);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    $cek->close();
    respon("error", "NISN sudah digunakan siswa lain.");
}
$cek->close();

$stmt = $conn->prepare("
    UPDATE siswa 
    SET nisn = ?, nama = ?, jenis_kelamin = ?, id_kelas = ?
    WHERE id_siswa = ?
");

if (!$stmt) {
    respon("error", "Prepare update gagal: " . $conn->error);
}

$stmt->bind_param("sssii", $nisn, $nama, $jenis_kelamin, $id_kelas, $id_siswa);

if ($stmt->execute()) {
    $stmt->close();
    respon("success", "Data siswa berhasil diperbarui.");
} else {
    $err = $stmt->error;
    $stmt->close();
    respon("error", "Gagal memperbarui siswa: " . $err);
}
?>