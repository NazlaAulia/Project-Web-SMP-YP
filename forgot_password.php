<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$adminWa = "6283123500258"; // GANTI nomor WA admin

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal."
    ]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Permintaan tidak valid."
    ]);
    exit;
}

$username = trim($data["username"] ?? "");

if ($username === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Username wajib diisi."
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        u.id_user,
        u.username,
        u.role_id,
        u.id_guru,
        u.id_siswa,
        s.nama AS nama_siswa
    FROM `user` u
    LEFT JOIN siswa s ON u.id_siswa = s.id_siswa
    WHERE u.username = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Query user gagal disiapkan."
    ]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Username tidak ditemukan."
    ]);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

$cek = $conn->prepare("
    SELECT id_request
    FROM password_reset_requests
    WHERE id_user = ?
    AND status = 'pending'
    LIMIT 1
");

if (!$cek) {
    echo json_encode([
        "status" => "error",
        "message" => "Query cek reset gagal disiapkan."
    ]);
    exit;
}

$cek->bind_param("i", $user["id_user"]);
$cek->execute();
$cek->store_result();

$nama = $user["nama_siswa"] ?: $user["username"];

$pesan = "Halo Admin, saya ingin konfirmasi reset password SIAKAD.%0A%0A" .
         "Username: " . $user["username"] . "%0A" .
         "Nama: " . $nama . "%0A" .
         "Role ID: " . $user["role_id"] . "%0A%0A" .
         "Mohon dibantu untuk reset password akun saya.";

$wa_link = "https://wa.me/" . $adminWa . "?text=" . $pesan;

if ($cek->num_rows > 0) {
    $cek->close();
    $conn->close();

    echo json_encode([
        "status" => "success",
        "message" => "Permintaan reset sandi kamu sudah masuk. Silakan konfirmasi ke admin melalui WhatsApp.",
        "wa_link" => $wa_link
    ]);
    exit;
}

$cek->close();

$insert = $conn->prepare("
    INSERT INTO password_reset_requests
    (id_user, id_siswa, id_guru, username, role_id, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");

if (!$insert) {
    echo json_encode([
        "status" => "error",
        "message" => "Query insert reset gagal disiapkan."
    ]);
    exit;
}

$id_siswa = $user["id_siswa"];
$id_guru = $user["id_guru"];

$insert->bind_param(
    "iiisi",
    $user["id_user"],
    $id_siswa,
    $id_guru,
    $user["username"],
    $user["role_id"]
);

if (!$insert->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Permintaan reset sandi gagal dikirim."
    ]);
    exit;
}

$insert->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "message" => "Permintaan reset sandi berhasil dikirim. Silakan konfirmasi ke admin melalui WhatsApp.",
    "wa_link" => $wa_link
]);
?>