<?php
include '../koneksi.php';

// ========== 1. TAHUN AJARAN (DROPDOWN & FILTER) ==========
$query_ta = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC";
$result_ta = mysqli_query($conn, $query_ta);

$id_tahun_terpilih = isset($_GET['id_tahun']) ? (int)$_GET['id_tahun'] : 0;
if ($id_tahun_terpilih == 0) {
    $ta_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status='aktif'"));
    $id_tahun_terpilih = $ta_aktif['id_tahun_ajaran'];
}

$info_ta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun_terpilih"));
$is_nonaktif = ($info_ta['status'] == 'nonaktif');
$diterima = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_terpilih AND status='diterima'"))['total'];
$kuota = $info_ta['kuota'];
$sisa = $kuota - $diterima;

// ========== CEK JUMLAH PENDAFTAR MENUNGGU ==========
$query_menunggu = "SELECT COUNT(*) as total FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_terpilih AND status = 'menunggu'";
$result_menunggu = mysqli_query($conn, $query_menunggu);
$data_menunggu = mysqli_fetch_assoc($result_menunggu);
$jumlah_menunggu = $data_menunggu['total'];

// ========== 2. PAGINATION & SEARCH ==========
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$isMenungguMode = ($filter === 'menunggu');

$searchLike = '%' . $search . '%';

$countSql = "SELECT COUNT(*) AS total FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_terpilih";
$params = [];
$types = "";

if ($search !== '') {
    $countSql .= " AND ( 
        nama_lengkap LIKE ? OR
        nisn LIKE ? OR
        jenis_kelamin LIKE ? OR
        tanggal_lahir LIKE ? OR
        no_hp LIKE ? OR
        asal_sekolah LIKE ? OR
        nama_wali LIKE ? OR
        pendapatan_ortu LIKE ? OR
        status LIKE ?
    )";
    $params = [$searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike];
    $types = "sssssssss";
}

$countStmt = mysqli_prepare($conn, $countSql);
if ($countStmt) {
    if ($search !== '') mysqli_stmt_bind_param($countStmt, $types, ...$params);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $totalData = mysqli_fetch_assoc($countResult)['total'];
} else $totalData = 0;
$totalPages = ceil($totalData / $limit);
$totalPages = max($totalPages, 1);

$orderBy = $isMenungguMode
    ? "ORDER BY CASE WHEN status = 'menunggu' THEN 0 ELSE 1 END, id_pendaftaran DESC"
    : "ORDER BY id_pendaftaran DESC";

$dataSql = "SELECT * FROM pendaftaran WHERE id_tahun_ajaran = $id_tahun_terpilih";
if ($search !== '') {
    $dataSql .= " AND ( 
        nama_lengkap LIKE ? OR
        nisn LIKE ? OR
        jenis_kelamin LIKE ? OR
        tanggal_lahir LIKE ? OR
        no_hp LIKE ? OR
        asal_sekolah LIKE ? OR
        nama_wali LIKE ? OR
        pendapatan_ortu LIKE ? OR
        status LIKE ?
    )";
}
$dataSql .= " $orderBy LIMIT ? OFFSET ?";

$dataStmt = mysqli_prepare($conn, $dataSql);
if ($dataStmt) {
    if ($search !== '') {
        $paramsData = array_merge($params, [$limit, $offset]);
        $typesData = $types . "ii";
        mysqli_stmt_bind_param($dataStmt, $typesData, ...$paramsData);
    } else {
        mysqli_stmt_bind_param($dataStmt, "ii", $limit, $offset);
    }
    mysqli_stmt_execute($dataStmt);
    $result = mysqli_stmt_get_result($dataStmt);
} else {
    $result = false;
}

$startData = $totalData > 0 ? $offset + 1 : 0;
$endData = min($offset + $limit, $totalData);

