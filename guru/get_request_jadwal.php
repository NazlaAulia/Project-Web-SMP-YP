<?php
include 'koneksi.php';

$id_guru = $_GET['id_guru'];

$query = "SELECT 
            r.id_request,
            r.hari_baru,
            r.jam_baru,
            r.alasan,
            r.status,
            r.tanggal_request,
            j.hari AS hari_lama,
            CONCAT(j.jam_mulai, ' - ', j.jam_selesai) AS jam_lama,
            m.nama_mapel,
            k.nama_kelas
          FROM request_jadwal r
          JOIN jadwal j ON r.id_jadwal = j.id_jadwal
          JOIN mapel m ON j.id_mapel = m.id_mapel
          JOIN kelas k ON j.id_kelas = k.id_kelas
          WHERE r.id_guru = ?
          ORDER BY r.tanggal_request DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_guru);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>