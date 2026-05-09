<?php
$filename = "template_import_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

fputcsv($output, [
    "nisn",
    "nama",
    "jenis_kelamin",
    "tanggal_lahir",
    "alamat",
    "kelas",
    "tahun_ajaran"
], ";");

fputcsv($output, [
    "0000012345",
    "Contoh Nama Siswa",
    "L",
    "2012-05-10",
    "Jl. Contoh No. 1",
    "7A",
    "2026/2027"
], ";");

fclose($output);
exit;
?>
