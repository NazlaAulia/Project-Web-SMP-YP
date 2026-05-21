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
    kirim_json("error", "Upload file gagal. Kode error: " . $file["error"]);
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

// Buat folder jika belum ada
$folderUpload = "uploads/profile/";
if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

// Ambil foto lama dari database
$queryOld = "SELECT foto_profil FROM user WHERE id_guru = $id_guru AND role_id = 2 LIMIT 1";
$resultOld = mysqli_query($conn, $queryOld);

if (!$resultOld) {
    kirim_json("error", "Gagal mengambil data user: " . mysqli_error($conn));
}

if (mysqli_num_rows($resultOld) === 0) {
    kirim_json("error", "User guru tidak ditemukan.");
}

$dataOld = mysqli_fetch_assoc($resultOld);
$fotoLama = $dataOld["foto_profil"] ?? "";

// Buat nama file baru
$namaBaru = "guru_" . $id_guru . "_" . time() . "." . $ext;
$pathSimpan = $folderUpload . $namaBaru;

// Pindahkan file yang diupload
if (!move_uploaded_file($tmpFile, $pathSimpan)) {
    kirim_json("error", "Gagal menyimpan foto ke folder upload.");
}

// Update database
$queryUpdate = "UPDATE user SET foto_profil = '$pathSimpan' WHERE id_guru = $id_guru AND role_id = 2";

if (mysqli_query($conn, $queryUpdate)) {
    // Hapus foto lama jika ada dan berbeda
    if (!empty($fotoLama) && file_exists($fotoLama) && $fotoLama !== $pathSimpan) {
        @unlink($fotoLama);
    }
    kirim_json("success", "Foto profil berhasil disimpan.", ["foto_url" => $pathSimpan]);
} else {
    // Jika gagal update database, hapus file yang baru diupload
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }
    kirim_json("error", "Gagal menyimpan foto ke database: " . mysqli_error($conn));
}
?>