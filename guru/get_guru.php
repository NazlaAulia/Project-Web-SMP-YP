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

if (!isset($conn)) {
    echo json_encode([
        "status" => "error",
        "message" => "Variabel koneksi \$conn tidak ditemukan. Cek koneksi.php"
    ]);
    exit;
}

$sqlGuru = "
    SELECT *
    FROM guru
    WHERE id_guru = '$id_guru'
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
        "message" => "Data guru tidak ditemukan",
        "id_guru" => $id_guru
    ]);
    exit;
}

$data['nama_mapel'] = "-";

if (!empty($data['id_mapel'])) {
    $id_mapel = $data['id_mapel'];

    $sqlMapel = "
        SELECT *
        FROM mapel
        WHERE id_mapel = '$id_mapel'
        LIMIT 1
    ";

    $queryMapel = mysqli_query($conn, $sqlMapel);

    if ($queryMapel) {
        $mapel = mysqli_fetch_assoc($queryMapel);

        if ($mapel && isset($mapel['nama_mapel'])) {
            $data['nama_mapel'] = $mapel['nama_mapel'];
        }
    } else {
        $data['nama_mapel'] = "Mapel error: " . mysqli_error($conn);
    }
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);
?>