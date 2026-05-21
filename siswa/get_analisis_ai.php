<?php
// ==============================================
// SESSION HANDLING + DUKUNGAN GET id_siswa DARI LOCALSTORAGE
// ==============================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Ambil config API key
$configPath = __DIR__ . '/../admin/penjadwalan/config.php';
if (!file_exists($configPath)) {
    echo json_encode(['success' => false, 'message' => 'File config tidak ditemukan']);
    exit;
}
require_once $configPath;
if (!defined('GEMINI_API_KEY') && !isset($GEMINI_API_KEY)) {
    echo json_encode(['success' => false, 'message' => 'GEMINI_API_KEY tidak ditemukan']);
    exit;
}
$api_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : $GEMINI_API_KEY;

// Koneksi database
$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";
$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}
$conn->set_charset("utf8mb4");

// ==============================================
// Ambil id_siswa (PRIORITAS: GET -> SESSION id_siswa -> SESSION id_user)
// ==============================================
$id_siswa = 0;

// 1. Dari GET
if (isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}
// 2. Dari session id_siswa
if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int)$_SESSION['id_siswa'];
}
// 3. Dari session id_user
if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int)$_SESSION['id_user'];
    $sqlUser = "SELECT id_siswa FROM user WHERE id_user = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);
    if ($stmtUser) {
        $stmtUser->bind_param("i", $id_user);
        $stmtUser->execute();
        $stmtUser->bind_result($id_siswa);
        $stmtUser->fetch();
        $stmtUser->close();
        if ($id_siswa) {
            $_SESSION['id_siswa'] = $id_siswa;
        }
    }
}
// Fallback GET
if ($id_siswa <= 0 && isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu. Session tidak ditemukan.',
        'debug' => ['session_id' => session_id(), 'session_data_keys' => array_keys($_SESSION)]
    ]);
    $conn->close();
    exit;
}

// 5. Ambil data siswa
$sqlSiswa = "SELECT s.id_siswa, s.nama, s.nisn, s.id_kelas, k.nama_kelas, s.id_tahun_ajaran
             FROM siswa s
             LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
             WHERE s.id_siswa = ? LIMIT 1";
$stmt = $conn->prepare($sqlSiswa);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();
$siswa = $result->fetch_assoc();
if (!$siswa) {
    echo json_encode(['success' => false, 'message' => 'Data siswa tidak ditemukan']);
    $stmt->close();
    $conn->close();
    exit;
}
$nama_siswa = $siswa['nama'];
$nisn = $siswa['nisn'];
$id_kelas = $siswa['id_kelas'];
$kelas_siswa = $siswa['nama_kelas'] ?? 'SMP';
$id_tahun_ajaran = $siswa['id_tahun_ajaran'];
$stmt->close();

// 5.5 Ambil tahun ajaran
$sqlTahun = "SELECT tahun_ajaran FROM tahun_ajaran WHERE id_tahun_ajaran = ?";
$stmtTahun = $conn->prepare($sqlTahun);
$stmtTahun->bind_param("i", $id_tahun_ajaran);
$stmtTahun->execute();
$resultTahun = $stmtTahun->get_result();
$tahun_ajaran_data = $resultTahun->fetch_assoc();
$tahun_ajaran = $tahun_ajaran_data['tahun_ajaran'] ?? '2024/2025';
$stmtTahun->close();

// 6. Ambil data nilai siswa per semester
$queryNilai = "SELECT 
                    m.id_mapel,
                    m.nama_mapel,
                    n.semester,
                    AVG(n.nilai_angka) as rata_rata
               FROM nilai n 
               JOIN mapel m ON n.id_mapel = m.id_mapel 
               WHERE n.id_siswa = ? 
               GROUP BY m.id_mapel, n.semester
               ORDER BY m.id_mapel, n.semester ASC";
$stmt = $conn->prepare($queryNilai);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$resultNilai = $stmt->get_result();
$nilai_per_mapel = [];
while ($row = $resultNilai->fetch_assoc()) {
    $id_mapel = $row['id_mapel'];
    $semester = $row['semester'];
    $rata = round($row['rata_rata'], 2);
    if (!isset($nilai_per_mapel[$id_mapel])) {
        $nilai_per_mapel[$id_mapel] = [
            'nama_mapel' => $row['nama_mapel'],
            'semester_1' => null,
            'semester_2' => null
        ];
    }
    if ($semester == 1) {
        $nilai_per_mapel[$id_mapel]['semester_1'] = $rata;
    } else {
        $nilai_per_mapel[$id_mapel]['semester_2'] = $rata;
    }
}
$stmt->close();

