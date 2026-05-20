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

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

$whereSemester = "";
$semesterValue = 0;

if ($semester === "Ganjil") {
    $whereSemester = "WHERE n.semester = ?";
    $semesterValue = 1;
} elseif ($semester === "Genap") {
    $whereSemester = "WHERE n.semester = ?";
    $semesterValue = 2;
}

$sql = "
    SELECT
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ROUND(AVG(n.nilai_angka), 2) AS rata_rata
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    $whereSemester
    GROUP BY s.id_siswa, s.nama, k.nama_kelas
    ORDER BY rata_rata DESC, s.nama ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    kirim_json("error", "Query peringkat gagal: " . $conn->error);
}

if ($whereSemester !== "") {
    $stmt->bind_param("i", $semesterValue);
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

kirim_json("success", "Data peringkat berhasil dimuat.", [
    "data" => $data,
    "summary" => [
        "unggul" => $jumlahUnggul,
        "baik" => $jumlahBaik,
        "perhatian" => $jumlahPerhatian
    ]
]);
?>