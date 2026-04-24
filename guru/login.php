<?php
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
        window.location.href='login.html';
    </script>";
}
?>