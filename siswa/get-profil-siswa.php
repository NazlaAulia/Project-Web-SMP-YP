<?php
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = isset($_GET['id_siswa']) ? (int) $_GET['id_siswa'] : 0;

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak valid."
    ]);
    exit;
}

$query = "
    SELECT 
        s.nama,
        s.nis,
        s.nisn,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        s.id_kelas,
        k.nama_kelas AS kelas,
        p.email,
        p.no_hp,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN pendaftaran p ON s.id_pendaftaran = p.id_pendaftaran
    LEFT JOIN user u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = $id_siswa
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);
?>