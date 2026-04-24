<?php
session_start();
include "koneksi.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$query = mysqli_query($conn, "SELECT * FROM user WHERE username = '$username' LIMIT 1");

if (!$query) {
    echo "Query error: " . mysqli_error($conn);
    exit;
}

$user = mysqli_fetch_assoc($query);

if (!$user) {
    echo "Username tidak ditemukan";
    exit;
}

if ($password != $user['password']) {
    echo "Password salah";
    exit;
}

$_SESSION['id_user'] = $user['id_user'];
$_SESSION['username'] = $user['username'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['id_guru'] = $user['id_guru'];
$_SESSION['id_siswa'] = $user['id_siswa'];

session_write_close();

if ($user['role_id'] == 1) {
    header("Location: ../admin/admin.html");
    exit;
}

if ($user['role_id'] == 2) {
    header("Location: guru.html");
    exit;
}

if ($user['role_id'] == 3) {
    header("Location: ../siswa/siswa.html");
    exit;
}

echo "Role tidak dikenali";
exit;
?>