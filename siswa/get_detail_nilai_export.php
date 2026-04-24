<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

$idSiswa = isset($_GET['id_siswa']) ? (int) $_GET['id_siswa'] : 0;
$semesterText = isset($_GET['semester']) ? trim($_GET['semester']) : '';

if ($idSiswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid."
    ]);
    exit;
}

$semesterAngka = (stripos($semesterText, 'genap') !== false) ? 2 : 1;

$stmtSiswa = $conn->prepare("
    SELECT 
        s.id_siswa,
        s.nama,
        s.nis,
        s.nisn,
        k.nama_kelas AS kelas,
        ta.tahun_ajaran
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id_tahun_ajaran
    WHERE s.id_siswa = ?
    LIMIT 1
");
$stmtSiswa->bind_param("i", $idSiswa);
$stmtSiswa->execute();
$resultSiswa = $stmtSiswa->get_result();
$siswa = $resultSiswa->fetch_assoc();

if (!$siswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

$stmtNilai = $conn->prepare("
    SELECT 
        m.nama_mapel,
        n.nilai_angka,
        CASE 
            WHEN n.nilai_angka >= 75 THEN 'Tuntas'
            ELSE 'Belum Tuntas'
        END AS keterangan
    FROM nilai n
    INNER JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE n.id_siswa = ? AND n.semester = ?
    ORDER BY m.id_mapel ASC
");
$stmtNilai->bind_param("ii", $idSiswa, $semesterAngka);
$stmtNilai->execute();
$resultNilai = $stmtNilai->get_result();

$detailNilai = [];
while ($row = $resultNilai->fetch_assoc()) {
    $detailNilai[] = [
        "mapel" => $row["nama_mapel"],
        "nilai" => (int) $row["nilai_angka"],
        "keterangan" => $row["keterangan"]
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "id_siswa" => $siswa["id_siswa"],
        "nama" => $siswa["nama"],
        "nis" => $siswa["nis"],
        "nisn" => $siswa["nisn"],
        "kelas" => $siswa["kelas"],
        "tahun_ajaran" => $siswa["tahun_ajaran"]
    ],
    "detail_nilai" => $detailNilai
]);