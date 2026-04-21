<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    respon("error", "Koneksi database gagal.");
}

$mode = $_GET['mode'] ?? 'siswa';

if ($mode === 'kelas') {
    $data = [];
    $query = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY tingkat ASC, nama_kelas ASC");

    if (!$query) {
        respon("error", "Gagal mengambil data kelas: " . $conn->error);
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    respon("success", "Data kelas berhasil diambil.", $data);
}

$data = [];
$sql = "
    SELECT 
        s.id_siswa,
        s.nisn,
        s.nama,
        s.jenis_kelamin,
        s.tahun_ajaran,
        u.username,
        k.nama_kelas
    FROM siswa s
    LEFT JOIN user u 
        ON u.id_siswa = s.id_siswa 
        AND u.role_id = 3
    LEFT JOIN kelas k 
        ON k.id_kelas = s.id_kelas
    ORDER BY s.nama ASC
";

$query = mysqli_query($conn, $sql);

if (!$query) {
    respon("error", "Gagal mengambil data siswa: " . $conn->error);
}

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

respon("success", "Data siswa berhasil diambil.", $data);
?>