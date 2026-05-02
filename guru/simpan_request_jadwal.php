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

$tipe_request = trim($data["tipe_request"] ?? "slot_kosong");
$id_jadwal_tukar = isset($data["id_jadwal_tukar"]) && $data["id_jadwal_tukar"] !== null
    ? (int)$data["id_jadwal_tukar"]
    : null;

if (!in_array($tipe_request, ["slot_kosong", "tukar"], true)) {
    $tipe_request = "slot_kosong";
}

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

if ($tipe_request === "tukar" && (!$id_jadwal_tukar || $id_jadwal_tukar <= 0)) {
    kirim_json("error", "Data jadwal tukar tidak valid.");
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

if ($tipe_request === "slot_kosong") {
    // Validasi slot kosong: guru atau kelas tidak boleh bentrok
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
}

if ($tipe_request === "tukar") {
    // Ambil jadwal yang akan ditukar
    $stmtTukar = $conn->prepare("
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
          AND id_kelas = ?
        LIMIT 1
    ");

    if (!$stmtTukar) {
        kirim_json("error", "Query cek jadwal tukar gagal: " . $conn->error);
    }

    $stmtTukar->bind_param("ii", $id_jadwal_tukar, $id_kelas);
    $stmtTukar->execute();
    $resultTukar = $stmtTukar->get_result();

    if ($resultTukar->num_rows === 0) {
        kirim_json("error", "Jadwal tukar tidak ditemukan atau bukan dari kelas yang sama.");
    }

    $jadwal_tukar = $resultTukar->fetch_assoc();
    $stmtTukar->close();

    if ((int)$jadwal_tukar["id_jadwal"] === $id_jadwal) {
        kirim_json("error", "Jadwal tukar tidak boleh sama dengan jadwal lama.");
    }

    if ((int)$jadwal_tukar["jumlah_jp"] !== $jumlah_jp_lama) {
        kirim_json("error", "Jadwal tukar harus memiliki jumlah JP yang sama.");
    }

    if ((int)$jadwal_tukar["id_guru"] === $id_guru) {
        kirim_json("error", "Jadwal tukar tidak boleh dengan guru yang sama.");
    }

    $id_guru_tukar = (int)$jadwal_tukar["id_guru"];

    // Guru lama harus kosong di slot jadwal tukar
    $stmtCekGuruLama = $conn->prepare("
        SELECT id_jadwal
        FROM jadwal
        WHERE hari = ?
          AND id_guru = ?
          AND id_jadwal NOT IN (?, ?)
          AND jp_mulai <= ?
          AND jp_selesai >= ?
        LIMIT 1
    ");

    if (!$stmtCekGuruLama) {
        kirim_json("error", "Query cek guru lama gagal: " . $conn->error);
    }

    $stmtCekGuruLama->bind_param(
        "siiiii",
        $jadwal_tukar["hari"],
        $id_guru,
        $id_jadwal,
        $id_jadwal_tukar,
        $jadwal_tukar["jp_selesai"],
        $jadwal_tukar["jp_mulai"]
    );

    $stmtCekGuruLama->execute();
    $resultCekGuruLama = $stmtCekGuruLama->get_result();

    if ($resultCekGuruLama->num_rows > 0) {
        kirim_json("error", "Guru pemohon bentrok pada slot jadwal tukar.");
    }

    $stmtCekGuruLama->close();

    // Guru tukar harus kosong di slot jadwal lama
    $stmtCekGuruTukar = $conn->prepare("
        SELECT id_jadwal
        FROM jadwal
        WHERE hari = ?
          AND id_guru = ?
          AND id_jadwal NOT IN (?, ?)
          AND jp_mulai <= ?
          AND jp_selesai >= ?
        LIMIT 1
    ");

    if (!$stmtCekGuruTukar) {
        kirim_json("error", "Query cek guru tukar gagal: " . $conn->error);
    }

    $stmtCekGuruTukar->bind_param(
        "siiiii",
        $jadwal_lama["hari"],
        $id_guru_tukar,
        $id_jadwal,
        $id_jadwal_tukar,
        $jadwal_lama["jp_selesai"],
        $jadwal_lama["jp_mulai"]
    );

    $stmtCekGuruTukar->execute();
    $resultCekGuruTukar = $stmtCekGuruTukar->get_result();

    if ($resultCekGuruTukar->num_rows > 0) {
        kirim_json("error", "Guru pada jadwal tukar bentrok pada slot jadwal lama.");
    }

    $stmtCekGuruTukar->close();
}

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
        tipe_request,
        id_jadwal_tukar,
        status,
        tanggal_request
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', NOW())
");

if (!$stmtInsert) {
    kirim_json("error", "Query simpan request gagal: " . $conn->error);
}

$stmtInsert->bind_param(
    "iiissiiisssi",
    $id_guru,
    $id_kelas,
    $id_jadwal,
    $hari_baru,
    $jam_baru,
    $jp_mulai_baru,
    $jp_selesai_baru,
    $jumlah_jp_baru,
    $alasan,
    $pesan_ai,
    $tipe_request,
    $id_jadwal_tukar
);

if (!$stmtInsert->execute()) {
    kirim_json("error", "Pengajuan gagal disimpan: " . $stmtInsert->error);
}

$id_request = $stmtInsert->insert_id;
$stmtInsert->close();
$conn->close();

kirim_json("success", "Pengajuan ganti jadwal berhasil dikirim ke admin.", [
    "id_request" => $id_request,
    "tipe_request" => $tipe_request
]);
?>