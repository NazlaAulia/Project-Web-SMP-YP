<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = 0;

if (isset($_SESSION['id_siswa'])) {
    $id_siswa = (int) $_SESSION['id_siswa'];
}

if ($id_siswa <= 0 && isset($_GET['id_siswa'])) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int) $_SESSION['id_user'];

    $stmtUser = $conn->prepare("SELECT id_siswa FROM user WHERE id_user = ? LIMIT 1");
    $stmtUser->bind_param("i", $id_user);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();

    if ($userData && !empty($userData['id_siswa'])) {
        $id_siswa = (int) $userData['id_siswa'];
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$stmt = $conn->prepare("
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
        COALESCE(p.email, u.username) AS email,
        p.no_hp,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN pendaftaran p 
        ON s.id_pendaftaran = p.id_pendaftaran 
        OR s.nisn = p.nisn
    LEFT JOIN user u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);
$stmt->execute();

$result = $stmt->get_result();

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query gagal: " . $conn->error
    ]);
    exit;
}

$data = $result->fetch_assoc();

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