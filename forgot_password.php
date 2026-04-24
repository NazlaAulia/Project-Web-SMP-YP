<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));

    exit;
}

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    kirim_json("error", "Permintaan tidak valid.");
}

$username = trim($data["username"] ?? "");

if ($username === "") {
    kirim_json("error", "Username wajib diisi.");
}

$stmt = $conn->prepare("
    SELECT 
        u.id_user,
        u.username,
        u.role_id,
        u.id_guru,
        u.id_siswa,
        g.nama AS nama_guru,
        g.email AS email_guru,
        s.nama AS nama_siswa
    FROM `user` u
    LEFT JOIN guru g ON u.id_guru = g.id_guru
    LEFT JOIN siswa s ON u.id_siswa = s.id_siswa
    WHERE u.username = ?
    LIMIT 1
");

if (!$stmt) {
    kirim_json("error", "Query user gagal: " . $conn->error);
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    kirim_json("error", "Request gagal diproses.");
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    kirim_json("error", "Username tidak ditemukan.");
}

$user = $result->fetch_assoc();
$stmt->close();

$role_id = (int)$user["role_id"];

/*
    role_id:
    1 = admin
    2 = guru
    3 = siswa
*/

/* =========================
   SISWA: KONFIRMASI VIA WA ADMIN
========================= */
if ($role_id === 3) {
    $nomor_admin = "6283123500258"; // GANTI NOMOR ADMIN, pakai 62 bukan 08

    $nama_siswa = $user["nama_siswa"] ?: "-";
    $id_siswa = $user["id_siswa"] ?: "-";

    $pesan = "Halo Admin, saya ingin konfirmasi lupa password akun siswa.%0A%0A"
        . "Username: " . rawurlencode($user["username"]) . "%0A"
        . "Nama Siswa: " . rawurlencode($nama_siswa) . "%0A"
        . "ID Siswa: " . rawurlencode($id_siswa) . "%0A%0A"
        . "Mohon bantu reset password saya.";

    $wa_link = "https://wa.me/" . $nomor_admin . "?text=" . $pesan;

    $insert = $conn->prepare("
        INSERT INTO password_reset_requests
        (id_user, id_siswa, id_guru, username, role_id, status, created_at)
        VALUES (?, ?, NULL, ?, ?, 'pending', NOW())
    ");

    if ($insert) {
        $insert->bind_param(
            "iisi",
            $user["id_user"],
            $user["id_siswa"],
            $user["username"],
            $role_id
        );
        $insert->execute();
        $insert->close();
    }

    kirim_json("success", "Akun siswa harus konfirmasi ke admin melalui WhatsApp.", [
        "tipe" => "siswa",
        "wa_link" => $wa_link
    ]);
}

/* =========================
   GURU: RESET VIA EMAIL
========================= */
if ($role_id === 2) {
    if (empty($user["id_guru"])) {
        kirim_json("error", "Data guru tidak ditemukan.");
    }

    if (empty($user["email_guru"])) {
        kirim_json("error", "Email guru belum terdaftar. Silakan hubungi admin.");
    }

    $token = bin2hex(random_bytes(32));
    $expired = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    $insert = $conn->prepare("
        INSERT INTO password_reset_requests
        (id_user, id_siswa, id_guru, username, role_id, reset_token, token_expires_at, status, created_at)
        VALUES (?, NULL, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    if (!$insert) {
        kirim_json("error", "Gagal membuat request reset password: " . $conn->error);
    }

    $insert->bind_param(
        "iisiss",
        $user["id_user"],
        $user["id_guru"],
        $user["username"],
        $role_id,
        $token,
        $expired
    );

    if (!$insert->execute()) {
        kirim_json("error", "Request reset password gagal disimpan.");
    }

    $insert->close();

  $base_url = "https://projectsekolahyp.my.id";// GANTI dengan domain website kamu
    $reset_link = $base_url . "/reset_password.html?token=" . urlencode($token);

    $to = $user["email_guru"];
    $subject = "Reset Password SIAKAD SMP YP 17";

    $message = "Halo " . ($user["nama_guru"] ?: "Bapak/Ibu Guru") . ",\n\n";
    $message .= "Kami menerima permintaan reset password akun guru SIAKAD.\n\n";
    $message .= "Klik link berikut untuk mengganti password:\n";
    $message .= $reset_link . "\n\n";
    $message .= "Link ini berlaku selama 30 menit.\n\n";
    $message .= "Jika kamu tidak meminta reset password, abaikan email ini.\n\n";
    $message .= "Terima kasih.";
    
$headers = "From: osbebslk@projectsekolahyp.my.id\r\n";
$headers .= "Reply-To: osbebslk@projectsekolahyp.my.id\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (!mail($to, $subject, $message, $headers)) {
        kirim_json("error", "Email reset gagal dikirim. Cek fitur mail di hosting.");
    }

    kirim_json("success", "Link reset password sudah dikirim ke email guru.", [
        "tipe" => "guru"
    ]);
}

/* =========================
   ADMIN
========================= */
if ($role_id === 1) {
    kirim_json("error", "Reset password admin harus melalui pengelola sistem.");
}

kirim_json("error", "Role akun tidak valid.");