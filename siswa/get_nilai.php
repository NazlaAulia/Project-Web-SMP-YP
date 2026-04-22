<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config/koneksi.php';

if (!isset($_SESSION['id_siswa'])) {
    echo json_encode([
        "success" => false,
        "message" => "Siswa belum login."
    ]);
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$kelasFilter = $_GET['kelas'] ?? '';
$semester = $_GET['semester'] ?? '';

if (empty($semester)) {
    $semester = "2025/2026 - Genap";
}

/* ambil data siswa login */
$stmtSiswa = $conn->prepare("
    SELECT 
        s.id_siswa,
        s.nama_siswa,
        k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
");

if (!$stmtSiswa) {
    echo json_encode([
        "success" => false,
        "message" => "Query siswa gagal: " . $conn->error
    ]);
    exit;
}

$stmtSiswa->bind_param("i", $id_siswa);
$stmtSiswa->execute();
$resSiswa = $stmtSiswa->get_result();
$siswa = $resSiswa->fetch_assoc();

if (!$siswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

$kelasAktif = !empty($kelasFilter) ? $kelasFilter : $siswa['nama_kelas'];

/* ringkasan nilai siswa login */
$stmtRingkasan = $conn->prepare("
    SELECT 
        AVG(n.nilai_angka) AS rata_rata,
        MAX(n.nilai_angka) AS nilai_tertinggi
    FROM nilai n
    WHERE n.id_siswa = ? 
      AND n.kelas = ? 
      AND n.semester = ?
");

if (!$stmtRingkasan) {
    echo json_encode([
        "success" => false,
        "message" => "Query ringkasan gagal: " . $conn->error
    ]);
    exit;
}

$stmtRingkasan->bind_param("iss", $id_siswa, $kelasAktif, $semester);
$stmtRingkasan->execute();
$resRingkasan = $stmtRingkasan->get_result();
$ringkasan = $resRingkasan->fetch_assoc();

/* mapel tertinggi */
$stmtTopMapel = $conn->prepare("
    SELECT mapel, guru, nilai_angka
    FROM nilai
    WHERE id_siswa = ? 
      AND kelas = ? 
      AND semester = ?
    ORDER BY nilai_angka DESC
    LIMIT 1
");

if (!$stmtTopMapel) {
    echo json_encode([
        "success" => false,
        "message" => "Query mapel tertinggi gagal: " . $conn->error
    ]);
    exit;
}

$stmtTopMapel->bind_param("iss", $id_siswa, $kelasAktif, $semester);
$stmtTopMapel->execute();
$resTopMapel = $stmtTopMapel->get_result();
$topMapel = $resTopMapel->fetch_assoc();

/* rata-rata semester sebelumnya */
$stmtPrev = $conn->prepare("
    SELECT AVG(nilai_angka) AS rata_prev
    FROM nilai
    WHERE id_siswa = ? 
      AND kelas = ? 
      AND semester <> ?
");

if (!$stmtPrev) {
    echo json_encode([
        "success" => false,
        "message" => "Query semester sebelumnya gagal: " . $conn->error
    ]);
    exit;
}

$stmtPrev->bind_param("iss", $id_siswa, $kelasAktif, $semester);
$stmtPrev->execute();
$resPrev = $stmtPrev->get_result();
$prev = $resPrev->fetch_assoc();

$avgNow = floatval($ringkasan['rata_rata'] ?? 0);
$avgPrev = floatval($prev['rata_prev'] ?? 0);
$selisih = round($avgNow - $avgPrev, 1);

/* tabel ranking berdasarkan kelas + semester */
$stmtTable = $conn->prepare("
    SELECT 
        s.nama_siswa,
        n.kelas,
        AVG(n.nilai_angka) AS nilai_rata_rata,
        MAX(n.predikat) AS predikat,
        MAX(n.absensi) AS absensi,
        MAX(n.status_arah) AS status_arah
    FROM nilai n
    JOIN siswa s ON s.id_siswa = n.id_siswa
    WHERE n.kelas = ? 
      AND n.semester = ?
    GROUP BY n.id_siswa, s.nama_siswa, n.kelas
    ORDER BY nilai_rata_rata DESC
");

if (!$stmtTable) {
    echo json_encode([
        "success" => false,
        "message" => "Query tabel nilai gagal: " . $conn->error
    ]);
    exit;
}

$stmtTable->bind_param("ss", $kelasAktif, $semester);
$stmtTable->execute();
$resTable = $stmtTable->get_result();

$tabel = [];
$rank = 1;

while ($row = $resTable->fetch_assoc()) {
    $tabel[] = [
        "rank" => $rank++,
        "nama" => $row['nama_siswa'],
        "kelas" => $row['kelas'],
        "nilai_rata_rata" => round($row['nilai_rata_rata'], 1),
        "status_arah" => $row['status_arah'] ?: 'up',
        "predikat" => $row['predikat'] ?: '-',
        "absensi" => $row['absensi'] ?: '-'
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => $siswa['id_siswa'],
        "nama" => $siswa['nama_siswa'],
        "kelas" => $siswa['nama_kelas'],
        "inisial" => strtoupper(substr($siswa['nama_siswa'], 0, 1))
    ],
    "ringkasan" => [
        "rata_rata" => round($avgNow, 1),
        "selisih" => $selisih,
        "nilai_tertinggi" => round(floatval($ringkasan['nilai_tertinggi'] ?? 0), 1),
        "mapel_tertinggi" => ($topMapel['mapel'] ?? '-') . (!empty($topMapel['guru']) ? " - " . $topMapel['guru'] : ""),
        "kelas" => $kelasAktif
    ],
    "tabel" => $tabel
]);

$conn->close();
?>