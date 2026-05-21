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
    kirim_json("error", "Akses ditolak.");
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
$maxSize = 1 * 1024 * 1024; // Turunkan jadi 1MB biar cepet

$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    kirim_json("error", "Format foto harus JPG, JPEG, PNG, atau WEBP.");
}

if ($file["size"] > $maxSize) {
    kirim_json("error", "Ukuran foto maksimal 1 MB.");
}

// Kompres gambar sebelum upload
function compressImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $destination, 8);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $destination, $quality);
    } else {
        return false;
    }
    imagedestroy($image);
    return true;
}

$folderUpload = "uploads/profile/";
if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

// Ambil foto lama
$queryOld = "SELECT foto_profil FROM user WHERE id_guru = $id_guru AND role_id = 2 LIMIT 1";
$resultOld = mysqli_query($conn, $queryOld);

if (!$resultOld || mysqli_num_rows($resultOld) === 0) {
    kirim_json("error", "User guru tidak ditemukan.");
}

$dataOld = mysqli_fetch_assoc($resultOld);
$fotoLama = $dataOld["foto_profil"] ?? "";

// Buat nama file baru
$namaBaru = "guru_" . $id_guru . "_" . time() . ".webp";
$pathSimpan = $folderUpload . $namaBaru;

// Kompres dan simpan
$tempFile = $file["tmp_name"];
if (compressImage($tempFile, $pathSimpan, 75)) {
    // Update database
    $queryUpdate = "UPDATE user SET foto_profil = '$pathSimpan' WHERE id_guru = $id_guru AND role_id = 2";
    
    if (mysqli_query($conn, $queryUpdate)) {
        if (!empty($fotoLama) && file_exists($fotoLama) && $fotoLama !== $pathSimpan) {
            @unlink($fotoLama);
        }
        kirim_json("success", "Foto profil berhasil disimpan.", ["foto_url" => $pathSimpan]);
    } else {
        if (file_exists($pathSimpan)) {
            @unlink($pathSimpan);
        }
        kirim_json("error", "Gagal menyimpan ke database.");
    }
} else {
    kirim_json("error", "Gagal memproses gambar.");
}
?>