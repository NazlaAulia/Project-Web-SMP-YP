<?php
require_once __DIR__ . '/../koneksi.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: utama.php?status=gagal");
    exit;
}

$stmt = $conn->prepare("SELECT gambar FROM pengumuman WHERE id_pengumuman = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$stmt->close();

if ($data && !empty($data['gambar'])) {
    $filePath = __DIR__ . '/' . $data['gambar'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

$stmt = $conn->prepare("DELETE FROM pengumuman WHERE id_pengumuman = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: utama.php?status=hapus");
    exit;
}

$stmt->close();
header("Location: utama.php?status=gagal");
exit;