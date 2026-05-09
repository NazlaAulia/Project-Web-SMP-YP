<?php
$filename = "template_import_siswa_kelas_7_dummy.csv";

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

fputcsv($output, ["nisn", "nama", "jenis_kelamin", "kelas", "tahun_ajaran"]);

$namaDepan = [
    "Aditya", "Aisyah", "Akbar", "Almira", "Ananda", "Aqila", "Ardi", "Aurel", "Bagas", "Bella",
    "Cahya", "Citra", "Dafa", "Dinda", "Eka", "Elvina", "Fahri", "Farah", "Gilang", "Hana",
    "Ilham", "Intan", "Jihan", "Kevin", "Laila", "Malik", "Nabila", "Rafi", "Salsa", "Zidan"
];

$namaBelakang = [
    "Pratama", "Putri", "Saputra", "Lestari", "Ramadhan", "Permata", "Wijaya", "Azzahra", "Firmansyah", "Safitri",
    "Maulana", "Rahmawati", "Kurniawan", "Puspita", "Nugroho", "Febriani", "Hidayat", "Anggraini", "Setiawan", "Amelia",
    "Wicaksono", "Nuraini", "Fadilah", "Syahputra", "Damayanti", "Hakim", "Maharani", "Santoso", "Melati", "Yusuf"
];

$kelasList = ["7A", "7B", "7C"];
$counter = 1;

foreach ($kelasList as $kelas) {
    for ($i = 0; $i < 30; $i++) {
        $nisn = "73" . str_pad((string)$counter, 8, "0", STR_PAD_LEFT);
        $nama = $namaDepan[($counter - 1) % count($namaDepan)] . " " . $namaBelakang[(($counter * 7) - 1) % count($namaBelakang)];
        $jenisKelamin = $counter % 2 === 0 ? "P" : "L";

        fputcsv($output, [
            $nisn,
            $nama,
            $jenisKelamin,
            $kelas,
            "2026/2027"
        ]);

        $counter++;
    }
}

fclose($output);
exit;
?>
