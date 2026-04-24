<?php
session_start();
include "koneksi.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$query = mysqli_query($conn, "SELECT * FROM user WHERE username = '$username' LIMIT 1");
$user = mysqli_fetch_assoc($query);

if ($user) {

    if ($password == $user['password']) {

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['id_guru'] = $user['id_guru'];
        $_SESSION['id_siswa'] = $user['id_siswa'];

        if ($user['role_id'] == 1) {
            header("Location: ../dashboard_admin.php");
            exit;
        } elseif ($user['role_id'] == 2) {
            header("Location: guru.html");
            exit;
        } elseif ($user['role_id'] == 3) {
            header("Location: ../dashboard_siswa.php");
            exit;
        } else {
            echo "Role tidak dikenali";
            exit;
        }

    } else {
        echo "Password salah";
        exit;
    }

} else {
    echo "Username tidak ditemukan";
    exit;
}
?>