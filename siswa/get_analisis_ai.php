<?php
// Pastikan output file ini terbaca sebagai JSON
header('Content-Type: application/json; charset=utf-8');

// 1. Aktifkan error reporting untuk debugging (matiikan di production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 2. Ambil config API key dari folder admin (GANTI DENGAN PATH YANG BENAR)
// Karena file ini di: siswa/get_analisis_ai.php
// Dan config di: admin/penjadwalan/config.php
$configPath = __DIR__ . '/../admin/penjadwalan/config.php';

if (!file_exists($configPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'File config tidak ditemukan di: ' . $configPath
    ]);
    exit;
}

require_once $configPath;

// Cek apakah API key sudah didefinisikan
if (!defined('GEMINI_API_KEY') && !isset($GEMINI_API_KEY)) {
    echo json_encode([
        'success' => false,
        'message' => 'GEMINI_API_KEY tidak ditemukan di config.php'
    ]);
    exit;
}

// Ambil nilai API key (baik berupa define atau variable biasa)
$api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : $GEMINI_API_KEY;

// 3. Hubungkan dengan file koneksi database
require_once __DIR__ . '/koneksi.php';

// 4. Cek koneksi database
if (!isset($koneksi) || !$koneksi) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database tidak ditemukan. Cek file koneksi.php.'
    ]);
    exit;
}

// 5. Ambil session login siswa
session_start();
$nisn = $_SESSION['nisn'] ?? null;

if (!$nisn) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu.'
    ]);
    exit;
}

// 6. Ambil data nilai siswa dari database
$queryNilai = "SELECT m.nama_mapel, AVG(n.nilai) as rata_rata 
               FROM nilai n 
               JOIN mata_pelajaran m ON n.id_mapel = m.id_mapel 
               WHERE n.nisn = ? 
               GROUP BY m.id_mapel 
               ORDER BY rata_rata ASC";

$stmt = $koneksi->prepare($queryNilai);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal prepare query database.',
        'db_error' => $koneksi->error
    ]);
    exit;
}

$stmt->bind_param("s", $nisn);
$stmt->execute();
$result = $stmt->get_result();

$semua_nilai = [];
$mapel_terendah = '';
$nilai_terendah = 100;

while ($row = $result->fetch_assoc()) {
    $rata = round($row['rata_rata'], 2);
    $semua_nilai[] = [
        'nama_mapel' => $row['nama_mapel'],
        'rata_rata' => $rata
    ];
    
    if ($rata < $nilai_terendah) {
        $nilai_terendah = $rata;
        $mapel_terendah = $row['nama_mapel'];
    }
}

// Jika tidak ada data nilai
if (empty($semua_nilai)) {
    echo json_encode([
        'success' => false,
        'message' => 'Belum ada data nilai untuk siswa ini.'
    ]);
    $stmt->close();
    $koneksi->close();
    exit;
}

// 7. Ambil nama siswa
$querySiswa = "SELECT nama_lengkap FROM users WHERE nisn = ?";
$stmt2 = $koneksi->prepare($querySiswa);
$stmt2->bind_param("s", $nisn);
$stmt2->execute();
$result2 = $stmt2->get_result();
$siswa = $result2->fetch_assoc();
$nama_siswa = $siswa['nama_lengkap'] ?? 'Siswa';

$stmt->close();
$stmt2->close();

// 8. Siapkan prompt untuk Gemini API
$prompt = "Kamu adalah asisten AI untuk siswa SMP YP 17 Surabaya. Berikan analisis belajar yang singkat, jelas, dan memotivasi.

Data siswa:
Nama: $nama_siswa

Nilai per mata pelajaran:\n";

foreach ($semua_nilai as $mapel) {
    $prompt .= "- {$mapel['nama_mapel']}: {$mapel['rata_rata']}\n";
}

$rata_keseluruhan = array_sum(array_column($semua_nilai, 'rata_rata')) / count($semua_nilai);
$prompt .= "\nRata-rata keseluruhan: " . round($rata_keseluruhan, 1) . "\n";
$prompt .= "Mapel terendah: $mapel_terendah (nilai: $nilai_terendah)\n\n";

$prompt .= "Tugasmu:
Buat analisis dalam 4 paragraf:

Paragraf 1: Apresiasi dan semangat untuk siswa (sesuaikan dengan prestasinya)
Paragraf 2: Analisis spesifik untuk mapel terendah (kenapa mungkin sulit)
Paragraf 3: Saran konkret yang bisa dilakukan siswa (minimal 3 poin)
Paragraf 4: Kata-kata motivasi yang membangkitkan semangat

