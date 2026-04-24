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
        k.nama_kelas
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
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
$inisial   = strtoupper(substr($namaSiswa, 0, 1));

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

$queryTotal = mysqli_query($conn, "
    SELECT COUNT(j.id_jadwal) AS total_pelajaran
    FROM jadwal j
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
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

$queryUtama = mysqli_query($conn, "
    SELECT mp.nama_mapel, COUNT(*) AS total
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
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

$queryHariIni = mysqli_query($conn, "
    SELECT 
        j.jam AS jam_mulai,
        j.jam AS jam_selesai,
        mp.nama_mapel,
        COALESCE(g.nama, '-') AS nama_guru,
        '-' AS ruangan,
        'Mendatang' AS status_jadwal
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
      AND j.hari = '$hariDb'
    ORDER BY j.jam ASC
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
        "jam_mulai" => substr($rowHariIni['jam_mulai'], 0, 5),
        "mapel" => $rowHariIni['nama_mapel'],
        "ruangan" => $rowHariIni['ruangan']
    ];
}

$queryJadwal = mysqli_query($conn, "
    SELECT 
        j.hari AS hari,
        j.jam AS jam,
        mp.nama_mapel AS mata_pelajaran,
        COALESCE(g.nama, '-') AS guru,
        'Mendatang' AS status
    FROM jadwal j
    INNER JOIN mapel mp ON j.id_mapel = mp.id_mapel
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    INNER JOIN siswa s ON s.id_kelas = j.id_kelas
    WHERE s.id_siswa = $id_siswa
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam ASC
");

if (!$queryJadwal) {
    echo json_encode([
        "success" => false,
        "message" => "Query jadwal gagal: " . mysqli_error($conn)
    ]);
    exit;
}

$jamMulai = substr($rowJadwal['jam_mulai'], 0, 5);
$jamSelesai = substr($rowJadwal['jam_selesai'], 0, 5);

$jadwalMinggu = [];

while ($rowJadwal = mysqli_fetch_assoc($queryJadwal)) {
    $jadwalMinggu[] = [
        "hari" => $rowJadwal['hari'],
        "jam" => $rowJadwal['jam'],
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
        "tahun_ajaran" => "2025/2026",
        "semester" => "Genap"
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

