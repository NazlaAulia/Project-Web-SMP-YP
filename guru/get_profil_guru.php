<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

if (isset($_SESSION['id_guru']) && $_SESSION['id_guru'] != '') {
    $id_guru = $_SESSION['id_guru'];
} else {
    // sementara untuk akun Murni
    $id_guru = 4;
}

$query = mysqli_query($conn, "
    SELECT 
        id_guru,
        nip,
        nama,
        email,
        id_mapel
    FROM guru
    WHERE id_guru = '$id_guru'
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