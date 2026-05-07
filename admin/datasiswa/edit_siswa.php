<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';

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
