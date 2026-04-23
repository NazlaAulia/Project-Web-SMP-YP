<?php
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = isset($_GET['id_siswa']) ? (int) $_GET['id_siswa'] : 0;

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid."
    ]);
    exit;
}

$query = "SELECT nama, nisn, kelas, email, no_hp, alamat, jenis_kelamin, tanggal_lahir, foto_profil
          FROM siswa
          WHERE id = $id_siswa
          LIMIT 1";

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