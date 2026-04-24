<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

// Prioritas utama: ambil dari session login
if (isset($_SESSION['id_guru'])) {
    $id_guru = $_SESSION['id_guru'];
} 
// Cadangan kalau masih ada halaman yang kirim ?id_guru=
else if (isset($_GET['id_guru'])) {
    $id_guru = $_GET['id_guru'];
} 
else {
    echo json_encode([
        "status" => "error",
        "message" => "ID guru tidak ditemukan"
    ]);
    exit;
}

$query = mysqli_query($conn, "
    SELECT 
        guru.id_guru,
        guru.nip,
        guru.nama,
        guru.email,
        guru.id_mapel,
        mapel.nama_mapel
    FROM guru
    LEFT JOIN mapel ON guru.id_mapel = mapel.id_mapel
    WHERE guru.id_guru = '$id_guru'
    LIMIT 1
");

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => "Query error: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($query);

if ($data) {
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Data guru tidak ditemukan"
    ]);
}
?>