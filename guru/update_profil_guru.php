<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

if (!isset($_SESSION['id_guru'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Belum login"
    ]);
    exit;
}

$id_guru = $_SESSION['id_guru'];

$nama  = $_POST['nama'];
$nip   = $_POST['nip'];
$email = $_POST['email'];

$query = mysqli_query($conn, "
    UPDATE guru SET
        nama = '$nama',
        nip = '$nip',
        email = '$email'
    WHERE id_guru = '$id_guru'
");

if ($query) {
    mysqli_query($conn, "
        UPDATE user SET
            username = '$nama',
            password = '$nip'
        WHERE id_guru = '$id_guru'
        AND role = 'guru'
    ");

    echo json_encode([
        "status" => "success",
        "message" => "Profil berhasil diperbarui"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal memperbarui profil"
    ]);
}
?>