<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = 0;

if (isset($_GET['id_siswa'])) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int) $_SESSION['id_siswa'];
}

if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int) $_SESSION['id_user'];

    $queryUser = "SELECT id_siswa FROM user WHERE id_user = $id_user LIMIT 1";
    $resultUser = mysqli_query($conn, $queryUser);

    if ($resultUser && mysqli_num_rows($resultUser) > 0) {
        $userData = mysqli_fetch_assoc($resultUser);

        if (!empty($userData['id_siswa'])) {
            $id_siswa = (int) $userData['id_siswa'];
        }
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan."
    ]);
    exit;
}

$query = "
    SELECT 
        s.id_siswa,
        s.nama,
        s.nis,
        s.nisn,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        s.id_kelas,
        k.nama_kelas AS kelas,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN user u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = $id_siswa
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

$_SESSION['id_siswa'] = $data['id_siswa'];

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>