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

/* Ambil semua mapel */
$mapel = [];
$qMapel = $conn->query("
    SELECT id_mapel, nama_mapel
    FROM mapel
    ORDER BY id_mapel ASC
");

if (!$qMapel) {
    kirim_json("error", "Query mapel gagal: " . $conn->error);
}

while ($row = $qMapel->fetch_assoc()) {
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

/* Rekap kehadiran dari tabel nilai */
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

/* Total kelas yang sudah punya data nilai */
$qKelasTerisi = $conn->query("
    SELECT COUNT(DISTINCT s.id_kelas) AS total_kelas_terisi
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    WHERE s.id_kelas IS NOT NULL
");

if (!$qKelasTerisi) {
    kirim_json("error", "Query kelas terisi gagal: " . $conn->error);
}

$kelasTerisi = $qKelasTerisi->fetch_assoc();

/* Total semua kelas */
$qTotalKelas = $conn->query("
    SELECT COUNT(*) AS total_kelas
    FROM kelas
");

if (!$qTotalKelas) {
    kirim_json("error", "Query total kelas gagal: " . $conn->error);
}

$totalKelas = $qTotalKelas->fetch_assoc();

/* Peringkat teratas berdasarkan rata-rata nilai */
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
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    ORDER BY rata_rata DESC
    LIMIT 2
");

if (!$qPeringkat) {
    kirim_json("error", "Query peringkat gagal: " . $conn->error);
}

while ($row = $qPeringkat->fetch_assoc()) {
    $nama = $row["nama"] ?? "-";
    $inisial = "";

    $pecahNama = explode(" ", trim($nama));
    foreach ($pecahNama as $kata) {
        if ($kata !== "") {
            $inisial .= strtoupper(substr($kata, 0, 1));
        }

        if (strlen($inisial) >= 2) {
            break;
        }
    }

    $peringkat[] = [
        "nama" => $nama,
        "kelas" => $row["nama_kelas"] ?? "-",
        "rata_rata" => $row["rata_rata"] ?? 0,
        "inisial" => $inisial ?: "S"
    ];
}

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
    "peringkat" => $peringkat
]);
?>