function buildPageUrl($pageNumber, $search, $filter, $id_tahun) {
    $query = ['page' => $pageNumber, 'id_tahun' => $id_tahun];
    if ($search !== '') $query['q'] = $search;
    if ($filter !== '') $query['filter'] = $filter;
    return '?' . http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftaran Siswa</title>
    <link rel="stylesheet" href="components/admin-nav.css">
    <link rel="stylesheet" href="admin_pendaftaran.css?v=105">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
    <style>
        .modal-atur {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        .modal-atur .modal-box {
            background: white;
            width: 500px;
            max-width: 90%;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
        }
        .modal-atur h3 {
            margin-bottom: 20px;
            color: #064e4b;
        }
        .modal-atur .form-group { margin-bottom: 15px; }
        .modal-atur label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        .modal-atur input, .modal-atur select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .modal-atur .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
        .modal-atur button { padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-batal { background: #e0e0e0; color: #333; }
        .btn-simpan { background: #064e4b; color: white; }
        .btn-atur-pendaftaran { background: #0f5d5d; color: white; border: none; padding: 8px 16px; border-radius: 20px; margin-left: 10px; cursor: pointer; font-size: 13px; }
        .btn-atur-pendaftaran:hover { background: #053f3d; }
        .btn-proses-semua {
            background: #0f5d5d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-proses-semua:hover {
            background: #053f3d;
        }
        .btn-tandai {
            cursor: pointer;
        }
    </style>
</head>
<body data-page="pendaftaran" data-nav-path="components/admin-nav.html">
<div class="container">
    <div id="admin-nav-root"></div>
    <main class="main-content">
        <div class="admin-container">
            <div class="admin-header">
                <div>
                    <h1>Data Pendaftaran Siswa</h1>
                    <p>Daftar siswa yang telah mengisi formulir pendaftaran online.</p>
                </div>
                <div class="header-filter-actions">
                    <a href="<?= buildPageUrl(1, $search, $isMenungguMode ? '' : 'menunggu', $id_tahun_terpilih); ?>" 
                       class="btn-filter-waiting <?= $isMenungguMode ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Menunggu
                    </a>
                </div>
            </div>

            <!-- DROPDOWN TAHUN, KUOTA, CETAK, TOMBOL ATUR, TOMBOL PROSES SEMUA -->
            <div class="filter-bar" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <label style="font-weight: bold;">Tahun Ajaran:</label>
                    <select name="id_tahun" onchange="this.form.submit()" style="padding:6px 12px; border-radius:20px; border:1px solid #ccc;">
                        <?php 
                        mysqli_data_seek($result_ta, 0);
                        while($ta = mysqli_fetch_assoc($result_ta)): ?>
                            <option value="<?= $ta['id_tahun_ajaran'] ?>" <?= $id_tahun_terpilih == $ta['id_tahun_ajaran'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ta['tahun_ajaran']) ?> <?= $ta['status'] == 'aktif' ? '(Aktif)' : '' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <?php if($search !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search); ?>"><?php endif; ?>
                    <?php if($filter !== ''): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>"><?php endif; ?>

                    <div style="background: #eef2f3; padding: 5px 12px; border-radius: 20px;">
                        <i class="fas fa-users"></i> Kuota: <?= $diterima ?> / <?= $kuota ?> | Sisa: <?= $sisa ?>
                    </div>
<a href="cetak_kurang_mampu.php?id_tahun=<?= $id_tahun_terpilih ?>" 
   class="btn-print-pdf" 
   target="_blank">
    <i class="fas fa-file-pdf"></i>
    Cetak Kurang Mampu
</a>


                    <button type="button" class="btn-atur-pendaftaran" onclick="openModalAtur()">
                        <i class="fas fa-cog"></i> Atur Pendaftaran
                    </button>
                </form>

                <form method="GET" action="" style="display: flex; gap: 5px;">
                    <input type="hidden" name="id_tahun" value="<?= $id_tahun_terpilih ?>">
                    <?php if($filter !== ''): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>"><?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari nama, NISN..." style="padding:6px 12px; border-radius:20px; border:1px solid #ccc;">
                    <button type="submit" style="border-radius:20px; padding:6px 12px;"><i class="fas fa-search"></i></button>
                    <?php if($search !== ''): ?>
                        <a href="?id_tahun=<?= $id_tahun_terpilih ?><?= $filter ? '&filter='.$filter : '' ?>" style="border-radius:50%; background:#ccc; padding:6px 10px;">✕</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TOMBOL PROSES SEMUA (jika ada pendaftar menunggu) -->
            <?php if ($jumlah_menunggu > 0 && !$is_nonaktif): ?>
            <div class="bulk-actions" style="margin-bottom: 15px; text-align: right;">
                <button type="button" id="btnProsesSemua" class="btn-proses-semua">
                    <i class="fas fa-tasks"></i> Proses Semua Pendaftar (<?= $jumlah_menunggu ?>)
                </button>
            </div>
            <?php endif; ?>

            <!-- REMINDER WA BELUM TERKIRIM -->
            <?php
            $query_reminder = "SELECT id_pendaftaran, nama_lengkap, no_hp, status 
                               FROM pendaftaran 
                               WHERE id_tahun_ajaran = $id_tahun_terpilih 
                               AND wa_sent = 0 
                               AND status IN ('diterima', 'ditolak')
                               ORDER BY id_pendaftaran DESC";
            $res_reminder = mysqli_query($conn, $query_reminder);
            if (mysqli_num_rows($res_reminder) > 0) {
                echo '<div class="reminder-box" style="background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;">';
                echo '<strong><i class="fas fa-bell"></i> Reminder Belum Kirim WhatsApp:</strong><br>';
                echo '<table style="width:100%; margin-top:10px; border-collapse:collapse;">';
                echo '<thead><tr><th>Nama</th><th>Status</th><th>Aksi</th></td></thead><tbody>';
                while ($row = mysqli_fetch_assoc($res_reminder)) {
                    $nama = htmlspecialchars($row['nama_lengkap']);
                    $status = $row['status'];
                    $no_hp = $row['no_hp'];
                    $clean_no = preg_replace('/[^0-9]/', '', $no_hp);
                    if (substr($clean_no, 0, 1) == '0') $clean_no = '62' . substr($clean_no, 1);
                    $wa_link = "https://wa.me/$clean_no?text=" . urlencode("Halo $nama, pendaftaran Anda dinyatakan $status. Terima kasih.");
                    echo "<tr data-id='{$row['id_pendaftaran']}'>
                             <td>{$nama}</td>
                             <td>{$status}</td>
                             <td>
                                <a href='{$wa_link}' target='_blank' class='btn-wa-reminder' style='background:#25d366; color:white; padding:4px 12px; border-radius:20px; text-decoration:none; font-size:12px;'>Kirim WA</a>
                                <button onclick='tandaiWA({$row['id_pendaftaran']}, this)' class='btn-tandai' style='background:#6c757d; color:white; border:none; padding:4px 12px; border-radius:20px; margin-left:5px;'>Tandai Terkirim</button>
                             </td>
                           </tr>";
                }
                echo '</tbody></table></div>';
            }
            ?>

            <!-- TABEL PENDAFTARAN -->
            <div class="table-card">
          <table id="tablePendaftaran">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Lengkap</th>
            <th>NISN</th>
            <th>Tanggal Daftar</th>
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
        <?php $no = $offset + 1; ?>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?= htmlspecialchars($row['nisn']); ?></td>
                    <td><?= date('d-m-Y H:i:s', strtotime($row['tanggal_daftar'])); ?></td>
                    <td><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                    <td><?= htmlspecialchars($row['tanggal_lahir']); ?></td>
                    <td><?= htmlspecialchars($row['no_hp']); ?></td>
                    <td><?= htmlspecialchars($row['asal_sekolah']); ?></td>
                    <td><?= htmlspecialchars($row['nama_wali']); ?></td>
                    <td>Rp <?= number_format($row['pendapatan_ortu'], 0, ',', '.'); ?></td>

                    <td>
                        <?php if ($row['status'] == 'menunggu'): ?>
                            <span class="badge waiting">Menunggu</span>
                        <?php elseif ($row['status'] == 'diterima'): ?>
                            <span class="badge accepted">Diterima</span>
                        <?php else: ?>
                            <span class="badge rejected">Ditolak</span>
                        <?php endif; ?>
                    </td>

                    <td class="action-cell">
                        <?php if ($is_nonaktif): ?>
                            <span class="badge badge-secondary">Arsip</span>
                        <?php elseif ($row['status'] == 'menunggu'): ?>
                            <a href="/admin/update_status.php?id=<?= $row['id_pendaftaran']; ?>&status=diterima&id_tahun=<?= $id_tahun_terpilih ?>" class="btn-accept" onclick="konfirmasiAksi(event, this.href, 'terima', this)">Terima</a>
                            <a href="/admin/update_status.php?id=<?= $row['id_pendaftaran']; ?>&status=ditolak&id_tahun=<?= $id_tahun_terpilih ?>" class="btn-reject" onclick="konfirmasiAksi(event, this.href, 'tolak', this)">Tolak</a>
                        <?php elseif ($row['status'] == 'diterima'): ?>
                            <button disabled class="btn-disabled accepted-disabled">Sudah diterima</button>
                        <?php else: ?>
                            <button disabled class="btn-disabled rejected-disabled">Sudah ditolak</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="12" class="empty-data">Data tidak ditemukan.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

                <!-- PAGINATION -->
                <div class="pagination-wrapper">
                    <p class="pagination-info">Menampilkan <?= $startData; ?> sampai <?= $endData; ?> dari <?= $totalData; ?> Pendaftar</p>
                    <div class="pagination">
                        <?php if ($page > 1) { ?><a href="<?= buildPageUrl($page-1, $search, $filter, $id_tahun_terpilih); ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php } else { ?><span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span><?php }
                        $startPage = max(1, $page-2); $endPage = min($totalPages, $page+2);
                        if ($page <= 3) $endPage = min($totalPages,5);
                        if ($page > $totalPages-2) $startPage = max(1, $totalPages-4);
                        for ($i=$startPage; $i<=$endPage; $i++) { ?>
                            <?php if ($i == $page) { ?><span class="page-btn active"><?= $i; ?></span><?php } else { ?><a href="<?= buildPageUrl($i, $search, $filter, $id_tahun_terpilih); ?>" class="page-btn"><?= $i; ?></a><?php } ?>
                        <?php } ?>
                        <?php if ($page < $totalPages) { ?><a href="<?= buildPageUrl($page+1, $search, $filter, $id_tahun_terpilih); ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php } else { ?><span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span><?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL ATUR PENDAFTARAN -->
<div id="modalAturPendaftaran" class="modal-atur">
    <div class="modal-box">
        <h3>Atur Pendaftaran</h3>
        <form id="formAturPendaftaran">
            <div class="form-group"><label>Tahun Ajaran</label><select name="id_tahun_ajaran" id="id_tahun_ajaran" required>
                <?php $ta_all = mysqli_query($conn, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC"); while($ta = mysqli_fetch_assoc($ta_all)): ?>
                    <option value="<?= $ta['id_tahun_ajaran'] ?>" <?= $ta['status'] == 'aktif' ? 'selected' : '' ?>><?= htmlspecialchars($ta['tahun_ajaran']) ?> (<?= $ta['status'] ?>)</option>
                <?php endwhile; ?>
            </select></div>
            <div class="form-group"><label>Kuota</label><input type="number" name="kuota" id="kuota" required></div>
            <div class="form-group"><label>Tanggal Buka Pendaftaran</label><input type="date" name="tgl_buka" id="tgl_buka" required></div>
            <div class="form-group"><label>Tanggal Tutup Pendaftaran</label><input type="date" name="tgl_tutup" id="tgl_tutup" required></div>
            <div class="form-group"><label>Status</label><select name="status" id="status"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select><small style="color:gray;">Hanya satu tahun ajaran yang boleh aktif.</small></div>
            <div class="btn-group"><button type="button" class="btn-batal" onclick="closeModalAtur()">Batal</button><button type="submit" class="btn-simpan">Simpan</button></div>
        </form>
    </div>
</div>

<script src="/admin/components/admin-nav.js?v=999"></script>
<script>
function openModalAtur() {
    let idTahun = <?= $id_tahun_terpilih ?>;
    fetch(`/admin/ajax_get_tahun_ajaran.php?id=${idTahun}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('id_tahun_ajaran').value = data.id_tahun_ajaran;
            document.getElementById('kuota').value = data.kuota;
            document.getElementById('tgl_buka').value = data.tgl_buka;
            document.getElementById('tgl_tutup').value = data.tgl_tutup;
            document.getElementById('status').value = data.status;
            document.getElementById('modalAturPendaftaran').style.display = 'flex';
        });
}
function closeModalAtur() {
    document.getElementById('modalAturPendaftaran').style.display = 'none';
}
document.getElementById('formAturPendaftaran').addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    fetch('/admin/ajax_update_tahun_ajaran.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) Swal.fire('Berhasil', 'Pengaturan pendaftaran disimpan', 'success').then(() => location.reload());
            else Swal.fire('Gagal', data.message, 'error');
        });
});

// ========== PROSES SEMUA PENDAFTAR ==========
document.getElementById('btnProsesSemua')?.addEventListener('click', function() {
    Swal.fire({
        title: 'Proses Semua Pendaftar?',
        text: 'Semua pendaftar dengan status "Menunggu" akan diproses otomatis (diterima jika kuota masih ada, ditolak jika kuota penuh).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0f5d5d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Proses Semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            fetch('/admin/proses_semua_pendaftar.php?id_tahun=<?= $id_tahun_terpilih ?>')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', 'Terjadi kesalahan: ' + error, 'error');
                });
        }
    });
});

// ========== FUNGSI KONFIRMASI AKSI (TERIMA/TOLAK) + REMINDER HANYA SAAT KLIK "NANTI SAJA" ==========
function konfirmasiAksi(event, url, aksi, elemenTombol) {
    event.preventDefault(); 
    let judul = aksi === 'terima' ? 'Terima Pendaftaran?' : 'Tolak Pendaftaran?';
    let teks = aksi === 'terima' ? 'Siswa akan resmi terdaftar di sistem.' : 'Data pendaftaran siswa ini akan ditolak.';
    let warnaTombol = aksi === 'terima' ? '#22c55e' : '#ef4444'; 
    Swal.fire({
        title: judul, text: teks, icon: aksi === 'terima' ? 'success' : 'warning',
        showCancelButton: true, confirmButtonColor: warnaTombol, cancelButtonColor: '#eef2f3',
        confirmButtonText: 'Ya, Lanjutkan!', cancelButtonText: '<span style="color:#0f5d5d;font-weight:600;">Batal</span>',
        borderRadius: '24px'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Memproses Data...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 1. Update tampilan di tabel utama
                    let tr = elemenTombol.closest('tr');
                    let tdStatus = tr.querySelector('td:nth-child(11)');
                    let tdAksi = tr.querySelector('.action-cell');
                    if (aksi === 'terima') {
                        tdStatus.innerHTML = '<span class="badge accepted">Diterima</span>';
                        tdAksi.innerHTML = '<button type="button" class="btn-disabled accepted-disabled" disabled>Sudah diterima</button>';
                    } else {
                        tdStatus.innerHTML = '<span class="badge rejected">Ditolak</span>';
                        tdAksi.innerHTML = '<button type="button" class="btn-disabled rejected-disabled" disabled>Sudah ditolak</button>';
                    }

                    // 2. Ambil data untuk reminder (nama, status baru, no_hp, id)
                    let nama = tr.querySelector('td:nth-child(2)').innerText;
                    let statusBaru = aksi === 'terima' ? 'diterima' : 'ditolak';
                    let no_hp = tr.querySelector('td:nth-child(7)').innerText;
                    let idPendaftaran = url.match(/id=(\d+)/)[1];
                    let clean_no = no_hp.replace(/[^0-9]/g, '');
                    if (clean_no.startsWith('0')) clean_no = '62' + clean_no.substring(1);
                    let wa_link = `https://wa.me/${clean_no}?text=${encodeURIComponent('Halo ' + nama + ', pendaftaran Anda dinyatakan ' + statusBaru + '. Terima kasih.')}`;

                    // 3. Tampilkan popup hasil (dengan tombol Kirim WA / Nanti Saja)
                    let judulHasil = aksi === 'terima' ? 'Pendaftaran Diterima' : 'Pendaftaran Ditolak';
                    let textHasil = data.link_wa !== '' 
                        ? `Data pendaftaran ${data.nama_siswa} berhasil ${aksi}. Kirim pemberitahuan WhatsApp?` 
                        : `Data pendaftaran ${data.nama_siswa} berhasil ${aksi}.`;
                    let swalOptions = {
                        title: judulHasil,
                        text: textHasil,
                        icon: aksi === 'terima' ? 'success' : 'error',
                        borderRadius: '24px'
                    };
                    if (data.link_wa !== '') {
                        swalOptions.showCancelButton = true;
                        swalOptions.confirmButtonColor = '#22c55e';
                        swalOptions.cancelButtonColor = '#eef2f3';
                        swalOptions.confirmButtonText = 'Kirim ke WhatsApp';
                        swalOptions.cancelButtonText = '<span style="color:#0f5d5d;font-weight:600;">Nanti Saja</span>';
                        swalOptions.reverseButtons = true;
                    } else {
                        swalOptions.confirmButtonColor = '#0f5d5d';
                        swalOptions.confirmButtonText = 'Tutup';
                    }
                    
                    Swal.fire(swalOptions).then((result2) => {
                        // Jika klik "Kirim ke WhatsApp" -> buka WA, jangan tambah reminder
                        if (result2.isConfirmed && data.link_wa !== '') {
                            window.open(data.link_wa, '_blank');
                            // Tidak tambah reminder
                        } 
                        // Jika klik "Nanti Saja" (cancel) atau jika tidak ada WA (tutup)
                        else if ((result2.dismiss === Swal.DismissReason.cancel) || (data.link_wa === '' && result2.isConfirmed)) {
                            // Tambah reminder
                            tambahKeReminder(nama, statusBaru, wa_link, idPendaftaran);
                        }
                        // Jika popup hanya memiliki tombol "Tutup" (tidak ada WA) dan user klik Tutup -> tambah reminder
                        else if (data.link_wa === '' && result2.isConfirmed) {
                            tambahKeReminder(nama, statusBaru, wa_link, idPendaftaran);
                        }
                    });
                } else {
                    Swal.fire({ title: 'Oops!', text: data.message, icon: 'warning', confirmButtonColor: '#0f5d5d' });
                }
            })
            .catch(error => { Swal.fire('Error!', 'Terjadi kesalahan pada server.', 'error'); console.error(error); });
        }
    });
}

// Fungsi untuk menambah baris ke reminder box (digunakan hanya saat "Nanti Saja")
function tambahKeReminder(nama, statusBaru, wa_link, idPendaftaran) {
    let reminderBox = document.querySelector('.reminder-box');
    let reminderTable = reminderBox ? reminderBox.querySelector('table') : null;
    if (!reminderBox) {
        let newBox = document.createElement('div');
        newBox.className = 'reminder-box';
        newBox.style.cssText = 'background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 8px;';
        newBox.innerHTML = '<strong><i class="fas fa-bell"></i> Reminder Belum Kirim WhatsApp:</strong><br>';
        let newTable = document.createElement('table');
        newTable.style.width = '100%';
        newTable.style.marginTop = '10px';
        newTable.style.borderCollapse = 'collapse';
        newTable.innerHTML = '<thead><tr><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead><tbody></tbody>';
        newBox.appendChild(newTable);
        document.querySelector('.table-card').parentNode.insertBefore(newBox, document.querySelector('.table-card'));
        reminderBox = newBox;
        reminderTable = newTable;
    }
    let tbody = reminderTable.querySelector('tbody');
    let newRow = document.createElement('tr');
    newRow.setAttribute('data-id', idPendaftaran);
    newRow.innerHTML = `<td>${nama}</td><td>${statusBaru}</td><td><a href="${wa_link}" target="_blank" class="btn-wa-reminder" style="background:#25d366; color:white; padding:4px 12px; border-radius:20px; text-decoration:none; font-size:12px;">Kirim WA</a> <button onclick="tandaiWA(${idPendaftaran}, this)" class="btn-tandai" style="background:#6c757d; color:white; border:none; padding:4px 12px; border-radius:20px; margin-left:5px;">Tandai Terkirim</button></td>`;
    tbody.appendChild(newRow);
}

// ========== FUNGSI TANDAI WA TERKIRIM (HAPUS BARIS TANPA RELOAD) ==========
function tandaiWA(id, btn) {
    Swal.fire({ title: 'Menandai...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    fetch(`/admin/mark_wa_sent.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                let row = btn.closest('tr');
                if (row) row.remove();
                let reminderBox = document.querySelector('.reminder-box');
                if (reminderBox && reminderBox.querySelectorAll('tbody tr').length === 0) {
                    reminderBox.remove();
                }
                Swal.fire('Sukses', 'WA ditandai terkirim', 'success');
            } else {
                Swal.fire('Gagal', data.message || 'Gagal menandai', 'error');
            }
        })
        .catch(error => { Swal.fire('Error', 'Terjadi kesalahan server', 'error'); });
}
</script>
</body>
</html>