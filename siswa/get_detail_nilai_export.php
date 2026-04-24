<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

require_once 'koneksi.php';

// Biar aman kalau koneksi kamu namanya $conn atau $koneksi
$db = null;

if (isset($conn)) {
    $db = $conn;
} elseif (isset($koneksi)) {
    $db = $koneksi;
}

if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database tidak ditemukan. Cek nama variabel di koneksi.php."
    ]);
    exit;
}

$idSiswa = isset($_GET['id_siswa']) ? intval($_GET['id_siswa']) : 0;
$semesterText = isset($_GET['semester']) ? $_GET['semester'] : '';

if ($idSiswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid."
    ]);
    exit;
}

// Ganjil = 1, Genap = 2
$semesterAngka = 1;
if (stripos($semesterText, 'genap') !== false) {
    $semesterAngka = 2;
}

// ==========================
// AMBIL DATA SISWA
// ==========================
$sqlSiswa = "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nisn,
        s.nama,
        k.nama_kelas AS kelas,
        ta.tahun_ajaran
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id_tahun_ajaran
    WHERE s.id_siswa = ?
    LIMIT 1
";

$stmtSiswa = mysqli_prepare($db, $sqlSiswa);

if (!$stmtSiswa) {
    echo json_encode([
        "success" => false,
        "message" => "Query siswa gagal disiapkan."
    ]);
    exit;
}

mysqli_stmt_bind_param($stmtSiswa, "i", $idSiswa);
mysqli_stmt_execute($stmtSiswa);
$resultSiswa = mysqli_stmt_get_result($stmtSiswa);
$siswa = mysqli_fetch_assoc($resultSiswa);

if (!$siswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

// ==========================
// AMBIL DETAIL NILAI PER MAPEL
// ==========================
$sqlNilai = "
    SELECT 
        m.nama_mapel,
        n.nilai_angka,
        n.hadir,
        n.izin,
        n.sakit,
        n.alfa
    FROM nilai n
    INNER JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE n.id_siswa = ? AND n.semester = ?
    ORDER BY m.id_mapel ASC
";

$stmtNilai = mysqli_prepare($db, $sqlNilai);

if (!$stmtNilai) {
    echo json_encode([
        "success" => false,
        "message" => "Query nilai gagal disiapkan."
    ]);
    exit;
}

mysqli_stmt_bind_param($stmtNilai, "ii", $idSiswa, $semesterAngka);
mysqli_stmt_execute($stmtNilai);
$resultNilai = mysqli_stmt_get_result($stmtNilai);

$detailNilai = [];

while ($row = mysqli_fetch_assoc($resultNilai)) {
    $nilai = intval($row['nilai_angka']);

    $detailNilai[] = [
        "mapel" => $row['nama_mapel'],
        "nilai" => $nilai,
        "keterangan" => $nilai >= 75 ? "Tuntas" : "Belum Tuntas",
        "hadir" => $row['hadir'],
        "izin" => $row['izin'],
        "sakit" => $row['sakit'],
        "alfa" => $row['alfa']
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => $siswa['id_siswa'],
        "nama" => $siswa['nama'],
        "nis" => $siswa['nis'],
        "nisn" => $siswa['nisn'],
        "kelas" => $siswa['kelas'],
        "tahun_ajaran" => $siswa['tahun_ajaran']
    ],
    "detail_nilai" => $detailNilai
]);
exit;
?>