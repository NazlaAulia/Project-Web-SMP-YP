<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

if (!isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    echo json_encode([
        "success" => false,
        "message" => "Session id_siswa tidak ada. Login ulang."
    ]);
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

$query = "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nisn,
        s.nama,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        s.id_kelas,
        k.nama_kelas AS kelas,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN user u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = $id_siswa
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($result);

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