// 7. Ambil CP
$queryCP = "SELECT cp.id_mapel, m.nama_mapel, cp.elemen, cp.deskripsi_cp, cp.level_kognitif
            FROM capaian_pembelajaran cp
            JOIN mapel m ON cp.id_mapel = m.id_mapel
            WHERE cp.fase = 'D'
            ORDER BY cp.id_mapel, cp.id_cp";
$resultCP = $conn->query($queryCP);
$cp_per_mapel = [];
while ($row = $resultCP->fetch_assoc()) {
    $id = $row['id_mapel'];
    if (!isset($cp_per_mapel[$id])) {
        $cp_per_mapel[$id] = [
            'nama_mapel' => $row['nama_mapel'],
            'cp_list' => []
        ];
    }
    $cp_per_mapel[$id]['cp_list'][] = [
        'elemen' => $row['elemen'],
        'deskripsi' => $row['deskripsi_cp'],
        'level' => $row['level_kognitif']
    ];
}

// 8. Gabungkan data untuk AI
$data_analisis = [];
$mapel_terendah = '';
$nilai_terendah = 100;
foreach ($nilai_per_mapel as $id_mapel => $nilai) {
    $nilai_s1 = $nilai['semester_1'];
    $nilai_s2 = $nilai['semester_2'];
    $nilai_akhir = $nilai_s2 ?? $nilai_s1 ?? 0;
    $growth = null;
    $trend_text = '';
    if ($nilai_s1 !== null && $nilai_s2 !== null) {
        $growth = $nilai_s2 - $nilai_s1;
        if ($growth > 0) $trend_text = "naik " . abs($growth) . " poin";
        elseif ($growth < 0) $trend_text = "turun " . abs($growth) . " poin";
        else $trend_text = "stabil";
    }
    if ($nilai_akhir < $nilai_terendah && $nilai_akhir > 0) {
        $nilai_terendah = $nilai_akhir;
        $mapel_terendah = $nilai['nama_mapel'];
    }
    $data_analisis[] = [
        'id_mapel' => $id_mapel,
        'nama_mapel' => $nilai['nama_mapel'],
        'nilai_semester_1' => $nilai_s1,
        'nilai_semester_2' => $nilai_s2,
        'nilai_akhir' => $nilai_akhir,
        'growth' => $growth,
        'trend_text' => $trend_text,
        'cp_daftar' => $cp_per_mapel[$id_mapel]['cp_list'] ?? []
    ];
}

// Rata-rata keseluruhan
$rata_keseluruhan = 0;
$total_nilai = 0;
$jumlah_mapel = 0;
foreach ($data_analisis as $mapel) {
    if ($mapel['nilai_akhir'] > 0) {
        $total_nilai += $mapel['nilai_akhir'];
        $jumlah_mapel++;
    }
}
if ($jumlah_mapel > 0) $rata_keseluruhan = round($total_nilai / $jumlah_mapel, 1);

// 9. Siapkan prompt untuk Gemini (DIPERBAIKI)
$prompt = "Kamu adalah seorang GURU SMP YP 17 Surabaya yang sedang memberikan masukan pribadi ke muridmu. Kamu bisa laki-laki atau perempuan, jadi gunakan sapaan 'Saya' atau 'Guru' saja.

PENTING: 
- JANGAN sebut dirimu sebagai AI, chatbot, asisten, atau teknologi apapun
- JANGAN gunakan kata 'AI', 'bot', 'asisten', 'saya sebagai AI'
- JANGAN gunakan simbol seperti ✅, 📊, 💡, 🌟, atau emoji apapun
- JANGAN sebut 'Ibu' atau 'Bapak' - cukup pakai 'Saya' atau 'Guru'
- JANGAN tulis 'Kesimpulan:' sebagai judul terpisah

Gunakan bahasa Indonesia yang hangat, penuh perhatian.

Data siswa:
Nama: $nama_siswa
Kelas: $kelas_siswa
Tahun Ajaran: $tahun_ajaran

Berikut adalah nilai dan perkembangan siswa per mata pelajaran:\n\n";

foreach ($data_analisis as $mapel) {
    $prompt .= "=== {$mapel['nama_mapel']} ===\n";
    if ($mapel['nilai_semester_1'] !== null) $prompt .= "Nilai Semester 1: {$mapel['nilai_semester_1']}\n";
    if ($mapel['nilai_semester_2'] !== null) $prompt .= "Nilai Semester 2: {$mapel['nilai_semester_2']}\n";
    if ($mapel['growth'] !== null) $prompt .= "Perkembangan: {$mapel['trend_text']}\n";
    $prompt .= "\nCapaian Pembelajaran (CP) yang harus dikuasai di kelas ini:\n";
    if (!empty($mapel['cp_daftar'])) {
        foreach ($mapel['cp_daftar'] as $cp) {
            $prompt .= "  - {$cp['elemen']}: {$cp['deskripsi']}\n";
        }
    } else {
        $prompt .= "  - (Belum ada data CP untuk mapel ini)\n";
    }
    $prompt .= "\n";
}

