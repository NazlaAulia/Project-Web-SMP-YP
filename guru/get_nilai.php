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

$getGuru = $conn->prepare("
    SELECT id_mapel
    FROM guru
    WHERE id_guru = ?
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

$stmt = $conn->prepare("
    SELECT
        n.id_siswa,
        s.nama AS nama_siswa,
        n.id_mapel,
        m.nama_mapel,
        n.semester,
        n.nilai_angka,
        n.hadir,
        n.izin,
        n.sakit,
        n.alfa
    FROM nilai n
    LEFT JOIN siswa s ON n.id_siswa = s.id_siswa
    LEFT JOIN mapel m ON n.id_mapel = m.id_mapel
    WHERE n.id_mapel = ?
    ORDER BY n.id_siswa ASC, n.semester ASC
");

if (!$stmt) {
    kirim_json("error", "Query nilai gagal: " . $conn->error);
}

$stmt->bind_param("i", $id_mapel_guru);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "id_siswa" => (int) $row["id_siswa"],
        "nama_siswa" => $row["nama_siswa"] ?? "-",
        "id_mapel" => (int) $row["id_mapel"],
        "nama_mapel" => $row["nama_mapel"] ?? "-",
        "semester" => (int) $row["semester"],
        "nilai_angka" => (int) $row["nilai_angka"],
        "hadir" => (int) $row["hadir"],
        "izin" => (int) $row["izin"],
        "sakit" => (int) $row["sakit"],
        "alfa" => (int) $row["alfa"]
    ];
}

kirim_json("success", "Data nilai berhasil dimuat.", [
    "data" => $data
]);
?>