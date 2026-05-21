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

if (isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}
if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int)$_SESSION['id_siswa'];
}
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
if ($id_siswa <= 0 && isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Silakan login terlebih dahulu. Session tidak ditemukan.',
        'debug' => [
            'session_id' => session_id(),
            'session_data_keys' => array_keys($_SESSION)
        ]
    ]);
    $conn->close();
    exit;
}

// ==============================================
// 5. Ambil data siswa
// ==============================================
$sqlSiswa = "SELECT s.id_siswa, s.nama, s.nisn, s.id_kelas, k.nama_kelas, s.id_tahun_ajaran
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

// ==============================================
// 9. Siapkan prompt untuk Gemini (DIPERBAIKI - Kesimpulan Singkat)
// ==============================================
$prompt = "Kamu adalah seorang GURU SMP YP 17 Surabaya. Gunakan sapaan 'Saya' atau 'Guru'.

LARANGAN KERAS:
- JANGAN sebut AI, bot, asisten, teknologi apapun
- JANGAN pakai emoji atau simbol aneh
- JANGAN sebut 'Ibu' atau 'Bapak'
- JANGAN tulis 'Kesimpulan:' atau 'Kesimpulan & Motivasi' sebagai judul
- **JANGAN ulang analisis per mapel di bagian kesimpulan**

Data siswa:
Nama: $nama_siswa
Kelas: $kelas_siswa
Tahun Ajaran: $tahun_ajaran

Berikut nilai dan perkembangan per mapel:

";

foreach ($data_analisis as $mapel) {
    $prompt .= "=== {$mapel['nama_mapel']} ===\n";
    if ($mapel['nilai_semester_1'] !== null) $prompt .= "Nilai S1: {$mapel['nilai_semester_1']}\n";
    if ($mapel['nilai_semester_2'] !== null) $prompt .= "Nilai S2: {$mapel['nilai_semester_2']}\n";
    if ($mapel['growth'] !== null) $prompt .= "Perkembangan: {$mapel['trend_text']}\n";
    $prompt .= "CP: ";
    if (!empty($mapel['cp_daftar'])) {
        foreach ($mapel['cp_daftar'] as $cp) $prompt .= "{$cp['elemen']}, ";
    } else {
        $prompt .= "(belum ada data CP)";
    }
    $prompt .= "\n\n";
}

$prompt .= "Rata-rata keseluruhan: $rata_keseluruhan\n";
$prompt .= "Mapel terendah: $mapel_terendah ($nilai_terendah)\n\n";

$prompt .= "TUGAS:
1. Untuk setiap mapel, tulis analisis 1-2 kalimat dengan format [NAMA MAPEL]: isi analisis.
   - Apresiasi jika nilai naik/tinggi
   - Semangat jika nilai turun
   - Sebutkan CP yang dikuasai atau perlu ditingkatkan

2. **SETELAH SEMUA MAPEL**, tulis KESIMPULAN yang SANGAT SINGKAT (maksimal 3 kalimat). 
   Kesimpulan hanya berisi:
   - Apresiasi umum (contoh: \"Nak Sandi, secara umum kamu sudah berprestasi sangat baik.\")
   - Saran perbaikan (hanya untuk mapel terendah, jika ada)
   - Motivasi singkat (contoh: \"Pertahankan semangat belajarmu!\")

CONTOH KESIMPULAN YANG BENAR (pendek, tidak mengulang mapel):
\"Nak Sandi, nilai rata-ratamu 96.3 sangat membanggakan. Coba lebih giat lagi untuk PKN agar semakin seimbang. Teruslah belajar dengan tekun!\"

CONTOH KESIMPULAN YANG SALAH (JANGAN DITULIS, karena terlalu panjang):
( Tidak perlu menulis ulang analisis PKN, Matematika, IPA, dll. )

JANGAN tulis kata 'Kesimpulan:' sebagai judul. Langsung tulis isi kesimpulan setelah selesai semua mapel.

Aturan tambahan:
- Gunakan sapaan 'Nak $nama_siswa' di awal kesimpulan
- JANGAN pakai 'Ibu Guru' atau 'Bapak Guru' - cukup 'Saya' atau 'Guru'
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
        $response = "Nak $nama,\n\n";
        $response .= "Saya sudah melihat perkembangan belajarmu di Tahun Ajaran ini.\n\n";
        $response .= "Rata-rata nilai keseluruhanmu adalah $rataKeseluruhan. ";
        if ($rataKeseluruhan >= 85) $response .= "Prestasi yang sangat membanggakan! Saya bangga dengan kerja kerasmu.\n\n";
        elseif ($rataKeseluruhan >= 70) $response .= "Hasil yang cukup baik. Masih ada ruang untuk lebih baik lagi.\n\n";
        else $response .= "Jangan menyerah ya, Nak. Saya yakin kamu bisa lebih baik.\n\n";
        $response .= "Berdasarkan nilai dan Capaian Pembelajaran (CP), berikut catatan saya:\n\n";
        foreach ($dataAnalisis as $mapel) {
            $response .= "📖 {$mapel['nama_mapel']}\n";
            if ($mapel['nilai_semester_1'] !== null && $mapel['nilai_semester_2'] !== null)
                $response .= "   Perkembangan: {$mapel['trend_text']} (S1: {$mapel['nilai_semester_1']} → S2: {$mapel['nilai_semester_2']})\n";
            elseif ($mapel['nilai_akhir'] > 0)
                $response .= "   Nilai: {$mapel['nilai_akhir']}\n";
            if (!empty($mapel['cp_daftar'])) {
                $response .= "   CP yang perlu diperhatikan:\n";
                foreach ($mapel['cp_daftar'] as $cp) $response .= "      - {$cp['elemen']}\n";
            }
            $response .= "\n";
        }
        $response .= "🎯 Fokus utama: $mapelTerendah (nilai: $nilaiTerendah)\n\n";
        $response .= "💡 Saran untuk $mapelTerendah:\n";
        $response .= "   - Luangkan waktu 30 menit setiap hari khusus belajar mapel ini\n";
        $response .= "   - Catat materi yang terasa sulit, lalu tanyakan ke guru\n";
        $response .= "   - Belajar bersama teman yang lebih paham\n\n";
        $response .= "Tetap semangat, Nak $nama! Masa depan cerah menantimu. Saya selalu mendukungmu.";
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

// 13. Kirim respons sukses dari AI
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