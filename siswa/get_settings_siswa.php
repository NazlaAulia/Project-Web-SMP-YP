<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$id_siswa = 0;

if (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
} elseif (isset($_GET['id_siswa']) && (int)$_GET['id_siswa'] > 0) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas AS kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Query settings siswa gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$result = $stmt->get_result();
$siswa = $result->fetch_assoc();

if (!$siswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => $siswa["id_siswa"],
        "nama" => $siswa["nama"],
        "kelas" => $siswa["kelas"],
        "inisial" => strtoupper(substr($siswa["nama"], 0, 1))
    ]
]);

$stmt->close();
$conn->close();
?>