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

$id_guru = isset($_POST["id_guru"]) ? (int) $_POST["id_guru"] : 0;
$role_id = isset($_POST["role_id"]) ? (int) $_POST["role_id"] : 0;

$nama = trim($_POST["nama"] ?? "");
$nip = trim($_POST["nip"] ?? "");
$email = trim($_POST["email"] ?? "");
$id_mapel = isset($_POST["id_mapel"]) ? (int) $_POST["id_mapel"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if ($nama === "" || $nip === "" || $email === "") {
    kirim_json("error", "Nama, NIP, dan email wajib diisi.");
}

if ($id_mapel <= 0) {
    kirim_json("error", "Mata pelajaran wajib dipilih.");
}

$cek = $conn->prepare("
    SELECT id_user 
    FROM user 
    WHERE id_guru = ? AND role_id = 2 
    LIMIT 1
");

if (!$cek) {
    kirim_json("error", "Query cek user gagal: " . $conn->error);
}

$cek->bind_param("i", $id_guru);
$cek->execute();
$resultCek = $cek->get_result();

if ($resultCek->num_rows === 0) {
    kirim_json("error", "Akun guru tidak ditemukan.");
}

$update = $conn->prepare("
    UPDATE guru 
    SET nama = ?, 
        nip = ?, 
        email = ?, 
        id_mapel = ?
    WHERE id_guru = ?
");

if (!$update) {
    kirim_json("error", "Query update gagal: " . $conn->error);
}

$update->bind_param("sssii", $nama, $nip, $email, $id_mapel, $id_guru);

if ($update->execute()) {
    kirim_json("success", "Profil guru berhasil diperbarui.");
} else {
    kirim_json("error", "Gagal menyimpan profil guru.");
}
?>