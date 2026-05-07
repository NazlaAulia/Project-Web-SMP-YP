<?php
require_once __DIR__ . '/../koneksi.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Akses ditolak']);
    exit;
}

$query = "SELECT id_tahun_ajaran, jadwal_locked FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    if ($row['jadwal_locked'] == 1) {
        echo json_encode(['success'=>false,'message'=>'Jadwal sudah terkunci sebelumnya']);
    } else {
        $stmt = $conn->prepare("UPDATE tahun_ajaran SET jadwal_locked = 1 WHERE id_tahun_ajaran = ?");
        $stmt->bind_param("i", $row['id_tahun_ajaran']);
        $success = $stmt->execute();
        echo json_encode(['success'=>$success, 'message'=>$success ? 'Jadwal berhasil dikunci' : 'Gagal mengunci']);
        $stmt->close();
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Tidak ada tahun ajaran aktif']);
}
$conn->close();
?>