<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));

    exit;
}

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    kirim_json("error", "Permintaan tidak valid.");
}

$token = trim($data["token"] ?? "");
$password = trim($data["password"] ?? "");

if ($token === "" || $password === "") {
    kirim_json("error", "Token dan password wajib diisi.");
}

if (strlen($password) < 6) {
    kirim_json("error", "Password minimal 6 karakter.");
}

$stmt = $conn->prepare("
    SELECT id_request, id_user, role_id
    FROM password_reset_requests
    WHERE reset_token = ?
    AND status = 'pending'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    LIMIT 1
");

if (!$stmt) {
    kirim_json("error", "Query token gagal: " . $conn->error);
}

$stmt->bind_param("s", $token);

if (!$stmt->execute()) {
    kirim_json("error", "Token gagal diproses: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    kirim_json("error", "Link reset tidak valid atau sudah kedaluwarsa. Silakan klik Lupa Sandi lagi.");
}

$request = $result->fetch_assoc();
$stmt->close();

if ((int)$request["role_id"] !== 2) {
    kirim_json("error", "Link reset ini hanya untuk akun guru.");
}

$updateUser = $conn->prepare("
    UPDATE `user`
    SET password = ?
    WHERE id_user = ?
");

if (!$updateUser) {
    kirim_json("error", "Query update password gagal: " . $conn->error);
}

$updateUser->bind_param("si", $password, $request["id_user"]);

if (!$updateUser->execute()) {
    kirim_json("error", "Password gagal diperbarui: " . $updateUser->error);
}

$updateUser->close();

$updateRequest = $conn->prepare("
    UPDATE password_reset_requests
    SET status = 'used', handled_at = NOW()
    WHERE id_request = ?
");

if ($updateRequest) {
    $updateRequest->bind_param("i", $request["id_request"]);
    $updateRequest->execute();
    $updateRequest->close();
}

$conn->close();

kirim_json("success", "Password berhasil diganti. Silakan login dengan password baru.");