<?php
include '../koneksi.php';

$query = "SELECT * FROM pendaftaran ORDER BY id_pendaftaran DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pendaftaran Siswa</title>

    <link rel="stylesheet" href="/admin/components/admin-nav.css">
    <link rel="stylesheet" href="/admin/admin_pendaftaran.css?v=40">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body data-page="pendaftaran" data-nav-path="/admin/components/admin-nav.html">

<div class="container">
    <div id="admin-nav-root"></div>

    <main class="main-content">
        <div class="admin-container">

            <div class="admin-header">
                <div>
                    <h1>Data Pendaftaran Siswa</h1>
                    <p>Daftar siswa yang telah mengisi formulir pendaftaran online.</p>

                    <div class="search-container-elegant search-below-title">
                        <span class="search-icon">
                            <i class="fas fa-search"></i>
                        </span>
                        <input 
                            type="text" 
                            id="searchPendaftaran" 
                            class="search-input" 
                            placeholder="Cari nama, NISN, sekolah..."
                        >
                    </div>
                </div>
            </div>

            <div class="table-card">
                <table id="tablePendaftaran">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>NISN</th>
                            <th>JK</th>
                            <th>Tanggal Lahir</th>
                            <th>No HP Wali</th>
                            <th>Asal Sekolah</th>
                            <th>Nama Wali</th>
                            <th>Pendapatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;

                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?= $no++; ?></td>

                                <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>

                                <td><?= htmlspecialchars($row['nisn']); ?></td>

                                <td>
                                    <?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                                </td>

                                <td><?= htmlspecialchars($row['tanggal_lahir']); ?></td>

                                <td><?= htmlspecialchars($row['no_hp']); ?></td>

                                <td><?= htmlspecialchars($row['asal_sekolah']); ?></td>

                                <td><?= htmlspecialchars($row['nama_wali']); ?></td>

                                <td>
                                    Rp <?= number_format($row['pendapatan_ortu'], 0, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php if ($row['status'] == 'menunggu') { ?>
                                        <span class="badge waiting">Menunggu</span>
                                    <?php } elseif ($row['status'] == 'diterima') { ?>
                                        <span class="badge accepted">Diterima</span>
                                    <?php } else { ?>
                                        <span class="badge rejected">Ditolak</span>
                                    <?php } ?>
                                </td>

                                <td class="action-cell">
                                    <a 
                                        href="/admin/update_status.php?id=<?= $row['id_pendaftaran']; ?>&status=diterima"
                                        class="btn-accept"
                                        onclick="return confirm('Terima pendaftaran ini?')"
                                    >
                                        Terima
                                    </a>

                                    <a 
                                        href="/admin/update_status.php?id=<?= $row['id_pendaftaran']; ?>&status=ditolak"
                                        class="btn-reject"
                                        onclick="return confirm('Tolak pendaftaran ini?')"
                                    >
                                        Tolak
                                    </a>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="11" class="empty-data">
                                    Belum ada data pendaftaran.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

                <div id="noSearchResult" class="no-search-result" style="display: none;">
                    Data tidak ditemukan.
                </div>
            </div>

        </div>
    </main>
</div>

<script src="/admin/components/admin-nav.js?v=999"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchPendaftaran");
    const table = document.getElementById("tablePendaftaran");
    const noSearchResult = document.getElementById("noSearchResult");

    if (!searchInput || !table) return;

    searchInput.addEventListener("keyup", function () {
        const keyword = this.value.toLowerCase().trim();
        const rows = table.querySelectorAll("tbody tr");

        let visibleCount = 0;

        rows.forEach(row => {
            const isEmptyRow = row.querySelector(".empty-data");

            if (isEmptyRow) {
                return;
            }

            const rowText = row.textContent.toLowerCase();

            if (rowText.includes(keyword)) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        });

        if (noSearchResult) {
            noSearchResult.style.display = visibleCount === 0 && keyword !== "" ? "block" : "none";
        }
    });
});
</script>

</body>
</html>