<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

$id_guru = "";

// Ambil dari URL dulu, contoh: get_guru.php?id_guru=4
if (isset($_GET['id_guru']) && $_GET['id_guru'] !== "") {
    $id_guru = $_GET['id_guru'];
}

// Kalau URL kosong, ambil dari session
if ($id_guru === "" && isset($_SESSION['id_guru']) && $_SESSION['id_guru'] !== "") {
    $id_guru = $_SESSION['id_guru'];
}

if ($id_guru === "") {
    echo json_encode([
        "status" => "error",
        "message" => "ID guru tidak ditemukan"
    ]);
    exit;
}

$id_guru = mysqli_real_escape_string($conn, $id_guru);

$sqlGuru = "
    SELECT 
        `id_guru`,
        `nip`,
        `nama`,
        `email`,
        `id_mapel`
    FROM `guru`
    WHERE `id_guru` = '$id_guru'
    LIMIT 1
";

$queryGuru = mysqli_query($conn, $sqlGuru);

if (!$queryGuru) {
    echo json_encode([
        "status" => "error",
        "message" => "Query guru error: " . mysqli_error($conn),
        "sql" => $sqlGuru
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
    $id_mapel = mysqli_real_escape_string($conn, $data['id_mapel']);

    $sqlMapel = "
        SELECT `nama_mapel`
        FROM `mapel`
        WHERE `id_mapel` = '$id_mapel'
        LIMIT 1
    ";

    $queryMapel = mysqli_query($conn, $sqlMapel);

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