Aturan:
- Gunakan bahasa Indonesia yang ramah, hangat, dan tidak terlalu formal
- Maksimal 400 kata
- Jangan gunakan markdown atau format khusus
- Jangan sebut \"Sebagai AI\" atau \"Berdasarkan data\"
- Langsung berikan analisisnya tanpa kata pengantar";

// 9. Panggil Gemini API
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 800
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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Eksekusi API
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

// 10. Proteksi jika koneksi ke Google gagal
if ($response === false || $http_code < 200 || $http_code >= 300) {
    $fallback_response = generateFallbackResponse($nama_siswa, $semua_nilai, $mapel_terendah, $nilai_terendah);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'semua_nilai' => $semua_nilai,
            'mapel_terendah' => $mapel_terendah,
            'nilai_terendah' => $nilai_terendah,
            'ai_response' => $fallback_response,
            'note' => 'Mode offline (AI tidak dapat dihubungi)',
            'http_code' => $http_code,
            'curl_error' => $curl_error
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 11. Olah balasan dari AI
$gemini_data = json_decode($response, true);

// Cek apakah response dari Gemini valid
if (!isset($gemini_data['candidates'][0]['content']['parts'][0]['text'])) {
    $fallback_response = generateFallbackResponse($nama_siswa, $semua_nilai, $mapel_terendah, $nilai_terendah);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'semua_nilai' => $semua_nilai,
            'mapel_terendah' => $mapel_terendah,
            'nilai_terendah' => $nilai_terendah,
            'ai_response' => $fallback_response,
            'note' => 'Format response AI tidak sesuai',
            'debug_response' => $gemini_data
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$ai_response = $gemini_data['candidates'][0]['content']['parts'][0]['text'];

if (empty(trim($ai_response))) {
    $ai_response = generateFallbackResponse($nama_siswa, $semua_nilai, $mapel_terendah, $nilai_terendah);
}

// Kirim respons sukses
echo json_encode([
    'success' => true,
    'data' => [
        'semua_nilai' => $semua_nilai,
        'mapel_terendah' => $mapel_terendah,
        'nilai_terendah' => $nilai_terendah,
        'ai_response' => $ai_response
    ]
], JSON_UNESCAPED_UNICODE);

// Tutup koneksi database
$koneksi->close();

// 12. Fungsi fallback jika AI gagal
function generateFallbackResponse($nama, $nilaiList, $mapelTerendah, $nilaiTerendah) {
    $rataSemua = array_sum(array_column($nilaiList, 'rata_rata')) / count($nilaiList);
    
    $response = "Halo $nama! 👋\n\n";
    $response .= "Terima kasih sudah menggunakan fitur analisis belajar.\n\n";
    $response .= "Berdasarkan data nilaimu:\n";
    $response .= "📊 Rata-rata keseluruhan: " . round($rataSemua, 1) . "\n\n";
    
    if ($rataSemua >= 85) {
        $response .= "✨ **Selamat!** Prestasimu sangat membanggakan. Kamu telah menunjukkan kerja keras yang luar biasa! ✨\n\n";
    } elseif ($rataSemua >= 70) {
        $response .= "👍 **Bagus!** Kamu sudah berada di jalur yang tepat. Terus pertahankan dan tingkatkan lagi!\n\n";
    } else {
        $response .= "💪 **Jangan menyerah!** Setiap orang punya proses belajarnya masing-masing. Kamu pasti bisa!\n\n";
    }
    
    $response .= "🎯 **Fokus perbaikan:** $mapelTerendah (nilai: $nilaiTerendah)\n\n";
    $response .= "💡 **Tips untuk $mapelTerendah:**\n";
    $response .= "• Luangkan waktu 30 menit setiap hari khusus untuk latihan soal\n";
    $response .= "• Catat materi yang sulit dan tanyakan ke guru\n";
    $response .= "• Tonton video pembelajaran di YouTube\n";
    $response .= "• Belajar bersama teman yang lebih paham\n\n";
    $response .= "📝 **Kebiasaan belajar yang baik:**\n";
    $response .= "1. Buat jadwal belajar teratur\n";
    $response .= "2. Istirahat 7-8 jam setiap hari\n";
    $response .= "3. Kurangi main gadget saat belajar\n\n";
    $response .= "🌟 **Motivasi untukmu:**\n\"Kesuksesan bukan tentang seberapa cepat kamu belajar, tapi seberapa kuat kamu bertahan. Teruslah berusaha, $nama! Masa depan cerah menantimu!\" 🌟";
    
    return $response;
}
?>