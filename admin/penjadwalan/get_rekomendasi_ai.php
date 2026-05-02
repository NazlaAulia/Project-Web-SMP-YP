<?php
// Pastikan output file ini terbaca sebagai JSON
header('Content-Type: application/json; charset=utf-8');

// 1. Tangkap Input dari Request AJAX / Thunder Client
$id_jadwal_lama = isset($_POST['id_jadwal']) ? intval($_POST['id_jadwal']) : 0;
$id_guru        = isset($_POST['id_guru']) ? intval($_POST['id_guru']) : 0;
$id_kelas       = isset($_POST['id_kelas']) ? intval($_POST['id_kelas']) : 0;

// Validasi input
if ($id_jadwal_lama === 0 || $id_guru === 0 || $id_kelas === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Data request tidak lengkap. Pastikan id_jadwal, id_guru, dan id_kelas dikirim.'
    ]);
    exit;
}

// 2. Hubungkan dengan file koneksi database & config rahasia
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/config.php';

// Validasi API Key dari config.php
if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
    echo json_encode([
        'success' => false,
        'message' => 'GEMINI_API_KEY belum terbaca. Cek file config.php.'
    ]);
    exit;
}

// Validasi koneksi database
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak ditemukan. Cek file koneksi.php.'
    ]);
    exit;
}

// Master Waktu
// Sesuaikan dengan jam pelajaran sekolah
$master_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$master_jam  = [
    '07:00-08:00',
    '08:00-09:00',
    '09:00-10:00',
    '10:00-11:00',
    '11:00-12:00'
];

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

        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal prepare query database.',
                'db_error' => $conn->error
            ]);
            $conn->close();
            exit;
        }

        $stmt->bind_param("ssiii", $hari, $jam, $id_guru, $id_kelas, $id_jadwal_lama);
        $stmt->execute();
        $stmt->store_result();

        // Jika tidak ada data yang bentrok, masukkan ke array slot_kosong
        if ($stmt->num_rows == 0) {
            $slot_kosong[] = [
                'hari' => $hari,
                'jam'  => $jam
            ];
        }

        $stmt->close();
    }
}

// Jika database benar-benar penuh
if (empty($slot_kosong)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada slot jadwal yang kosong minggu ini.'
    ]);
    $conn->close();
    exit;
}

// ==========================================
// 4. INTEGRASI GEMINI AI MELALUI cURL
// ==========================================

$api_key = trim(GEMINI_API_KEY);

// Model Gemini yang lebih aman untuk dicoba
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

$prompt = "
Kamu adalah asisten penjadwalan cerdas untuk SMP YP 17 Surabaya.

Berikut adalah daftar slot jadwal yang dipastikan 100% kosong dan tidak bentrok:
" . json_encode($slot_kosong, JSON_UNESCAPED_UNICODE) . "

Tugasmu:
Pilih maksimal 3 slot jadwal yang paling masuk akal dan nyaman untuk guru.

Aturan jawaban:
- WAJIB hanya berupa array JSON.
- Jangan gunakan markdown.
- Jangan gunakan ```json.
- Setiap objek wajib memiliki field: hari, jam, pesan_ai.
- pesan_ai berisi kalimat ramah maksimal 1 kalimat.

Contoh format:
[
  {
    \"hari\": \"Senin\",
    \"jam\": \"08:00-09:00\",
    \"pesan_ai\": \"Slot ini direkomendasikan karena berada di awal hari sehingga pembelajaran masih terasa segar.\"
  }
]
";

$data = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $prompt
                ]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.3,
        "responseMimeType" => "application/json"
    ]
];

// Setup cURL
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $api_key
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Jika hosting bermasalah SSL, aktifkan 2 baris ini.
// Tapi default-nya lebih aman tetap dimatikan.
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

// Eksekusi API
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

// Tutup koneksi database
$conn->close();

// Proteksi jika koneksi ke Google gagal
if ($response === false || $http_code < 200 || $http_code >= 300) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghubungi server AI.',
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'gemini_response' => json_decode($response, true),
        'raw_response' => $response
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. Olah Balasan AI dan Kirim ke Frontend
$gemini_data = json_decode($response, true);

$text_response = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($text_response)) {
    echo json_encode([
        'success' => false,
        'message' => 'Response AI kosong atau format response Gemini berubah.',
        'gemini_response' => $gemini_data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Bersihkan jika AI masih mengirimkan format markdown
$text_response = str_replace(['```json', '```'], '', $text_response);
$text_response = trim($text_response);

$rekomendasi = json_decode($text_response, true);

// Jika format JSON dari AI tidak valid
if (!$rekomendasi || !is_array($rekomendasi)) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memproses data JSON dari AI.',
        'raw' => $text_response,
        'gemini_response' => $gemini_data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Berhasil! Kirim hasil ke Frontend
echo json_encode([
    'success' => true,
    'data' => $rekomendasi,
    'message' => 'Rekomendasi AI berhasil didapatkan.'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>