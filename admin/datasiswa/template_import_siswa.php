<?php
$filename = "template_import_siswa.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

fputcsv($output, [
    "nisn",
    "nama",
    "jenis_kelamin",
    "kelas",
    "tahun_ajaran"
]);

fclose($output);
exit;
?>
