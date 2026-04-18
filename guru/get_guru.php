<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}

$id_guru = isset($_GET['id_guru']) ? (int)$_GET['id_guru'] : 0;

if ($id_guru <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "id_guru tidak valid"
    ]);
    exit;
}

$sql = "SELECT id_guru, nama, nip FROM guru WHERE id_guru = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_guru);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Execute gagal: " . $stmt->error
    ]);
    exit;
}

$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Data guru tidak ditemukan"
    ]);
    exit;
}

$stmt->bind_result($db_id_guru, $nama, $nip);
$stmt->fetch();

echo json_encode([
    "status" => "success",
    "data" => [
        "id_guru" => $db_id_guru,
        "nama" => $nama,
        "nip" => $nip
    ]
]);

$stmt->close();
$conn->close();
?>