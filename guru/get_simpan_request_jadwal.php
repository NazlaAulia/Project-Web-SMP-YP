<?php
include 'koneksi.php';

$id_guru = $_POST['id_guru'];
$id_jadwal = $_POST['id_jadwal'];
$hari_baru = $_POST['hari_baru'];
$jam_baru = $_POST['jam_baru'];
$alasan = $_POST['alasan'];

$query = "INSERT INTO request_jadwal 
          (id_guru, id_jadwal, hari_baru, jam_baru, alasan, status, tanggal_request)
          VALUES (?, ?, ?, ?, ?, 'menunggu', NOW())";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "iisss", $id_guru, $id_jadwal, $hari_baru, $jam_baru, $alasan);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => true,
        "message" => "Request ganti jadwal berhasil dikirim."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Gagal mengirim request."
    ]);
}
?>