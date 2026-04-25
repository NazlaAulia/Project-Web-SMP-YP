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
$username_baru = trim($_POST["username_baru"] ?? "");

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if ($username_baru === "") {
    kirim_json("error", "Username baru wajib diisi.");
}

$cek = $conn->prepare("
    SELECT id_user
    FROM user
    WHERE username = ? AND NOT (id_guru = ? AND role_id = 2)
    LIMIT 1
");

if (!$cek) {
    kirim_json("error", "Query cek username gagal: " . $conn->error);
}

$cek->bind_param("si", $username_baru, $id_guru);
$cek->execute();
$result = $cek->get_result();

if ($result->num_rows > 0) {
    kirim_json("error", "Username sudah digunakan akun lain.");
}

$update = $conn->prepare("
    UPDATE user
    SET username = ?
    WHERE id_guru = ? AND role_id = 2
");

if (!$update) {
    kirim_json("error", "Query update username gagal: " . $conn->error);
}

$update->bind_param("si", $username_baru, $id_guru);

if ($update->execute()) {
    kirim_json("success", "Username berhasil diperbarui.");
} else {
    kirim_json("error", "Gagal memperbarui username.");
}
?>