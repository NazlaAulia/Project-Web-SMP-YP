<?php
require_once '../koneksi.php';

$filename = "template_import_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// BOM agar Excel membaca UTF-8 dengan benar
echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

// Header wajib
fputcsv($output, [
    "nisn",
    "nama",
    "jenis_kelamin",
    "tanggal_lahir",
    "alamat",
    "kelas",
    "tahun_ajaran"
], ";");

// Contoh data
fputcsv($output, [
    "0000012345",
    "Contoh Nama Siswa",
    "L",
    "2012-05-10",
    "Jl. Contoh No. 1",
    "7A",
    "2026/2027"
], ";");

// Panduan
fputcsv($output, [], ";");
fputcsv($output, ["# Panduan pengisian"], ";");
fputcsv($output, ["# nisn wajib angka dan tidak boleh sama dengan siswa lain"], ";");
fputcsv($output, ["# nama isi nama lengkap siswa"], ";");
fputcsv($output, ["# jenis_kelamin isi L untuk Laki-laki atau P untuk Perempuan"], ";");
fputcsv($output, ["# tanggal_lahir wajib format YYYY-MM-DD, contoh: 2012-05-10"], ";");
fputcsv($output, ["# alamat isi alamat siswa"], ";");
fputcsv($output, ["# kelas harus sama dengan salah satu nama kelas di bawah ini"], ";");
fputcsv($output, ["# tahun_ajaran harus sama dengan salah satu tahun ajaran di bawah ini"], ";");

fputcsv($output, [], ";");
fputcsv($output, ["# Daftar kelas valid:"], ";");

if (isset($conn) && !$conn->connect_error) {
    $queryKelas = mysqli_query($conn, "
        SELECT nama_kelas 
        FROM kelas 
        ORDER BY tingkat ASC, nama_kelas ASC
    ");

    if ($queryKelas) {
        while ($row = mysqli_fetch_assoc($queryKelas)) {
            fputcsv($output, ["# " . $row['nama_kelas']], ";");
        }
    }

    fputcsv($output, [], ";");
    fputcsv($output, ["# Daftar tahun ajaran valid:"], ";");

    $queryTahun = mysqli_query($conn, "
        SELECT tahun_ajaran 
        FROM tahun_ajaran 
        ORDER BY tahun_ajaran ASC
    ");

    if ($queryTahun) {
        while ($row = mysqli_fetch_assoc($queryTahun)) {
            fputcsv($output, ["# " . $row['tahun_ajaran']], ";");
        }
    }
}

fclose($output);
exit;
?>