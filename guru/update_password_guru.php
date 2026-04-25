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

$password_lama = trim($_POST["password_lama"] ?? "");
$password_baru = trim($_POST["password_baru"] ?? "");
$konfirmasi_password = trim($_POST["konfirmasi_password"] ?? "");

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if ($password_lama === "") {
    kirim_json("error", "Password lama wajib diisi.");
}

if ($password_baru === "") {
    kirim_json("error", "Password baru wajib diisi.");
}

if ($konfirmasi_password === "") {
    kirim_json("error", "Konfirmasi password baru wajib diisi.");
}

if ($password_baru !== $konfirmasi_password) {
    kirim_json("error", "Konfirmasi password baru tidak sama.");
}

$get = $conn->prepare("
    SELECT id_user, password
    FROM user
    WHERE id_guru = ? AND role_id = 2
    LIMIT 1
");

if (!$get) {
    kirim_json("error", "Query cek akun gagal: " . $conn->error);
}

$get->bind_param("i", $id_guru);
$get->execute();
$result = $get->get_result();

if ($result->num_rows === 0) {
    kirim_json("error", "Akun guru tidak ditemukan.");
}

$user = $result->fetch_assoc();

if ($user["password"] !== $password_lama) {
    kirim_json("error", "Password lama salah.");
}

$update = $conn->prepare("
    UPDATE user
    SET password = ?
    WHERE id_guru = ? AND role_id = 2
");

if (!$update) {
    kirim_json("error", "Query update password gagal: " . $conn->error);
}

$update->bind_param("si", $password_baru, $id_guru);

if ($update->execute()) {
    kirim_json("success", "Password berhasil diperbarui.");
} else {
    kirim_json("error", "Gagal memperbarui password.");
}
?>