<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "NAMA_DATABASE";
$dbuser = "USERNAME_DATABASE";
$dbpass = "PASSWORD_DATABASE";

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

$username = trim($data["username"] ?? "");

if ($username === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Username wajib diisi."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT id_user, username, role_id, id_guru, id_siswa
    FROM `user`
    WHERE username = ?
    LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Jika username terdaftar, permintaan reset sandi akan dikirim ke admin."
    ]);
    exit;
}

$stmt->bind_result($id_user, $db_username, $role_id, $id_guru, $id_siswa);
$stmt->fetch();
$stmt->close();

$cek = $conn->prepare("
    SELECT id_request 
    FROM password_reset_requests 
    WHERE id_user = ? 
    AND status = 'pending'
    LIMIT 1
");
$cek->bind_param("i", $id_user);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Permintaan reset sandi kamu sudah masuk. Silakan tunggu admin memproses."
    ]);
    exit;
}
$cek->close();

$insert = $conn->prepare("
    INSERT INTO password_reset_requests
    (id_user, id_siswa, id_guru, username, role_id, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");

$insert->bind_param(
    "iiisi",
    $id_user,
    $id_siswa,
    $id_guru,
    $db_username,
    $role_id
);

if (!$insert->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Permintaan reset sandi gagal dikirim."
    ]);
    exit;
}

$insert->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "message" => "Permintaan reset sandi berhasil dikirim ke admin."
]);
?>