<?php
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = isset($_POST['id_siswa']) ? (int) $_POST['id_siswa'] : 0;

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid."
    ]);
    exit;
}

if (!isset($_FILES['foto'])) {
    echo json_encode([
        "success" => false,
        "message" => "File foto tidak ditemukan."
    ]);
    exit;
}

$file = $_FILES['foto'];

if ($file['error'] !== 0) {
    echo json_encode([
        "success" => false,
        "message" => "Upload file gagal."
    ]);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 2 * 1024 * 1024;

$namaFile = $file['name'];
$tmpFile = $file['tmp_name'];
$fileSize = $file['size'];

$ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    echo json_encode([
        "success" => false,
        "message" => "Format file harus jpg, jpeg, png, atau webp."
    ]);
    exit;
}

if ($fileSize > $maxSize) {
    echo json_encode([
        "success" => false,
        "message" => "Ukuran file maksimal 2 MB."
    ]);
    exit;
}

$folderUpload = "uploads/profile/";
if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

$queryOld = "SELECT foto_profil FROM siswa WHERE id = $id_siswa LIMIT 1";
$resultOld = mysqli_query($conn, $queryOld);
$dataOld = mysqli_fetch_assoc($resultOld);
$fotoLama = $dataOld['foto_profil'] ?? null;

$namaBaru = "siswa_" . $id_siswa . "_" . time() . "." . $ext;
$pathSimpan = $folderUpload . $namaBaru;

if (!move_uploaded_file($tmpFile, $pathSimpan)) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan file."
    ]);
    exit;
}

$queryUpdate = "UPDATE siswa SET foto_profil = '$pathSimpan' WHERE id = $id_siswa";

if (mysqli_query($conn, $queryUpdate)) {
    if (!empty($fotoLama) && file_exists($fotoLama)) {
        @unlink($fotoLama);
    }

    echo json_encode([
        "success" => true,
        "message" => "Foto profil berhasil disimpan.",
        "foto_url" => $pathSimpan
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal update database: " . mysqli_error($conn)
    ]);
}
?>