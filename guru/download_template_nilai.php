<?php
require_once "koneksi.php";

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;

if ($role_id !== 2 || $id_guru <= 0) {
    die("Akses tidak valid.");
}

/* Ambil mapel guru yang login */
$getGuru = $conn->prepare("
    SELECT 
        g.id_mapel,
        m.nama_mapel
    FROM guru g
    LEFT JOIN mapel m ON g.id_mapel = m.id_mapel
    WHERE g.id_guru = ?
    LIMIT 1
");

$getGuru->bind_param("i", $id_guru);
$getGuru->execute();
$resultGuru = $getGuru->get_result();

if ($resultGuru->num_rows === 0) {
    die("Data guru tidak ditemukan.");
}

$guru = $resultGuru->fetch_assoc();

$id_mapel = (int) $guru["id_mapel"];
$nama_mapel = $guru["nama_mapel"] ?? "-";

/* Ambil semua siswa */
$getSiswa = $conn->prepare("
    SELECT 
        id_siswa,
        nama
    FROM siswa
    ORDER BY nama ASC
");

$getSiswa->execute();
$resultSiswa = $getSiswa->get_result();

$filename = "template_import_nilai_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

/* Biar Excel langsung memisahkan kolom pakai koma */
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ["sep=,"]);

/* Header template */
fputcsv($output, [
    "id_siswa",
    "nama_siswa",
    "id_mapel",
    "nama_mapel",
    "semester",
    "nilai_angka",
    "hadir",
    "izin",
    "sakit",
    "alfa"
]);

while ($siswa = $resultSiswa->fetch_assoc()) {
    fputcsv($output, [
        $siswa["id_siswa"],
        $siswa["nama"],
        $id_mapel,
        $nama_mapel,
        1,
        "",
        "",
        "",
        "",
        ""
    ]);
}

fclose($output);
exit;
?>