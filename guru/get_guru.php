<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

if (!isset($_SESSION['id_guru'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Belum login"
    ]);
    exit;
}

$id_guru = $_SESSION['id_guru'];

$queryGuru = mysqli_query($conn, "
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

if (!$queryGuru) {
    echo json_encode([
        "status" => "error",
        "message" => "Query guru error: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($queryGuru);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Data guru tidak ditemukan"
    ]);
    exit;
}

$data['nama_mapel'] = "-";

if (!empty($data['id_mapel'])) {
    $id_mapel = $data['id_mapel'];

    $queryMapel = mysqli_query($conn, "
        SELECT nama_mapel
        FROM mapel
        WHERE id_mapel = '$id_mapel'
        LIMIT 1
    ");

    if ($queryMapel) {
        $mapel = mysqli_fetch_assoc($queryMapel);

        if ($mapel) {
            $data['nama_mapel'] = $mapel['nama_mapel'];
        }
    }
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);
?>