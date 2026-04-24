<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

if (!isset($_SESSION['id_siswa'])) {
    echo json_encode([
        "success" => false,
        "message" => "Session siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

$stmt = $conn->prepare("
    SELECT 
        s.nama,
        s.nis,
        s.nisn,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        s.id_kelas,
        k.nama_kelas AS kelas,
        p.email,
        p.no_hp,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN pendaftaran p ON s.id_pendaftaran = p.id_pendaftaran
    LEFT JOIN user u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = ?
    LIMIT 1
");

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$result = $stmt->get_result();

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query gagal."
    ]);
    exit;
}

$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>