<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../koneksi.php";

$config_path = __DIR__ . "/../admin/penjadwalan/config.php";
if (file_exists($config_path)) {
    require_once $config_path;
}

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function formatJam($jam) {
    return substr($jam, 0, 5);
}

function isBentrokGuru($conn, $id_guru, $hari, $jp_mulai, $jp_selesai, $exclude_ids = []) {
    $exclude_sql = "";
    $types = "siii";
    $params = [$hari, $id_guru, $jp_selesai, $jp_mulai];

    if (!empty($exclude_ids)) {
        $placeholders = implode(",", array_fill(0, count($exclude_ids), "?"));
        $exclude_sql = " AND id_jadwal NOT IN ($placeholders)";
        $types .= str_repeat("i", count($exclude_ids));
        foreach ($exclude_ids as $id) {
            $params[] = (int)$id;
        }
    }

    $sql = "
        SELECT id_jadwal
        FROM jadwal
        WHERE hari = ?
          AND id_guru = ?
          AND jp_mulai <= ?
          AND jp_selesai >= ?
          $exclude_sql
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return true;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $bentrok = $result->num_rows > 0;
    $stmt->close();

    return $bentrok;
}

function isBentrokGuruAtauKelas($conn, $id_guru, $id_kelas, $hari, $jp_mulai, $jp_selesai, $exclude_ids = []) {
    $exclude_sql = "";
    $types = "siiii";
    $params = [$hari, $id_guru, $id_kelas, $jp_selesai, $jp_mulai];

    if (!empty($exclude_ids)) {
        $placeholders = implode(",", array_fill(0, count($exclude_ids), "?"));
        $exclude_sql = " AND id_jadwal NOT IN ($placeholders)";
        $types .= str_repeat("i", count($exclude_ids));
        foreach ($exclude_ids as $id) {
            $params[] = (int)$id;
        }
    }

    $sql = "
        SELECT id_jadwal
        FROM jadwal
        WHERE hari = ?
          AND (id_guru = ? OR id_kelas = ?)
          AND jp_mulai <= ?
          AND jp_selesai >= ?
          $exclude_sql
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return true;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $bentrok = $result->num_rows > 0;
    $stmt->close();

    return $bentrok;
}

function ambilBlockJP($jp_per_hari, $hari, $jumlah_jp) {
    $hasil = [];

    if (!isset($jp_per_hari[$hari])) {
        return $hasil;
    }

    $list = $jp_per_hari[$hari];
    $total = count($list);

    for ($i = 0; $i <= $total - $jumlah_jp; $i++) {
        $block = array_slice($list, $i, $jumlah_jp);
        $valid = true;

        for ($j = 0; $j < count($block) - 1; $j++) {
            $jp_sekarang = $block[$j];
            $jp_berikutnya = $block[$j + 1];

            if ((int)$jp_berikutnya["nomor_jp"] !== (int)$jp_sekarang["nomor_jp"] + 1) {
                $valid = false;
                break;
            }

            if ($jp_sekarang["jam_selesai"] !== $jp_berikutnya["jam_mulai"]) {
                $valid = false;
                break;
            }
        }

        if ($valid) {
            $hasil[] = $block;
        }
    }

    return $hasil;
}

function urutkanRekomendasi($list) {
    usort($list, function ($a, $b) {
        $hariOrder = [
            "Senin" => 1,
            "Selasa" => 2,
            "Rabu" => 3,
            "Kamis" => 4,
            "Jumat" => 5,
            "Sabtu" => 6
        ];

        $ha = $hariOrder[$a["hari"]] ?? 99;
        $hb = $hariOrder[$b["hari"]] ?? 99;

        if ($ha !== $hb) return $ha <=> $hb;

        return ((int)$a["jp_mulai"]) <=> ((int)$b["jp_mulai"]);
    });

    return $list;
}

if ($conn->connect_error) {
    kirim_json("error", "Koneksi database gagal.");
}

$conn->set_charset("utf8mb4");

$id_jadwal = isset($_GET["id_jadwal"]) ? (int)$_GET["id_jadwal"] : 0;
$id_guru = isset($_GET["id_guru"]) ? (int)$_GET["id_guru"] : 0;

if ($id_jadwal <= 0) {
    kirim_json("error", "ID jadwal tidak valid.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid. Silakan login ulang.");
}

