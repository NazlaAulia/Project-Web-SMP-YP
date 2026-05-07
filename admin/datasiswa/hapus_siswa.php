<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';

function respon($status, $message) {
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    respon("error", "Koneksi database gagal.");
}

$idSiswa = (int)($_POST['id_siswa'] ?? 0);

if ($idSiswa <= 0) {
    respon("error", "ID siswa tidak valid.");
}

$stmt = $conn->prepare("
    UPDATE siswa
    SET status = 'keluar'
    WHERE id_siswa = ?
");

if (!$stmt) {
    respon("error", "Prepare update status gagal: " . $conn->error);
}

$stmt->bind_param("i", $idSiswa);

if ($stmt->execute()) {
    $stmt->close();
    respon("success", "Status siswa berhasil diubah menjadi keluar.");
} else {
    $err = $stmt->error;
    $stmt->close();
    respon("error", "Gagal mengubah status siswa: " . $err);
}
?>
