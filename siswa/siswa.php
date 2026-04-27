<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]);
    exit;
}

$id_siswa = 0;

/*
  Ambil id_siswa dari URL kalau ada
*/
if (isset($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
}

/*
  Kalau URL kosong, ambil dari session id_siswa
*/
if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int)$_SESSION['id_siswa'];
}

/*
  Kalau session id_siswa kosong, ambil dari session id_user
  lalu cari id_siswa di tabel user
*/
if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int)$_SESSION['id_user'];

    $sqlUser = "SELECT id_siswa FROM user WHERE id_user = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);

    if (!$stmtUser) {
        echo json_encode([
            "status" => "error",
            "message" => "Prepare user gagal: " . $conn->error
        ]);
        exit;
    }

    $stmtUser->bind_param("i", $id_user);

    if (!$stmtUser->execute()) {
        echo json_encode([
            "status" => "error",
            "message" => "Execute user gagal: " . $stmtUser->error
        ]);
        exit;
    }

    $stmtUser->store_result();

    if ($stmtUser->num_rows > 0) {
        $stmtUser->bind_result($hasil_id_siswa);
        $stmtUser->fetch();

        if (!empty($hasil_id_siswa)) {
            $id_siswa = (int)$hasil_id_siswa;
            $_SESSION['id_siswa'] = $id_siswa;
        }
    }

    $stmtUser->close();
}

if ($id_siswa <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "id_siswa tidak valid. Session login belum menyimpan id_siswa."
    ]);
    exit;
}

/*
  Ambil data siswa + kelas
*/
$sql = "SELECT s.id_siswa, s.nama, s.id_kelas, k.nama_kelas
        FROM siswa s
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE s.id_siswa = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Execute gagal: " . $stmt->error
    ]);
    exit;
}

$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Data siswa tidak ditemukan"
    ]);
    exit;
}

$stmt->bind_result($db_id_siswa, $nama, $id_kelas, $nama_kelas);
$stmt->fetch();
$stmt->close();

/*
  Tentukan hari ini dalam Bahasa Indonesia
*/
$hariInggris = date("l");

$namaHari = [
    "Monday" => "Senin",
    "Tuesday" => "Selasa",
    "Wednesday" => "Rabu",
    "Thursday" => "Kamis",
    "Friday" => "Jumat",
    "Saturday" => "Sabtu",
    "Sunday" => "Minggu"
];

$hariIni = $namaHari[$hariInggris] ?? "";

/*
  Ambil jadwal berdasarkan kelas dan hari ini
*/
$jadwal = [];

$sqlJadwal = "SELECT 
                j.id_jadwal,
                j.hari,
                j.jam,
                m.nama_mapel,
                g.nama AS nama_guru
              FROM jadwal j
              LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
              LEFT JOIN guru g ON j.id_guru = g.id_guru
              WHERE j.id_kelas = ?
              AND j.hari = ?
              ORDER BY j.jam ASC";

$stmtJadwal = $conn->prepare($sqlJadwal);

if (!$stmtJadwal) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare jadwal gagal: " . $conn->error
    ]);
    exit;
}

$stmtJadwal->bind_param("is", $id_kelas, $hariIni);

if (!$stmtJadwal->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Execute jadwal gagal: " . $stmtJadwal->error
    ]);
    exit;
}

$resultJadwal = $stmtJadwal->get_result();

while ($row = $resultJadwal->fetch_assoc()) {
    $jadwal[] = $row;
}

$stmtJadwal->close();

/*
  Ambil nilai akademik siswa
*/
$nilai = [];

$sqlNilai = "SELECT 
                m.nama_mapel,
                n.nilai_angka
             FROM nilai n
             LEFT JOIN mapel m ON n.id_mapel = m.id_mapel
             WHERE n.id_siswa = ?
             AND n.semester = (
                SELECT MAX(semester)
                FROM nilai
                WHERE id_siswa = ?
             )
             ORDER BY n.id_mapel ASC";

$stmtNilai = $conn->prepare($sqlNilai);

if (!$stmtNilai) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare nilai gagal: " . $conn->error
    ]);
    exit;
}

$stmtNilai->bind_param("ii", $id_siswa, $id_siswa);

if (!$stmtNilai->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Execute nilai gagal: " . $stmtNilai->error
    ]);
    exit;
}

$resultNilai = $stmtNilai->get_result();

while ($row = $resultNilai->fetch_assoc()) {
    $nilai[] = $row;
}

$stmtNilai->close();

echo json_encode([
    "status" => "success",
    "data" => [
        "id_siswa" => $db_id_siswa,
        "nama" => $nama,
        "id_kelas" => $id_kelas,
        "nama_kelas" => $nama_kelas,
        "hari_ini" => $hariIni,
        "jadwal_hari_ini" => $jadwal,
        "nilai_akademik" => $nilai
    ]
]);

$conn->close();
?>