$prompt .= "=== RINGKASAN ===\n";
$prompt .= "Rata-rata keseluruhan: $rata_keseluruhan\n";
$prompt .= "Mapel dengan nilai terendah: $mapel_terendah ($nilai_terendah)\n\n";

$prompt .= "TUGASMU:
Buat analisis untuk siswa ini dengan memperhatikan PERKEMBANGAN (growth) dari semester 1 ke semester 2.

Untuk SETIAP mata pelajaran, berikan analisis singkat dengan format:

[MAPEL]: [analisis]

Dalam analisis per mapel, sebutkan:
1. Apresiasi jika nilai naik/membaik, atau semangat jika nilai turun
2. CP mana yang sudah dikuasai dengan baik (jika nilai bagus)
3. CP mana yang perlu ditingkatkan (jika nilai masih kurang atau turun)

Setelah semua mapel, tulis baris '---' lalu tulis KESIMPULAN yang SANGAT SINGKAT (maksimal 3 kalimat). 
**JANGAN ulang analisis per mapel di kesimpulan.** Kesimpulan hanya berisi:
- Apresiasi umum (contoh: 'Nak $nama_siswa, secara umum kamu sudah berprestasi sangat baik.')
- Saran perbaikan (hanya untuk mapel terendah, jika ada)
- Motivasi singkat (contoh: 'Pertahankan semangat belajarmu!')

CONTOH KESIMPULAN YANG BENAR:
--- 
Nak $nama_siswa, rata-rata $rata_keseluruhan sangat membanggakan. Lebih giat lagi untuk $mapel_terendah. Teruslah belajar!

Aturan:
- Gunakan sapaan 'Nak $nama_siswa' di awal kesimpulan
- JANGAN pakai 'Ibu Guru' atau 'Bapak Guru' - cukup 'Saya' atau 'Guru'
- Tulis seperti guru sungguhan
- Jangan pakai kata 'AI', 'bot', 'asisten', 'teknologi'
- Jangan pakai emoji atau simbol aneh
- Langsung tulis pesannya tanpa kata pengantar";

// 10. Fungsi callGeminiAPI
function callGeminiAPI($api_key, $prompt, $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 4096,
            "topP" => 0.95,
            "topK" => 40
        ]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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

// 11. Coba beberapa model
$models = ['gemini-2.0-flash', 'gemini-2.0-flash-lite', 'gemini-1.5-flash', 'gemini-flash-latest'];
$ai_response = null;
foreach ($models as $model) {
    $result = callGeminiAPI($api_key, $prompt, $model);
    if ($result['success']) {
        $ai_response = $result['response'];
        break;
    }
}

// 12. Fallback jika AI gagal
if ($ai_response === null) {
    function generateFallbackResponse($nama, $dataAnalisis, $mapelTerendah, $nilaiTerendah, $rataKeseluruhan) {
        $response = "";
        foreach ($dataAnalisis as $mapel) {
            $response .= "[{$mapel['nama_mapel']}]: Nilai {$mapel['nilai_akhir']}. ";
            if ($mapel['nama_mapel'] == $mapelTerendah) {
                $response .= "Perlu ditingkatkan lagi. ";
            } else {
                $response .= "Pertahankan prestasimu. ";
            }
            $response .= "\n";
        }
        $response .= "---\nNak $nama, rata-rata $rataKeseluruhan. Fokus perbaiki $mapelTerendah. Semangat terus!";
        return $response;
    }
    $fallback_response = generateFallbackResponse($nama_siswa, $data_analisis, $mapel_terendah, $nilai_terendah, $rata_keseluruhan);
    echo json_encode([
        'success' => true,
        'data' => [
            'siswa' => $nama_siswa,
            'kelas' => $kelas_siswa,
            'tahun_ajaran' => $tahun_ajaran,
            'rata_keseluruhan' => $rata_keseluruhan,
            'data_analisis' => $data_analisis,
            'ai_response' => $fallback_response,
            'note' => 'Mode offline (AI tidak dapat dihubungi)'
        ]
    ], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// 13. Kirim respons
echo json_encode([
    'success' => true,
    'data' => [
        'siswa' => $nama_siswa,
        'kelas' => $kelas_siswa,
        'tahun_ajaran' => $tahun_ajaran,
        'rata_keseluruhan' => $rata_keseluruhan,
        'data_analisis' => $data_analisis,
        'ai_response' => $ai_response
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>