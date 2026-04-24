<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = "localhost";
$dbname = "osbebslk_sekolahyp";
$dbuser = "osbebslk_aliyahzz";
$dbpass = "semangatgaes";

$adminWa = "6283123500258"; // GANTI nomor WA admin
$baseUrl = "https://projectsekolahyp.my.id"; // GANTI kalau folder login kamu beda
$fromEmail = "noreply@projectsekolahyp.my.id"; // ini cuma email pengirim sistem

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

$role = trim($data["role"] ?? "");
$identifier = trim($data["identifier"] ?? "");

if ($role === "" || $identifier === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Jenis akun dan data akun wajib diisi."
    ]);
    exit;
}

/* =========================
   SISWA: USERNAME -> WA ADMIN
========================= */
if ($role === "siswa") {
    $stmt = $conn->prepare("
        SELECT 
            u.id_user,
            u.username,
            u.role_id,
            u.id_guru,
            u.id_siswa,
            s.nama AS nama_lengkap
        FROM `user` u
        LEFT JOIN siswa s ON u.id_siswa = s.id_siswa
        WHERE u.username = ?
        AND u.role_id = 3
        LIMIT 1
    ");

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Query siswa gagal disiapkan."
        ]);
        exit;
    }

    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Username siswa tidak ditemukan."
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

    $id_user = $user["id_user"];

    $cek->bind_param("i", $id_user);
    $cek->execute();
    $cek->store_result();

    $nama = $user["nama_lengkap"] ?: $user["username"];

    $pesan = "Halo Admin, saya ingin konfirmasi reset password SIAKAD.\n\n" .
             "Jenis Akun: SISWA\n" .
             "Username: " . $user["username"] . "\n" .
             "Nama: " . $nama . "\n\n" .
             "Mohon dibantu untuk reset password akun saya.";

    $wa_link = "https://wa.me/" . $adminWa . "?text=" . urlencode($pesan);

    if ($cek->num_rows > 0) {
        $cek->close();
        $conn->close();

        echo json_encode([
            "status" => "success",
            "message" => "Permintaan reset sandi sudah masuk. Silakan konfirmasi ke admin melalui WhatsApp.",
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
            "message" => "Query insert siswa gagal disiapkan."
        ]);
        exit;
    }

    $id_siswa = $user["id_siswa"];
    $id_guru = $user["id_guru"];
    $username = $user["username"];
    $role_id = $user["role_id"];

    $insert->bind_param(
        "iiisi",
        $id_user,
        $id_siswa,
        $id_guru,
        $username,
        $role_id
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
    exit;
}

/* =========================
   GURU: EMAIL -> LINK RESET KE EMAIL GURU
========================= */
if ($role === "guru") {
    $stmt = $conn->prepare("
        SELECT 
            u.id_user,
            u.username,
            u.role_id,
            u.id_guru,
            u.id_siswa,
            g.nama_guru AS nama_lengkap,
            g.email
        FROM `user` u
        INNER JOIN guru g ON u.id_guru = g.id_guru
        WHERE g.email = ?
        AND u.role_id = 2
        LIMIT 1
    ");

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Query guru gagal disiapkan."
        ]);
        exit;
    }

    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Email guru tidak ditemukan."
        ]);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    $token = bin2hex(random_bytes(32));
    $expired = date("Y-m-d H:i:s", strtotime("+30 minutes"));

    $id_user = $user["id_user"];

    $cek = $conn->prepare("
        SELECT id_request
        FROM password_reset_requests
        WHERE id_user = ?
        AND status = 'pending'
        LIMIT 1
    ");

    $cek->bind_param("i", $id_user);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $cek->bind_result($id_request);
        $cek->fetch();
        $cek->close();

        $update = $conn->prepare("
            UPDATE password_reset_requests
            SET reset_token = ?, token_expires_at = ?, created_at = CURRENT_TIMESTAMP
            WHERE id_request = ?
        ");

        $update->bind_param("ssi", $token, $expired, $id_request);

        if (!$update->execute()) {
            echo json_encode([
                "status" => "error",
                "message" => "Token reset gagal diperbarui."
            ]);
            exit;
        }

        $update->close();
    } else {
        $cek->close();

        $insert = $conn->prepare("
            INSERT INTO password_reset_requests
            (id_user, id_siswa, id_guru, username, role_id, status, reset_token, token_expires_at)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ");

        if (!$insert) {
            echo json_encode([
                "status" => "error",
                "message" => "Query insert guru gagal disiapkan."
            ]);
            exit;
        }

        $id_siswa = $user["id_siswa"];
        $id_guru = $user["id_guru"];
        $username = $user["username"];
        $role_id = $user["role_id"];

        $insert->bind_param(
            "iiisiss",
            $id_user,
            $id_siswa,
            $id_guru,
            $username,
            $role_id,
            $token,
            $expired
        );

        if (!$insert->execute()) {
            echo json_encode([
                "status" => "error",
                "message" => "Permintaan reset sandi guru gagal dibuat."
            ]);
            exit;
        }

        $insert->close();
    }

    $resetLink = $baseUrl . "/reset_password.html?token=" . urlencode($token);

    $to = $user["email"];
    $subject = "Reset Password SIAKAD";
    $nama = $user["nama_lengkap"] ?: $user["username"];

    $message = "Halo Bapak/Ibu " . $nama . ",\n\n" .
               "Kami menerima permintaan reset password akun SIAKAD Anda.\n\n" .
               "Silakan klik link berikut untuk mengganti password:\n" .
               $resetLink . "\n\n" .
               "Link ini berlaku selama 30 menit.\n\n" .
               "Jika Anda tidak meminta reset password, abaikan email ini.\n\n" .
               "Terima kasih.";

    $headers = "From: SIAKAD <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = mail($to, $subject, $message, $headers);

    if (!$sent) {
        echo json_encode([
            "status" => "error",
            "message" => "Link reset sudah dibuat, tapi email gagal dikirim. Coba hubungi admin."
        ]);
        exit;
    }

    $conn->close();

    echo json_encode([
        "status" => "success",
        "message" => "Link reset password sudah dikirim ke email guru."
    ]);
    exit;
}

echo json_encode([
    "status" => "error",
    "message" => "Jenis akun tidak valid."
]);
?>