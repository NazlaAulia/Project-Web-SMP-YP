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

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid.");
}

if (!isset($_FILES["foto"])) {
    kirim_json("error", "File foto tidak ditemukan.");
}

$file = $_FILES["foto"];

if ($file["error"] !== 0) {
    kirim_json("error", "Upload file gagal.");
}

$allowedExt = ["jpg", "jpeg", "png", "webp"];
$allowedMime = ["image/jpeg", "image/png", "image/webp"];
$maxSize = 2 * 1024 * 1024;

$namaFile = $file["name"];
$tmpFile = $file["tmp_name"];
$fileSize = $file["size"];

$ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    kirim_json("error", "Format foto harus JPG, JPEG, PNG, atau WEBP.");
}

if ($fileSize > $maxSize) {
    kirim_json("error", "Ukuran foto maksimal 2 MB.");
}

$mime = mime_content_type($tmpFile);

if (!in_array($mime, $allowedMime)) {
    kirim_json("error", "File bukan gambar yang valid.");
}

$folderUpload = "uploads/profile/";

if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

$stmtOld = $conn->prepare("
    SELECT foto_profil 
    FROM user 
    WHERE id_guru = ? AND role_id = 2 
    LIMIT 1
");

if (!$stmtOld) {
    kirim_json("error", "Query foto lama gagal: " . $conn->error);
}

$stmtOld->bind_param("i", $id_guru);
$stmtOld->execute();
$resultOld = $stmtOld->get_result();

if ($resultOld->num_rows === 0) {
    kirim_json("error", "User guru tidak ditemukan.");
}

$dataOld = $resultOld->fetch_assoc();
$fotoLama = $dataOld["foto_profil"] ?? "";

$namaBaru = "guru_" . $id_guru . "_" . time() . "." . $ext;
$pathSimpan = $folderUpload . $namaBaru;

if (!move_uploaded_file($tmpFile, $pathSimpan)) {
    kirim_json("error", "Gagal menyimpan foto ke folder upload.");
}

$stmtUpdate = $conn->prepare("
    UPDATE user 
    SET foto_profil = ? 
    WHERE id_guru = ? AND role_id = 2
");

if (!$stmtUpdate) {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    kirim_json("error", "Query update foto gagal: " . $conn->error);
}

$stmtUpdate->bind_param("si", $pathSimpan, $id_guru);

if ($stmtUpdate->execute()) {
    if (!empty($fotoLama) && file_exists($fotoLama)) {
        @unlink($fotoLama);
    }

    kirim_json("success", "Foto profil berhasil disimpan.", [
        "foto_url" => $pathSimpan
    ]);
} else {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    kirim_json("error", "Gagal menyimpan foto ke database.");
}
?>