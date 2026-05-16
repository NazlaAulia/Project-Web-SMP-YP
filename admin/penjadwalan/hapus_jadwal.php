<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id_jadwal = isset($_POST['id_jadwal']) ? (int)$_POST['id_jadwal'] : 0;

if ($id_jadwal <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID jadwal tidak valid']);
    exit;
}

// Cek apakah jadwal sudah fix (terkunci)
$cek = $conn->query("SELECT j.status, ta.jadwal_locked 
                      FROM jadwal j 
                      JOIN tahun_ajaran ta ON j.id_tahun_ajaran = ta.id_tahun_ajaran 
                      WHERE j.id_jadwal = $id_jadwal");
$row = $cek->fetch_assoc();

if ($row['status'] == 'fix' || $row['jadwal_locked'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Jadwal sudah terkunci. Tidak bisa dihapus.']);
    exit;
}

$query = "DELETE FROM jadwal WHERE id_jadwal = $id_jadwal";
if ($conn->query($query)) {
    echo json_encode(['success' => true, 'message' => 'Jadwal berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>