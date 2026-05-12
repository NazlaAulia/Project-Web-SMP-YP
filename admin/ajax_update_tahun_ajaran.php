<?php
include '../koneksi.php';
header('Content-Type: application/json');

$id = (int)$_POST['id_tahun_ajaran'];
$kuota = (int)$_POST['kuota'];
$tgl_buka = $_POST['tgl_buka'];
$tgl_tutup = $_POST['tgl_tutup'];
$status = $_POST['status'];

// Jika status baru = aktif, nonaktifkan tahun ajaran lain
if ($status == 'aktif') {
    mysqli_query($conn, "UPDATE tahun_ajaran SET status = 'nonaktif' WHERE id_tahun_ajaran != $id");
}

$query = "UPDATE tahun_ajaran SET kuota = $kuota, tgl_buka = '$tgl_buka', tgl_tutup = '$tgl_tutup', status = '$status' WHERE id_tahun_ajaran = $id";
if (mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>