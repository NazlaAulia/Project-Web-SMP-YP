<?php
session_start();
include "koneksi.php";

header("Content-Type: application/json");

if (isset($_SESSION['id_user']) && $_SESSION['id_user'] != '') {
    $id_user = $_SESSION['id_user'];
} else {
    // sementara untuk akun Murni kalau session belum kebaca
    $id_user = 36;
}

$query = mysqli_query($conn, "
    SELECT 
        id_user,
        username,
        role_id,
        id_guru
    FROM user
    WHERE id_user = '$id_user'
    LIMIT 1
");

if (!$query) {
    echo json_encode([
        "status" => "error",
        "message" => "Query error: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($query);

if ($data) {

    if ($data['username'] == 'murnispd') {
        $nama = 'Murni S.Pd';
    } else {
        $nama = $data['username'];
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "id_user" => $data['id_user'],
            "id_guru" => $data['id_guru'],
            "username" => $data['username'],
            "nama" => $nama
        ]
    ]);

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Data user tidak ditemukan"
    ]);
}
?>