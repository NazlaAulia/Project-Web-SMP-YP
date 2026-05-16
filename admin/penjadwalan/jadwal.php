<?php
require_once __DIR__ . '/../koneksi.php';

// ========== AMBIL SEMUA TAHUN AJARAN UNTUK DROPDOWN ==========
$daftar_tahun = [];
$resultAll = $conn->query("SELECT id_tahun_ajaran, tahun_ajaran, status, jadwal_locked FROM tahun_ajaran ORDER BY id_tahun_ajaran DESC");
while ($row = $resultAll->fetch_assoc()) {
    $daftar_tahun[] = $row;
}

// ========== TENTUKAN TAHUN YANG DIPILIH ==========
$id_tahun_dipilih = 0;
$tahun_dipilih = null;
$is_tahun_aktif = false;
$is_locked = false;

if (isset($_GET['id_tahun']) && is_numeric($_GET['id_tahun'])) {
    $id_cari = (int)$_GET['id_tahun'];
    foreach ($daftar_tahun as $t) {
        if ($t['id_tahun_ajaran'] == $id_cari) {
            $tahun_dipilih = $t;
            $id_tahun_dipilih = $id_cari;
            $is_tahun_aktif = ($t['status'] == 'aktif');
            $is_locked = (bool)$t['jadwal_locked'];
            break;
        }
    }
}
if (!$tahun_dipilih) {
    foreach ($daftar_tahun as $t) {
        if ($t['status'] == 'aktif') {
            $tahun_dipilih = $t;
            $id_tahun_dipilih = $t['id_tahun_ajaran'];
            $is_tahun_aktif = true;
            $is_locked = (bool)$t['jadwal_locked'];
            break;
        }
    }
}
if (!$tahun_dipilih && !empty($daftar_tahun)) {
    $tahun_dipilih = $daftar_tahun[0];
    $id_tahun_dipilih = $tahun_dipilih['id_tahun_ajaran'];
    $is_tahun_aktif = ($tahun_dipilih['status'] == 'aktif');
    $is_locked = (bool)$tahun_dipilih['jadwal_locked'];
}

// ========== STATISTIK (BERDASARKAN TAHUN DIPILIH) ==========
$total_jadwal = 0;
$total_guru = 0;
$total_kelas = 0;
$total_mapel = 0;

if ($id_tahun_dipilih > 0) {
    $qJadwal = $conn->prepare("SELECT COUNT(*) AS total FROM jadwal WHERE id_tahun_ajaran = ?");
    $qJadwal->bind_param("i", $id_tahun_dipilih);
    $qJadwal->execute();
    $res = $qJadwal->get_result();
    if ($row = $res->fetch_assoc()) $total_jadwal = $row['total'];
    $qJadwal->close();
}

$qGuru = $conn->query("SELECT COUNT(*) AS total FROM guru");
if ($qGuru) $total_guru = $qGuru->fetch_assoc()['total'];

$qKelas = $conn->query("SELECT COUNT(*) AS total FROM kelas");
if ($qKelas) $total_kelas = $qKelas->fetch_assoc()['total'];

$qMapel = $conn->query("SELECT COUNT(*) AS total FROM mapel");
if ($qMapel) $total_mapel = $qMapel->fetch_assoc()['total'];

// ========== QUERY JADWAL BERDASARKAN TAHUN DIPILIH (TAMBAHKAN STATUS) ==========
$jadwal_by_kelas = [];
$hari_urutan = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

