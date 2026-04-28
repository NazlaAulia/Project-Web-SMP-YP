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

$conn->begin_transaction();

try {
    $hapusUser = $conn->prepare("DELETE FROM user WHERE id_siswa = ?");
    $hapusUser->bind_param("i", $idSiswa);

    if (!$hapusUser->execute()) {
        throw new Exception("Gagal menghapus akun user siswa.");
    }

    $hapusUser->close();

    $hapusSiswa = $conn->prepare("DELETE FROM siswa WHERE id_siswa = ?");
    $hapusSiswa->bind_param("i", $idSiswa);

    if (!$hapusSiswa->execute()) {
        throw new Exception("Gagal menghapus data siswa.");
    }

    $hapusSiswa->close();

    $conn->commit();
    respon("success", "Data siswa berhasil dihapus.");
} catch (Exception $e) {
    $conn->rollback();
    respon("error", $e->getMessage());
}
?>