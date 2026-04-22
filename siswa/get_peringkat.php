<?php
session_start();
header('Content-Type: application/json');

require_once '../koneksi.php';

function konversiSemesterKeAngka($semesterText) {
    $semesterText = trim((string)$semesterText);

    if ($semesterText === '') return 2;
    if (stripos($semesterText, 'genap') !== false) return 2;
    if (stripos($semesterText, 'ganjil') !== false) return 1;
    if (is_numeric($semesterText)) return (int)$semesterText;

    return 2;
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

/* ambil data siswa login */
$sqlSiswa = "
    SELECT 
        s.id_siswa,
        s.nama,
        s.id_kelas,
        k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    WHERE s.id_siswa = ?
    LIMIT 1
";

$stmt = $conn->prepare($sqlSiswa);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query siswa gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);

if (!$stmt->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query siswa gagal: " . $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

$kelasAktif = !empty($kelasFilter) ? $kelasFilter : $row['nama_kelas'];

/* data ranking siswa login */
$sqlRankSiswa = "
    SELECT 
        p.rank,
        p.nilai_rata_rata,
        p.posisi_sebelumnya,
        p.status
    FROM peringkat p
    WHERE p.id_siswa = ?
    LIMIT 1
";

$stmtRankSiswa = $conn->prepare($sqlRankSiswa);

if (!$stmtRankSiswa) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query rank siswa gagal: " . $conn->error
    ]);
    exit;
}

$stmtRankSiswa->bind_param("i", $id_siswa);

if (!$stmtRankSiswa->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query rank siswa gagal: " . $stmtRankSiswa->error
    ]);
    exit;
}

$resultRankSiswa = $stmtRankSiswa->get_result();
$rankSiswa = $resultRankSiswa->fetch_assoc();

/* ranking satu kelas */
$sqlRank = "
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        p.rank,
        p.nilai_rata_rata,
        p.status
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    INNER JOIN peringkat p ON p.id_siswa = s.id_siswa
    WHERE k.nama_kelas = ?
    ORDER BY p.rank ASC
";

$stmtRank = $conn->prepare($sqlRank);

if (!$stmtRank) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare query ranking gagal: " . $conn->error
    ]);
    exit;
}

$stmtRank->bind_param("s", $kelasAktif);

if (!$stmtRank->execute()) {
    echo json_encode([
        "success" => false,
        "message" => "Execute query ranking gagal: " . $stmtRank->error
    ]);
    exit;
}

$resultRank = $stmtRank->get_result();
$ranking = [];

while ($r = $resultRank->fetch_assoc()) {
    $ranking[] = [
        "rank" => (int) ($r["rank"] ?? 0),
        "nama" => $r["nama"] ?? "",
        "kelas" => $r["nama_kelas"] ?? "",
        "nilai" => (float) ($r["nilai_rata_rata"] ?? 0),
        "status" => $r["status"] ?? "↔"
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => (int) $row["id_siswa"],
        "nama" => $row["nama"] ?? "",
        "kelas" => $row["nama_kelas"] ?? "",
        "rank" => (int) ($rankSiswa["rank"] ?? 0),
        "nilai" => (float) ($rankSiswa["nilai_rata_rata"] ?? 0),
        "posisi_sebelumnya" => (int) ($rankSiswa["posisi_sebelumnya"] ?? 0),
        "status" => $rankSiswa["status"] ?? "↔"
    ],
    "ranking" => $ranking
]);

$stmt->close();
$stmtRankSiswa->close();
$stmtRank->close();
$conn->close();
?>