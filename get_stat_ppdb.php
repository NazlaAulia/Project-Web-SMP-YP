<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

// Ambil tahun ajaran aktif
$ta = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT * FROM tahun_ajaran 
    WHERE status='aktif' 
    LIMIT 1
"));

if (!$ta) {
    echo json_encode(['status'=>'error','message'=>'Tahun ajaran aktif tidak ditemukan']);
    exit;
}

$id_ta = $ta['id_tahun_ajaran'];
$kuota = (int)$ta['kuota'];

// Hitung semua pendaftar di tahun ajaran aktif (termasuk menunggu)
$jumlah_pendaftar = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM pendaftaran 
    WHERE id_tahun_ajaran = $id_ta
"))['total'];

// Kuota tersisa = kuota maksimal - jumlah pendaftar
$kuota_tersisa = $kuota - $jumlah_pendaftar;

echo json_encode([
    'status' => 'success',
    'data' => [
        'tahun_ajaran' => $ta['tahun_ajaran'],
        'jumlah_pendaftar' => (int)$jumlah_pendaftar,
        'kuota_max' => $kuota,
        'kuota_tersisa' => $kuota_tersisa,
        'tgl_buka' => $ta['tgl_buka'],
        'tgl_tutup' => $ta['tgl_tutup']
    ]
]);