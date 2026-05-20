<?php
header("Content-Type: application/json; charset=utf-8");

require_once "koneksi.php";

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;
$semester = $_GET["semester"] ?? "Semua";
$angkatan = isset($_GET["angkatan"]) ? (int) $_GET["angkatan"] : 0;

if ($role_id !== 2 || $id_guru <= 0) {
    echo json_encode(["status" => "error", "message" => "Akses ditolak"]);
    exit;
}

$th = $conn->query("SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1")->fetch_assoc();
$id_tahun = $th ? $th['id_tahun_ajaran'] : 0;

if ($id_tahun == 0) {
    echo json_encode(["status" => "error", "message" => "Tidak ada tahun ajaran aktif"]);
    exit;
}

$filter_semester = "";
if ($semester == "Ganjil") $filter_semester = "AND n.semester = 1";
if ($semester == "Genap") $filter_semester = "AND n.semester = 2";

$filter_angkatan = "";
if ($angkatan > 0) $filter_angkatan = "AND k.tingkat = $angkatan";

// Query dari SISWA
$sql = "
    SELECT 
        s.nama,
        k.nama_kelas,
        ROUND(COALESCE(AVG(n.nilai_angka), 0), 2) AS rata_rata
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas AND k.id_tahun_ajaran = $id_tahun
    LEFT JOIN nilai n ON n.id_siswa = s.id_siswa AND n.id_tahun_ajaran = $id_tahun $filter_semester
    WHERE k.id_tahun_ajaran = $id_tahun $filter_angkatan
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    ORDER BY rata_rata DESC, s.nama ASC
    LIMIT 10
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query error: " . $conn->error]);
    exit;
}

$data = [];
$rank = 1;
$unggul = $baik = $perhatian = 0;

while ($row = $result->fetch_assoc()) {
    $nilai = (float) $row["rata_rata"];
    
    if ($nilai >= 90) {
        $status = "Excellent";
        $unggul++;
    } elseif ($nilai >= 75) {
        $status = "Good";
        $baik++;
    } else {
        $status = "Need Attention";
        $perhatian++;
    }
    
    $data[] = [
        "rank" => $rank++,
        "nama" => $row["nama"] ?? "-",
        "kelas" => $row["nama_kelas"] ?? "-",
        "nilai" => $nilai,
        "status" => $status
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $data,
    "summary" => [
        "unggul" => $unggul,
        "baik" => $baik,
        "perhatian" => $perhatian
    ]
]);
?>