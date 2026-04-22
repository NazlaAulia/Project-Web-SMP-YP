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

$idGuru  = (int)($_POST['id_guru'] ?? 0);
$nip     = trim($_POST['nip'] ?? '');
$nama    = trim($_POST['nama'] ?? '');
$email   = trim($_POST['email'] ?? '');
$idMapel = isset($_POST['id_mapel']) && $_POST['id_mapel'] !== '' ? (int)$_POST['id_mapel'] : null;

if ($idGuru <= 0) {
    respon("error", "ID guru tidak valid.");
}

if ($nip === '' || $nama === '' || $email === '') {
    respon("error", "NIP, nama, dan email wajib diisi.");
}

$conn->begin_transaction();

try {
    $cekGuru = $conn->prepare("SELECT id_guru FROM guru WHERE id_guru = ?");
    $cekGuru->bind_param("i", $idGuru);
    $cekGuru->execute();
    $resultGuru = $cekGuru->get_result()->fetch_assoc();
    $cekGuru->close();

    if (!$resultGuru) {
        throw new Exception("Data guru tidak ditemukan.");
    }

    $cekDuplikat = $conn->prepare("SELECT id_guru FROM guru WHERE (nip = ? OR email = ?) AND id_guru != ?");
    $cekDuplikat->bind_param("ssi", $nip, $email, $idGuru);
    $cekDuplikat->execute();
    $resultDuplikat = $cekDuplikat->get_result()->fetch_assoc();
    $cekDuplikat->close();

    if ($resultDuplikat) {
        throw new Exception("NIP atau email sudah digunakan guru lain.");
    }

    if ($idMapel === null) {
        $updateGuru = $conn->prepare("UPDATE guru SET nip = ?, nama = ?, email = ?, id_mapel = NULL WHERE id_guru = ?");
        $updateGuru->bind_param("sssi", $nip, $nama, $email, $idGuru);
    } else {
        $updateGuru = $conn->prepare("UPDATE guru SET nip = ?, nama = ?, email = ?, id_mapel = ? WHERE id_guru = ?");
        $updateGuru->bind_param("sssii", $nip, $nama, $email, $idMapel, $idGuru);
    }

    if (!$updateGuru->execute()) {
        throw new Exception("Gagal memperbarui data guru.");
    }
    $updateGuru->close();

    $conn->commit();
    respon("success", "Data guru berhasil diperbarui.");

} catch (Exception $e) {
    $conn->rollback();
    respon("error", $e->getMessage());
}
?>