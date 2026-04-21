<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

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

$nisn = trim($_POST['nisn'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$id_kelas = trim($_POST['id_kelas'] ?? '');

if ($nisn === '' || $nama === '' || $jenis_kelamin === '' || $id_kelas === '') {
    respon("error", "Semua field wajib diisi.");
}

if (!in_array($jenis_kelamin, ['L', 'P'])) {
    respon("error", "Jenis kelamin tidak valid.");
}

$id_kelas = (int)$id_kelas;

$cek = $conn->prepare("SELECT id_siswa FROM siswa WHERE nisn = ?");
if (!$cek) {
    respon("error", "Prepare cek gagal: " . $conn->error);
}

$cek->bind_param("s", $nisn);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    $cek->close();
    respon("error", "NISN sudah terdaftar.");
}
$cek->close();

/*
Kalau DB-mu punya trigger auto create user untuk siswa:
cukup insert ke tabel siswa.
Kalau belum, nanti file ini bisa ditambah insert ke tabel user.
*/
$stmt = $conn->prepare("INSERT INTO siswa (nisn, nama, jenis_kelamin, id_kelas) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    respon("error", "Prepare insert gagal: " . $conn->error);
}

$stmt->bind_param("sssi", $nisn, $nama, $jenis_kelamin, $id_kelas);

if ($stmt->execute()) {
    $idSiswa = $stmt->insert_id;
    $stmt->close();

    respon("success", "Siswa berhasil ditambahkan.", [
        "id_siswa" => $idSiswa
    ]);
} else {
    $err = $stmt->error;
    $stmt->close();
    respon("error", "Gagal menambah siswa: " . $err);
}
?>