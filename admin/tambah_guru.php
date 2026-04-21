<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    respon("error", "Koneksi database gagal.");
}

$nip = trim($_POST['nip'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$id_mapel = trim($_POST['id_mapel'] ?? '');

if ($nip === '' || $nama === '' || $email === '') {
    respon("error", "NIP, nama, dan email wajib diisi.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respon("error", "Format email tidak valid.");
}

if ($id_mapel === '') {
    $id_mapel = null;
}

$cek = $conn->prepare("SELECT id_guru FROM guru WHERE nip = ?");
if (!$cek) {
    respon("error", "Prepare cek gagal: " . $conn->error);
}

$cek->bind_param("s", $nip);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    $cek->close();
    respon("error", "NIP sudah terdaftar.");
}
$cek->close();

$stmt = $conn->prepare("INSERT INTO guru (nip, nama, email, id_mapel) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    respon("error", "Prepare insert gagal: " . $conn->error);
}

if ($id_mapel === null) {
    $stmt->bind_param("sssi", $nip, $nama, $email, $id_mapel);
} else {
    $id_mapel = (int)$id_mapel;
    $stmt->bind_param("sssi", $nip, $nama, $email, $id_mapel);
}

if ($stmt->execute()) {
    $idGuru = $stmt->insert_id;
    $stmt->close();

    respon("success", "Guru berhasil ditambahkan. Akun user dibuat otomatis oleh sistem.", [
        "id_guru" => $idGuru
    ]);
} else {
    $err = $stmt->error;
    $stmt->close();
    respon("error", "Gagal menambah guru: " . $err);
}
?>