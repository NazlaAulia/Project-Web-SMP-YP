<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
session_start();
include "koneksi.php";

$username = $_POST['username'];
$password = $_POST['password'];

$query = mysqli_query($conn, "
    SELECT user.*, guru.nama, guru.nip
    FROM user
    JOIN guru ON user.id_guru = guru.id_guru
    WHERE user.username = '$username'
    AND user.password = '$password'
    AND user.role = 'guru'
");

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    kirim_json("error", "Permintaan login tidak valid.", [
        "raw" => $raw
    ]);
}

$username = trim($data["username"] ?? "");
$password = trim($data["password"] ?? "");

if ($username === "" && $password === "") {
    kirim_json("error", "Username dan password wajib diisi.");
}

if ($username === "") {
    kirim_json("error", "Username wajib diisi.");
}

if ($password === "") {
    kirim_json("error", "Password wajib diisi.");
}

$stmt = $conn->prepare("
    SELECT id_user, username, password, role_id, id_guru, id_siswa
    FROM `user`
    WHERE username = ?
    LIMIT 1
");

if (!$stmt) {
    kirim_json("error", "Query gagal: " . $conn->error);
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    kirim_json("error", "Login gagal diproses: " . $stmt->error);
}

$stmt->store_result();

if ($stmt->num_rows === 0) {
    kirim_json("error", "Username tidak ditemukan.");
}

$stmt->bind_result($id_user, $db_username, $db_password, $role_id, $id_guru, $id_siswa);
$stmt->fetch();

if ($db_password !== $password) {
    kirim_json("error", "P
    
}

if (!in_array((int)$role_id, [1, 2, 3])) {
    kirim_json("error", "Role akun tidak valid.");
}

kirim_json("success", "Login berhasil.", [
    "user" => [
        "id_user" => $id_user,
        "username" => $db_username,
        "role_id" => $role_id,
        "id_guru" => $id_guru,
        "id_siswa" => $id_siswa
    ]
]);
$data = mysqli_fetch_assoc($query);

if ($data) {
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['id_guru'] = $data['id_guru'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];

    header("Location: guru.html");
    exit;
} else {
    echo "<script>
        alert('Username atau password salah');
        window.location.href='../login.html';
    </script>";
}
?>
