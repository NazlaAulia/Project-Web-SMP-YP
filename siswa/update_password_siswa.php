<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

$id_siswa = 0;

if (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
} elseif (isset($_POST['id_siswa']) && (int)$_POST['id_siswa'] > 0) {
    $id_siswa = (int) $_POST['id_siswa'];
}

$password_lama = trim($_POST['password_lama'] ?? '');
$password_baru = trim($_POST['password_baru'] ?? '');
$konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

if ($password_lama === '' || $password_baru === '' || $konfirmasi_password === '') {
    echo json_encode([
        "success" => false,
        "message" => "Semua field wajib diisi."
    ]);
    exit;
}

if (strlen($password_baru) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password baru minimal 8 karakter."
    ]);
    exit;
}

if ($password_baru !== $konfirmasi_password) {
    echo json_encode([
        "success" => false,
        "message" => "Konfirmasi password baru tidak cocok."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_user, password
    FROM user
    WHERE id_siswa = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Query user gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "Data user tidak ditemukan."
    ]);
    exit;
}

if ($password_lama !== $user['password']) {
    echo json_encode([
        "success" => false,
        "message" => "Password lama salah."
    ]);
    exit;
}

$stmtUpdate = $conn->prepare("
    UPDATE user
    SET password = ?
    WHERE id_siswa = ?
");

if (!$stmtUpdate) {
    echo json_encode([
        "success" => false,
        "message" => "Query update password gagal: " . $conn->error
    ]);
    exit;
}

$stmtUpdate->bind_param("si", $password_baru, $id_siswa);

if (!$stmtUpdate->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal update password: " . $stmtUpdate->error
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Password berhasil diubah."
]);

$stmt->close();
$stmtUpdate->close();
$conn->close();
?>