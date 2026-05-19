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

// 7. Siapkan prompt untuk Gemini API - GURU NETRAL
$prompt = "Kamu adalah seorang GURU SMP YP 17 Surabaya yang sedang memberikan masukan pribadi ke muridmu. Kamu bisa laki-laki atau perempuan, jadi gunakan sapaan 'Saya' atau 'Guru' saja.

PENTING: 
- JANGAN sebut dirimu sebagai AI, chatbot, asisten, atau teknologi apapun
- JANGAN gunakan kata 'AI', 'bot', 'asisten', 'saya sebagai AI'
- JANGAN gunakan simbol seperti ✅, 📊, 💡, 🌟, atau emoji apapun
- JANGAN sebut 'Ibu' atau 'Bapak' - cukup pakai 'Saya' atau 'Guru'

Gunakan bahasa Indonesia yang hangat, penuh perhatian, seperti seorang guru yang peduli dengan muridnya.
Bicaralah secara personal, seolah-olah sedang berbicara langsung dengan murid bernama $nama_siswa.

Data nilai murid:
Nama: $nama_siswa

Nilai per mata pelajaran:\n";

foreach ($semua_nilai as $mapel) {
    $prompt .= "- {$mapel['nama_mapel']}: {$mapel['rata_rata']}\n";
}

$rata_keseluruhan = array_sum(array_column($semua_nilai, 'rata_rata')) / count($semua_nilai);
$prompt .= "\nRata-rata keseluruhan: " . round($rata_keseluruhan, 1) . "\n";
$prompt .= "Mapel terendah: $mapel_terendah (nilai: $nilai_terendah)\n\n";

$prompt .= "Tugasmu:
Tulis pesan pribadi untuk murid ini dalam 4 paragraf:

Paragraf 1: Berikan apresiasi tulus atas prestasi yang sudah diraih (tunjukkan bahwa guru bangga)
Paragraf 2: Bahas mata pelajaran yang terendah dengan nada sabar, jelaskan kemungkinan penyebabnya
Paragraf 3: Berikan saran konkret yang bisa dilakukan (minimal 3 poin, seperti guru memberi arahan)
Paragraf 4: Tutup dengan kata-kata motivasi dan harapan untuk masa depan murid

Aturan:
- Gunakan sapaan 'Nak' atau langsung panggil nama
- JANGAN pakai 'Ibu Guru' atau 'Bapak Guru' - cukup 'Saya' atau 'Guru'
- Tulis seperti guru sungguhan yang sedang berbicara dengan muridnya
- Jangan pakai kata 'AI', 'bot', 'asisten', 'teknologi'
- Jangan pakai emoji atau simbol aneh
- Maksimal 500 kata
- Langsung tulis pesannya tanpa kata pengantar";

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
            "maxOutputTokens" => 2048,
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
            'ai_response' => $fallback_response
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kirim respons sukses (TANPA note AI Online)
echo json_encode([
    'success' => true,
    'data' => [
        'semua_nilai' => $semua_nilai,
        'mapel_terendah' => $mapel_terendah,
        'nilai_terendah' => $nilai_terendah,
        'ai_response' => $ai_response
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();

// 11. Fungsi fallback (gaya guru netral)
function generateFallbackResponse($nama, $nilaiList, $mapelTerendah, $nilaiTerendah) {
    $rataSemua = array_sum(array_column($nilaiList, 'rata_rata')) / count($nilaiList);
    
    $response = "Nak $nama,\n\n";
    $response .= "Saya sangat bangga melihat prestasi yang telah kamu raih. ";
    $response .= "Rata-rata nilai " . round($rataSemua, 1) . " adalah pencapaian yang luar biasa.\n\n";
    
    if ($rataSemua >= 85) {
        $response .= "Selamat ya, Nak! Kamu telah menunjukkan kerja keras yang membuahkan hasil membanggakan. Terus pertahankan semangat belajarmu!\n\n";
    } elseif ($rataSemua >= 70) {
        $response .= "Bagus sekali, Nak! Kamu sudah berada di jalur yang tepat. Masih ada sedikit ruang untuk lebih baik lagi.\n\n";
    } else {
        $response .= "Jangan menyerah ya, Nak. Setiap orang punya proses belajarnya masing-masing. Saya yakin kamu pasti bisa!\n\n";
    }
    
    $response .= "Untuk mata pelajaran $mapelTerendah yang nilainya $nilaiTerendah, coba beberapa tips ini:\n";
    $response .= "1. Luangkan waktu 30 menit setiap hari khusus belajar $mapelTerendah\n";
    $response .= "2. Catat materi yang terasa sulit, lalu tanyakan ke guru\n";
    $response .= "3. Belajar bersama teman yang lebih paham\n\n";
    
    $response .= "Tetap semangat, Nak $nama! Masa depan cerah menantimu. Saya selalu mendukungmu.";
    
    return $response;
}
?>