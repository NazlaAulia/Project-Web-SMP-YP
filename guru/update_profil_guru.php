<?php
include "koneksi.php";

header("Content-Type: application/json");

$id_guru = $_POST['id_guru'] ?? '';
$nama    = $_POST['nama'] ?? '';
$nip     = $_POST['nip'] ?? '';
$email   = $_POST['email'] ?? '';
$id_mapel = $_POST['id_mapel'] ?? '';

if ($id_guru == '') {
    echo json_encode([
        "status" => "error",
        "message" => "ID guru tidak ditemukan"
    ]);
    exit;
}

$query = mysqli_query($conn, "
    UPDATE guru SET
        nama = '$nama',
        nip = '$nip',
        email = '$email',
        id_mapel = '$id_mapel'
    WHERE id_guru = '$id_guru'
");

if ($query) {
    mysqli_query($conn, "
        UPDATE user SET
            username = LOWER(REPLACE(REPLACE(REPLACE('$nama', ' ', ''), '.', ''), ',', '')),
            password = '$nip'
        WHERE id_guru = '$id_guru'
    ");

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