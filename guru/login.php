<?php
/*ob_start();
session_start();

header("Content-Type: application/json; charset=utf-8");

error_reporting(E_ALL);
ini_set("display_errors", 0);
ini_set("log_errors", 1);

require_once "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));

    exit;
}

if (!isset($conn)) {
    kirim_json("error", "Variabel koneksi database tidak ditemukan.");
}

$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

if ($username === "" && $password === "") {
    kirim_json("error", "Username dan password wajib diisi.");
}

if ($username === "") {
    kirim_json("error", "Username wajib diisi.");
}

if ($password === "") {
    kirim_json("error", "Password wajib diisi.");
}

$stmt = mysqli_prepare($conn, "
    SELECT id_user, username, password, role_id, id_guru, id_siswa
    FROM user
    WHERE username = ?
    LIMIT 1
");

if (!$stmt) {
    kirim_json("error", "Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $username);

if (!mysqli_stmt_execute($stmt)) {
    kirim_json("error", "Login gagal diproses: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    kirim_json("error", "Username tidak ditemukan.");
}

$user = mysqli_fetch_assoc($result);

if ($password !== $user["password"]) {
    kirim_json("error", "Password yang kamu masukkan salah.");
}

if ((int)$user["role_id"] !== 2) {
    kirim_json("error", "Akun ini bukan role guru.");
}

$_SESSION["id_user"] = $user["id_user"];
$_SESSION["username"] = $user["username"];
$_SESSION["role_id"] = $user["role_id"];
$_SESSION["id_guru"] = $user["id_guru"];
$_SESSION["id_siswa"] = $user["id_siswa"];

kirim_json("success", "Login guru berhasil.", [
    "redirect" => "guru/guru.html"
]);}*/
?>