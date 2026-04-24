<?php
$host = "localhost";
$user = "USERNAME_DATABASE_KAMU";
$pass = "PASSWORD_DATABASE_KAMU";
$db   = "osbebslk_sekolahyp";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>