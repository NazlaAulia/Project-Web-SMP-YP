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
$semester = $_GET["semester"] ?? "Semua";
$angkatan = isset($_GET["angkatan"]) ? (int) $_GET["angkatan"] : 0;

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

// Filter semester
$whereSemester = "";
$semesterValue = 0;

if ($semester === "Ganjil") {
    $whereSemester = "AND n.semester = ?";
    $semesterValue = 1;
} elseif ($semester === "Genap") {
    $whereSemester = "AND n.semester = ?";
    $semesterValue = 2;
}

// Filter angkatan (kelas 7,8,9)
$whereAngkatan = "";
if ($angkatan > 0) {
    $whereAngkatan = "AND k.tingkat = ?";
}

$sql = "
    SELECT
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ROUND(COALESCE(AVG(n.nilai_angka), 0), 2) AS rata_rata
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas AND k.id_tahun_ajaran = ?
    LEFT JOIN nilai n ON n.id_siswa = s.id_siswa 
        AND n.id_tahun_ajaran = ?
        $whereSemester
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    HAVING (k.id_tahun_ajaran = ? OR k.id_tahun_ajaran IS NULL)
      $whereAngkatan
    ORDER BY rata_rata DESC, s.nama ASC
    LIMIT 10
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    kirim_json("error", "Query peringkat gagal: " . $conn->error);
}

// Bind parameter sesuai filter
if ($whereSemester !== "" && $whereAngkatan !== "") {
    $stmt->bind_param("iiiiii", $id_tahun_aktif, $id_tahun_aktif, $semesterValue, $id_tahun_aktif, $angkatan);
} elseif ($whereSemester !== "") {
    $stmt->bind_param("iiiii", $id_tahun_aktif, $id_tahun_aktif, $semesterValue, $id_tahun_aktif);
} elseif ($whereAngkatan !== "") {
    $stmt->bind_param("iiii", $id_tahun_aktif, $id_tahun_aktif, $id_tahun_aktif, $angkatan);
} else {
    $stmt->bind_param("iii", $id_tahun_aktif, $id_tahun_aktif, $id_tahun_aktif);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$rank = 1;

$jumlahUnggul = 0;
$jumlahBaik = 0;
$jumlahPerhatian = 0;

while ($row = $result->fetch_assoc()) {
    $nilai = (float) $row["rata_rata"];

    if ($nilai >= 90) {
        $status = "Excellent";
        $jumlahUnggul++;
    } elseif ($nilai >= 75) {
        $status = "Good";
        $jumlahBaik++;
    } else {
        $status = "Need Attention";
        $jumlahPerhatian++;
    }

    $data[] = [
        "rank" => $rank,
        "nama" => $row["nama"] ?? "-",
        "kelas" => $row["nama_kelas"] ?? "-",
        "nilai" => $nilai,
        "status" => $status
    ];

    $rank++;
}

// Ambil daftar angkatan untuk dropdown
$angkatanOptions = [];
$queryAngkatan = $conn->query("SELECT DISTINCT tingkat FROM kelas ORDER BY tingkat ASC");
while ($row = $queryAngkatan->fetch_assoc()) {
    $angkatanOptions[] = $row["tingkat"];
}

kirim_json("success", "Data peringkat berhasil dimuat.", [
    "data" => $data,
    "angkatan_options" => $angkatanOptions,
    "summary" => [
        "unggul" => $jumlahUnggul,
        "baik" => $jumlahBaik,
        "perhatian" => $jumlahPerhatian
    ]
]);
?>