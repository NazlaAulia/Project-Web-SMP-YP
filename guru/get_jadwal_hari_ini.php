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
    Ambil id_guru dari banyak kemungkinan:
    1. session id_guru
    2. session guru_id
    3. GET id_guru dari JavaScript
    4. username dari session/GET lalu dicari ke tabel user
    5. nama guru dari GET lalu dicari ke tabel guru
*/

$id_guru = null;

if (isset($_SESSION['id_guru'])) {
    $id_guru = $_SESSION['id_guru'];
} elseif (isset($_SESSION['guru_id'])) {
    $id_guru = $_SESSION['guru_id'];
} elseif (isset($_SESSION['user']['id_guru'])) {
    $id_guru = $_SESSION['user']['id_guru'];
} elseif (isset($_GET['id_guru']) && $_GET['id_guru'] !== '') {
    $id_guru = $_GET['id_guru'];
}

$username = $_GET['username'] ?? ($_SESSION['username'] ?? ($_SESSION['user']['username'] ?? null));
$namaGuru = $_GET['nama'] ?? null;

/* Kalau id_guru belum ketemu, cari lewat username di tabel user */
if (!$id_guru && $username) {
    $sqlUser = "SELECT id_guru FROM user WHERE username = ? AND id_guru IS NOT NULL LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);

    if ($stmtUser) {
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($rowUser = $resultUser->fetch_assoc()) {
            $id_guru = $rowUser['id_guru'];
        }
    }
}

/* Kalau masih belum ketemu, cari lewat nama guru */
if (!$id_guru && $namaGuru) {
    $namaGuru = trim($namaGuru);

    if ($namaGuru !== "" && strtolower($namaGuru) !== "profil guru") {
        $sqlGuru = "SELECT id_guru FROM guru WHERE nama = ? LIMIT 1";
        $stmtGuru = $conn->prepare($sqlGuru);

        if ($stmtGuru) {
            $stmtGuru->bind_param("s", $namaGuru);
            $stmtGuru->execute();
            $resultGuru = $stmtGuru->get_result();

            if ($rowGuru = $resultGuru->fetch_assoc()) {
                $id_guru = $rowGuru['id_guru'];
            }
        }
    }
}

if (!$id_guru) {
    respon("error", "ID guru tidak ditemukan. Pastikan data login guru membawa id_guru.");
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
    "id_guru" => $id_guru,
    "hari" => $hariIni,
    "jadwal" => $data
]);