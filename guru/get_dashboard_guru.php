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

/* =========================
   MAPEL SESUAI GURU (FIX)
========================= */
$mapel = [];

$qMapel = $conn->prepare("
    SELECT DISTINCT m.id_mapel, m.nama_mapel
    FROM (
        SELECT id_mapel FROM jadwal WHERE id_guru = ?
        UNION
        SELECT j.id_mapel
        FROM request_jadwal r
        JOIN jadwal j ON r.id_jadwal = j.id_jadwal
        WHERE r.id_guru = ?
    ) AS data_mapel
    JOIN mapel m ON data_mapel.id_mapel = m.id_mapel
    ORDER BY m.id_mapel ASC
");

if (!$qMapel) {
    kirim_json("error", "Query mapel gagal: " . $conn->error);
}

$qMapel->bind_param("ii", $id_guru, $id_guru);
$qMapel->execute();
$resultMapel = $qMapel->get_result();

while ($row = $resultMapel->fetch_assoc()) {

    $deskripsi = $row["nama_mapel"];

    if ($row["nama_mapel"] === "BIN") $deskripsi = "Bahasa Indonesia";
    if ($row["nama_mapel"] === "B. JAWA") $deskripsi = "Bahasa Jawa";
    if ($row["nama_mapel"] === "PKN") $deskripsi = "Pendidikan Kewarganegaraan";
    if ($row["nama_mapel"] === "INFOR") $deskripsi = "Informatika";
    if ($row["nama_mapel"] === "MAT") $deskripsi = "Matematika";
    if ($row["nama_mapel"] === "BIG") $deskripsi = "Bahasa Inggris";
    if ($row["nama_mapel"] === "IPA") $deskripsi = "Ilmu Pengetahuan Alam";
    if ($row["nama_mapel"] === "IPS") $deskripsi = "Ilmu Pengetahuan Sosial";
    if ($row["nama_mapel"] === "BK") $deskripsi = "Bimbingan Konseling";
    if ($row["nama_mapel"] === "INFO/BK") $deskripsi = "Informatika / BK";
    if ($row["nama_mapel"] === "PAI/BHQ") $deskripsi = "PAI / BHQ";
    if ($row["nama_mapel"] === "PJOK") $deskripsi = "Pendidikan Jasmani";

    $mapel[] = [
        "id_mapel" => (int) $row["id_mapel"],
        "nama_mapel" => $row["nama_mapel"],
        "deskripsi" => $deskripsi
    ];
}

/* =========================
   KEHADIRAN (TIDAK DIUBAH)
========================= */
$qKehadiran = $conn->query("
    SELECT
        COALESCE(SUM(hadir), 0) AS total_hadir,
        COALESCE(SUM(izin), 0) AS total_izin,
        COALESCE(SUM(sakit), 0) AS total_sakit,
        COALESCE(SUM(alfa), 0) AS total_alfa
    FROM nilai
");

if (!$qKehadiran) {
    kirim_json("error", "Query kehadiran gagal: " . $conn->error);
}

$rekapKehadiran = $qKehadiran->fetch_assoc();

$totalHadir = (int) $rekapKehadiran["total_hadir"];
$totalIzin = (int) $rekapKehadiran["total_izin"];
$totalSakit = (int) $rekapKehadiran["total_sakit"];
$totalAlfa = (int) $rekapKehadiran["total_alfa"];

$totalSemua = $totalHadir + $totalIzin + $totalSakit + $totalAlfa;
$persenHadir = $totalSemua > 0 ? round(($totalHadir / $totalSemua) * 100) : 0;

/* =========================
   KELAS TERISI (TIDAK DIUBAH)
========================= */
$qKelasTerisi = $conn->query("
    SELECT COUNT(DISTINCT s.id_kelas) AS total_kelas_terisi
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    WHERE s.id_kelas IS NOT NULL
");

$kelasTerisi = $qKelasTerisi->fetch_assoc();

/* =========================
   TOTAL KELAS (TIDAK DIUBAH)
========================= */
$qTotalKelas = $conn->query("
    SELECT COUNT(*) AS total_kelas
    FROM kelas
");

$totalKelas = $qTotalKelas->fetch_assoc();

/* =========================
   PERINGKAT (TIDAK DIUBAH)
========================= */
$peringkat = [];

$qPeringkat = $conn->query("
    SELECT
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ROUND(AVG(n.nilai_angka), 2) AS rata_rata
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    GROUP BY s.id_siswa
    ORDER BY rata_rata DESC
    LIMIT 2
");

while ($row = $qPeringkat->fetch_assoc()) {
    $peringkat[] = $row;
}

/* =========================
   REQUEST JADWAL GURU LOGIN
========================= */
$requestJadwal = [];

$qRequest = $conn->prepare("
    SELECT
        r.id_request,
        r.id_guru,
        r.id_jadwal,
        r.alasan,
        r.status,
        r.tanggal_request,
        j.hari,
        jp.jam_mulai,
        jp.jam_selesai,
        k.nama_kelas,
        m.nama_mapel
    FROM request_jadwal r
    LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal
    LEFT JOIN jam_pelajaran jp ON j.id_jam = jp.id_jam
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE r.id_guru = ?
    ORDER BY r.id_request DESC
    LIMIT 5
");

if (!$qRequest) {
    kirim_json("error", "Query request jadwal gagal: " . $conn->error);
}

$qRequest->bind_param("i", $id_guru);
$qRequest->execute();
$resultRequest = $qRequest->get_result();

while ($row = $resultRequest->fetch_assoc()) {
    $requestJadwal[] = $row;
}
/* =========================
   OUTPUT (TIDAK DIUBAH)
========================= */
kirim_json("success", "Data dashboard berhasil dimuat.", [
    "mapel" => $mapel,
    "kehadiran" => [
        "persen_hadir" => $persenHadir,
        "kelas_terisi" => (int) $kelasTerisi["total_kelas_terisi"],
        "total_kelas" => (int) $totalKelas["total_kelas"],
        "total_hadir" => $totalHadir,
        "total_izin" => $totalIzin,
        "total_sakit" => $totalSakit,
        "total_alfa" => $totalAlfa
    ],
    "peringkat" => $peringkat,
    "request_jadwal" => $requestJadwal
]);
?>