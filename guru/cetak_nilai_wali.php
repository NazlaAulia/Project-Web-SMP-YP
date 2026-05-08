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
$id_kelas = isset($_GET["id_kelas"]) ? (int) $_GET["id_kelas"] : 0;
$id_siswa = isset($_GET["id_siswa"]) ? (int) $_GET["id_siswa"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0 || $id_kelas <= 0) {
    kirim_json("error", "Data guru atau kelas tidak valid.");
}

/* CEK WALI KELAS */
$cekWali = $conn->prepare("
    SELECT 
        k.id_kelas,
        k.nama_kelas,
        g.nama AS nama_wali,
        ta.tahun_ajaran
    FROM kelas k
    LEFT JOIN guru g ON k.id_wali_kelas = g.id_guru
    LEFT JOIN tahun_ajaran ta ON ta.status = 'aktif'
    WHERE k.id_kelas = ? 
      AND k.id_wali_kelas = ?
    LIMIT 1
");

if (!$cekWali) {
    kirim_json("error", "Query wali kelas gagal: " . $conn->error);
}

$cekWali->bind_param("ii", $id_kelas, $id_guru);
$cekWali->execute();
$resultWali = $cekWali->get_result();

if ($resultWali->num_rows === 0) {
    kirim_json("error", "Anda tidak memiliki akses untuk mencetak nilai kelas ini.");
}

$wali = $resultWali->fetch_assoc();

/* AMBIL NILAI SISWA */
$sql = "
    SELECT
        s.id_siswa,
        s.nama AS nama_siswa,
        k.nama_kelas,
        m.nama_mapel,
        n.semester,
        n.nilai_angka,
        n.hadir,
        n.izin,
        n.sakit,
        n.alfa
    FROM nilai n
    INNER JOIN siswa s ON n.id_siswa = s.id_siswa
    INNER JOIN kelas k ON s.id_kelas = k.id_kelas
    INNER JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE s.id_kelas = ?
";

$types = "i";
$params = [$id_kelas];

if ($id_siswa > 0) {
    $sql .= " AND s.id_siswa = ?";
    $types .= "i";
    $params[] = $id_siswa;
}

$sql .= " ORDER BY s.nama ASC, n.semester ASC, m.id_mapel ASC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    kirim_json("error", "Query nilai gagal: " . $conn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$siswaMap = [];

while ($row = $result->fetch_assoc()) {
    $id = (int) $row["id_siswa"];

    if (!isset($siswaMap[$id])) {
        $siswaMap[$id] = [
            "id_siswa" => $id,
            "nama_siswa" => $row["nama_siswa"],
            "nama_kelas" => $row["nama_kelas"],
            "nilai" => []
        ];
    }

    $siswaMap[$id]["nilai"][] = [
        "nama_mapel" => $row["nama_mapel"],
        "semester" => (int) $row["semester"],
        "nilai_angka" => (int) $row["nilai_angka"],
        "hadir" => (int) $row["hadir"],
        "izin" => (int) $row["izin"],
        "sakit" => (int) $row["sakit"],
        "alfa" => (int) $row["alfa"]
    ];
}

kirim_json("success", "Data cetak nilai berhasil dimuat.", [
    "kelas" => [
        "id_kelas" => (int) $wali["id_kelas"],
        "nama_kelas" => $wali["nama_kelas"],
        "tahun_ajaran" => $wali["tahun_ajaran"] ?? "-"
    ],
    "wali" => [
        "id_guru" => $id_guru,
        "nama" => $wali["nama_wali"]
    ],
    "kepala_sekolah" => [
        "nama" => "Dra. Slamet Suwarni",
        "nip" => "196712241994121001"
    ],
    "siswa" => array_values($siswaMap)
]);
?>