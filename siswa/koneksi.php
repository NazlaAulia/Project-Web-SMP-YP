<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_smp_yp"; // Sesuaikan dengan nama database yang dibuat temanmu

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>