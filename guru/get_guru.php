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

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak ditemukan. Silakan login ulang.");
}

$stmt = $conn->prepare("
    SELECT 
        g.id_guru,
        g.nip,
        g.nama,
        g.email,
        g.jenis_kelamin,
        g.id_mapel,
        m.nama_mapel,
        u.id_user,
        u.username,
        u.role_id,
        u.foto_profil
    FROM guru g
    LEFT JOIN mapel m ON g.id_mapel = m.id_mapel
    LEFT JOIN user u ON u.id_guru = g.id_guru
    WHERE g.id_guru = ? AND u.role_id = 2
    LIMIT 1
");

if (!$stmt) {
    kirim_json("error", "Query gagal: " . $conn->error);
}

$stmt->bind_param("i", $id_guru);

if (!$stmt->execute()) {
    kirim_json("error", "Data guru gagal diproses.");
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    kirim_json("error", "Data guru tidak ditemukan.");
}

$guru = $result->fetch_assoc();

kirim_json("success", "Data guru berhasil diambil.", [
    "data" => $guru
]);
?>