$stmtJadwal = $conn->prepare("
    SELECT 
        j.id_jadwal,
        j.id_guru,
        j.id_kelas,
        j.id_mapel,
        j.hari,
        j.jam,
        j.jp_mulai,
        j.jp_selesai,
        j.jumlah_jp,
        g.nama AS nama_guru,
        k.nama_kelas,
        m.nama_mapel
    FROM jadwal j
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_jadwal = ?
      AND j.id_guru = ?
    LIMIT 1
");

if (!$stmtJadwal) {
    kirim_json("error", "Query jadwal gagal: " . $conn->error);
}

$stmtJadwal->bind_param("ii", $id_jadwal, $id_guru);
$stmtJadwal->execute();
$resultJadwal = $stmtJadwal->get_result();

if ($resultJadwal->num_rows === 0) {
    kirim_json("error", "Jadwal tidak ditemukan atau bukan milik guru ini.");
}

$jadwal_lama = $resultJadwal->fetch_assoc();
$stmtJadwal->close();

$id_kelas = (int)$jadwal_lama["id_kelas"];
$jumlah_jp = (int)($jadwal_lama["jumlah_jp"] ?? 1);

if ($jumlah_jp <= 0) {
    $jumlah_jp = 1;
}

$jp_per_hari = [];

$qJP = $conn->query("
    SELECT id_jp, hari, nomor_jp, jam_mulai, jam_selesai
    FROM jam_pelajaran
    WHERE aktif = 1
    ORDER BY 
        FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        nomor_jp ASC
");

if (!$qJP) {
    kirim_json("error", "Gagal mengambil data jam pelajaran: " . $conn->error);
}

while ($row = $qJP->fetch_assoc()) {
    $hari = $row["hari"];

    if (!isset($jp_per_hari[$hari])) {
        $jp_per_hari[$hari] = [];
    }

    $jp_per_hari[$hari][] = [
        "id_jp" => (int)$row["id_jp"],
        "hari" => $row["hari"],
        "nomor_jp" => (int)$row["nomor_jp"],
        "jam_mulai" => $row["jam_mulai"],
        "jam_selesai" => $row["jam_selesai"]
    ];
}

if (empty($jp_per_hari)) {
    kirim_json("error", "Data jam pelajaran masih kosong.");
}

/*
|--------------------------------------------------------------------------
| 1. Cari slot kosong dulu
|--------------------------------------------------------------------------
*/

$slot_kosong = [];

foreach ($jp_per_hari as $hari => $listJP) {
    $blocks = ambilBlockJP($jp_per_hari, $hari, $jumlah_jp);

    foreach ($blocks as $block) {
        $jp_mulai = (int)$block[0]["nomor_jp"];
        $jp_selesai = (int)$block[count($block) - 1]["nomor_jp"];
        $jam = formatJam($block[0]["jam_mulai"]) . "-" . formatJam($block[count($block) - 1]["jam_selesai"]);

        if (
            $hari === $jadwal_lama["hari"] &&
            $jp_mulai === (int)$jadwal_lama["jp_mulai"] &&
            $jp_selesai === (int)$jadwal_lama["jp_selesai"]
        ) {
            continue;
        }

        $bentrok = isBentrokGuruAtauKelas(
            $conn,
            $id_guru,
            $id_kelas,
            $hari,
            $jp_mulai,
            $jp_selesai,
            [$id_jadwal]
        );

        if (!$bentrok) {
            $slot_kosong[] = [
                "tipe_request" => "slot_kosong",
                "id_jadwal_tukar" => null,
                "hari" => $hari,
                "jam" => $jam,
                "jp_mulai" => $jp_mulai,
                "jp_selesai" => $jp_selesai,
                "jumlah_jp" => $jumlah_jp,
                "pesan_ai" => "Slot ini kosong, tidak bentrok, dan durasi JP sesuai dengan jadwal lama."
            ];
        }
    }
}

$rekomendasi = [];

if (!empty($slot_kosong)) {
    $slot_kosong = urutkanRekomendasi($slot_kosong);
    $rekomendasi = array_slice($slot_kosong, 0, 3);

    $conn->close();

    kirim_json("success", "Rekomendasi slot kosong berhasil dibuat.", [
        "mode" => "slot_kosong",
        "jadwal_lama" => [
            "id_jadwal" => (int)$jadwal_lama["id_jadwal"],
            "id_guru" => (int)$jadwal_lama["id_guru"],
            "id_kelas" => (int)$jadwal_lama["id_kelas"],
            "id_mapel" => (int)$jadwal_lama["id_mapel"],
            "guru" => $jadwal_lama["nama_guru"] ?? "-",
            "kelas" => $jadwal_lama["nama_kelas"] ?? "-",
            "mapel" => $jadwal_lama["nama_mapel"] ?? "-",
            "hari" => $jadwal_lama["hari"],
            "jam" => $jadwal_lama["jam"],
            "jp_mulai" => $jadwal_lama["jp_mulai"],
            "jp_selesai" => $jadwal_lama["jp_selesai"],
            "jumlah_jp" => $jumlah_jp
        ],
        "data" => $rekomendasi
    ]);
}

/*
|--------------------------------------------------------------------------
| 2. Kalau slot kosong tidak ada, cari opsi tukar jadwal
|--------------------------------------------------------------------------
| Logika tukar:
| - Cari jadwal lain di kelas yang sama
| - Durasi JP harus sama
| - Guru lama harus bisa mengisi slot jadwal tukar
| - Guru jadwal tukar harus bisa mengisi slot jadwal lama
|--------------------------------------------------------------------------
*/

$opsi_tukar = [];

$stmtTukar = $conn->prepare("
    SELECT 
        j.id_jadwal,
        j.id_guru,
        j.id_kelas,
        j.id_mapel,
        j.hari,
        j.jam,
        j.jp_mulai,
        j.jp_selesai,
        j.jumlah_jp,
        g.nama AS nama_guru,
        m.nama_mapel
    FROM jadwal j
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_kelas = ?
      AND j.id_jadwal != ?
      AND j.jumlah_jp = ?
      AND j.id_guru != ?
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        COALESCE(j.jp_mulai, 0),
        j.jam ASC
");

if (!$stmtTukar) {
    kirim_json("error", "Query opsi tukar gagal: " . $conn->error);
}

$stmtTukar->bind_param("iiii", $id_kelas, $id_jadwal, $jumlah_jp, $id_guru);
$stmtTukar->execute();
$resultTukar = $stmtTukar->get_result();

while ($tukar = $resultTukar->fetch_assoc()) {
    $id_jadwal_tukar = (int)$tukar["id_jadwal"];
    $id_guru_tukar = (int)$tukar["id_guru"];

    $guru_lama_bentrok_di_slot_tukar = isBentrokGuru(
        $conn,
        $id_guru,
        $tukar["hari"],
        (int)$tukar["jp_mulai"],
        (int)$tukar["jp_selesai"],
        [$id_jadwal, $id_jadwal_tukar]
    );

    if ($guru_lama_bentrok_di_slot_tukar) {
        continue;
    }

    $guru_tukar_bentrok_di_slot_lama = isBentrokGuru(
        $conn,
        $id_guru_tukar,
        $jadwal_lama["hari"],
        (int)$jadwal_lama["jp_mulai"],
        (int)$jadwal_lama["jp_selesai"],
        [$id_jadwal, $id_jadwal_tukar]
    );

    if ($guru_tukar_bentrok_di_slot_lama) {
        continue;
    }

    $opsi_tukar[] = [
        "tipe_request" => "tukar",
        "id_jadwal_tukar" => $id_jadwal_tukar,

        "hari" => $tukar["hari"],
        "jam" => $tukar["jam"],
        "jp_mulai" => (int)$tukar["jp_mulai"],
        "jp_selesai" => (int)$tukar["jp_selesai"],
        "jumlah_jp" => $jumlah_jp,

        "mapel_tukar" => $tukar["nama_mapel"] ?? "-",
        "guru_tukar" => $tukar["nama_guru"] ?? "-",

        "pesan_ai" => "Tidak ada slot kosong. Opsi ini menukar jadwal dengan " . ($tukar["nama_mapel"] ?? "-") . " agar durasi JP tetap sama dan tidak bentrok dengan guru terkait."
    ];
}

$stmtTukar->close();

if (empty($opsi_tukar)) {
    $conn->close();

    kirim_json("error", "Tidak ada slot kosong maupun opsi tukar jadwal yang aman untuk jadwal ini.");
}

$opsi_tukar = urutkanRekomendasi($opsi_tukar);
$rekomendasi = array_slice($opsi_tukar, 0, 3);

$conn->close();

kirim_json("success", "Tidak ada slot kosong. Sistem membuat rekomendasi tukar jadwal.", [
    "mode" => "tukar",
    "jadwal_lama" => [
        "id_jadwal" => (int)$jadwal_lama["id_jadwal"],
        "id_guru" => (int)$jadwal_lama["id_guru"],
        "id_kelas" => (int)$jadwal_lama["id_kelas"],
        "id_mapel" => (int)$jadwal_lama["id_mapel"],
        "guru" => $jadwal_lama["nama_guru"] ?? "-",
        "kelas" => $jadwal_lama["nama_kelas"] ?? "-",
        "mapel" => $jadwal_lama["nama_mapel"] ?? "-",
        "hari" => $jadwal_lama["hari"],
        "jam" => $jadwal_lama["jam"],
        "jp_mulai" => $jadwal_lama["jp_mulai"],
        "jp_selesai" => $jadwal_lama["jp_selesai"],
        "jumlah_jp" => $jumlah_jp
    ],
    "data" => $rekomendasi
]);
?>