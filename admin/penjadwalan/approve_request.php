<?php
// Pastikan output file ini selalu terbaca sebagai JSON oleh frontend
header('Content-Type: application/json');

// 1. Tangkap data dari request AJAX frontend
$id_jadwal_lama = isset($_POST['id_jadwal']) ? intval($_POST['id_jadwal']) : 0;
$id_guru = isset($_POST['id_guru']) ? intval($_POST['id_guru']) : 0;

// Validasi sederhana, pastikan data tidak kosong
if ($id_jadwal_lama === 0 || $id_guru === 0) {
    echo json_encode(['success' => false, 'message' => 'Data request tidak lengkap.']);
    exit;
}

// TODO: Sertakan file koneksi database kamu di sini
// require_once '../../koneksi.php'; 

// 2. TODO: Buat Query SQL untuk mencari slot hari & jam yang kosong
$slot_kosong = []; // Nanti array ini diisi hasil query database

// 3. TODO: Setup cURL untuk mengirim $slot_kosong ke API Gemini/AI
$rekomendasi_ai = []; 

// 4. Kirim respons balik ke frontend
echo json_encode([
    'success' => true,
    'data' => $rekomendasi_ai,
    'message' => 'Proses AI berhasil'
]);
?>