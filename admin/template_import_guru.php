<?php
require_once 'koneksi.php';

$filename = "template_import_guru.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

fputcsv($output, [
    "nip",
    "nama",
    "email",
    "jenis_kelamin",
    "mapel"
], ";");

fputcsv($output, [
    "1987654321",
    "Contoh Nama Guru",
    "contoh.guru@email.com",
    "L",
    "IPS"
], ";");

fputcsv($output, [], ";");
fputcsv($output, ["# Panduan pengisian"], ";");
fputcsv($output, ["# jenis_kelamin isi L untuk Laki-laki atau P untuk Perempuan"], ";");
fputcsv($output, ["# Kolom mapel harus sama dengan salah satu nama mapel di bawah ini"], ";");
fputcsv($output, ["# Daftar mapel valid:"], ";");

if (isset($conn) && !$conn->connect_error) {
    $query = mysqli_query($conn, "SELECT nama_mapel FROM mapel ORDER BY nama_mapel ASC");

    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            fputcsv($output, ["# " . $row['nama_mapel']], ";");
        }
    }
}

fclose($output);
exit;
?>
