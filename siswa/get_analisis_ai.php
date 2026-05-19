<?php
// Pastikan output file ini terbaca sebagai JSON
header('Content-Type: application/json; charset=utf-8');

// 1. Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 2. Ambil config API key dari folder admin
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

// Ambil nilai API key
$api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : $GEMINI_API_KEY;

// 3. Koneksi database
$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

// 4. Ambil session login siswa
session_start();

$id_siswa = 0;

if (isset($_SESSION['id_siswa'])) {
    $id_siswa = (int)$_SESSION['id_siswa'];
}

if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int)$_SESSION['id_user'];
    
    $sqlUser = "SELECT id_siswa FROM user WHERE id_user = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    
    if ($stmtUser) {
        $stmtUser->bind_param("i", $id_user);
        $stmtUser->execute();
        $stmtUser->store_result();
        
        if ($stmtUser->num_rows > 0) {
            $stmtUser->bind_result($hasil_id_siswa);
            $stmtUser->fetch();
            if (!empty($hasil_id_siswa)) {
                $id_siswa = (int)$hasil_id_siswa;
                $_SESSION['id_siswa'] = $id_siswa;
            }
        }
        $stmtUser->close();
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu. Session tidak ditemukan.'
    ]);
    $conn->close();
    exit;
}

// 5. Ambil data siswa
$sqlSiswa = "SELECT s.id_siswa, s.nama, s.nisn, s.id_kelas, k.nama_kelas
             FROM siswa s
             LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
             WHERE s.id_siswa = ?
             LIMIT 1";

$stmt = $conn->prepare($sqlSiswa);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();
$siswa = $result->fetch_assoc();

if (!$siswa) {
    echo json_encode([
        'success' => false,
        'message' => 'Data siswa tidak ditemukan'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$nama_siswa = $siswa['nama'];
$nisn = $siswa['nisn'];
$id_kelas = $siswa['id_kelas'];
$stmt->close();

// 6. Ambil data nilai siswa
$queryNilai = "SELECT m.nama_mapel, AVG(n.nilai_angka) as rata_rata 
               FROM nilai n 
               JOIN mapel m ON n.id_mapel = m.id_mapel 
               WHERE n.id_siswa = ? 
               GROUP BY m.id_mapel 
               ORDER BY rata_rata ASC";

$stmt = $conn->prepare($queryNilai);
$stmt->bind_param("i", $id_siswa);
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

if (empty($semua_nilai)) {
    echo json_encode([
        'success' => false,
        'message' => 'Belum ada data nilai untuk siswa ini.'
    ]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();

// 7. Siapkan prompt untuk Gemini API
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

// 8. Fungsi untuk memanggil Gemini API dengan model tertentu
function callGeminiAPI($api_key, $prompt, $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    
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
            "maxOutputTokens" => 800,
            "topP" => 0.95,
            "topK" => 40
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; GeminiBot/1.0)');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || $http_code < 200 || $http_code >= 300) {
        $error_body = json_decode($response, true);
        $error_message = isset($error_body['error']['message']) ? $error_body['error']['message'] : ($curl_error ?: 'HTTP ' . $http_code);
        return ['success' => false, 'error' => $error_message];
    }
    
    $gemini_data = json_decode($response, true);
    if (!isset($gemini_data['candidates'][0]['content']['parts'][0]['text'])) {
        return ['success' => false, 'error' => 'Format response tidak sesuai'];
    }
    
    return ['success' => true, 'response' => $gemini_data['candidates'][0]['content']['parts'][0]['text']];
}

// 9. Daftar model yang akan dicoba secara berurutan
$models = [
    'gemini-2.0-flash',
    'gemini-2.0-flash-lite',
    'gemini-1.5-flash',
    'gemini-flash-latest',
    'gemini-2.5-flash-lite'
];

$ai_response = null;
$model_used = null;
$error_message = null;

foreach ($models as $model) {
    $result = callGeminiAPI($api_key, $prompt, $model);
    if ($result['success']) {
        $ai_response = $result['response'];
        $model_used = $model;
        break;
    } else {
        $error_message = $result['error'];
        // Lanjut ke model berikutnya
        continue;
    }
}

// 10. Jika semua model gagal, pakai fallback
if ($ai_response === null) {
    $fallback_response = generateFallbackResponse($nama_siswa, $semua_nilai, $mapel_terendah, $nilai_terendah);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'semua_nilai' => $semua_nilai,
            'mapel_terendah' => $mapel_terendah,
            'nilai_terendah' => $nilai_terendah,
            'ai_response' => $fallback_response,
            'note' => 'Mode offline (Semua model gagal: ' . $error_message . ')'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kirim respons sukses dengan AI online
echo json_encode([
    'success' => true,
    'data' => [
        'semua_nilai' => $semua_nilai,
        'mapel_terendah' => $mapel_terendah,
        'nilai_terendah' => $nilai_terendah,
        'ai_response' => $ai_response,
        'note' => '✅ AI Online - ditenagai ' . $model_used
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();

// 11. Fungsi fallback
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