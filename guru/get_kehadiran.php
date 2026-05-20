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

// ========== AMBIL TAHUN AJARAN AKTIF ==========
$queryTahun = $conn->query("SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
$tahunAktif = $queryTahun->fetch_assoc();
$id_tahun_aktif = $tahunAktif ? $tahunAktif['id_tahun_ajaran'] : 0;

if ($id_tahun_aktif == 0) {
    kirim_json("error", "Tidak ada tahun ajaran aktif.");
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
$id_mapel_guru = 0;

while ($mapel = $resultMapel->fetch_assoc()) {
    $mapelOptions[] = $mapel["nama_mapel"];
    $id_mapel_guru = $mapel["id_mapel"];
}

if (empty($mapelOptions)) {
    kirim_json("success", "Guru ini belum punya mapel.", [
        "data" => [],
        "kelas_options" => [],
        "mapel_options" => []
    ]);
}

/* AMBIL DATA KEHADIRAN - PAKSA MUNCUL 2 SEMESTER TANPA DUPLIKASI */
$stmt = $conn->prepare("
    SELECT
        s.id_siswa,
        s.nama AS nama_siswa,
        k.nama_kelas,
        ? AS nama_mapel,
        sem.semester,
        COALESCE(MAX(n.hadir), 0) AS hadir,
        COALESCE(MAX(n.izin), 0) AS izin,
        COALESCE(MAX(n.sakit), 0) AS sakit,
        COALESCE(MAX(n.alfa), 0) AS alfa
    FROM siswa s
    JOIN kelas k ON s.id_kelas = k.id_kelas
    JOIN jadwal j ON j.id_kelas = k.id_kelas
    CROSS JOIN (SELECT 1 AS semester UNION SELECT 2) AS sem
    LEFT JOIN nilai n ON n.id_siswa = s.id_siswa 
        AND n.id_mapel = j.id_mapel 
        AND n.id_tahun_ajaran = ?
        AND n.semester = sem.semester
    WHERE j.id_guru = ?
      AND j.id_mapel = ?
    GROUP BY s.id_siswa, s.nama, k.nama_kelas, sem.semester
    ORDER BY k.nama_kelas ASC, s.nama ASC, sem.semester ASC
");

if (!$stmt) {
    kirim_json("error", "Query kehadiran gagal: " . $conn->error);
}

$stmt->bind_param("siii", $mapelOptions[0], $id_tahun_aktif, $id_guru, $id_mapel_guru);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$kelasOptions = [];

while ($row = $result->fetch_assoc()) {
    $semesterAngka = (int) $row["semester"];
    $semesterText = ($semesterAngka === 1) ? "Ganjil" : "Genap";

    $kelasOptions[] = $row["nama_kelas"];

    $data[] = [
        "id_siswa" => (int) $row["id_siswa"],
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