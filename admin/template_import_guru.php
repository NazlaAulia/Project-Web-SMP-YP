<?php
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
    "Matematika"
], ";");

fclose($output);
exit;
?>
