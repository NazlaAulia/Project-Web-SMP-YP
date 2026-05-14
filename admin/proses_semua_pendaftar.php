<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id_tahun = isset($_GET['id_tahun']) ? (int)$_GET['id_tahun'] : 0;
if ($id_tahun <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tahun ajaran tidak valid']);
    exit;
}

// Ambil informasi kuota
$info_ta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kuota FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun"));
$kuota = $info_ta['kuota'];

// Hitung jumlah pendaftar yang sudah diterima
$diterima = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun AND status='diterima'"))['total'];
$sisa_kuota = $kuota - $diterima;

// Ambil semua pendaftar menunggu, urutkan dari yang terlama
$query = "SELECT id_pendaftaran, nama_lengkap FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun AND status = 'menunggu' ORDER BY tanggal_daftar ASC";
$result = mysqli_query($conn, $query);
$jumlah_menunggu = mysqli_num_rows($result);

if ($jumlah_menunggu == 0) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada pendaftar yang menunggu']);
    exit;
}

$terima = 0;
$tolak = 0;

// Proses berdasarkan sisa kuota
while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id_pendaftaran'];
    if ($sisa_kuota > 0) {
        // Terima jika masih ada kuota
        mysqli_query($conn, "UPDATE pendaftaran SET status = 'diterima', wa_sent = 0 WHERE id_pendaftaran = $id");
        $terima++;
        $sisa_kuota--;
    } else {
        // Tolak jika kuota habis
        mysqli_query($conn, "UPDATE pendaftaran SET status = 'ditolak', wa_sent = 0 WHERE id_pendaftaran = $id");
        $tolak++;
    }
}

echo json_encode([
    'success' => true,
    'message' => "Proses selesai. $terima pendaftar diterima, $tolak pendaftar ditolak. Sisa kuota sekarang: $sisa_kuota"
]);
?>