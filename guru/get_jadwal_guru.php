<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");

$id_guru = isset($_GET["id_guru"]) ? (int)$_GET["id_guru"] : 0;

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid. Silakan login ulang.");
}

$stmtGuru = $conn->prepare("
    SELECT id_guru, nama
    FROM guru
    WHERE id_guru = ?
    LIMIT 1
");

if (!$stmtGuru) {
    kirim_json("error", "Query guru gagal: " . $conn->error);
}

$stmtGuru->bind_param("i", $id_guru);
$stmtGuru->execute();
$resultGuru = $stmtGuru->get_result();
$guru = $resultGuru->fetch_assoc();
$stmtGuru->close();

if (!$guru) {
    kirim_json("error", "Data guru tidak ditemukan.");
}

$stmt = $conn->prepare("
    SELECT 
        j.id_jadwal,
        j.id_guru,
        j.id_kelas,
        j.id_mapel,
        j.hari,
        j.jam,
        j.jp_mulai,
        j.jp_selesai,
        j.jumlah_jp,
        g.nama AS nama_guru,
        k.nama_kelas,
        m.nama_mapel
    FROM jadwal j
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_guru = ?
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        COALESCE(j.jp_mulai, 0),
        j.jam ASC
");

if (!$stmt) {
    kirim_json("error", "Query jadwal gagal: " . $conn->error);
}

$stmt->bind_param("i", $id_guru);

if (!$stmt->execute()) {
    kirim_json("error", "Jadwal guru gagal diproses.");
}

$result = $stmt->get_result();

$jadwal = [];

while ($row = $result->fetch_assoc()) {
    $jadwal[] = [
        "id_jadwal" => (int)$row["id_jadwal"],
        "id_guru" => (int)$row["id_guru"],
        "id_kelas" => (int)$row["id_kelas"],
        "id_mapel" => (int)$row["id_mapel"],
        "guru" => $row["nama_guru"] ?? "-",
        "kelas" => $row["nama_kelas"] ?? "-",
        "mapel" => $row["nama_mapel"] ?? "-",
        "hari" => $row["hari"] ?? "-",
        "jam" => $row["jam"] ?? "-",
        "jp_mulai" => $row["jp_mulai"],
        "jp_selesai" => $row["jp_selesai"],
        "jumlah_jp" => (int)($row["jumlah_jp"] ?? 1)
    ];
}

$stmt->close();
$conn->close();

kirim_json("success", "Jadwal guru berhasil diambil.", [
    "guru" => $guru,
    "data" => $jadwal
]);
?>