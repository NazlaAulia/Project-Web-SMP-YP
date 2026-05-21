<?php
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
}
?>