<?php
include '../koneksi.php';
include 'datasiswa/fungsi_siswa.php'; // tambahkan ini

header('Content-Type: application/json');

function formatNomorWa($nomor)
{
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if ($nomor === '') return '';
    if (substr($nomor, 0, 1) === '0') return '62' . substr($nomor, 1);
    if (substr($nomor, 0, 2) === '62') return $nomor;
    return $nomor;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$id_pendaftaran = (int) $_GET['id'];
$status = trim($_GET['status']);
$id_tahun = isset($_GET['id_tahun']) ? (int) $_GET['id_tahun'] : 0;

if (!in_array($status, ['diterima', 'ditolak'])) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
    exit;
}

// Cek data pendaftaran
$cek = mysqli_prepare($conn, "SELECT id_pendaftaran, nama_lengkap, no_hp, status FROM pendaftaran WHERE id_pendaftaran = ? LIMIT 1");
mysqli_stmt_bind_param($cek, "i", $id_pendaftaran);
mysqli_stmt_execute($cek);
$result = mysqli_stmt_get_result($cek);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($cek);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Data pendaftaran tidak ditemukan.']);
    exit;
}

$namaSiswa = $data['nama_lengkap'];
$nomorWa = formatNomorWa($data['no_hp']);
$statusLama = $data['status'];

if ($statusLama !== 'menunggu') {
    echo json_encode([
        'success' => false, 
        'message' => "Pendaftaran atas nama {$namaSiswa} sudah diproses sebelumnya dengan status " . ucfirst($statusLama) . "."
    ]);
    exit;
}

// Jika diterima, cek ketersediaan kelas 7 (atau kelas tujuan)
if ($status === 'diterima') {
    // Cari kelas 7 yang masih ada kuota (default: tingkat 7, urut berdasarkan yang paling sedikit siswanya)
    $query_kelas = "SELECT k.id_kelas, k.kapasitas, 
                    (SELECT COUNT(*) FROM siswa WHERE id_kelas = k.id_kelas AND status = 'aktif') as terisi 
                    FROM kelas k WHERE k.tingkat = 7 
                    ORDER BY terisi ASC LIMIT 1";
    $result_kelas = mysqli_query($conn, $query_kelas);
    $kelas_tujuan = mysqli_fetch_assoc($result_kelas);
    
    if (!$kelas_tujuan) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada kelas 7 yang tersedia.']);
        exit;
    }
    
    if ($kelas_tujuan['terisi'] >= $kelas_tujuan['kapasitas']) {
        echo json_encode(['success' => false, 'message' => 'Semua kelas 7 sudah penuh. Tidak bisa menerima pendaftaran baru.']);
        exit;
    }
    
    // Simpan id_kelas_tujuan untuk digunakan nanti (misal di trigger atau insert manual)
    $id_kelas_tujuan = $kelas_tujuan['id_kelas'];
}

// Proses Update: jika diterima dan ada id_tahun, update juga kolom id_tahun_ajaran
if ($status === 'diterima' && $id_tahun > 0) {
    $stmt = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ?, id_tahun_ajaran = ? WHERE id_pendaftaran = ?");
    mysqli_stmt_bind_param($stmt, "sii", $status, $id_tahun, $id_pendaftaran);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ? WHERE id_pendaftaran = ?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id_pendaftaran);
}

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate database.']);
    exit;
}
mysqli_stmt_close($stmt);

// Jika status diterima, update juga tabel siswa (trigger biasanya otomatis, tapi pastikan id_kelas terisi)
if ($status === 'diterima') {
    // Update siswa yang baru dibuat oleh trigger dengan id_kelas yang sudah ditentukan
    $update_siswa = mysqli_query($conn, "UPDATE siswa SET id_kelas = $id_kelas_tujuan, id_tahun_ajaran = $id_tahun, status = 'aktif' WHERE id_pendaftaran = $id_pendaftaran");
    if (!$update_siswa) {
        // Jika gagal, log error tapi tetap lanjut (trigger mungkin sudah handle)
    }
}

// Siapkan pesan WA
$linkWa = '';
if ($status === 'diterima') {
    $pesanWa = "Assalamu'alaikum Bapak/Ibu Wali dari *{$namaSiswa}*.\n\n"
        . "Kami informasikan bahwa hasil pendaftaran peserta didik baru di *SMP YP 17 Surabaya* adalah *DITERIMA*.\n\n"
        . "Status saat ini tercatat sebagai *siswa baru* dan pembagian kelas akan dilakukan pada tahun ajaran baru.\n\n"
        . "Terima kasih.";
} else {
    $pesanWa = "Assalamu'alaikum Bapak/Ibu Wali dari *{$namaSiswa}*.\n\n"
        . "Kami informasikan bahwa hasil pendaftaran peserta didik baru di *SMP YP 17 Surabaya* adalah *DITOLAK*.\n\n"
        . "Terima kasih telah melakukan pendaftaran.\n\n"
        . "Hormat kami,\nAdmin SMP YP 17 Surabaya";
}

if ($nomorWa !== '') {
    $linkWa = "https://wa.me/" . $nomorWa . "?text=" . rawurlencode($pesanWa);
}

// Kembalikan respons berhasil
echo json_encode([
    'success' => true,
    'status_baru' => $status,
    'nama_siswa' => $namaSiswa,
    'link_wa' => $linkWa
]);
?>