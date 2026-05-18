<?php
session_start();
header('Content-Type: application/json');

require_once '../koneksi.php';

$id_siswa = 0;

if (isset($_GET['id_siswa']) && (int)$_GET['id_siswa'] > 0) {
    $id_siswa = (int) $_GET['id_siswa'];
} elseif (isset($_SESSION['id_siswa']) && (int)$_SESSION['id_siswa'] > 0) {
    $id_siswa = (int) $_SESSION['id_siswa'];
}

if ($id_siswa <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID siswa tidak ditemukan. Silakan login ulang."
    ]);
    exit;
}

$querySiswa = mysqli_query($conn, "
    SELECT 
        s.id_siswa,
        s.nama,
        k.nama_kelas,
        ta.tahun_ajaran
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id_tahun_ajaran
    WHERE s.id_siswa = $id_siswa
");

if (!$querySiswa) {
    echo json_encode([
        "success" => false,
        "message" => "Query siswa gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$dataSiswa = mysqli_fetch_assoc($querySiswa);

if (!$dataSiswa) {
    echo json_encode([
        "success" => false,
        "message" => "Data siswa tidak ditemukan."
    ]);
    exit;
}

$namaSiswa = $dataSiswa['nama'];
$namaKelas = $dataSiswa['nama_kelas'] ?? '-';
$tahunAjaran = $dataSiswa['tahun_ajaran'] ?? '-';
$inisial   = strtoupper(substr($namaSiswa, 0, 1));

$queryTahunAjaran = mysqli_query($conn, "
    SELECT id_tahun_ajaran, tahun_ajaran, status
    FROM tahun_ajaran
    ORDER BY id_tahun_ajaran ASC
");

$tahunAjaranList = [];

if ($queryTahunAjaran) {
    while ($rowTA = mysqli_fetch_assoc($queryTahunAjaran)) {
        $tahunAjaranList[] = [
            "id_tahun_ajaran" => $rowTA['id_tahun_ajaran'],
            "tahun_ajaran" => $rowTA['tahun_ajaran'],
            "status" => $rowTA['status']
        ];
    }
}

$hariIndonesia = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$bulanIndonesia = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$hariInggris = date('l');
$hariDb = $hariIndonesia[$hariInggris];
$hariIniText = $hariIndonesia[$hariInggris] . ', ' . date('d') . ' ' . $bulanIndonesia[(int)date('n')] . ' ' . date('Y');
$jamSekarang = date('H:i:s');

// ========== QUERY TOTAL PELAJARAN (HANYA STATUS FIX) ==========
$queryTotal = mysqli_query($conn, "
    SELECT COUNT(j.id_jadwal) AS total_pelajaran
    FROM jadwal j
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
      AND j.status = 'fix'
");

if (!$queryTotal) {
    echo json_encode([
        "success" => false,
        "message" => "Query total gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$totalPelajaran = 0;
if ($rowTotal = mysqli_fetch_assoc($queryTotal)) {
    $totalPelajaran = (int)$rowTotal['total_pelajaran'];
}

// ========== QUERY MAPEL UTAMA (HANYA STATUS FIX) ==========
$queryUtama = mysqli_query($conn, "
    SELECT mp.nama_mapel, COUNT(*) AS total
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
      AND j.status = 'fix'
    GROUP BY mp.nama_mapel
    ORDER BY total DESC
    LIMIT 3
");

if (!$queryUtama) {
    echo json_encode([
        "success" => false,
        "message" => "Query mapel utama gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$mapelUtama = [];
while ($rowUtama = mysqli_fetch_assoc($queryUtama)) {
    $mapelUtama[] = $rowUtama['nama_mapel'];
}

// ========== QUERY HARI INI (DENGAN STATUS BERDASARKAN JP) ==========
$queryHariIni = mysqli_query($conn, "
    SELECT 
        j.jp_mulai,
        j.jp_selesai,
        mp.nama_mapel,
        COALESCE(g.nama, '-') AS nama_guru,
        '-' AS ruangan,
        (
            SELECT jam_mulai 
            FROM jam_pelajaran jp 
            WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
            LIMIT 1
        ) AS jam_mulai,
        (
            SELECT jam_selesai 
            FROM jam_pelajaran jp 
            WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_selesai
            LIMIT 1
        ) AS jam_selesai,
        CASE
            WHEN TIME(NOW()) < (
                SELECT jam_mulai FROM jam_pelajaran jp 
                WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
                LIMIT 1
            ) THEN 'Mendatang'
            WHEN TIME(NOW()) BETWEEN (
                SELECT jam_mulai FROM jam_pelajaran jp 
                WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
                LIMIT 1
            ) AND (
                SELECT jam_selesai FROM jam_pelajaran jp 
                WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_selesai
                LIMIT 1
            ) THEN 'Berlangsung'
            ELSE 'Selesai'
        END AS status_jadwal
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
      AND j.hari = '$hariDb'
      AND j.status = 'fix'
    ORDER BY j.jp_mulai ASC
");

if (!$queryHariIni) {
    echo json_encode([
        "success" => false,
        "message" => "Query hari ini gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$updateTerbaru = [];
while ($rowHariIni = mysqli_fetch_assoc($queryHariIni)) {
    $updateTerbaru[] = [
        "jam_mulai" => date('H:i', strtotime($rowHariIni['jam_mulai'])),
        "mapel" => $rowHariIni['nama_mapel'],
        "ruangan" => $rowHariIni['ruangan'],
        "status" => $rowHariIni['status_jadwal']
    ];
}

// ========== QUERY JADWAL MINGGU (DENGAN STATUS BERDASARKAN JP) ==========
$queryJadwal = mysqli_query($conn, "
    SELECT 
        j.hari AS hari,
        j.jp_mulai,
        j.jp_selesai,
        mp.nama_mapel AS mata_pelajaran,
        COALESCE(g.nama, '-') AS guru,
        (
            SELECT jam_mulai 
            FROM jam_pelajaran jp 
            WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
            LIMIT 1
        ) AS jam_mulai,
        (
            SELECT jam_selesai 
            FROM jam_pelajaran jp 
            WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_selesai
            LIMIT 1
        ) AS jam_selesai,
        CASE
            WHEN j.hari = '$hariDb' THEN
                CASE
                    WHEN TIME(NOW()) < (
                        SELECT jam_mulai FROM jam_pelajaran jp 
                        WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
                        LIMIT 1
                    ) THEN 'Mendatang'
                    WHEN TIME(NOW()) BETWEEN (
                        SELECT jam_mulai FROM jam_pelajaran jp 
                        WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_mulai
                        LIMIT 1
                    ) AND (
                        SELECT jam_selesai FROM jam_pelajaran jp 
                        WHERE jp.hari = j.hari AND jp.nomor_jp = j.jp_selesai
                        LIMIT 1
                    ) THEN 'Berlangsung'
                    ELSE 'Selesai'
                END
            ELSE
                CASE
                    WHEN FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') 
                         > FIELD('$hariDb', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') 
                         THEN 'Mendatang'
                    ELSE 'Selesai'
                END
        END AS status
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
      AND j.status = 'fix'
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jp_mulai ASC
");

if (!$queryJadwal) {
    echo json_encode([
        "success" => false,
        "message" => "Query jadwal gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$jadwalMinggu = [];

while ($rowJadwal = mysqli_fetch_assoc($queryJadwal)) {
    // Format jam untuk ditampilkan
    $jamMulai = date('H:i', strtotime($rowJadwal['jam_mulai']));
    $jamSelesai = date('H:i', strtotime($rowJadwal['jam_selesai']));
    
    $jadwalMinggu[] = [
        "hari" => $rowJadwal['hari'],
        "jam" => $jamMulai . '-' . $jamSelesai,
        "mata_pelajaran" => $rowJadwal['mata_pelajaran'],
        "guru" => $rowJadwal['guru'],
        "status" => $rowJadwal['status']
    ];
}

echo json_encode([
    "success" => true,
    "siswa" => [
        "nama" => $namaSiswa,
        "kelas" => $namaKelas,
        "inisial" => $inisial,
        "tahun_ajaran" => $tahunAjaran,
        "semester" => "Genap",
        "tahun_ajaran_list" => $tahunAjaranList
    ],
    "ringkasan" => [
        "hari_ini" => $hariIniText,
        "total_pelajaran" => $totalPelajaran,
        "pelajaran_utama" => !empty($mapelUtama) ? implode(', ', $mapelUtama) : '-'
    ],
    "update_terbaru" => $updateTerbaru,
    "jadwal_minggu" => $jadwalMinggu
]);

$conn->close();
?>