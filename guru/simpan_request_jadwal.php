<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    kirim_json("error", "Data request tidak valid.");
}

$id_guru = isset($data["id_guru"]) ? (int)$data["id_guru"] : 0;
$id_jadwal = isset($data["id_jadwal"]) ? (int)$data["id_jadwal"] : 0;
$id_kelas = isset($data["id_kelas"]) ? (int)$data["id_kelas"] : 0;

$hari_baru = trim($data["hari_baru"] ?? "");
$jam_baru = trim($data["jam_baru"] ?? "");

$jp_mulai_baru = isset($data["jp_mulai_baru"]) ? (int)$data["jp_mulai_baru"] : 0;
$jp_selesai_baru = isset($data["jp_selesai_baru"]) ? (int)$data["jp_selesai_baru"] : 0;
$jumlah_jp_baru = isset($data["jumlah_jp_baru"]) ? (int)$data["jumlah_jp_baru"] : 1;

$alasan = trim($data["alasan"] ?? "");
$pesan_ai = trim($data["pesan_ai"] ?? "");

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid. Silakan login ulang.");
}

if ($id_jadwal <= 0) {
    kirim_json("error", "Jadwal lama tidak valid.");
}

if ($id_kelas <= 0) {
    kirim_json("error", "ID kelas tidak valid.");
}

if ($hari_baru === "" || $jam_baru === "") {
    kirim_json("error", "Hari dan jam baru wajib dipilih.");
}

if ($alasan === "") {
    kirim_json("error", "Alasan ganti jadwal wajib diisi.");
}

if ($jp_mulai_baru <= 0 || $jp_selesai_baru <= 0 || $jumlah_jp_baru <= 0) {
    kirim_json("error", "Data JP jadwal baru tidak valid.");
}

// Pastikan jadwal lama benar-benar milik guru yang login
$stmtJadwal = $conn->prepare("
    SELECT 
        id_jadwal,
        id_guru,
        id_kelas,
        id_mapel,
        hari,
        jam,
        jp_mulai,
        jp_selesai,
        jumlah_jp
    FROM jadwal
    WHERE id_jadwal = ?
      AND id_guru = ?
    LIMIT 1
");

if (!$stmtJadwal) {
    kirim_json("error", "Query cek jadwal gagal: " . $conn->error);
}

$stmtJadwal->bind_param("ii", $id_jadwal, $id_guru);
$stmtJadwal->execute();
$resultJadwal = $stmtJadwal->get_result();

if ($resultJadwal->num_rows === 0) {
    kirim_json("error", "Jadwal tidak ditemukan atau bukan milik guru ini.");
}

$jadwal_lama = $resultJadwal->fetch_assoc();
$stmtJadwal->close();

$jumlah_jp_lama = (int)($jadwal_lama["jumlah_jp"] ?? 1);

if ($jumlah_jp_baru !== $jumlah_jp_lama) {
    kirim_json("error", "Jumlah JP jadwal baru harus sama dengan jadwal lama.");
}

// Cek request menunggu untuk jadwal yang sama
$stmtCekRequest = $conn->prepare("
    SELECT id_request
    FROM request_jadwal
    WHERE id_jadwal = ?
      AND id_guru = ?
      AND status = 'menunggu'
    LIMIT 1
");

if (!$stmtCekRequest) {
    kirim_json("error", "Query cek request gagal: " . $conn->error);
}

$stmtCekRequest->bind_param("ii", $id_jadwal, $id_guru);
$stmtCekRequest->execute();
$resultCekRequest = $stmtCekRequest->get_result();

if ($resultCekRequest->num_rows > 0) {
    kirim_json("error", "Jadwal ini masih memiliki pengajuan yang menunggu persetujuan admin.");
}

$stmtCekRequest->close();

// Cek bentrok guru / kelas berdasarkan JP
$stmtBentrok = $conn->prepare("
    SELECT id_jadwal
    FROM jadwal
    WHERE hari = ?
      AND id_jadwal != ?
      AND (id_guru = ? OR id_kelas = ?)
      AND (
          jp_mulai <= ?
          AND jp_selesai >= ?
      )
    LIMIT 1
");

if (!$stmtBentrok) {
    kirim_json("error", "Query cek bentrok gagal: " . $conn->error);
}

$stmtBentrok->bind_param(
    "siiiii",
    $hari_baru,
    $id_jadwal,
    $id_guru,
    $id_kelas,
    $jp_selesai_baru,
    $jp_mulai_baru
);

$stmtBentrok->execute();
$resultBentrok = $stmtBentrok->get_result();

if ($resultBentrok->num_rows > 0) {
    kirim_json("error", "Slot jadwal baru bentrok dengan jadwal guru atau kelas.");
}

$stmtBentrok->close();

// Simpan request
$stmtInsert = $conn->prepare("
    INSERT INTO request_jadwal
    (
        id_guru,
        id_kelas,
        id_jadwal,
        hari_baru,
        jam_baru,
        jp_mulai_baru,
        jp_selesai_baru,
        jumlah_jp_baru,
        alasan,
        pesan_ai,
        status,
        tanggal_request
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', NOW())
");

if (!$stmtInsert) {
    kirim_json("error", "Query simpan request gagal: " . $conn->error);
}

$stmtInsert->bind_param(
    "iiissiiiss",
    $id_guru,
    $id_kelas,
    $id_jadwal,
    $hari_baru,
    $jam_baru,
    $jp_mulai_baru,
    $jp_selesai_baru,
    $jumlah_jp_baru,
    $alasan,
    $pesan_ai
);

if (!$stmtInsert->execute()) {
    kirim_json("error", "Pengajuan gagal disimpan: " . $stmtInsert->error);
}

$id_request = $stmtInsert->insert_id;
$stmtInsert->close();
$conn->close();

kirim_json("success", "Pengajuan ganti jadwal berhasil dikirim ke admin.", [
    "id_request" => $id_request
]);
?>