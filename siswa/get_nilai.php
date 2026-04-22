<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../koneksi.php';

function konversiSemesterKeAngka($semesterText) {
    $semesterText = trim((string)$semesterText);

    if ($semesterText === '') return 2;
    if (stripos($semesterText, 'genap') !== false) return 2;
    if (stripos($semesterText, 'ganjil') !== false) return 1;
    if (is_numeric($semesterText)) return (int)$semesterText;

    return 2;
}

function hitungPredikat($nilai) {
    $nilai = (float)$nilai;
    if ($nilai >= 90) return 'A';
    if ($nilai >= 80) return 'B';
    if ($nilai >= 70) return 'C';
    if ($nilai >= 60) return 'D';
    return 'E';
}

$id_siswa = 0;

if (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
} elseif (isset($_GET['id_siswa']) && (int)$_GET['id_siswa'] > 0) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$kelasFilter = trim($_GET['kelas'] ?? '');
$semesterText = trim($_GET['semester'] ?? '');

if (empty($semesterText)) {
    $semesterText = "2025/2026 - Genap";
}

$semester = konversiSemesterKeAngka($semesterText);

$stmtSiswa = $conn->prepare("
    SELECT 
        s.id_siswa,
        s.nama AS nama_siswa,
        k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
    LIMIT 1
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

$kelasAktif = !empty($kelasFilter) ? $kelasFilter : ($siswa['nama_kelas'] ?? '-');

$stmtRingkasan = $conn->prepare("
    SELECT 
        AVG(n.nilai_angka) AS rata_rata,
        MAX(n.nilai_angka) AS nilai_tertinggi
    FROM nilai n
    WHERE n.id_siswa = ?
      AND n.semester = ?
");

if (!$stmtRingkasan) {
    echo json_encode([
        "success" => false,
        "message" => "Query ringkasan gagal: " . $conn->error
    ]);
    exit;
}

$stmtRingkasan->bind_param("ii", $id_siswa, $semester);
$stmtRingkasan->execute();
$resRingkasan = $stmtRingkasan->get_result();
$ringkasan = $resRingkasan->fetch_assoc();

$stmtTopMapel = $conn->prepare("
    SELECT 
        m.nama_mapel AS mapel,
        COALESCE(g.nama, '-') AS guru,
        n.nilai_angka
    FROM nilai n
    LEFT JOIN mapel m ON n.id_mapel = m.id_mapel
    LEFT JOIN guru g ON g.id_mapel = m.id_mapel
    WHERE n.id_siswa = ?
      AND n.semester = ?
    ORDER BY n.nilai_angka DESC
    LIMIT 1
");

if (!$stmtTopMapel) {
    echo json_encode([
        "success" => false,
        "message" => "Query mapel tertinggi gagal: " . $conn->error
    ]);
    exit;
}

$stmtTopMapel->bind_param("ii", $id_siswa, $semester);
$stmtTopMapel->execute();
$resTopMapel = $stmtTopMapel->get_result();
$topMapel = $resTopMapel->fetch_assoc();

$stmtPrev = $conn->prepare("
    SELECT AVG(nilai_angka) AS rata_prev
    FROM nilai
    WHERE id_siswa = ?
      AND semester <> ?
");

if (!$stmtPrev) {
    echo json_encode([
        "success" => false,
        "message" => "Query semester sebelumnya gagal: " . $conn->error
    ]);
    exit;
}

$stmtPrev->bind_param("ii", $id_siswa, $semester);
$stmtPrev->execute();
$resPrev = $stmtPrev->get_result();
$prev = $resPrev->fetch_assoc();

$avgNow = round((float)($ringkasan['rata_rata'] ?? 0), 1);
$avgPrev = round((float)($prev['rata_prev'] ?? 0), 1);
$selisih = round($avgNow - $avgPrev, 1);

$stmtTable = $conn->prepare("
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        AVG(n.nilai_angka) AS nilai_rata_rata,
        SUM(n.hadir) AS total_hadir,
        SUM(n.izin) AS total_izin,
        SUM(n.sakit) AS total_sakit,
        SUM(n.alfa) AS total_alfa
    FROM nilai n
    JOIN siswa s ON s.id_siswa = n.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE k.nama_kelas = ?
      AND n.semester = ?
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    ORDER BY nilai_rata_rata DESC
");

if (!$stmtTable) {
    echo json_encode([
        "success" => false,
        "message" => "Query tabel nilai gagal: " . $conn->error
    ]);
    exit;
}

$stmtTable->bind_param("si", $kelasAktif, $semester);
$stmtTable->execute();
$resTable = $stmtTable->get_result();

$tabel = [];
$rank = 1;

while ($row = $resTable->fetch_assoc()) {
    $nilaiRata = round((float)$row['nilai_rata_rata'], 1);
    $statusArah = $nilaiRata >= $avgPrev ? 'up' : 'down';

    $absensi = "H:" . (int)$row['total_hadir']
        . " I:" . (int)$row['total_izin']
        . " S:" . (int)$row['total_sakit']
        . " A:" . (int)$row['total_alfa'];

    $tabel[] = [
        "rank" => $rank++,
        "nama" => $row['nama'],
        "kelas" => $row['nama_kelas'],
        "nilai_rata_rata" => $nilaiRata,
        "status_arah" => $statusArah,
        "predikat" => hitungPredikat($nilaiRata),
        "absensi" => $absensi
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
        "rata_rata" => $avgNow,
        "selisih" => $selisih,
        "nilai_tertinggi" => round((float)($ringkasan['nilai_tertinggi'] ?? 0), 1),
        "mapel_tertinggi" => ($topMapel['mapel'] ?? '-') . (!empty($topMapel['guru']) ? " - " . $topMapel['guru'] : ""),
        "kelas" => $kelasAktif
    ],
    "tabel" => $tabel
]);

$conn->close();
?>