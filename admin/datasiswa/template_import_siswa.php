<?php
$filename = "template_import_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

// BOM supaya Excel baca UTF-8
echo "\xEF\xBB\xBF";

$output = fopen("php://output", "w");

// Pakai ; supaya Excel Indonesia kebaca per kolom
fputcsv($output, [
    "nisn",
    "nama",
    "jenis_kelamin",
    "kelas",
    "tahun_ajaran"
], ";");

fputcsv($output, [
    "0000012345",
    "Contoh Nama Siswa",
    "L",
    "7A",
    "2026/2027"
], ";");

fclose($output);
exit;
?>
