<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "smartschool");
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal"
    ]);
    exit;
}

$id_siswa = $_GET['id_siswa'] ?? '';
$kelas = $_GET['kelas'] ?? '';
$semester = $_GET['semester'] ?? '';

if (empty($id_siswa)) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan"
    ]);
    exit;
}

$stmtSiswa = $conn->prepare("SELECT id_siswa, nama_siswa, kelas FROM siswa WHERE id_siswa = ?");
$stmtSiswa->bind_param("s", $id_siswa);
$stmtSiswa->execute();
$resSiswa = $stmtSiswa->get_result();
$siswa = $resSiswa->fetch_assoc();

if (!$siswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

if (empty($kelas)) {
    $kelas = $siswa['kelas'];
}

if (empty($semester)) {
    $semester = "2025/2026 - Genap";
}

$stmtRingkasan = $conn->prepare("
    SELECT 
        AVG(nilai_angka) AS rata_rata,
        MAX(nilai_angka) AS nilai_tertinggi
    FROM nilai
    WHERE id_siswa = ? AND kelas = ? AND semester = ?
");
$stmtRingkasan->bind_param("sss", $id_siswa, $kelas, $semester);
$stmtRingkasan->execute();
$resRingkasan = $stmtRingkasan->get_result();
$ringkasan = $resRingkasan->fetch_assoc();

$stmtTopMapel = $conn->prepare("
    SELECT mapel, guru, nilai_angka
    FROM nilai
    WHERE id_siswa = ? AND kelas = ? AND semester = ?
    ORDER BY nilai_angka DESC
    LIMIT 1
");
$stmtTopMapel->bind_param("sss", $id_siswa, $kelas, $semester);
$stmtTopMapel->execute();
$resTopMapel = $stmtTopMapel->get_result();
$topMapel = $resTopMapel->fetch_assoc();

$stmtPrev = $conn->prepare("
    SELECT AVG(nilai_angka) AS rata_prev
    FROM nilai
    WHERE id_siswa = ? AND kelas = ? AND semester <> ?
");
$stmtPrev->bind_param("sss", $id_siswa, $kelas, $semester);
$stmtPrev->execute();
$resPrev = $stmtPrev->get_result();
$prev = $resPrev->fetch_assoc();

$avgNow = floatval($ringkasan['rata_rata'] ?? 0);
$avgPrev = floatval($prev['rata_prev'] ?? 0);
$selisih = round($avgNow - $avgPrev, 1);

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
    WHERE n.kelas = ? AND n.semester = ?
    GROUP BY n.id_siswa, s.nama_siswa, n.kelas
    ORDER BY nilai_rata_rata DESC
");
$stmtTable->bind_param("ss", $kelas, $semester);
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
        "kelas" => $siswa['kelas']
    ],
    "ringkasan" => [
        "rata_rata" => round($avgNow, 1),
        "selisih" => $selisih,
        "nilai_tertinggi" => round(floatval($ringkasan['nilai_tertinggi'] ?? 0), 1),
        "mapel_tertinggi" => ($topMapel['mapel'] ?? '-') . (isset($topMapel['guru']) ? " - " . $topMapel['guru'] : ""),
        "kelas" => $kelas
    ],
    "tabel" => $tabel
]);

$conn->close();
?>