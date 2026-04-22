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

$mode = $_GET['mode'] ?? 'guru';

if ($mode === 'mapel') {
    $data = [];
    $query = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");

    if (!$query) {
        respon("error", "Gagal mengambil data mapel: " . $conn->error);
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = $row;
    }

    respon("success", "Data mapel berhasil diambil.", $data);
}

$data = [];
$sql = "
    SELECT 
        g.id_guru,
        g.nip,
        g.nama,
        g.email,
        g.id_mapel,
        u.username,
        m.nama_mapel,
        k.nama_kelas AS wali_kelas
    FROM guru g
    LEFT JOIN user u 
        ON u.id_guru = g.id_guru 
        AND u.role_id = 2
    LEFT JOIN mapel m 
        ON m.id_mapel = g.id_mapel
    LEFT JOIN kelas k 
        ON k.id_wali_kelas = g.id_guru
    ORDER BY g.nama ASC
";

$query = mysqli_query($conn, $sql);

if (!$query) {
    respon("error", "Gagal mengambil data guru: " . $conn->error);
}

while ($row = mysqli_fetch_assoc($query)) {
    $data[] = $row;
}

respon("success", "Data guru berhasil diambil.", $data);
?>