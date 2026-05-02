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

function fallbackRekomendasi($slot_kosong) {
    usort($slot_kosong, function ($a, $b) {
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

        return $a["jp_mulai"] <=> $b["jp_mulai"];
    });

    $ambil = array_slice($slot_kosong, 0, 3);

    foreach ($ambil as &$slot) {
        $slot["pesan_ai"] = "Slot ini direkomendasikan karena kosong, tidak bentrok, dan durasi JP sesuai dengan jadwal lama.";
    }

    return $ambil;
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
            $hari,
            $id_jadwal,
            $id_guru,
            $id_kelas,
            $jp_selesai,
            $jp_mulai
        );

        $stmtBentrok->execute();
        $resultBentrok = $stmtBentrok->get_result();
        $bentrok = $resultBentrok->num_rows > 0;
        $stmtBentrok->close();

        if (!$bentrok) {
            $slot_kosong[] = [
                "hari" => $hari,
                "jam" => $jam,
                "jp_mulai" => $jp_mulai,
                "jp_selesai" => $jp_selesai,
                "jumlah_jp" => $jumlah_jp
            ];
        }
    }
}

if (empty($slot_kosong)) {
    $conn->close();
    kirim_json("error", "Tidak ada slot kosong yang cocok untuk jadwal ini.");
}

$rekomendasi = fallbackRekomendasi($slot_kosong);

if (defined("GEMINI_API_KEY") && trim(GEMINI_API_KEY) !== "") {
    $slots_untuk_ai = [];

    foreach ($slot_kosong as $index => $slot) {
        $slots_untuk_ai[] = [
            "idx" => $index,
            "hari" => $slot["hari"],
            "jam" => $slot["jam"],
            "jp_mulai" => $slot["jp_mulai"],
            "jp_selesai" => $slot["jp_selesai"],
            "jumlah_jp" => $slot["jumlah_jp"]
        ];
    }

    $prompt = "Kamu adalah asisten penjadwalan SMP YP 17 Surabaya.
Jadwal lama:
- Guru: " . ($jadwal_lama["nama_guru"] ?? "-") . "
- Mapel: " . ($jadwal_lama["nama_mapel"] ?? "-") . "
- Kelas: " . ($jadwal_lama["nama_kelas"] ?? "-") . "
- Hari/Jam: " . $jadwal_lama["hari"] . " " . $jadwal_lama["jam"] . "
- Jumlah JP: " . $jumlah_jp . "

Berikut slot kosong yang sudah divalidasi sistem dan tidak bentrok:
" . json_encode($slots_untuk_ai, JSON_UNESCAPED_UNICODE) . "

Pilih maksimal 3 slot terbaik. Jawab WAJIB hanya array JSON tanpa markdown.
Format:
[
  {\"idx\": 0, \"pesan_ai\": \"alasan singkat maksimal 1 kalimat\"}
]";

    $api_key = trim(GEMINI_API_KEY);
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($api_key);

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $gemini_data = json_decode($response, true);
        $text_response = $gemini_data["candidates"][0]["content"]["parts"][0]["text"] ?? "";
        $text_response = str_replace(["```json", "```"], "", $text_response);

        $ai_result = json_decode(trim($text_response), true);

        if (is_array($ai_result) && !empty($ai_result)) {
            $hasil_ai = [];

            foreach ($ai_result as $item) {
                $idx = isset($item["idx"]) ? (int)$item["idx"] : -1;

                if (isset($slot_kosong[$idx])) {
                    $slot = $slot_kosong[$idx];
                    $slot["pesan_ai"] = $item["pesan_ai"] ?? "Slot ini direkomendasikan karena aman dan tidak bentrok.";
                    $hasil_ai[] = $slot;
                }

                if (count($hasil_ai) >= 3) {
                    break;
                }
            }

            if (!empty($hasil_ai)) {
                $rekomendasi = $hasil_ai;
            }
        }
    }
}

$conn->close();

kirim_json("success", "Rekomendasi jadwal berhasil dibuat.", [
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