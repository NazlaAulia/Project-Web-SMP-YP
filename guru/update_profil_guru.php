<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

if (!isset($_SESSION['id_guru']) || $_SESSION['id_guru'] == '') {
    echo json_encode([
        "status" => "error",
        "message" => "Belum login"
    ]);
    exit;
}

$id_guru  = $_SESSION['id_guru'];
$nama     = $_POST['nama'] ?? '';
$nip      = $_POST['nip'] ?? '';
$email    = $_POST['email'] ?? '';
$id_mapel = $_POST['id_mapel'] ?? '';

$query = mysqli_query($conn, "
    UPDATE guru SET
        nama = '$nama',
        nip = '$nip',
        email = '$email',
        id_mapel = '$id_mapel'
    WHERE id_guru = '$id_guru'
");

if ($query) {

    echo json_encode([
        "status" => "success",
        "message" => "Profil guru berhasil diperbarui"
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal update profil: " . mysqli_error($conn)
    ]);
}
?>