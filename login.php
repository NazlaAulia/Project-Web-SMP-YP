<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Data request tidak valid"
    ]);
    exit;
}

$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");

if ($username === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Username dan password wajib diisi."
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id_user, username, password, role_id, id_guru, id_siswa FROM `user` WHERE username = ? LIMIT 1");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Execute gagal: " . $stmt->error
    ]);
    exit;
}

$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Username tidak ditemukan."
    ]);
    exit;
}

$stmt->bind_result($id_user, $db_username, $db_password, $role_id, $id_guru, $id_siswa);
$stmt->fetch();

if ($db_password !== $password) {
    echo json_encode([
        "status" => "error",
        "message" => "Password salah."
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "user" => [
        "id_user" => $id_user,
        "username" => $db_username,
        "role_id" => $role_id,
        "id_guru" => $id_guru,
        "id_siswa" => $id_siswa
    ]
]);

$stmt->close();
$conn->close();
?>