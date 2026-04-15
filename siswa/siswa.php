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

$id_siswa = isset($_GET['id_siswa']) ? (int)$_GET['id_siswa'] : 0;

if ($id_siswa <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "id_siswa tidak valid"
    ]);
    exit;
}

$sql = "SELECT s.id_siswa, s.nama, s.id_kelas, k.nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.id_siswa = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);

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
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

$stmt->bind_result($db_id_siswa, $nama, $id_kelas, $nama_kelas);
$stmt->fetch();

echo json_encode([
    "status" => "success",
    "data" => [
        "id_siswa" => $db_id_siswa,
        "nama" => $nama,
        "id_kelas" => $id_kelas,
        "nama_kelas" => $nama_kelas
    ]
]);

$stmt->close();
$conn->close();
?>