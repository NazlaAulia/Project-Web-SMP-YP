<?php
include '../koneksi.php';
header('Content-Type: application/json');

$nama_kelas = trim($_POST['nama_kelas'] ?? '');
$tingkat = (int)($_POST['tingkat'] ?? 0);
$id_wali_kelas = (int)($_POST['id_wali_kelas'] ?? 0);
$kapasitas = (int)($_POST['kapasitas'] ?? 30);
$id_tahun_ajaran = (int)($_POST['id_tahun_ajaran'] ?? 0);

if (!$nama_kelas || !$tingkat || !$id_wali_kelas || !$id_tahun_ajaran) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Cek apakah kelas dengan nama yang sama sudah ada di tahun ajaran ini
$cek = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE nama_kelas = '$nama_kelas' AND id_tahun_ajaran = $id_tahun_ajaran");
if (mysqli_num_rows($cek) > 0) {
    echo json_encode(['success' => false, 'message' => 'Kelas sudah ada di tahun ajaran ini']);
    exit;
}

$query = "INSERT INTO kelas (nama_kelas, tingkat, id_wali_kelas, kapasitas, id_tahun_ajaran) VALUES ('$nama_kelas', $tingkat, $id_wali_kelas, $kapasitas, $id_tahun_ajaran)";
if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true, 'message' => 'Kelas berhasil ditambahkan']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>