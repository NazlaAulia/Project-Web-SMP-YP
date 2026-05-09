<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'koneksi.php';

function respon($status, $message, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    respon("error", "Koneksi database gagal.");
}

date_default_timezone_set('Asia/Jakarta');

$hariInggris = date('l');

$hariIndonesia = [
    "Monday" => "Senin",
    "Tuesday" => "Selasa",
    "Wednesday" => "Rabu",
    "Thursday" => "Kamis",
    "Friday" => "Jumat",
    "Saturday" => "Sabtu",
    "Sunday" => "Minggu"
];

$hariIni = $hariIndonesia[$hariInggris] ?? "";

/*
  Utama: ambil id_guru dari session.
  Kalau session kamu beda namanya, sesuaikan bagian ini.
*/
$id_guru = $_SESSION['id_guru'] ?? null;

/*
  Cadangan untuk testing:
  contoh buka: get_jadwal_hari_ini.php?id_guru=4
*/
if (!$id_guru && isset($_GET['id_guru'])) {
    $id_guru = $_GET['id_guru'];
}

if (!$id_guru) {
    respon("error", "ID guru tidak ditemukan. Pastikan guru sudah login.");
}

$sql = "
    SELECT 
        j.id_jadwal,
        j.hari,
        j.jam,
        j.jp_mulai,
        j.jp_selesai,
        j.jumlah_jp,
        k.nama_kelas,
        m.nama_mapel,
        g.nama AS nama_guru
    FROM jadwal j
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN tahun_ajaran ta ON j.id_tahun_ajaran = ta.id_tahun_ajaran
    WHERE j.id_guru = ?
      AND j.hari = ?
      AND (ta.status = 'aktif' OR ta.status IS NULL)
    ORDER BY j.jp_mulai ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    respon("error", "Query gagal disiapkan: " . $conn->error);
}

$stmt->bind_param("is", $id_guru, $hariIni);
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

respon("success", "Jadwal mengajar hari ini berhasil diambil.", [
    "hari" => $hariIni,
    "jadwal" => $data
]);