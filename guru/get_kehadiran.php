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

/* Ambil kelas sesuai jadwal guru login */
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

/* Ambil mapel sesuai jadwal guru login */
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

/* Ambil data kehadiran hanya sesuai kelas dan mapel yang diajar guru login */
$stmt = $conn->prepare("
    SELECT
        s.nama AS nama_siswa,
        k.nama_kelas,
        m.nama_mapel,
        n.semester,
        n.hadir,
        n.izin,
        n.sakit,
        n.alfa
    FROM nilai n
    INNER JOIN siswa s ON n.id_siswa = s.id_siswa
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    INNER JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE EXISTS (
        SELECT 1
        FROM jadwal j
        WHERE j.id_guru = ?
          AND j.id_mapel = n.id_mapel
          AND j.id_kelas = s.id_kelas
    )
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

kirim_json("success", "Data kehadiran berhasil dimuat.", [
    "data" => $data,
    "kelas_options" => $kelasOptions,
    "mapel_options" => $mapelOptions
]);
?>
