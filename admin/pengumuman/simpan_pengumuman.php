<?php
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: utama.php?status=gagal");
    exit;
}

$id_pengumuman = isset($_POST['id_pengumuman']) ? (int) $_POST['id_pengumuman'] : 0;
$judul = trim($_POST['judul'] ?? '');
$isi = trim($_POST['isi'] ?? '');
$tanggal = trim($_POST['tanggal'] ?? '');
$kategori = trim($_POST['kategori'] ?? 'Informasi');
$status = trim($_POST['status'] ?? 'tampil');
$gambar_lama = trim($_POST['gambar_lama'] ?? '');

if ($judul === '' || $isi === '' || $tanggal === '') {
    header("Location: utama.php?status=gagal");
    exit;
}

$gambarPath = $gambar_lama;

$folderUpload = __DIR__ . '/foto/';

if (!is_dir($folderUpload)) {
    mkdir($folderUpload, 0775, true);
}

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $namaFile = $_FILES['gambar']['name'];
    $tmpFile = $_FILES['gambar']['tmp_name'];
    $ukuranFile = $_FILES['gambar']['size'];

    $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    $ekstensiValid = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ekstensi, $ekstensiValid)) {
        header("Location: utama.php?status=gagal");
        exit;
    }

    if ($ukuranFile > 2 * 1024 * 1024) {
        header("Location: utama.php?status=gagal");
        exit;
    }

    $namaBaru = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $namaFile);
    $tujuan = $folderUpload . $namaBaru;

    if (move_uploaded_file($tmpFile, $tujuan)) {
        $gambarPath = 'foto/' . $namaBaru;

        if ($gambar_lama !== '' && file_exists(__DIR__ . '/' . $gambar_lama)) {
            unlink(__DIR__ . '/' . $gambar_lama);
        }
    }
}

if ($id_pengumuman > 0) {
    $stmt = $conn->prepare("
        UPDATE pengumuman
        SET judul = ?, isi = ?, gambar = ?, kategori = ?, status = ?, tanggal = ?
        WHERE id_pengumuman = ?
    ");

    $stmt->bind_param(
        "ssssssi",
        $judul,
        $isi,
        $gambarPath,
        $kategori,
        $status,
        $tanggal,
        $id_pengumuman
    );
} else {
    $stmt = $conn->prepare("
        INSERT INTO pengumuman (judul, isi, gambar, kategori, status, tanggal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssss",
        $judul,
        $isi,
        $gambarPath,
        $kategori,
        $status,
        $tanggal
    );
}

if ($stmt->execute()) {
    $stmt->close();
    header("Location: utama.php?status=sukses");
    exit;
}

$stmt->close();
header("Location: utama.php?status=gagal");
exit;