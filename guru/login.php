<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username == '' || $password == '') {
    echo json_encode([
        "status" => "error",
        "message" => "Username dan password wajib diisi"
    ]);
    exit;
}

$query = mysqli_query($conn, "
    SELECT user.*, guru.nama, guru.nip
    FROM user
    JOIN guru ON user.id_guru = guru.id_guru
    WHERE user.username = '$username'
    AND user.password = '$password'
    AND user.role = 'guru'
    LIMIT 1
");

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => "Query login error: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($query);

if ($data) {
    $_SESSION['id_user'] = $data['id_user'];
    $_SESSION['id_guru'] = $data['id_guru'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['role'] = $data['role'];

    echo json_encode([
        "status" => "success",
        "message" => "Login berhasil",
        "redirect" => "/guru/guru.html"
    ]);
    exit;
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Username atau password salah"
    ]);
    exit;
}
?>