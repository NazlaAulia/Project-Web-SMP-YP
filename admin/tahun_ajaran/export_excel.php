<?php
require_once __DIR__ . '/../koneksi.php';

$filename = "backup_data_siswa_" . date("Y-m-d_H-i-s") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

$query = "
    SELECT 
        s.nis,
        s.nisn,
        s.nama,
        s.jenis_kelamin,
        s.tanggal_lahir,
        s.alamat,
        COALESCE(k.nama_kelas, 'Belum ada kelas') AS nama_kelas,
        COALESCE(ta.tahun_ajaran, 'Belum ada tahun ajaran') AS tahun_ajaran,
        s.status
    FROM siswa s
    LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
    LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id_tahun_ajaran
    ORDER BY 
        FIELD(s.status, 'aktif', 'baru', 'lulus', 'keluar'),
        k.tingkat ASC,
        k.nama_kelas ASC,
        s.nama ASC
";

$result = $conn->query($query);

if (!$result) {
    die("Query gagal: " . $conn->error);
}
?>

<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>NIS</th>
            <th>NISN</th>
            <th>Nama</th>
            <th>Jenis Kelamin</th>
            <th>Tanggal Lahir</th>
            <th>Alamat</th>
            <th>Kelas</th>
            <th>Tahun Ajaran</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while ($row = $result->fetch_assoc()) :
        ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nis'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['nisn'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['nama'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['jenis_kelamin'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['tanggal_lahir'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['alamat'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['nama_kelas'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['tahun_ajaran'] ?? ''); ?></td>
                <td><?= htmlspecialchars($row['status'] ?? ''); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>