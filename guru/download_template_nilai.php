<?php
require_once "koneksi.php";

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;
$mode = isset($_GET["mode"]) ? trim($_GET["mode"]) : "mapel";
$id_kelas = isset($_GET["id_kelas"]) ? (int) $_GET["id_kelas"] : 0;

if ($role_id !== 2 || $id_guru <= 0) {
    die("Akses tidak valid.");
}

/*
|--------------------------------------------------------------------------
| MODE 1 = GURU MAPEL BIASA
| - hanya 1 mapel sesuai guru login
|--------------------------------------------------------------------------
*/
if ($mode === "mapel") {
    $getGuru = $conn->prepare("
        SELECT 
            g.id_mapel,
            m.nama_mapel
        FROM guru g
        LEFT JOIN mapel m ON g.id_mapel = m.id_mapel
        WHERE g.id_guru = ?
        LIMIT 1
    ");

    if (!$getGuru) {
        die("Query guru gagal: " . $conn->error);
    }

    $getGuru->bind_param("i", $id_guru);
    $getGuru->execute();
    $resultGuru = $getGuru->get_result();

    if ($resultGuru->num_rows === 0) {
        die("Data guru tidak ditemukan.");
    }

    $guru = $resultGuru->fetch_assoc();

    $id_mapel = (int) $guru["id_mapel"];
    $nama_mapel = $guru["nama_mapel"] ?? "-";

    $getSiswa = $conn->prepare("
        SELECT 
            s.id_siswa,
            s.nama AS nama_siswa
        FROM siswa s
        ORDER BY s.nama ASC
    ");

    if (!$getSiswa) {
        die("Query siswa gagal: " . $conn->error);
    }

    $getSiswa->execute();
    $resultSiswa = $getSiswa->get_result();

    $filename = "template_import_nilai_mapel.csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen("php://output", "w");

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ["sep=,"]);

    fputcsv($output, [
        "id_siswa",
        "nama_siswa",
        "id_mapel",
        "nama_mapel",
        "semester",
        "nilai_angka",
        "hadir",
        "izin",
        "sakit",
        "alfa"
    ]);

    while ($siswa = $resultSiswa->fetch_assoc()) {
        fputcsv($output, [
            $siswa["id_siswa"],
            $siswa["nama_siswa"],
            $id_mapel,
            $nama_mapel,
            1,
            "",
            "",
            "",
            "",
            ""
        ]);
    }

    fclose($output);
    exit;
}

/*
|--------------------------------------------------------------------------
| MODE 2 = WALI KELAS
| - mapel dibuat MENYAMPING
| - 1 siswa = 1 baris
| - hanya untuk kelas wali yang sesuai guru login
|--------------------------------------------------------------------------
*/
if ($mode === "wali") {
    if ($id_kelas <= 0) {
        die("ID kelas tidak valid.");
    }

    $cekWali = $conn->prepare("
        SELECT 
            k.id_kelas,
            k.nama_kelas
        FROM kelas k
        WHERE k.id_kelas = ? AND k.id_wali_kelas = ?
        LIMIT 1
    ");

    if (!$cekWali) {
        die("Query wali kelas gagal: " . $conn->error);
    }

    $cekWali->bind_param("ii", $id_kelas, $id_guru);
    $cekWali->execute();
    $resultWali = $cekWali->get_result();

    if ($resultWali->num_rows === 0) {
        die("Anda bukan wali kelas untuk kelas ini.");
    }

    $kelas = $resultWali->fetch_assoc();

    $getMapel = $conn->prepare("
        SELECT 
            id_mapel,
            nama_mapel
        FROM mapel
        ORDER BY id_mapel ASC
    ");

    if (!$getMapel) {
        die("Query mapel gagal: " . $conn->error);
    }

    $getMapel->execute();
    $resultMapel = $getMapel->get_result();

    $daftarMapel = [];
    while ($mapel = $resultMapel->fetch_assoc()) {
        $daftarMapel[] = [
            "id_mapel" => (int) $mapel["id_mapel"],
            "nama_mapel" => $mapel["nama_mapel"]
        ];
    }

    $getSiswa = $conn->prepare("
        SELECT 
            s.id_siswa,
            s.nama AS nama_siswa
        FROM siswa s
        WHERE s.id_kelas = ?
        ORDER BY s.nama ASC
    ");

    if (!$getSiswa) {
        die("Query siswa kelas gagal: " . $conn->error);
    }

    $getSiswa->bind_param("i", $id_kelas);
    $getSiswa->execute();
    $resultSiswa = $getSiswa->get_result();

    $filename = "template_import_nilai_wali_" . preg_replace('/[^a-zA-Z0-9]/', '_', $kelas["nama_kelas"]) . ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen("php://output", "w");

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ["sep=,"]);

    $header = [
        "id_siswa",
        "nama_siswa",
        "semester"
    ];

    foreach ($daftarMapel as $mapel) {
        $header[] = $mapel["nama_mapel"];
    }

    $header[] = "hadir";
    $header[] = "izin";
    $header[] = "sakit";
    $header[] = "alfa";

    fputcsv($output, $header);

    while ($siswa = $resultSiswa->fetch_assoc()) {
        $row = [
            $siswa["id_siswa"],
            $siswa["nama_siswa"],
            1
        ];

        foreach ($daftarMapel as $mapel) {
            $row[] = "";
        }

        $row[] = "";
        $row[] = "";
        $row[] = "";
        $row[] = "";

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

die("Mode template tidak dikenali.");
?>