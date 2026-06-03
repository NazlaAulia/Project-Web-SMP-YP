<?php
header('Content-Type: application/json');
error_reporting(0);
session_start();

// Sesuaikan koneksi database dengan file Anda
require_once 'koneksi.php'; // atau 'config/db.php' sesuai struktur

$id_siswa = isset($_GET['id_siswa']) ? intval($_GET['id_siswa']) : 0;
$semester_str = isset($_GET['semester']) ? $_GET['semester'] : '';

if (!$id_siswa) {
    echo json_encode(['success' => false, 'message' => 'ID siswa tidak valid']);
    exit;
}

// Parse semester dan tahun ajaran dari string (contoh: "2025/2026 - Ganjil")
preg_match('/(\d{4}\/\d{4})/', $semester_str, $match_tahun);
$tahun_ajaran = $match_tahun[1] ?? '2025/2026';
$semester = (stripos($semester_str, 'ganjil') !== false) ? 1 : 2;

// Cari id_tahun_ajaran
$query_ta = "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE tahun_ajaran = ?";
$stmt_ta = $conn->prepare($query_ta);
$stmt_ta->bind_param('s', $tahun_ajaran);
$stmt_ta->execute();
$result_ta = $stmt_ta->get_result();
$row_ta = $result_ta->fetch_assoc();
$id_tahun_ajaran = $row_ta['id_tahun_ajaran'] ?? 0;

if (!$id_tahun_ajaran) {
    echo json_encode(['success' => false, 'message' => 'Tahun ajaran tidak ditemukan']);
    exit;
}

// Ambil nilai per mapel
$query = "
    SELECT 
        m.nama_mapel,
        n.nilai_angka
    FROM nilai n
    JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE n.id_siswa = ?
        AND n.id_tahun_ajaran = ?
        AND n.semester = ?
    ORDER BY m.id_mapel
";
$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $id_siswa, $id_tahun_ajaran, $semester);
$stmt->execute();
$result = $stmt->get_result();

$nilai_mapel = [];
while ($row = $result->fetch_assoc()) {
    $nilai_mapel[] = [
        'mapel' => $row['nama_mapel'],
        'nilai' => (int)$row['nilai_angka']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $nilai_mapel,
    'tahun_ajaran' => $tahun_ajaran,
    'semester' => $semester
]);
?>