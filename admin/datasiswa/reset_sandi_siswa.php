<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';

function respon($status, $message) {
    echo json_encode(["status" => $status, "message" => $message]);
    exit;
}

$idSiswa = (int)($_POST['id_siswa'] ?? 0);
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($idSiswa <= 0 || $password === '' || $confirm === '') {
    respon("error", "Data tidak lengkap.");
}

if ($password !== $confirm) {
    respon("error", "Konfirmasi sandi tidak sama.");
}

if (strlen($password) < 6) {
    respon("error", "Sandi minimal 6 karakter.");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
    UPDATE user
    SET password = ?
    WHERE id_siswa = ? AND role_id = 3
");

$stmt->bind_param("si", $passwordHash, $idSiswa);

if ($stmt->execute()) {
    respon("success", "Sandi siswa berhasil diganti.");
}

respon("error", "Gagal mengganti sandi.");
?>
