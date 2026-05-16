<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id_kelas = (int) ($_POST['id_kelas'] ?? 0);

if ($id_kelas <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID kelas tidak valid']);
    exit;
}

// 1. Cek apakah kelas memiliki siswa aktif
$cek_siswa = $conn->query("SELECT COUNT(*) as total FROM siswa WHERE id_kelas = $id_kelas AND status = 'aktif'");
$row_siswa = $cek_siswa->fetch_assoc();

if ($row_siswa['total'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Kelas masih memiliki siswa aktif. Tidak bisa dihapus.']);
    exit;
}

// 2. Cek apakah kelas masih digunakan di jadwal
$cek_jadwal = $conn->query("SELECT COUNT(*) as total FROM jadwal WHERE id_kelas = $id_kelas");
$row_jadwal = $cek_jadwal->fetch_assoc();

if ($row_jadwal['total'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Kelas masih memiliki jadwal. Tidak bisa dihapus.']);
    exit;
}

// Hapus kelas
$query = "DELETE FROM kelas WHERE id_kelas = $id_kelas";
if ($conn->query($query)) {
    echo json_encode(['success' => true, 'message' => 'Kelas berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>