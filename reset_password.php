<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal."
    ]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Permintaan tidak valid."
    ]);
    exit;
}

$token = trim($data["token"] ?? "");
$password = trim($data["password"] ?? "");

if ($token === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Token dan password wajib diisi."
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        "status" => "error",
        "message" => "Password minimal 6 karakter."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_request, id_user, role_id
    FROM password_reset_requests
    WHERE reset_token = ?
    AND status = 'pending'
    AND token_expires_at >= NOW()
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Query token gagal disiapkan."
    ]);
    exit;
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Link reset tidak valid atau sudah kedaluwarsa."
    ]);
    exit;
}

$request = $result->fetch_assoc();
$stmt->close();

if ((int)$request["role_id"] !== 2) {
    echo json_encode([
        "status" => "error",
        "message" => "Link reset ini hanya untuk akun guru."
    ]);
    exit;
}

$updateUser = $conn->prepare("
    UPDATE `user`
    SET password = ?
    WHERE id_user = ?
");

if (!$updateUser) {
    echo json_encode([
        "status" => "error",
        "message" => "Query update password gagal disiapkan."
    ]);
    exit;
}

$updateUser->bind_param("si", $password, $request["id_user"]);

if (!$updateUser->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Password gagal diperbarui."
    ]);
    exit;
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

echo json_encode([
    "status" => "success",
    "message" => "Password berhasil diganti. Silakan login dengan password baru."
]);
?>