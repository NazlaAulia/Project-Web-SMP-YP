<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id_tahun = isset($_POST['id_tahun']) ? (int)$_POST['id_tahun'] : 0;

if ($id_tahun <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID tahun ajaran tidak valid']);
    exit;
}

// Cek apakah jadwal sudah dikunci
$cek = $conn->query("SELECT jadwal_locked FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun");
$row = $cek->fetch_assoc();

if ($row && $row['jadwal_locked'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Jadwal sudah dikunci. Tidak bisa dihapus.']);
    exit;
}

$query = "DELETE FROM jadwal WHERE id_tahun_ajaran = $id_tahun";
if ($conn->query($query)) {
    $jumlah = $conn->affected_rows;
    echo json_encode(['success' => true, 'message' => "$jumlah jadwal berhasil dihapus"]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>