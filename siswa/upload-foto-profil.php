<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = isset($_POST['id_siswa']) ? (int) $_POST['id_siswa'] : 0;

if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int) $_SESSION['id_siswa'];
}

if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int) $_SESSION['id_user'];

    $stmtUser = $conn->prepare("SELECT id_siswa FROM user WHERE id_user = ? LIMIT 1");
    $stmtUser->bind_param("i", $id_user);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();

    if ($userData && !empty($userData['id_siswa'])) {
        $id_siswa = (int) $userData['id_siswa'];
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid. Silakan login ulang."
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

$folderUpload = "uploads/profile/";
if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0777, true);
}

$queryOld = "SELECT foto_profil FROM user WHERE id_siswa = $id_siswa LIMIT 1";
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

$namaBaru = "siswa_" . $id_siswa . "_" . time() . "." . $ext;
$pathSimpan = $folderUpload . $namaBaru;

if (!move_uploaded_file($tmpFile, $pathSimpan)) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan file ke folder upload."
    ]);
    exit;
}

$pathDb = mysqli_real_escape_string($conn, $pathSimpan);

$queryCekUser = "SELECT id_user FROM user WHERE id_siswa = $id_siswa LIMIT 1";
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
    $queryUpdate = "UPDATE user SET foto_profil = '$pathDb' WHERE id_siswa = $id_siswa";
} else {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    echo json_encode([
        "success" => false,
        "message" => "User untuk siswa ini tidak ditemukan."
    ]);
    exit;
}

if (mysqli_query($conn, $queryUpdate)) {
    if (!empty($fotoLama) && file_exists($fotoLama)) {
        @unlink($fotoLama);
    }

    $_SESSION['id_siswa'] = $id_siswa;

    echo json_encode([
        "success" => true,
        "message" => "Foto profil berhasil disimpan.",
        "foto_url" => $pathSimpan,
        "id_siswa" => $id_siswa
    ]);
} else {
    if (file_exists($pathSimpan)) {
        @unlink($pathSimpan);
    }

    echo json_encode([
        "success" => false,
        "message" => "Gagal update database: " . mysqli_error($conn)
    ]);
}
?>