<?php
header("Content-Type: application/json; charset=utf-8");

require_once "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

// CEK APAKAH GURU SEBAGAI WALI KELAS
$sqlWali = "
    SELECT 
        k.id_kelas, 
        k.nama_kelas,
        k.tingkat
    FROM kelas k
    WHERE k.id_wali_kelas = ?
";
$stmtWali = $conn->prepare($sqlWali);
$stmtWali->bind_param("i", $id_guru);
$stmtWali->execute();
$resultWali = $stmtWali->get_result();

$waliKelas = [];
while ($row = $resultWali->fetch_assoc()) {
    $waliKelas[] = [
        "id_kelas" => $row["id_kelas"],
        "nama_kelas" => $row["nama_kelas"],
        "tingkat" => $row["tingkat"]
    ];
}

// CEK MAPEL YANG DIJAJAR GURU
$sqlMapel = "
    SELECT 
        m.id_mapel, 
        m.nama_mapel
    FROM mapel m
    WHERE m.id_mapel IN (
        SELECT g.id_mapel FROM guru g WHERE g.id_guru = ?
    )
";
$stmtMapel = $conn->prepare($sqlMapel);
$stmtMapel->bind_param("i", $id_guru);
$stmtMapel->execute();
$resultMapel = $stmtMapel->get_result();

$mapelDiajar = [];
while ($row = $resultMapel->fetch_assoc()) {
    $mapelDiajar[] = [
        "id_mapel" => $row["id_mapel"],
        "nama_mapel" => $row["nama_mapel"]
    ];
}

// AMBIL JUGA KELAS YANG ADA MAPELNYA (buat dropdown guru mapel)
$sqlKelasMapel = "
    SELECT DISTINCT
        k.id_kelas,
        k.nama_kelas,
        k.tingkat
    FROM kelas k
    INNER JOIN jadwal j ON j.id_kelas = k.id_kelas
    WHERE j.id_guru = ?
    AND j.id_tahun_ajaran = (
        SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1
    )
    ORDER BY k.nama_kelas ASC
";
$stmtKelasMapel = $conn->prepare($sqlKelasMapel);
$stmtKelasMapel->bind_param("i", $id_guru);
$stmtKelasMapel->execute();
$resultKelasMapel = $stmtKelasMapel->get_result();

$kelasMapel = [];
while ($row = $resultKelasMapel->fetch_assoc()) {
    $kelasMapel[] = [
        "id_kelas" => $row["id_kelas"],
        "nama_kelas" => $row["nama_kelas"],
        "tingkat" => $row["tingkat"]
    ];
}

kirim_json("success", "Data peran guru berhasil diambil.", [
    "is_wali" => count($waliKelas) > 0,
    "wali_kelas" => $waliKelas,
    "is_guru_mapel" => count($mapelDiajar) > 0,
    "mapel_diajar" => $mapelDiajar,
    "kelas_mapel" => $kelasMapel
]);