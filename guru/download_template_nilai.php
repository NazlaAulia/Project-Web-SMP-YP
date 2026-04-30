<?php
require_once "koneksi.php";

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;
$mode = isset($_GET["mode"]) ? $_GET["mode"] : "mapel";
$id_kelas = isset($_GET["id_kelas"]) ? (int) $_GET["id_kelas"] : 0;

if ($role_id !== 2 || $id_guru <= 0) {
    die("Akses tidak valid.");
}

$filename = "template_import_nilai_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ["sep=,"]);

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

/* MODE WALI KELAS: semua siswa kelas wali x semua mapel */
if ($mode === "wali") {
    if ($id_kelas <= 0) {
        fclose($output);
        exit;
    }

    $cekWali = $conn->prepare("
        SELECT id_kelas, nama_kelas
        FROM kelas
        WHERE id_kelas = ? AND id_wali_kelas = ?
        LIMIT 1
    ");

    if (!$cekWali) {
        fclose($output);
        exit;
    }

    $cekWali->bind_param("ii", $id_kelas, $id_guru);
    $cekWali->execute();
    $resultWali = $cekWali->get_result();

    if ($resultWali->num_rows === 0) {
        fclose($output);
        exit;
    }

    $getSiswa = $conn->prepare("
        SELECT id_siswa, nama
        FROM siswa
        WHERE id_kelas = ?
        ORDER BY nama ASC
    ");

    $getSiswa->bind_param("i", $id_kelas);
    $getSiswa->execute();
    $resultSiswa = $getSiswa->get_result();

    $getMapel = $conn->prepare("
        SELECT id_mapel, nama_mapel
        FROM mapel
        ORDER BY id_mapel ASC
    ");

    $getMapel->execute();
    $resultMapel = $getMapel->get_result();

    $mapelList = [];

    while ($mapel = $resultMapel->fetch_assoc()) {
        $mapelList[] = $mapel;
    }

    while ($siswa = $resultSiswa->fetch_assoc()) {
        foreach ($mapelList as $mapel) {
            fputcsv($output, [
                $siswa["id_siswa"],
                $siswa["nama"],
                $mapel["id_mapel"],
                $mapel["nama_mapel"],
                1,
                "",
                "",
                "",
                "",
                ""
            ]);
        }
    }

    fclose($output);
    exit;
}

/* MODE GURU MAPEL: hanya mapel guru login */
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
    fclose($output);
    exit;
}

$guru = $resultGuru->fetch_assoc();

$id_mapel = (int) $guru["id_mapel"];
$nama_mapel = $guru["nama_mapel"] ?? "-";

$getSiswa = $conn->prepare("
    SELECT 
        id_siswa,
        nama
    FROM siswa
    ORDER BY nama ASC
");

$getSiswa->execute();
$resultSiswa = $getSiswa->get_result();

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