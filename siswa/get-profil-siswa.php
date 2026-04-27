<?php
session_start();
header("Content-Type: application/json");
require_once "koneksi.php";

$id_siswa = 0;
$id_user = 0;
$username = "";

/* dari URL / JS */
if (isset($_GET['id_siswa']) && is_numeric($_GET['id_siswa'])) {
    $id_siswa = (int) $_GET['id_siswa'];
}

if (isset($_GET['id_user']) && is_numeric($_GET['id_user'])) {
    $id_user = (int) $_GET['id_user'];
}

if (isset($_GET['username']) && trim($_GET['username']) !== "") {
    $username = trim($_GET['username']);
}

/* dari session umum */
$sessionIdSiswaKeys = ['id_siswa', 'siswa_id'];
$sessionIdUserKeys = ['id_user', 'user_id', 'id'];
$sessionUsernameKeys = ['username', 'user_username', 'nama_user'];

foreach ($sessionIdSiswaKeys as $key) {
    if ($id_siswa <= 0 && isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
        $id_siswa = (int) $_SESSION[$key];
    }
}

foreach ($sessionIdUserKeys as $key) {
    if ($id_user <= 0 && isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
        $id_user = (int) $_SESSION[$key];
    }
}

foreach ($sessionUsernameKeys as $key) {
    if ($username === "" && isset($_SESSION[$key]) && trim($_SESSION[$key]) !== "") {
        $username = trim($_SESSION[$key]);
    }
}

/* kalau session bentuk array */
foreach ($_SESSION as $value) {
    if (is_array($value)) {
        if ($id_siswa <= 0 && isset($value['id_siswa']) && is_numeric($value['id_siswa'])) {
            $id_siswa = (int) $value['id_siswa'];
        }

        if ($id_user <= 0 && isset($value['id_user']) && is_numeric($value['id_user'])) {
            $id_user = (int) $value['id_user'];
        }

        if ($username === "" && isset($value['username']) && trim($value['username']) !== "") {
            $username = trim($value['username']);
        }
    }
}

/* kalau ada id_user, cari id_siswa */
if ($id_siswa <= 0 && $id_user > 0) {
    $stmtUser = $conn->prepare("SELECT id_siswa, username FROM `user` WHERE id_user = ? LIMIT 1");

    if ($stmtUser) {
        $stmtUser->bind_param("i", $id_user);
        $stmtUser->execute();
        $stmtUser->store_result();

        if ($stmtUser->num_rows > 0) {
            $stmtUser->bind_result($hasil_id_siswa, $hasil_username);
            $stmtUser->fetch();

            if (!empty($hasil_id_siswa)) {
                $id_siswa = (int) $hasil_id_siswa;
            }

            if ($username === "" && !empty($hasil_username)) {
                $username = $hasil_username;
            }
        }

        $stmtUser->close();
    }
}

/* kalau ada username, cari id_siswa */
if ($id_siswa <= 0 && $username !== "") {
    $stmtUser = $conn->prepare("SELECT id_siswa FROM `user` WHERE username = ? LIMIT 1");

    if ($stmtUser) {
        $stmtUser->bind_param("s", $username);
        $stmtUser->execute();
        $stmtUser->store_result();

        if ($stmtUser->num_rows > 0) {
            $stmtUser->bind_result($hasil_id_siswa);
            $stmtUser->fetch();

            if (!empty($hasil_id_siswa)) {
                $id_siswa = (int) $hasil_id_siswa;
            }
        }

        $stmtUser->close();
    }
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Login tidak mengirim id_siswa/id_user/username ke profil."
    ]);
    exit;
}

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
        k.nama_kelas,
        p.email,
        p.no_hp,
        u.username,
        u.foto_profil
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN pendaftaran p ON s.id_pendaftaran = p.id_pendaftaran
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
    $nama_kelas,
    $email,
    $no_hp,
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
        "kelas" => $nama_kelas,
        "email" => $email,
        "no_hp" => $no_hp,
        "username" => $db_username,
        "foto_profil" => $foto_profil
    ]
]);
?>