<?php
session_start();
header('Content-Type: application/json');
require_once '../koneksi.php';
// Ambil konfigurasi API dari folder admin
require_once '../admin/penjadwalan/config.php'; // file yang berisi define('GEMINI_API_KEY', ...)

$id_siswa = $_SESSION['id_siswa'] ?? 0;
if (!$id_siswa && isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}
if ($id_siswa <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID siswa tidak valid']);
    exit;
}

// Cek cache di session (10 menit)
if (isset($_SESSION['ai_analisis'][$id_siswa]) && $_SESSION['ai_analisis'][$id_siswa]['expires'] > time()) {
    echo json_encode(['success' => true, 'data' => $_SESSION['ai_analisis'][$id_siswa]['data']]);
    exit;
}

// Ambil nilai rata-rata per mapel untuk siswa ini
$query = "
    SELECT m.nama_mapel, AVG(n.nilai_angka) as rata_rata
    FROM nilai n
    JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE n.id_siswa = $id_siswa
    GROUP BY n.id_mapel
    ORDER BY rata_rata ASC
";
$result = mysqli_query($conn, $query);
$nilai_mapel = [];
$nilai_terendah = null;
$nilai_terendah_nama = null;

while ($row = mysqli_fetch_assoc($result)) {
    $nilai_mapel[] = $row;
    if ($nilai_terendah === null || $row['rata_rata'] < $nilai_terendah) {
        $nilai_terendah = $row['rata_rata'];
        $nilai_terendah_nama = $row['nama_mapel'];
    }
}

if (empty($nilai_mapel)) {
    echo json_encode(['success' => false, 'message' => 'Belum ada data nilai untuk siswa ini']);
    exit;
}

// Siapkan prompt untuk Gemini
$prompt = "Berikut adalah data nilai rata-rata siswa per mata pelajaran (KKM = 75):\n";
foreach ($nilai_mapel as $nm) {
    $prompt .= "- {$nm['nama_mapel']}: {$nm['rata_rata']}\n";
}
$prompt .= "\nSiswa ini memiliki nilai terendah di mapel {$nilai_terendah_nama} dengan nilai {$nilai_terendah}.\n";
$prompt .= "Beri saran belajar yang spesifik, tips meningkatkan nilai, dan pesan motivasi untuk siswa. Tulis dalam bahasa Indonesia yang ramah dan tidak terlalu panjang (maksimal 200 kata).\n";
$prompt .= "Gunakan format:\n📚 SARAN BELAJAR:\n...\n💡 TIPS:\n...\n🔥 MOTIVASI:\n...";

// Panggil Gemini API menggunakan fungsi dari admin (jika sudah ada) atau buat sendiri
function callGeminiAPI($prompt) {
    $apiKey = GEMINI_API_KEY; // dari config.php
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
    $data = [
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        return null;
    }
    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

$ai_text = callGeminiAPI($prompt);
if (!$ai_text) {
    echo json_encode(['success' => false, 'message' => 'Gagal memanggil AI. Coba lagi nanti.']);
    exit;
}

// Simpan ke session (cache 10 menit)
$_SESSION['ai_analisis'][$id_siswa] = [
    'expires' => time() + 600,
    'data' => [
        'ai_response' => $ai_text,
        'mapel_terendah' => $nilai_terendah_nama,
        'nilai_terendah' => round($nilai_terendah, 1),
        'semua_nilai' => $nilai_mapel
    ]
];

echo json_encode(['success' => true, 'data' => $_SESSION['ai_analisis'][$id_siswa]['data']]);
?>