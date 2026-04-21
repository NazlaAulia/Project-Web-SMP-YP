<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../koneksi.php';

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

$data = [];

$sql = "
    SELECT 
        id_pendaftaran,
        nama_lengkap,
        nisn,
        asal_sekolah,
        no_hp,
        tanggal_daftar
    FROM pendaftaran
    WHERE status = 'menunggu'
    ORDER BY tanggal_daftar DESC, id_pendaftaran DESC
";

$query = mysqli_query($conn, $sql);

if (!$query) {
    respon("error", "Gagal mengambil data pendaftaran: " . $conn->error);
}

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

respon("success", "Data pendaftaran berhasil diambil.", $data);
?>