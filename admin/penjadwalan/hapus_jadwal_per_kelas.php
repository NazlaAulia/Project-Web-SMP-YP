<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id_kelas = isset($_POST['id_kelas']) ? (int)$_POST['id_kelas'] : 0;
$id_tahun = isset($_POST['id_tahun']) ? (int)$_POST['id_tahun'] : 0;

if ($id_kelas <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID kelas tidak valid']);
    exit;
}

// Cek apakah jadwal sudah dikunci
$cek = $conn->query("SELECT jadwal_locked FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun");
$row = $cek->fetch_assoc();

if ($row && $row['jadwal_locked'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Jadwal sudah dikunci. Tidak bisa dihapus.']);
    exit;
}

// Hitung jumlah jadwal yang akan dihapus
$count = $conn->query("SELECT COUNT(*) as total FROM jadwal WHERE id_kelas = $id_kelas AND id_tahun_ajaran = $id_tahun");
$total = $count->fetch_assoc()['total'];

// Hapus jadwal untuk kelas tersebut
$query = "DELETE FROM jadwal WHERE id_kelas = $id_kelas AND id_tahun_ajaran = $id_tahun";
if ($conn->query($query)) {
    echo json_encode(['success' => true, 'message' => "$total jadwal untuk kelas ini berhasil dihapus", 'jumlah' => $total]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>