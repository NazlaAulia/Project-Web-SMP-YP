<?php
/* session_start();
header("Content-Type: application/json; charset=utf-8");
require_once "koneksi.php";

if (!isset($_SESSION["id_user"]) || !isset($_SESSION["id_guru"])) {
    echo json_encode([
        "status" => "error",
        "message" => "Session guru tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$id_user = $_SESSION["id_user"];
$id_guru = $_SESSION["id_guru"];

$sql = "
    SELECT 
        u.id_user,
        u.username,
        u.foto_profil,
        g.id_guru,
        g.nip,
        g.nama,
        g.email,
        g.id_mapel,
        m.nama_mapel
    FROM user u
    LEFT JOIN guru g ON u.id_guru = g.id_guru
    LEFT JOIN mapel m ON g.id_mapel = m.id_mapel
    WHERE u.id_user = ?
      AND u.id_guru = ?
      AND u.role_id = 2
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Query gagal: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "ii", $id_user, $id_guru);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal mengambil data guru."
    ]);
    exit;
}

$guru = mysqli_fetch_assoc($result);

if (!$guru) {
    echo json_encode([
        "status" => "error",
        "message" => "Data guru tidak ditemukan."
    ]);
    exit;
}

if (empty($guru["foto_profil"])) {
    $guru["foto_profil"] = "../img/default-profile.webp";
}

echo json_encode([
    "status" => "success",
    "data" => $guru
]);
exit;*/
?>