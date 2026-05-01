<?php
include '../koneksi.php';

// Beri tahu browser bahwa ini adalah balasan berupa data JSON
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

// Cek jika status sudah diproses
if ($statusLama !== 'menunggu') {
    echo json_encode([
        'success' => false, 
        'message' => "Pendaftaran atas nama {$namaSiswa} sudah diproses sebelumnya dengan status " . ucfirst($statusLama) . "."
    ]);
    exit;
}

// Proses Update
$stmt = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ? WHERE id_pendaftaran = ?");
mysqli_stmt_bind_param($stmt, "si", $status, $id_pendaftaran);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate database.']);
    exit;
}
mysqli_stmt_close($stmt);

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