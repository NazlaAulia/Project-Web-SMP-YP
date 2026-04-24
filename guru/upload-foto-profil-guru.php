<?php
/*header("Content-Type: application/json");
require_once "koneksi.php";

$id_guru = isset($_POST['id_guru']) ? (int) $_POST['id_guru'] : 0;

if ($id_guru <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID guru tidak valid."
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
        "message" => "Upload file gagal. Kode error: " . $file['error']
    ]);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
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

$mime = mime_content_type($tmpFile);

if (!in_array($mime, $allowedMime)) {
    echo json_encode([
        "success" => false,
        "message" => "File bukan gambar yang valid."
    ]);
    exit;
}

/*
  File ini ada di folder guru.
  Jadi path ini menyimpan foto ke:
  guru/uploads/profile/
*/
$folderUpload = "uploads/profile/";

if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

$queryOld = "SELECT foto_profil FROM user WHERE id_guru = $id_guru LIMIT 1";
$resultOld = mysqli_query($conn, $queryOld);

if (!$resultOld) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengambil foto lama: " . mysqli_error($conn)
    ]);
    exit;
}

$dataOld = mysqli_fetch_assoc($resultOld);
$fotoLama = $dataOld['foto_profil'] ?? null;

$namaBaru = "guru_" . $id_guru . "_" . time() . "." . $ext;
$pathSimpan = $folderUpload . $namaBaru;

if (!move_uploaded_file($tmpFile, $pathSimpan)) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan file ke folder upload."
    ]);
    exit;
}

$pathDb = mysqli_real_escape_string($conn, $pathSimpan);

$queryCekUser = "SELECT id_user FROM user WHERE id_guru = $id_guru LIMIT 1";
$resultCekUser = mysqli_query($conn, $queryCekUser);

if (!$resultCekUser) {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    echo json_encode([
        "success" => false,
        "message" => "Gagal mengecek user: " . mysqli_error($conn)
    ]);
    exit;
}

if (mysqli_num_rows($resultCekUser) > 0) {
    $queryUpdate = "UPDATE user SET foto_profil = '$pathDb' WHERE id_guru = $id_guru";
} else {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    echo json_encode([
        "success" => false,
        "message" => "User untuk guru ini tidak ditemukan."
    ]);
    exit;
}

if (mysqli_query($conn, $queryUpdate)) {
    if (!empty($fotoLama) && file_exists($fotoLama)) {
        @unlink($fotoLama);
    }

    echo json_encode([
        "success" => true,
        "message" => "Foto profil guru berhasil disimpan.",
        "foto_url" => $pathSimpan
    ]);
} else {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    echo json_encode([
        "success" => false,
        "message" => "Gagal update database: " . mysqli_error($conn)
    ]);
}*/
?>