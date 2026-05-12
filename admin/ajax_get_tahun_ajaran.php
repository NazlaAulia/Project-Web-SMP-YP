<?php
include '../koneksi.php';
$id = (int)$_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = $id");
echo json_encode(mysqli_fetch_assoc($result));
?>