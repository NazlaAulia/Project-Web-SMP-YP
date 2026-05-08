<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../koneksi.php';

$kkm = 75;
$maksMapelTidakLulus = 2;
$maksAlfa = 10;
$maksIzinSakit = 30;

try {
    $data = [];

    $summary = [
        'naik_kelas' => 0,
        'tidak_naik' => 0,
        'lulus' => 0,
        'siswa_baru' => 0
    ];

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
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN nilai n ON s.id_siswa = n.id_siswa
        WHERE s.status IN ('aktif', 'baru')
        GROUP BY s.id_siswa, s.nama, s.status, k.nama_kelas, k.tingkat
        ORDER BY 
            FIELD(s.status, 'aktif', 'baru'),
            k.tingkat ASC,
            k.nama_kelas ASC,
            s.nama ASC
    ";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("i", $kkm);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $kelasLama = $row['nama_kelas'] ?: 'Belum ada kelas';
        $kelasBaru = '-';

        $rataRata = $row['rata_rata'] !== null ? (float) $row['rata_rata'] : 0;
        $mapelTidakLulus = (int) $row['mapel_tidak_lulus'];
        $izin = (int) $row['total_izin'];
        $sakit = (int) $row['total_sakit'];
        $alfa = (int) $row['total_alfa'];
        $jumlahNilai = (int) $row['jumlah_nilai'];

        if ($row['status'] === 'baru') {
            $statusKenaikan = 'Siswa Baru';
            $kelasBaru = '7A / 7B / 7C';
            $summary['siswa_baru']++;
        } else {
            $memenuhiSyarat =
                $jumlahNilai > 0 &&
                $rataRata >= $kkm &&
                $mapelTidakLulus <= $maksMapelTidakLulus &&
                $alfa <= $maksAlfa &&
                ($izin + $sakit) <= $maksIzinSakit;

            $tingkat = (int) $row['tingkat'];
            $huruf = substr($kelasLama, -1);

            if ($memenuhiSyarat) {
                if ($tingkat === 7) {
                    $statusKenaikan = 'Naik Kelas';
                    $kelasBaru = '8' . $huruf;
                    $summary['naik_kelas']++;
                } elseif ($tingkat === 8) {
                    $statusKenaikan = 'Naik Kelas';
                    $kelasBaru = '9' . $huruf;
                    $summary['naik_kelas']++;
                } elseif ($tingkat === 9) {
                    $statusKenaikan = 'Lulus';
                    $kelasBaru = 'Lulus';
                    $summary['lulus']++;
                } else {
                    $statusKenaikan = 'Tidak Naik Kelas';
                    $kelasBaru = $kelasLama;
                    $summary['tidak_naik']++;
                }
            } else {
                $statusKenaikan = 'Tidak Naik Kelas';
                $kelasBaru = $kelasLama;
                $summary['tidak_naik']++;
            }
        }

        $data[] = [
            'id_siswa' => (int) $row['id_siswa'],
            'nama' => $row['nama'],
            'kelas_lama' => $kelasLama,
            'kelas_baru' => $kelasBaru,
            'rata_rata' => number_format($rataRata, 2),
            'mapel_tidak_lulus' => $mapelTidakLulus,
            'izin' => $izin,
            'sakit' => $sakit,
            'alfa' => $alfa,
            'status_kenaikan' => $statusKenaikan
        ];
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'summary' => $summary
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Preview gagal: ' . $e->getMessage()
    ]);
}