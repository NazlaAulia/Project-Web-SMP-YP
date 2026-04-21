<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

function kirim($data) {
    echo json_encode($data);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    kirim([
        "status" => "error",
        "message" => "Koneksi database gagal"
    ]);
}

$totalGuru = 0;
$totalSiswa = 0;
$totalKelas = 0;
$totalPending = 0;

$qGuru = mysqli_query($conn, "SELECT COUNT(*) AS total FROM guru");
if ($qGuru) {
    $row = mysqli_fetch_assoc($qGuru);
    $totalGuru = (int)($row['total'] ?? 0);
}

$qSiswa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa");
if ($qSiswa) {
    $row = mysqli_fetch_assoc($qSiswa);
    $totalSiswa = (int)($row['total'] ?? 0);
}

$qKelas = mysqli_query($conn, "SELECT COUNT(*) AS total FROM kelas");
if ($qKelas) {
    $row = mysqli_fetch_assoc($qKelas);
    $totalKelas = (int)($row['total'] ?? 0);
}

$qPending = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pendaftaran WHERE status = 'menunggu'");
if ($qPending) {
    $row = mysqli_fetch_assoc($qPending);
    $totalPending = (int)($row['total'] ?? 0);
}

kirim([
    "status" => "success",
    "total_guru" => $totalGuru,
    "total_siswa" => $totalSiswa,
    "total_kelas" => $totalKelas,
    "total_pending" => $totalPending
]);
?>