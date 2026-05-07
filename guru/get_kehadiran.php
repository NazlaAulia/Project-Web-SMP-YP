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

/* AMBIL MAPEL SESUAI GURU LOGIN */
$getMapel = $conn->prepare("
    SELECT DISTINCT
        m.id_mapel,
        m.nama_mapel
    FROM guru g
    JOIN mapel m ON g.id_mapel = m.id_mapel
    WHERE g.id_guru = ?
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

if (empty($mapelOptions)) {
    kirim_json("success", "Guru ini belum punya mapel.", [
        "data" => [],
        "kelas_options" => [],
        "mapel_options" => []
    ]);
}

/* AMBIL DATA KEHADIRAN SESUAI GURU LOGIN, MAPEL GURU, DAN KELAS YANG DIAJAR */
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
    JOIN guru g ON j.id_guru = g.id_guru
    JOIN mapel m ON j.id_mapel = m.id_mapel
    JOIN siswa s ON j.id_kelas = s.id_kelas
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN nilai n ON n.id_siswa = s.id_siswa
                AND n.id_mapel = j.id_mapel
    WHERE j.id_guru = ?
      AND g.id_guru = ?
      AND g.id_mapel = j.id_mapel
    ORDER BY k.nama_kelas ASC, s.nama ASC, n.semester ASC
");

if (!$stmt) {
    kirim_json("error", "Query kehadiran gagal: " . $conn->error);
}

$stmt->bind_param("ii", $id_guru, $id_guru);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$kelasOptions = [];

while ($row = $result->fetch_assoc()) {
    $semesterAngka = (int) $row["semester"];

    if ($semesterAngka === 1) {
        $semesterText = "Ganjil";
    } elseif ($semesterAngka === 2) {
        $semesterText = "Genap";
    } else {
        $semesterText = (string) $semesterAngka;
    }

    $kelasOptions[] = $row["nama_kelas"];

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

$kelasOptions = array_values(array_unique($kelasOptions));

kirim_json("success", "Data kehadiran berhasil dimuat.", [
    "data" => $data,
    "kelas_options" => $kelasOptions,
    "mapel_options" => $mapelOptions
]);
?>