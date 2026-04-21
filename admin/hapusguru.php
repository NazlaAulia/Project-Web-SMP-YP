<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

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

$idGuru = (int)($_POST['id_guru'] ?? 0);

if ($idGuru <= 0) {
    respon("error", "ID guru tidak valid.");
}

$conn->begin_transaction();

try {
    $cekWali = $conn->prepare("SELECT COUNT(*) as total FROM kelas WHERE id_wali_kelas = ?");
    $cekWali->bind_param("i", $idGuru);
    $cekWali->execute();
    $result = $cekWali->get_result()->fetch_assoc();
    $cekWali->close();

    if (($result['total'] ?? 0) > 0) {
        throw new Exception("Guru ini masih menjadi wali kelas. Ganti wali kelas dulu sebelum menghapus.");
    }

    $hapusUser = $conn->prepare("DELETE FROM user WHERE id_guru = ?");
    $hapusUser->bind_param("i", $idGuru);
    if (!$hapusUser->execute()) {
        throw new Exception("Gagal menghapus akun user guru.");
    }
    $hapusUser->close();

    $hapusGuru = $conn->prepare("DELETE FROM guru WHERE id_guru = ?");
    $hapusGuru->bind_param("i", $idGuru);
    if (!$hapusGuru->execute()) {
        throw new Exception("Gagal menghapus data guru.");
    }
    $hapusGuru->close();

    $conn->commit();
    respon("success", "Data guru berhasil dihapus.");

} catch (Exception $e) {
    $conn->rollback();
    respon("error", $e->getMessage());
}
?>