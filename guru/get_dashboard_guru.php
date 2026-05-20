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

if (!isset($conn) || $conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
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

/* =========================
   MAPEL SESUAI GURU
========================= */
$mapel = [];

$qMapel = $conn->prepare("
    SELECT DISTINCT m.id_mapel, m.nama_mapel
    FROM (
        SELECT id_mapel
        FROM guru
        WHERE id_guru = ?
          AND id_mapel IS NOT NULL

        UNION

        SELECT id_mapel
        FROM jadwal
        WHERE id_guru = ?
          AND id_mapel IS NOT NULL
          AND id_tahun_ajaran = ?

        UNION

        SELECT j.id_mapel
        FROM request_jadwal r
        JOIN jadwal j ON r.id_jadwal = j.id_jadwal
        WHERE r.id_guru = ?
          AND j.id_mapel IS NOT NULL
          AND j.id_tahun_ajaran = ?
    ) AS data_mapel
    JOIN mapel m ON data_mapel.id_mapel = m.id_mapel
    ORDER BY m.id_mapel ASC
");

if (!$qMapel) {
    kirim_json("error", "Query mapel gagal: " . $conn->error);
}

$qMapel->bind_param("iiiii", $id_guru, $id_guru, $id_tahun_aktif, $id_guru, $id_tahun_aktif);
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
   KEHADIRAN (HANYA TAHUN AKTIF)
========================= */
$qKehadiran = $conn->prepare("
    SELECT
        COALESCE(SUM(hadir), 0) AS total_hadir,
        COALESCE(SUM(izin), 0) AS total_izin,
        COALESCE(SUM(sakit), 0) AS total_sakit,
        COALESCE(SUM(alfa), 0) AS total_alfa
    FROM nilai
    WHERE id_tahun_ajaran = ?
");

if (!$qKehadiran) {
    kirim_json("error", "Query kehadiran gagal: " . $conn->error);
}

$qKehadiran->bind_param("i", $id_tahun_aktif);
$qKehadiran->execute();
$rekapKehadiran = $qKehadiran->get_result()->fetch_assoc();

$totalHadir = (int) $rekapKehadiran["total_hadir"];
$totalIzin = (int) $rekapKehadiran["total_izin"];
$totalSakit = (int) $rekapKehadiran["total_sakit"];
$totalAlfa = (int) $rekapKehadiran["total_alfa"];

$totalSemua = $totalHadir + $totalIzin + $totalSakit + $totalAlfa;
$persenHadir = $totalSemua > 0 ? round(($totalHadir / $totalSemua) * 100) : 0;

/* =========================
   KELAS TERISI (HANYA TAHUN AKTIF)
========================= */
$qKelasTerisi = $conn->prepare("
    SELECT COUNT(DISTINCT s.id_kelas) AS total_kelas_terisi
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    WHERE n.id_tahun_ajaran = ?
      AND s.id_kelas IS NOT NULL
");

if (!$qKelasTerisi) {
    kirim_json("error", "Query kelas terisi gagal: " . $conn->error);
}

$qKelasTerisi->bind_param("i", $id_tahun_aktif);
$qKelasTerisi->execute();
$kelasTerisi = $qKelasTerisi->get_result()->fetch_assoc();

/* =========================
   TOTAL KELAS (HANYA TAHUN AKTIF)
========================= */
$qTotalKelas = $conn->prepare("
    SELECT COUNT(*) AS total_kelas
    FROM kelas
    WHERE id_tahun_ajaran = ?
");

if (!$qTotalKelas) {
    kirim_json("error", "Query total kelas gagal: " . $conn->error);
}

$qTotalKelas->bind_param("i", $id_tahun_aktif);
$qTotalKelas->execute();
$totalKelas = $qTotalKelas->get_result()->fetch_assoc();

/* =========================
   PERINGKAT 5 BESAR (HANYA TAHUN AKTIF)
========================= */
$peringkat = [];

$qPeringkat = $conn->prepare("
    SELECT
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ROUND(COALESCE(AVG(n.nilai_angka), 0), 2) AS rata_rata
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas AND k.id_tahun_ajaran = ?
    LEFT JOIN nilai n ON n.id_siswa = s.id_siswa AND n.id_tahun_ajaran = ?
    WHERE k.id_tahun_ajaran = ?
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    ORDER BY rata_rata DESC, s.nama ASC
    LIMIT 2
");

if (!$qPeringkat) {
    kirim_json("error", "Query peringkat gagal: " . $conn->error);
}

$qPeringkat->bind_param("iii", $id_tahun_aktif, $id_tahun_aktif, $id_tahun_aktif);
$qPeringkat->execute();
$resultPeringkat = $qPeringkat->get_result();

while ($row = $resultPeringkat->fetch_assoc()) {
    $namaSiswa = $row["nama"] ?? "-";

    $peringkat[] = [
        "id_siswa" => $row["id_siswa"],
        "nama" => $namaSiswa,
        "inisial" => strtoupper(substr($namaSiswa, 0, 1)),
        "kelas" => $row["nama_kelas"] ?? "-",
        "rata_rata" => (float) $row["rata_rata"]
    ];
}

/* =========================
   REQUEST JADWAL (HANYA TAHUN AKTIF)
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
        k.nama_kelas,
        m.nama_mapel
    FROM request_jadwal r
    LEFT JOIN jadwal j ON r.id_jadwal = j.id_jadwal AND j.id_tahun_ajaran = ?
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE r.id_guru = ?
    ORDER BY r.id_request DESC
    LIMIT 5
");

if ($qRequest) {
    $qRequest->bind_param("ii", $id_tahun_aktif, $id_guru);
    $qRequest->execute();
    $resultRequest = $qRequest->get_result();

    while ($row = $resultRequest->fetch_assoc()) {
        $requestJadwal[] = $row;
    }
}

/* =========================
   OUTPUT
========================= */
kirim_json("success", "Data dashboard berhasil dimuat.", [
    "mapel" => $mapel,
    "kehadiran" => [
        "persen_hadir" => $persenHadir,
        "kelas_terisi" => (int) ($kelasTerisi["total_kelas_terisi"] ?? 0),
        "total_kelas" => (int) ($totalKelas["total_kelas"] ?? 0),
        "total_hadir" => $totalHadir,
        "total_izin" => $totalIzin,
        "total_sakit" => $totalSakit,
        "total_alfa" => $totalAlfa
    ],
    "peringkat" => $peringkat,
    "request_jadwal" => $requestJadwal
]);
?>