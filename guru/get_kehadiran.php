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
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

/* Ambil kelas yang benar-benar diajar guru login */
$getKelas = $conn->prepare("
    SELECT DISTINCT k.nama_kelas
    FROM jadwal j
    INNER JOIN kelas k ON j.id_kelas = k.id_kelas
    WHERE j.id_guru = ?
    ORDER BY k.nama_kelas ASC
");

if (!$getKelas) {
    kirim_json("error", "Query kelas gagal: " . $conn->error);
}

$getKelas->bind_param("i", $id_guru);
$getKelas->execute();
$resultKelas = $getKelas->get_result();

$kelasOptions = [];

while ($kelas = $resultKelas->fetch_assoc()) {
    $kelasOptions[] = $kelas["nama_kelas"];
}

$getKelas->close();

/* Ambil mapel yang benar-benar diajar guru login */
$getMapel = $conn->prepare("
    SELECT DISTINCT m.nama_mapel
    FROM jadwal j
    INNER JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_guru = ?
    ORDER BY m.id_mapel ASC
");

if (!$getMapel) {
    kirim_json("error", "Query mapel gagal: " . $conn->error);
}

$getMapel->bind_param("i", $id_guru);
$getMapel->execute();
$resultMapel = $getMapel->get_result();

$mapelOptions = [];

while ($mapel = $resultMapel->fetch_assoc()) {
    $mapelOptions[] = $mapel["nama_mapel"];
}

$getMapel->close();

/* Ambil data kehadiran sesuai jadwal guru login */
$stmt = $conn->prepare("
    SELECT DISTINCT
        s.nama AS nama_siswa,
        k.nama_kelas,
        m.nama_mapel,
        n.semester,
        n.hadir,
        n.izin,
        n.sakit,
        n.alfa
    FROM jadwal j
    INNER JOIN kelas k ON j.id_kelas = k.id_kelas
    INNER JOIN mapel m ON j.id_mapel = m.id_mapel
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    INNER JOIN nilai n ON n.id_siswa = s.id_siswa
                 AND n.id_mapel = j.id_mapel
    WHERE j.id_guru = ?
    ORDER BY k.nama_kelas ASC, m.nama_mapel ASC, s.nama ASC, n.semester ASC
");

if (!$stmt) {
    kirim_json("error", "Query kehadiran gagal: " . $conn->error);
}

$stmt->bind_param("i", $id_guru);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $semesterAngka = (int) $row["semester"];

    if ($semesterAngka === 1) {
        $semesterText = "Ganjil";
    } elseif ($semesterAngka === 2) {
        $semesterText = "Genap";
    } else {
        $semesterText = (string) $semesterAngka;
    }

    $data[] = [
        "nama" => $row["nama_siswa"] ?? "-",
        "kelas" => $row["nama_kelas"] ?? "-",
        "mapel" => $row["nama_mapel"] ?? "-",
        "semester" => $semesterText,
        "hadir" => (int) $row["hadir"],
        "izin" => (int) $row["izin"],
        "sakit" => (int) $row["sakit"],
        "alfa" => (int) $row["alfa"]
    ];
}

$stmt->close();

kirim_json("success", "Data kehadiran berhasil dimuat.", [
    "data" => $data,
    "kelas_options" => $kelasOptions,
    "mapel_options" => $mapelOptions
]);
?>