if ($id_tahun_dipilih > 0) {
    $query = "
        SELECT 
            j.id_jadwal,
            j.hari,
            j.jam,
            j.jp_mulai,
            j.jp_selesai,
            j.jumlah_jp,
            j.status,
            k.id_kelas,
            g.nama AS nama_guru,
            k.nama_kelas,
            m.nama_mapel
        FROM jadwal j
        LEFT JOIN guru g ON j.id_guru = g.id_guru
        LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
        WHERE j.id_tahun_ajaran = ?
        ORDER BY 
            k.nama_kelas ASC,
            FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
            COALESCE(j.jp_mulai, 0),
            j.jam ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_tahun_dipilih);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $kelas = $row['nama_kelas'] ?? 'Tanpa Kelas';
            $hari = $row['hari'] ?? '-';
            if (!isset($jadwal_by_kelas[$kelas])) $jadwal_by_kelas[$kelas] = [];
            if (!isset($jadwal_by_kelas[$kelas][$hari])) $jadwal_by_kelas[$kelas][$hari] = [];
            $jadwal_by_kelas[$kelas][$hari][] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Mengajar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/admin/components/admin-nav.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-teal: #064e4b;
            --soft-teal: #ecfdf5;
            --dark-text: #1f2937;
            --muted-text: #6b7280;
            --white: #ffffff;
            --border: #e5e7eb;
            --danger: #ef4444;
            --success: #22c55e;
            --blue-soft: #eff6ff;
            --blue: #2563eb;
            --draft-bg: #fef3c7;
            --draft-text: #92400e;
            --fix-bg: #dcfce7;
            --fix-text: #166534;
        }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { margin: 0; background: #f5f7fb; color: var(--dark-text); }
        .page-header { background: linear-gradient(135deg, var(--primary-teal), #115e59); color: white; padding: 26px; border-radius: 24px; margin-bottom: 24px; box-shadow: 0 14px 30px rgba(15, 118, 110, 0.20); }
        .page-header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .page-title h1 { margin: 0; font-size: 28px; font-weight: 700; }
        .page-title p { margin: 8px 0 0; font-size: 14px; opacity: 0.88; max-width: 760px; line-height: 1.7; }
        .header-badge { background: rgba(255, 255, 255, 0.16); border: 1px solid rgba(255, 255, 255, 0.25); color: white; padding: 10px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
        .tahun-selector { background: rgba(255,255,255,0.2); border-radius: 40px; padding: 5px 15px; display: flex; align-items: center; gap: 10px; }
        .tahun-selector select { background: white; border: none; border-radius: 30px; padding: 8px 15px; font-size: 14px; font-weight: 500; color: #064e4b; cursor: pointer; outline: none; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--white); padding: 20px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); display: flex; align-items: center; gap: 14px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: var(--soft-teal); color: var(--primary-teal); font-size: 20px; flex-shrink: 0; }
        .stat-info span { font-size: 13px; color: var(--muted-text); font-weight: 500; }
        .stat-info h3 { margin: 4px 0 0; font-size: 26px; line-height: 1; color: var(--dark-text); }
        .action-panel { background: var(--white); border-radius: 22px; padding: 22px; border: 1px solid var(--border); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
        .action-text h2 { margin: 0; font-size: 20px; color: var(--dark-text); }
        .action-text p { margin: 7px 0 0; color: var(--muted-text); font-size: 14px; line-height: 1.6; }
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { border: none; border-radius: 14px; padding: 12px 17px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: 0.25s ease; }
        .btn-primary { background: var(--primary-teal); color: white; }
        .btn-primary:hover { background: #115e59; transform: translateY(-1px); }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-light { background: #eef2f7; color: #334155; }
        .status-box { display: none; margin-top: 14px; padding: 13px 15px; border-radius: 14px; font-size: 14px; font-weight: 500; }
        .status-loading { display: block; background: var(--blue-soft); color: #1d4ed8; }
        .status-success { display: block; background: #dcfce7; color: #166534; }
        .status-error { display: block; background: #fee2e2; color: #991b1b; }
        .table-card { background: var(--white); border-radius: 22px; padding: 22px; border: 1px solid var(--border); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
        .table-header { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .table-header h2 { margin: 0; font-size: 20px; }
        .table-header p { margin: 6px 0 0; color: var(--muted-text); font-size: 14px; }
        .search-box { position: relative; width: 320px; max-width: 100%; }
        .search-box i { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: var(--muted-text); font-size: 14px; }
        .search-box input { width: 100%; border: 1px solid var(--border); border-radius: 14px; padding: 11px 14px 11px 38px; outline: none; font-size: 14px; }
        .search-box input:focus { border-color: var(--primary-teal); box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.10); }
        .schedule-wrapper { display: grid; gap: 24px; }
        .class-schedule-card { background: #ffffff; border: 1px solid var(--border); border-radius: 22px; overflow: hidden; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06); }
        .class-schedule-header { padding: 18px 22px; background: linear-gradient(135deg, var(--primary-teal), #115e59); color: white; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
        .class-schedule-header h3 { margin: 0; font-size: 20px; font-weight: 700; }
        .class-schedule-header span { font-size: 13px; opacity: 0.9; display: inline-flex; align-items: center; gap: 7px; }
        .print-class-btn { background: white; color: var(--primary-teal); padding: 8px 11px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s ease; }
        .print-class-btn:hover { background: #ecfdf5; transform: translateY(-1px); }
        .day-grid { display: grid; grid-template-columns: repeat(5, minmax(230px, 1fr)); overflow-x: auto; }
        .day-column { min-width: 230px; border-right: 1px solid var(--border); background: #ffffff; }
        .day-column:last-child { border-right: none; }
        .day-title { padding: 14px 16px; background: #f8fafc; color: var(--primary-teal); font-weight: 700; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .day-title small { color: #64748b; font-weight: 600; background: #e2e8f0; padding: 4px 8px; border-radius: 999px; white-space: nowrap; }
        .lesson-list { padding: 12px; display: grid; gap: 10px; }
        .lesson-card { border: 1px solid #e2e8f0; border-radius: 16px; padding: 12px; background: #ffffff; transition: 0.2s ease; position: relative; }
        .lesson-card:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08); }
        .lesson-time { font-size: 12px; color: #475569; font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
        .lesson-mapel { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 5px; }
        .lesson-guru { font-size: 12px; color: #64748b; line-height: 1.5; display: flex; align-items: flex-start; gap: 6px; }
        .lesson-jp { margin-top: 8px; display: inline-flex; padding: 4px 8px; border-radius: 999px; background: var(--soft-teal); color: var(--primary-teal); font-size: 11px; font-weight: 700; }
        .btn-hapus-jadwal { background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 11px; cursor: pointer; transition: 0.2s; margin-top: 8px; width: 100%; }
        .btn-hapus-jadwal:hover { background: #dc2626; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-fix { background: #dcfce7; color: #166534; }
        .empty-day { padding: 20px 14px; color: #94a3b8; font-size: 13px; text-align: center; }
        .empty-state { text-align: center; padding: 42px 20px; color: var(--muted-text); }
        .empty-state i { font-size: 38px; color: #cbd5e1; margin-bottom: 12px; }
        .empty-state h3 { margin: 0 0 6px; color: #334155; }
        .empty-state p { margin: 0; font-size: 14px; }
        .custom-modal-overlay { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); display: none; align-items: center; justify-content: center; z-index: 3000; padding: 20px; }
        .custom-modal-overlay.active { display: flex; }
        .custom-modal-box { width: 100%; max-width: 550px; background: #fff; border-radius: 22px; padding: 34px 28px 26px; text-align: center; box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22); animation: modalFadeIn 0.25s ease; }
        .custom-modal-icon { width: 94px; height: 94px; border-radius: 50%; margin: 0 auto 18px; display: flex; align-items: center; justify-content: center; font-size: 42px; border: 4px solid #d9f0d2; color: #8bc34a; background: #f7fff3; }
        .custom-modal-icon.loading { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
        .custom-modal-icon.error { color: #ef4444; border-color: #fecaca; background: #fff5f5; }
        .custom-modal-box h3 { margin: 0 0 8px; font-size: 24px; color: #444; font-weight: 700; }
        .custom-modal-box p { margin: 0; font-size: 15px; color: #666; line-height: 1.7; }
        .custom-modal-actions { margin-top: 26px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .modal-btn { min-width: 138px; height: 46px; padding: 0 18px; border-radius: 12px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.25s ease; }
        .modal-btn.confirm { background: #22c55e; color: white; }
        .modal-btn.confirm:hover { background: #16a34a; }
        .modal-btn.cancel, .modal-btn.secondary { background: #e9eef1; color: #244; }
        .modal-btn.cancel:hover, .modal-btn.secondary:hover { background: #dbe3e8; }
        .hidden { display: none !important; }
        @keyframes modalFadeIn { from { opacity: 0; transform: translateY(16px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @media (max-width: 1200px) { .day-grid { grid-template-columns: repeat(5, 250px); } }
        @media (max-width: 992px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { 
            .page-header { padding: 22px; border-radius: 20px; }
            .page-title h1 { font-size: 22px; }
            .stats-grid { grid-template-columns: 1fr; }
            .action-panel { align-items: stretch; }
            .action-buttons { width: 100%; }
            .btn { width: 100%; }
            .search-box { width: 100%; }
            .custom-modal-box { padding: 28px 20px 22px; }
            .custom-modal-actions { flex-direction: column; }
            .modal-btn { width: 100%; }
            .lesson-mapel { flex-direction: column; align-items: flex-start; }
            .btn-hapus-jadwal { width: 100%; }
        }
    </style>
</head>
<body data-page="jadwal" data-nav-path="/admin/components/admin-nav.html">
    <div class="container">
        <div id="admin-nav-root"></div>
        <main class="main-content">
            <section class="page-header">
                <div class="page-header-top">
                    <div class="page-title">
                        <h1>Jadwal Mengajar</h1>
                        <p>
                            Pilih tahun ajaran untuk melihat jadwal. Untuk tahun aktif, Anda dapat generate atau mengunci jadwal.
                        </p>
                    </div>
                    <div class="tahun-selector">
                        <i class="fas fa-calendar-alt"></i>
                        <select id="tahunAjaranSelect" onchange="pindahTahun()">
                            <?php foreach ($daftar_tahun as $t): ?>
                                <option value="<?= $t['id_tahun_ajaran'] ?>" <?= ($id_tahun_dipilih == $t['id_tahun_ajaran']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['tahun_ajaran']) ?> 
                                    <?= $t['status'] == 'aktif' ? '(Aktif)' : '' ?>
                                    <?= $t['jadwal_locked'] ? '🔒' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <section class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-days"></i></div><div class="stat-info"><span>Total Jadwal</span><h3><?= (int)$total_jadwal ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div><div class="stat-info"><span>Total Guru</span><h3><?= (int)$total_guru ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-school"></i></div><div class="stat-info"><span>Total Kelas</span><h3><?= (int)$total_kelas ?></h3></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-book-open"></i></div><div class="stat-info"><span>Total Mapel</span><h3><?= (int)$total_mapel ?></h3></div></div>
            </section>

            <?php if ($is_tahun_aktif): ?>
            <section class="action-panel">
                <div class="action-text">
                    <h2>Generate Jadwal Otomatis</h2>
                    <p>Klik tombol generate untuk menjalankan proses pembuatan jadwal untuk tahun ajaran <strong><?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?></strong>.</p>
                    <div id="statusBox" class="status-box"></div>
                </div>
                <div class="action-buttons">
                    <?php if (!$is_locked): ?>
                        <button type="button" class="btn btn-primary" onclick="openGenerateModal()">
                            <i class="fas fa-wand-magic-sparkles"></i> Generate Jadwal
                        </button>
                        <button type="button" class="btn btn-warning" id="lockJadwalBtn">
                            <i class="fas fa-lock"></i> Kunci Jadwal
                        </button>
                        <button type="button" class="btn btn-danger" id="hapusSemuaJadwalBtn">
                            <i class="fas fa-trash-alt"></i> Hapus Semua Jadwal
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" disabled style="opacity:0.6; cursor:not-allowed;">
                            <i class="fas fa-lock"></i> Generate Jadwal (Terkunci)
                        </button>
                    <?php endif; ?>
                    <a href="/admin/penjadwalan/jadwal.php?id_tahun=<?= $id_tahun_dipilih ?>" class="btn btn-light"><i class="fas fa-rotate-right"></i> Refresh</a>
                </div>
                <?php if ($is_locked): ?>
                    <div style="margin-top:10px; background:#fef3c7; border-left:4px solid #f59e0b; padding:10px; border-radius:8px;">
                        <i class="fas fa-info-circle"></i> Jadwal untuk tahun ajaran <?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?> sudah dikunci. Generate tidak dapat dilakukan.
                    </div>
                <?php endif; ?>
            </section>
            <?php else: ?>
            <section class="action-panel" style="background:#f1f5f9;">
                <div class="action-text">
                    <h2>Mode Lihat Saja</h2>
                    <p>Tahun ajaran <strong><?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?></strong> tidak aktif. Anda hanya dapat melihat jadwal.</p>
                </div>
                <div class="action-buttons">
                    <a href="/admin/penjadwalan/jadwal.php?id_tahun=<?= $id_tahun_dipilih ?>" class="btn btn-light"><i class="fas fa-rotate-right"></i> Refresh</a>
                </div>
            </section>
            <?php endif; ?>

            <section class="table-card">
                <div class="table-header">
                    <div>
                        <h2>Jadwal Mengajar Per Kelas</h2>
                        <p>Tahun Ajaran: <strong><?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?></strong></p>
                    </div>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari kelas, guru, mapel, hari...">
                    </div>
                </div>

                <?php if (!empty($jadwal_by_kelas)): ?>
                    <div class="schedule-wrapper" id="jadwalTable">
                        <?php foreach ($jadwal_by_kelas as $nama_kelas => $jadwal_harian): ?>
                            <div class="class-schedule-card">
                                <div class="class-schedule-header">
                                    <h3>Kelas <?php echo htmlspecialchars($nama_kelas); ?></h3>
                                    <?php
                                        $id_kelas_cetak = 0;
                                        foreach ($jadwal_harian as $hariItem) {
                                            if (!empty($hariItem) && !empty($hariItem[0]['id_kelas'])) {
                                                $id_kelas_cetak = (int)$hariItem[0]['id_kelas'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                        <span><i class="fas fa-calendar-days"></i> Jadwal Mingguan</span>
                                        <?php if ($id_kelas_cetak > 0): ?>
                                            <a href="/admin/penjadwalan/cetak_jadwal_kelas.php?id_kelas=<?php echo $id_kelas_cetak; ?>" target="_blank" class="print-class-btn">
                                                <i class="fas fa-file-pdf"></i> Cetak PDF
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="day-grid">
                                    <?php foreach ($hari_urutan as $hari): ?>
                                        <?php
                                            $list_jadwal = $jadwal_harian[$hari] ?? [];
                                            $total_jp_hari = 0;
                                            foreach ($list_jadwal as $item) $total_jp_hari += (int)($item['jumlah_jp'] ?? 1);
                                        ?>
                                        <div class="day-column">
                                            <div class="day-title"><span><?php echo htmlspecialchars($hari); ?></span><small><?php echo $total_jp_hari; ?> JP</small></div>
                                            <?php if (!empty($list_jadwal)): ?>
                                                <div class="lesson-list">
                                                    <?php foreach ($list_jadwal as $item): ?>
                                                        <div class="lesson-card" data-id-jadwal="<?= $item['id_jadwal'] ?>">
                                                            <div class="lesson-time"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($item['jam'] ?? '-'); ?></div>
                                                            <div class="lesson-mapel">
                                                                <span><?php echo htmlspecialchars($item['nama_mapel'] ?? '-'); ?></span>
                                                                <span class="status-badge <?php echo $item['status'] == 'fix' ? 'status-fix' : 'status-draft'; ?>">
                                                                    <?php echo $item['status'] == 'fix' ? '🔒 FIX' : '📝 DRAFT'; ?>
                                                                </span>
                                                            </div>
                                                            <div class="lesson-guru"><i class="fas fa-chalkboard-user"></i> <span><?php echo htmlspecialchars($item['nama_guru'] ?? 'Belum ada guru'); ?></span></div>
                                                            <div class="lesson-jp">JP <?php echo htmlspecialchars($item['jp_mulai'] ?? '-'); ?> - <?php echo htmlspecialchars($item['jp_selesai'] ?? '-'); ?> | <?php echo (int)($item['jumlah_jp'] ?? 1); ?> JP</div>
                                                            <?php if (!$is_locked && $is_tahun_aktif): ?>
                                                                <button type="button" class="btn-hapus-jadwal" onclick="hapusJadwal(<?= $item['id_jadwal'] ?>, this)">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="empty-day">Tidak ada jadwal</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-xmark"></i>
                        <h3>Belum ada jadwal</h3>
                        <p>Untuk tahun ajaran <?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?>, belum ada jadwal. <?= $is_tahun_aktif && !$is_locked ? 'Klik tombol Generate untuk membuat jadwal.' : '' ?></p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- MODAL KONFIRMASI GENERATE -->
    <div class="custom-modal-overlay" id="generateModal">
        <div class="custom-modal-box">
            <div class="custom-modal-icon"><i class="fas fa-check"></i></div>
            <h3>Generate Jadwal?</h3>
            <p>Jadwal lama untuk tahun ajaran <strong><?= htmlspecialchars($tahun_dipilih['tahun_ajaran']) ?></strong> akan dihapus dan digenerate ulang. Pastikan data sudah benar.</p>
            <div class="custom-modal-actions">
                <button type="button" class="modal-btn confirm" id="confirmGenerateBtn">Ya, Lanjutkan!</button>
                <button type="button" class="modal-btn cancel" onclick="closeGenerateModal()">Batal</button>
            </div>
        </div>
    </div>

    <!-- MODAL HASIL GENERATE -->
    <div class="custom-modal-overlay" id="resultModal">
        <div class="custom-modal-box">
            <div class="custom-modal-icon" id="resultModalIcon"><i class="fas fa-check"></i></div>
            <h3 id="resultModalTitle">Berhasil</h3>
            <p id="resultModalText">Proses berhasil dijalankan.</p>
            <div class="custom-modal-actions"><button type="button" class="modal-btn secondary" id="resultModalBtn" onclick="closeResultModal()">Tutup</button></div>
        </div>
    </div>

    <script src="/admin/components/admin-nav.js"></script>
    <script>
        function pindahTahun() {
            let id = document.getElementById('tahunAjaranSelect').value;
            window.location.href = 'jadwal.php?id_tahun=' + id;
        }
        function setStatus(type, message) {
            const statusBox = document.getElementById('statusBox');
            if(!statusBox) return;
            statusBox.className = 'status-box';
            if (type === 'loading') statusBox.classList.add('status-loading');
            else if (type === 'success') statusBox.classList.add('status-success');
            else statusBox.classList.add('status-error');
            statusBox.innerHTML = message;
        }
        function openGenerateModal() { document.getElementById('generateModal').classList.add('active'); }
        function closeGenerateModal() { document.getElementById('generateModal').classList.remove('active'); }
        function closeResultModal() { document.getElementById('resultModal').classList.remove('active'); }
        function showResultModal(type, title, text, reloadAfter = false) {
            const modal = document.getElementById('resultModal');
            const icon = document.getElementById('resultModalIcon');
            const titleEl = document.getElementById('resultModalTitle');
            const textEl = document.getElementById('resultModalText');
            const btn = document.getElementById('resultModalBtn');
            icon.className = 'custom-modal-icon';
            btn.textContent = 'Tutup';
            if (type === 'loading') {
                icon.classList.add('loading');
                icon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.classList.add('hidden');
            } else if (type === 'success') {
                icon.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.remove('hidden');
            } else {
                icon.classList.add('error');
                icon.innerHTML = '<i class="fas fa-xmark"></i>';
                btn.classList.remove('hidden');
            }
            titleEl.textContent = title;
            textEl.textContent = text;
            modal.classList.add('active');
            if (reloadAfter) setTimeout(() => window.location.reload(), 1400);
        }
        function runGenerateJadwal() {
            closeGenerateModal();
            setStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Sedang generate jadwal...');
            showResultModal('loading', 'Sedang Diproses', 'Mohon tunggu, sistem sedang membuat jadwal otomatis.');
            fetch('/admin/penjadwalan/generate_master.php', { method: 'POST' })
            .then(async response => {
                const text = await response.text();
                let data = null;
                try { data = JSON.parse(text); } catch (e) { data = null; }
                if (!response.ok) throw new Error(text);
                if (data && data.success === false) throw new Error(data.message);
                const msg = data && data.message ? data.message : 'Generate jadwal berhasil dijalankan.';
                setStatus('success', '<i class="fas fa-check-circle"></i> ' + msg);
                showResultModal('success', 'Generate Berhasil!', msg, true);
            })
            .catch(error => {
                setStatus('error', '<i class="fas fa-circle-exclamation"></i> Gagal generate jadwal: ' + error.message);
                showResultModal('error', 'Generate Gagal', error.message);
            });
        }
        
        // ========== HAPUS SATU JADWAL ==========
        function hapusJadwal(idJadwal, btn) {
            let card = btn.closest('.lesson-card');
            
            Swal.fire({
                title: 'Hapus Jadwal?',
                text: 'Jadwal ini akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    fetch('/admin/penjadwalan/hapus_jadwal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id_jadwal=' + idJadwal
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            card.remove();
                            Swal.fire('Berhasil!', 'Jadwal dihapus', 'success');
                            // Update total jadwal di statistik
                            let totalSpan = document.querySelector('.stats-grid .stat-card:first-child .stat-info h3');
                            if (totalSpan) totalSpan.textContent = parseInt(totalSpan.textContent) - 1;
                        } else {
                            Swal.fire('Gagal!', data.message, 'error');
                        }
                    })
                    .catch(error => Swal.fire('Error!', 'Terjadi kesalahan', 'error'));
                }
            });
        }
        
        // ========== HAPUS SEMUA JADWAL ==========
        const hapusSemuaBtn = document.getElementById('hapusSemuaJadwalBtn');
        if (hapusSemuaBtn) {
            hapusSemuaBtn.addEventListener('click', function() {
                let idTahun = <?= $id_tahun_dipilih ?>;
                
                Swal.fire({
                    title: 'Hapus Semua Jadwal?',
                    text: 'SEMUA jadwal untuk tahun ajaran ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan!',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus Semua!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        fetch('/admin/penjadwalan/hapus_semua_jadwal.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id_tahun=' + idTahun
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error!', 'Terjadi kesalahan', 'error'));
                    }
                });
            });
        }
        
        const confirmGenerateBtn = document.getElementById('confirmGenerateBtn');
        if (confirmGenerateBtn) confirmGenerateBtn.addEventListener('click', runGenerateJadwal);
        const generateModal = document.getElementById('generateModal');
        if (generateModal) generateModal.addEventListener('click', function(e) { if (e.target === this) closeGenerateModal(); });
        const resultModal = document.getElementById('resultModal');
        if (resultModal) resultModal.addEventListener('click', function(e) { if (e.target === this) closeResultModal(); });
        const searchInput = document.getElementById('searchInput');
        const jadwalTable = document.getElementById('jadwalTable');
        if (searchInput && jadwalTable) {
            searchInput.addEventListener('keyup', function () {
                const keyword = this.value.toLowerCase();
                const cards = jadwalTable.querySelectorAll('.class-schedule-card');
                cards.forEach(card => { card.style.display = card.innerText.toLowerCase().includes(keyword) ? '' : 'none'; });
            });
        }
        
        // ========== TOMBOL KUNCI JADWAL DENGAN SWEETALERT ==========
        const lockBtn = document.getElementById('lockJadwalBtn');
        if (lockBtn) {
            lockBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Kunci Jadwal?',
                    text: "Setelah dikunci, jadwal untuk tahun ajaran ini tidak dapat digenerate ulang dan guru tidak bisa mengajukan perubahan.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Kunci Sekarang!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Sedang mengunci jadwal',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                        fetch('/admin/penjadwalan/kunci_jadwal.php', { method: 'POST' })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                                    window.location.href = 'jadwal.php?id_tahun=<?= $id_tahun_dipilih ?>';
                                });
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error!', 'Terjadi kesalahan: ' + err, 'error');
                        });
                    }
                });
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>