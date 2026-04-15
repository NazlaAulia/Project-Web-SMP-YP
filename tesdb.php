<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "PASSWORD_DB_KAMU";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    die("GAGAL: " . $conn->connect_error);
}

echo "KONEK BERHASIL";
$conn->close();
?>