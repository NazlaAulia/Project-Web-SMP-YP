<?php
header("Content-Type: application/json; charset=utf-8");

require_once "koneksi.php";

function kirim_json($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

$id_guru = isset($_GET["id_guru"]) ? (int) $_GET["id_guru"] : 0;
$role_id = isset($_GET["role_id"]) ? (int) $_GET["role_id"] : 0;

if ($role_id !== 2) {
    kirim_json("error", "Akses ditolak. Akun ini bukan guru.");
}

if ($id_guru <= 0) {
    kirim_json("error", "ID guru tidak valid. Silakan login ulang.");
}

$kkm = 75;
$maksMapelTidakLulus = 2;
$maksAlfa = 10;
$maksIzinSakit = 30;

try {
    $cekWali = $conn->prepare("
        SELECT id_kelas, nama_kelas, tingkat
        FROM kelas
        WHERE id_wali_kelas = ?
        ORDER BY tingkat ASC, nama_kelas ASC
    ");

    if (!$cekWali) {
        kirim_json("error", "Query wali kelas gagal: " . $conn->error);
    }

    $cekWali->bind_param("i", $id_guru);
    $cekWali->execute();
    $resultWali = $cekWali->get_result();

    $kelasWali = [];

    while ($row = $resultWali->fetch_assoc()) {
        $kelasWali[] = $row;
    }

    $cekWali->close();

    if (count($kelasWali) === 0) {
        kirim_json("success", "Guru ini belum menjadi wali kelas.", [
            "kelas_wali" => [],
            "summary" => [
                "naik_kelas" => 0,
                "tidak_naik" => 0,
                "lulus" => 0,
                "total_siswa" => 0
            ],
            "data" => []
        ]);
    }

    $query = "
        SELECT 
            s.id_siswa,
            s.nama,
            s.status,
            k.nama_kelas,
            k.tingkat,
            ROUND(AVG(n.nilai_angka), 2) AS rata_rata,
            SUM(CASE WHEN n.nilai_angka < ? THEN 1 ELSE 0 END) AS mapel_tidak_lulus,
            COALESCE(SUM(n.izin), 0) AS total_izin,
            COALESCE(SUM(n.sakit), 0) AS total_sakit,
            COALESCE(SUM(n.alfa), 0) AS total_alfa,
            COUNT(n.id_siswa) AS jumlah_nilai
        FROM siswa s
        JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n ON s.id_siswa = n.id_siswa
        WHERE s.status = 'aktif'
          AND k.id_wali_kelas = ?
        GROUP BY s.id_siswa, s.nama, s.status, k.nama_kelas, k.tingkat
        ORDER BY k.tingkat ASC, k.nama_kelas ASC, s.nama ASC
    ";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        kirim_json("error", "Query kenaikan kelas gagal: " . $conn->error);
    }

    $stmt->bind_param("ii", $kkm, $id_guru);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    $summary = [
        "naik_kelas" => 0,
        "tidak_naik" => 0,
        "lulus" => 0,
        "total_siswa" => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $kelasLama = $row["nama_kelas"] ?: "-";
        $kelasBaru = "-";

        $rataRata = $row["rata_rata"] !== null ? (float) $row["rata_rata"] : 0;
        $mapelTidakLulus = (int) $row["mapel_tidak_lulus"];
        $izin = (int) $row["total_izin"];
        $sakit = (int) $row["total_sakit"];
        $alfa = (int) $row["total_alfa"];
        $jumlahNilai = (int) $row["jumlah_nilai"];
        $tingkat = (int) $row["tingkat"];
        $huruf = substr($kelasLama, -1);

        $memenuhiSyarat =
            $jumlahNilai > 0 &&
            $rataRata >= $kkm &&
            $mapelTidakLulus <= $maksMapelTidakLulus &&
            $alfa <= $maksAlfa &&
            ($izin + $sakit) <= $maksIzinSakit;

        if ($memenuhiSyarat) {
            if ($tingkat === 7) {
                $statusKenaikan = "Naik Kelas";
                $kelasBaru = "8" . $huruf;
                $summary["naik_kelas"]++;
            } elseif ($tingkat === 8) {
                $statusKenaikan = "Naik Kelas";
                $kelasBaru = "9" . $huruf;
                $summary["naik_kelas"]++;
            } elseif ($tingkat === 9) {
                $statusKenaikan = "Lulus";
                $kelasBaru = "Lulus";
                $summary["lulus"]++;
            } else {
                $statusKenaikan = "Tidak Naik Kelas";
                $kelasBaru = $kelasLama;
                $summary["tidak_naik"]++;
            }
        } else {
            $statusKenaikan = "Tidak Naik Kelas";
            $kelasBaru = $kelasLama;
            $summary["tidak_naik"]++;
        }

        $summary["total_siswa"]++;

        $data[] = [
            "id_siswa" => (int) $row["id_siswa"],
            "nama" => $row["nama"],
            "kelas_lama" => $kelasLama,
            "kelas_baru" => $kelasBaru,
            "rata_rata" => number_format($rataRata, 2),
            "mapel_tidak_lulus" => $mapelTidakLulus,
            "izin" => $izin,
            "sakit" => $sakit,
            "alfa" => $alfa,
            "status_kenaikan" => $statusKenaikan
        ];
    }

    $stmt->close();

    kirim_json("success", "Data kenaikan kelas wali berhasil diambil.", [
        "kelas_wali" => $kelasWali,
        "summary" => $summary,
        "data" => $data
    ]);
} catch (Exception $e) {
    kirim_json("error", "Gagal mengambil data kenaikan kelas: " . $e->getMessage());
}
?>