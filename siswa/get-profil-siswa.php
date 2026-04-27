<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = 0;
$username = "";

/* Ambil dari GET kalau JS ngirim */
if (isset($_GET['id_siswa']) && is_numeric($_GET['id_siswa'])) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if (isset($_GET['username']) && trim($_GET['username']) !== "") {
    $username = trim($_GET['username']);
}

/* Ambil dari session kalau login sudah nyimpen */
if ($id_siswa <= 0 && isset($_SESSION['id_siswa'])) {
    $id_siswa = (int) $_SESSION['id_siswa'];
}

if ($username === "" && isset($_SESSION['username'])) {
    $username = trim($_SESSION['username']);
}

/* Kalau ada id_user di session, cari id_siswa dari tabel user */
if ($id_siswa <= 0 && isset($_SESSION['id_user'])) {
    $id_user = (int) $_SESSION['id_user'];

    $sqlUser = "SELECT id_siswa, username FROM `user` WHERE id_user = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);

    if ($stmtUser) {
        $stmtUser->bind_param("i", $id_user);
        $stmtUser->execute();
        $stmtUser->store_result();

        if ($stmtUser->num_rows > 0) {
            $stmtUser->bind_result($hasil_id_siswa, $hasil_username);
            $stmtUser->fetch();

            if (!empty($hasil_id_siswa)) {
                $id_siswa = (int) $hasil_id_siswa;
                $_SESSION['id_siswa'] = $id_siswa;
            }

            if ($username === "" && !empty($hasil_username)) {
                $username = $hasil_username;
                $_SESSION['username'] = $username;
            }
        }

        $stmtUser->close();
    }
}

/* Kalau id_siswa belum ada tapi username ada, cari id_siswa dari tabel user */
if ($id_siswa <= 0 && $username !== "") {
    $sqlUser = "SELECT id_siswa FROM `user` WHERE username = ? LIMIT 1";
    $stmtUser = $conn->prepare($sqlUser);

    if ($stmtUser) {
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $stmtUser->store_result();

        if ($stmtUser->num_rows > 0) {
            $stmtUser->bind_result($hasil_id_siswa);
            $stmtUser->fetch();

            if (!empty($hasil_id_siswa)) {
                $id_siswa = (int) $hasil_id_siswa;
                $_SESSION['id_siswa'] = $id_siswa;
            }
        }

        $stmtUser->close();
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Profil tidak bisa tahu siswa yang login siapa."
    ]);
    exit;
}

/* Ambil data dari tabel siswa */
$sql = "
    SELECT 
        s.id_siswa,
        s.nis,
        s.nisn,
        s.nama,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        s.id_kelas,
        k.nama_kelas AS kelas,
        u.username,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN `user` u ON s.id_siswa = u.id_siswa
    WHERE s.id_siswa = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare gagal: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

$stmt->bind_result(
    $db_id_siswa,
    $nis,
    $nisn,
    $nama,
    $jenis_kelamin,
    $tanggal_lahir,
    $alamat,
    $id_kelas,
    $kelas,
    $db_username,
    $foto_profil
);

$stmt->fetch();
$stmt->close();

$_SESSION['id_siswa'] = $db_id_siswa;

if (!empty($db_username)) {
    $_SESSION['username'] = $db_username;
}

echo json_encode([
    "success" => true,
    "data" => [
        "id_siswa" => $db_id_siswa,
        "nis" => $nis,
        "nisn" => $nisn,
        "nama" => $nama,
        "jenis_kelamin" => $jenis_kelamin,
        "tanggal_lahir" => $tanggal_lahir,
        "alamat" => $alamat,
        "id_kelas" => $id_kelas,
        "kelas" => $kelas,
        "username" => $db_username,
        "foto_profil" => $foto_profil
    ]
]);
?>