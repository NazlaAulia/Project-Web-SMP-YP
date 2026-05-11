<?php
include '../koneksi.php';

// Ambil parameter tahun ajaran dari URL
$id_tahun = isset($_GET['id_tahun']) ? (int)$_GET['id_tahun'] : 0;

if ($id_tahun == 0) {
    // Jika tidak ada parameter, ambil tahun aktif
    $ta_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status='aktif'"));
    $id_tahun = $ta_aktif['id_tahun_ajaran'];
}

// Ambil informasi tahun ajaran untuk judul
$thn_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT tahun_ajaran FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun"));
$tahun_ajaran = $thn_info['tahun_ajaran'];

// Query data kurang mampu (pendapatan <= 500.000)
$query = "SELECT 
            nama_lengkap, 
            nisn, 
            jenis_kelamin, 
            tanggal_lahir, 
            alamat, 
            asal_sekolah, 
            no_hp, 
            email, 
            nama_wali, 
            pendapatan_ortu, 
            status, 
            DATE_FORMAT(tanggal_daftar, '%d-%m-%Y %H:%i:%s') as tanggal_daftar 
          FROM pendaftaran 
          WHERE pendapatan_ortu <= 500000 
          AND id_tahun_ajaran = $id_tahun 
          ORDER BY nama_lengkap ASC";

$result = mysqli_query($conn, $query);

// Set header untuk download file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=siswa_kurang_mampu_{$tahun_ajaran}.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Mulai output tabel
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Daftar Siswa Kurang Mampu - $tahun_ajaran</title>";
echo "</head>";
echo "<body>";

echo "<h2>Daftar Siswa Kurang Mampu (Pendapatan OrtU ≤ Rp 500.000)</h2>";
echo "<h3>Tahun Ajaran: $tahun_ajaran</h3>";
echo "<p>Tanggal Cetak: " . date('d-m-Y H:i:s') . "</p>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<thead style='background-color:#f2f2f2;'>";
echo "<tr>";
echo "<th>No</th>";
echo "<th>Nama Lengkap</th>";
echo "<th>NISN</th>";
echo "<th>Jenis Kelamin</th>";
echo "<th>Tanggal Lahir</th>";
echo "<th>Alamat</th>";
echo "<th>Asal Sekolah</th>";
echo "<th>No HP Wali</th>";
echo "<th>Email</th>";
echo "<th>Nama Wali</th>";
echo "<th>Pendapatan Ortu</th>";
echo "<th>Status</th>";
echo "<th>Tanggal Daftar</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

if (mysqli_num_rows($result) > 0) {
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nisn']) . "</td>";
        echo "<td>" . ($row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan') . "</td>";
        echo "<td>" . htmlspecialchars($row['tanggal_lahir']) . "</td>";
        echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['asal_sekolah']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_hp']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_wali']) . "</td>";
        echo "<td>Rp " . number_format($row['pendapatan_ortu'], 0, ',', '.') . "</td>";
        
        // Status badge teks biasa
        $status_text = '';
        if ($row['status'] == 'menunggu') $status_text = 'Menunggu';
        elseif ($row['status'] == 'diterima') $status_text = 'Diterima';
        else $status_text = 'Ditolak';
        echo "<td>" . $status_text . "</td>";
        
        echo "<td>" . $row['tanggal_daftar'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='13' align='center'>Tidak ada data siswa kurang mampu untuk tahun ajaran ini.</td></tr>";
}

echo "</tbody>";
echo "</table>";
echo "<p><em>* Data ini diekspor dari sistem PPDB. Pendapatan ortu ≤ Rp 500.000.</em></p>";
echo "</body>";
echo "</html>";

mysqli_close($conn);
?>