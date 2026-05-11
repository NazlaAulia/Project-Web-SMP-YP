<?php
include "koneksi.php"; // pastikan koneksi ke database

$id_siswa = $_GET['id_siswa'] ?? '';

if (!$id_siswa) {
    echo json_encode(["status" => "error", "message" => "ID siswa tidak ditemukan"]);
    exit;
}

// Ambil data siswa
$query_siswa = "SELECT nama, nama_kelas, status FROM siswa WHERE id_siswa = '$id_siswa'";
$result_siswa = mysqli_query($conn, $query_siswa);

if (!$result_siswa || mysqli_num_rows($result_siswa) === 0) {
    echo json_encode(["status" => "error", "message" => "Siswa tidak ditemukan"]);
    exit;
}

$siswa = mysqli_fetch_assoc($result_siswa);

// Ambil nilai akademik
$query_nilai = "SELECT nama_mapel, nilai_angka FROM nilai_akademik WHERE id_siswa = '$id_siswa'";
$result_nilai = mysqli_query($conn, $query_nilai);

$nilai_akademik = [];
while ($row = mysqli_fetch_assoc($result_nilai)) {
    $nilai_akademik[] = [
        "nama_mapel" => $row['nama_mapel'],
        "nilai_angka" => $row['nilai_angka']
    ];
}

// Ambil jadwal hari ini (contoh)
$hari_ini = date('Y-m-d');
$query_jadwal = "SELECT j.nama_mapel, g.nama AS nama_guru, j.jam 
                 FROM jadwal j 
                 LEFT JOIN guru g ON j.id_guru = g.id_guru
                 WHERE j.id_siswa = '$id_siswa' AND j.tanggal = '$hari_ini'";
$result_jadwal = mysqli_query($conn, $query_jadwal);

$jadwal_hari_ini = [];
while ($row = mysqli_fetch_assoc($result_jadwal)) {
    $jadwal_hari_ini[] = [
        "nama_mapel" => $row['nama_mapel'],
        "nama_guru" => $row['nama_guru'],
        "jam" => $row['jam']
    ];
}

// Output JSON
echo json_encode([
    "status" => "success",
    "data" => [
        "nama" => $siswa['nama'],
        "nama_kelas" => $siswa['nama_kelas'],
        "status" => $siswa['status'],
        "nilai_akademik" => $nilai_akademik,
        "jadwal_hari_ini" => $jadwal_hari_ini
    ]
]);
?>