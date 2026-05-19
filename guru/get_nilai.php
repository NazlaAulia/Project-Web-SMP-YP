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

$mode = $_GET["mode"] ?? "mapel";
$id_kelas = isset($_GET["id_kelas"]) ? (int) $_GET["id_kelas"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

/* AMBIL DATA GURU */
$getGuru = $conn->prepare("
    SELECT 
        g.id_guru,
        g.nama,
        g.id_mapel,
        m.nama_mapel
    FROM guru g
    LEFT JOIN mapel m ON g.id_mapel = m.id_mapel
    WHERE g.id_guru = ?
    LIMIT 1
");

if (!$getGuru) {
    kirim_json("error", "Query guru gagal: " . $conn->error);
}

$getGuru->bind_param("i", $id_guru);
$getGuru->execute();
$resultGuru = $getGuru->get_result();

if ($resultGuru->num_rows === 0) {
    kirim_json("error", "Data guru tidak ditemukan.");
}

$guru = $resultGuru->fetch_assoc();
$id_mapel_guru = (int) $guru["id_mapel"];
$nama_mapel_guru = $guru["nama_mapel"] ?? "-";

/* AMBIL KELAS WALI */
$getWali = $conn->prepare("
    SELECT 
        id_kelas,
        nama_kelas,
        tingkat
    FROM kelas
    WHERE id_wali_kelas = ?
    ORDER BY tingkat ASC, nama_kelas ASC
");

if (!$getWali) {
    kirim_json("error", "Query wali kelas gagal: " . $conn->error);
}

$getWali->bind_param("i", $id_guru);
$getWali->execute();
$resultWali = $getWali->get_result();

$wali_kelas = [];
while ($row = $resultWali->fetch_assoc()) {
    $wali_kelas[] = [
        "id_kelas" => (int) $row["id_kelas"],
        "nama_kelas" => $row["nama_kelas"],
        "tingkat" => (int) $row["tingkat"]
    ];
}

$is_wali_kelas = count($wali_kelas) > 0;

/* AMBIL KELAS MAPEL YANG DIAJAR GURU */
$kelas_mapel = [];
$getKelasMapel = $conn->prepare("
    SELECT DISTINCT
        k.id_kelas,
        k.nama_kelas,
        k.tingkat
    FROM jadwal j
    INNER JOIN kelas k ON j.id_kelas = k.id_kelas
    INNER JOIN guru g ON j.id_guru = g.id_guru
    WHERE j.id_guru = ?
      AND j.id_mapel = g.id_mapel
    ORDER BY k.tingkat ASC, k.nama_kelas ASC
");

if (!$getKelasMapel) {
    kirim_json("error", "Query kelas mapel gagal: " . $conn->error);
}

$getKelasMapel->bind_param("i", $id_guru);
$getKelasMapel->execute();
$resultKelasMapel = $getKelasMapel->get_result();

while ($row = $resultKelasMapel->fetch_assoc()) {
    $kelas_mapel[] = [
        "id_kelas" => (int) $row["id_kelas"],
        "nama_kelas" => $row["nama_kelas"],
        "tingkat" => (int) $row["tingkat"]
    ];
}

/* VALIDASI MODE WALI */
if ($mode === "wali") {
    if (!$is_wali_kelas) {
        kirim_json("error", "Guru ini bukan wali kelas.");
    }

    if ($id_kelas <= 0) {
        $id_kelas = $wali_kelas[0]["id_kelas"];
    }

    $bolehAksesKelas = false;
    foreach ($wali_kelas as $kelas) {
        if ((int) $kelas["id_kelas"] === $id_kelas) {
            $bolehAksesKelas = true;
            break;
        }
    }

    if (!$bolehAksesKelas) {
        kirim_json("error", "Anda tidak memiliki akses ke kelas ini.");
    }
}

/* QUERY DATA NILAI */
if ($mode === "wali") {
    // MODE WALI KELAS - ambil nilai semua mapel untuk setiap siswa
    $stmt = $conn->prepare("
        SELECT 
            s.id_siswa,
            s.nama AS nama_siswa,
            s.id_kelas,
            k.nama_kelas,
            m.id_mapel,
            m.nama_mapel,
            COALESCE(n.semester, 1) AS semester,
            COALESCE(n.nilai_angka, 0) AS nilai_angka,
            COALESCE(n.hadir, 0) AS hadir,
            COALESCE(n.izin, 0) AS izin,
            COALESCE(n.sakit, 0) AS sakit,
            COALESCE(n.alfa, 0) AS alfa
        FROM siswa s
        INNER JOIN kelas k ON s.id_kelas = k.id_kelas
        CROSS JOIN mapel m
        LEFT JOIN nilai n ON n.id_siswa = s.id_siswa AND n.id_mapel = m.id_mapel
        WHERE s.id_kelas = ?
        ORDER BY s.nama ASC, m.id_mapel ASC
    ");

    if (!$stmt) {
        kirim_json("error", "Query wali kelas gagal: " . $conn->error);
    }

    $stmt->bind_param("i", $id_kelas);
} else {
    // MODE GURU MAPEL - ambil SEMUA siswa di kelas
    $stmt = $conn->prepare("
        SELECT 
            s.id_siswa,
            s.nama AS nama_siswa,
            s.id_kelas,
            k.nama_kelas,
            ? AS id_mapel,
            ? AS nama_mapel,
            COALESCE(n.semester, 1) AS semester,
            COALESCE(n.nilai_angka, 0) AS nilai_angka,
            COALESCE(n.hadir, 0) AS hadir,
            COALESCE(n.izin, 0) AS izin,
            COALESCE(n.sakit, 0) AS sakit,
            COALESCE(n.alfa, 0) AS alfa
        FROM siswa s
        INNER JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n ON n.id_siswa = s.id_siswa AND n.id_mapel = ?
        WHERE s.id_kelas = ?
        ORDER BY s.nama ASC
    ");

    if (!$stmt) {
        kirim_json("error", "Query nilai mapel gagal: " . $conn->error);
    }

    $stmt->bind_param("isii", $id_mapel_guru, $nama_mapel_guru, $id_mapel_guru, $id_kelas);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $semesterAngka = (int) $row["semester"];
    
    $data[] = [
        "id_siswa" => (int) $row["id_siswa"],
        "nama_siswa" => $row["nama_siswa"] ?? "-",
        "id_kelas" => (int) ($row["id_kelas"] ?? 0),
        "nama_kelas" => $row["nama_kelas"] ?? "-",
        "id_mapel" => (int) $row["id_mapel"],
        "nama_mapel" => $row["nama_mapel"] ?? "-",
        "semester" => $semesterAngka,
        "semester_text" => $semesterAngka === 1 ? "Ganjil" : "Genap",
        "nilai_angka" => (int) ($row["nilai_angka"] ?? 0),
        "hadir" => (int) ($row["hadir"] ?? 0),
        "izin" => (int) ($row["izin"] ?? 0),
        "sakit" => (int) ($row["sakit"] ?? 0),
        "alfa" => (int) ($row["alfa"] ?? 0)
    ];
}

kirim_json("success", "Data nilai berhasil dimuat.", [
    "mode" => $mode,
    "guru" => [
        "id_guru" => (int) $guru["id_guru"],
        "nama" => $guru["nama"],
        "id_mapel" => $id_mapel_guru,
        "nama_mapel" => $nama_mapel_guru
    ],
    "is_wali_kelas" => $is_wali_kelas,
    "wali_kelas" => $wali_kelas,
    "kelas_mapel" => $kelas_mapel,
    "data" => $data
]);
?>