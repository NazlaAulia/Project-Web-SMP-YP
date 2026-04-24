<?php
/*header("Content-Type: application/json; charset=utf-8");
require_once "koneksi.php";

/*
  SEMENTARA UNTUK TESTING
  Karena login.php dan login.js belum menyimpan session/localStorage.

$id_user = 36;

if (!isset($_FILES["foto"])) {
    echo json_encode([
        "status" => "error",
        "message" => "File foto belum dipilih."
    ]);
    exit;
}

$file = $_FILES["foto"];

$allowed = ["jpg", "jpeg", "png", "webp"];
$namaFile = $file["name"];
$tmpFile = $file["tmp_name"];
$error = $file["error"];
$size = $file["size"];

if ($error !== 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Upload foto gagal."
    ]);
    exit;
}

$ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode([
        "status" => "error",
        "message" => "Format foto harus JPG, JPEG, PNG, atau WEBP."
    ]);
    exit;
}

if ($size > 2 * 1024 * 1024) {
    echo json_encode([
        "status" => "error",
        "message" => "Ukuran foto maksimal 2MB."
    ]);
    exit;
}

$namaBaru = "guru_" . $id_user . "_" . time() . "." . $ext;

$folderTujuan = "../uploads/profile/";
$pathDatabase = "uploads/profile/" . $namaBaru;
$pathUpload = $folderTujuan . $namaBaru;

if (!is_dir($folderTujuan)) {
    mkdir($folderTujuan, 0777, true);
}

if (!move_uploaded_file($tmpFile, $pathUpload)) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menyimpan foto."
    ]);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE user SET foto_profil = ? WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "si", $pathDatabase, $id_user);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal update foto ke database."
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Foto profil berhasil diubah.",
    "foto" => "../" . $pathDatabase
]);