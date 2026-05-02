<?php
// Pastikan output file ini terbaca sebagai JSON
header('Content-Type: application/json');

// 1. Tangkap Input dari Request AJAX Frontend
$id_jadwal_lama = isset($_POST['id_jadwal']) ? intval($_POST['id_jadwal']) : 0;
$id_guru        = isset($_POST['id_guru']) ? intval($_POST['id_guru']) : 0;
$id_kelas       = isset($_POST['id_kelas']) ? intval($_POST['id_kelas']) : 0;

// Validasi
if ($id_jadwal_lama === 0 || $id_guru === 0 || $id_kelas === 0) {
    echo json_encode(['success' => false, 'message' => 'Data request tidak lengkap.']);
    exit;
}

// 2. Hubungkan dengan file koneksi database-mu
require_once '../koneksi.php'; 

// Master Waktu (Sesuaikan dengan jam pelajaran sekolah)
$master_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$master_jam  = ['07:00-08:00', '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00'];

$slot_kosong = [];

// 3. Cari Slot Kosong di Database
foreach ($master_hari as $hari) {
    foreach ($master_jam as $jam) {
        $query_cek = "
            SELECT 1 FROM jadwal 
            WHERE hari = ? 
            AND jam = ? 
            AND (id_guru = ? OR id_kelas = ?)
            AND id_jadwal != ?
            LIMIT 1
        ";
        
        $stmt = $conn->prepare($query_cek);
        $stmt->bind_param("ssiii", $hari, $jam, $id_guru, $id_kelas, $id_jadwal_lama);
        $stmt->execute();
        $stmt->store_result();
        
        // Jika tidak ada data yang bentrok, masukkan ke array slot_kosong
        if ($stmt->num_rows == 0) {
            $slot_kosong[] = ['hari' => $hari, 'jam'  => $jam];
        }
        $stmt->close();
    }
}

if (empty($slot_kosong)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada slot jadwal yang kosong minggu ini.']);
    $conn->close();
    exit;
}


// ==========================================
// 4. INTEGRASI GEMINI AI MELALUI cURL
// ==========================================

// API Key Gemini kamu
$api_key = 'AIzaSyDVGFr4u07mKUZ2O3Ahca9ZP142wOtyN_4'; 
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

// Instruksi cerdas untuk AI
$prompt = "Kamu adalah asisten penjadwalan cerdas untuk SMP YP 17 Surabaya. Berikut adalah daftar slot jadwal yang dipastikan 100% kosong dan tidak bentrok: " . json_encode($slot_kosong) . ". 
Tugasmu: Pilih maksimal 3 slot jadwal yang paling masuk akal dan nyaman untuk guru.
Format balasan: WAJIB HANYA berupa array JSON (tanpa tag markdown ```json) dengan struktur objek: 'hari', 'jam', dan 'pesan_ai' (isi dengan kalimat ramah maksimal 1 kalimat kenapa direkomendasikan).";

$data = [
    "contents" => [
        ["parts" => [["text" => $prompt]]]
    ]
];

// Setup cURL untuk nembak API
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// Bypass SSL Verification (Solusi ampuh di shared hosting Domainesia/cPanel)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

// Eksekusi API
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch); // Tangkap error asli dari cURL
curl_close($ch);

// Tutup koneksi database
$conn->close();

// Cek apakah API merespons dengan baik
if ($http_code != 200 || !$response) {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal menghubungi server AI.',
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'gemini_response' => json_decode($response, true)
    ]);
    exit;
}

// 5. Olah Balasan AI dan Kirim ke Frontend
$gemini_data = json_decode($response, true);
$text_response = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

// Bersihkan format markdown jika AI ngeyel menambahkan teks ```json
$text_response = str_replace(['```json', '```'], '', $text_response);
$rekomendasi = json_decode(trim($text_response), true);

if (!$rekomendasi) {
    echo json_encode(['success' => false, 'message' => 'Gagal memproses data dari AI.', 'raw' => $text_response]);
    exit;
}

// Berhasil! Lempar data JSON ke Frontend
echo json_encode([
    'success' => true,
    'data' => $rekomendasi,
    'message' => 'Rekomendasi AI berhasil didapatkan.'
]);
?>