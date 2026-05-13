<?php
// File: admin/datasiswa/fungsi_siswa.php

function cekKapasitasKelas($conn, $id_kelas, $except_id_siswa = null) {
    // Hitung jumlah siswa aktif di kelas tersebut
    $sql = "SELECT COUNT(*) as total FROM siswa WHERE id_kelas = $id_kelas AND status = 'aktif'";
    if ($except_id_siswa) {
        $sql .= " AND id_siswa != $except_id_siswa";
    }
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return ['tersedia' => false, 'jumlah_saat_ini' => 0, 'kapasitas' => 30, 'sisa' => 0, 'error' => mysqli_error($conn)];
    }
    $row = mysqli_fetch_assoc($result);
    $jumlah_saat_ini = $row['total'];
    
    // Ambil kapasitas kelas
    $q = mysqli_query($conn, "SELECT kapasitas FROM kelas WHERE id_kelas = $id_kelas");
    if (!$q) {
        return ['tersedia' => false, 'jumlah_saat_ini' => $jumlah_saat_ini, 'kapasitas' => 30, 'sisa' => 0, 'error' => mysqli_error($conn)];
    }
    $kelas = mysqli_fetch_assoc($q);
    $kapasitas = $kelas ? $kelas['kapasitas'] : 30;
    
    return [
        'tersedia' => ($jumlah_saat_ini < $kapasitas),
        'jumlah_saat_ini' => $jumlah_saat_ini,
        'kapasitas' => $kapasitas,
        'sisa' => $kapasitas - $jumlah_saat_ini
    ];
}
?>