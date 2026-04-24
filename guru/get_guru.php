<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

$id_guru = $_SESSION['id_guru'] ?? ($_GET['id_guru'] ?? '');

if ($id_guru == '') {
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