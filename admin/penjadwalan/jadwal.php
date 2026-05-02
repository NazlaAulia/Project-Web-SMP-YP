<?php
require_once __DIR__ . '/../koneksi.php';

$total_jadwal = 0;
$total_guru = 0;
$total_kelas = 0;
$total_mapel = 0;

$qJadwal = $conn->query("SELECT COUNT(*) AS total FROM jadwal");
if ($qJadwal) {
    $total_jadwal = $qJadwal->fetch_assoc()['total'];
}

$qGuru = $conn->query("SELECT COUNT(*) AS total FROM guru");
if ($qGuru) {
    $total_guru = $qGuru->fetch_assoc()['total'];
}

$qKelas = $conn->query("SELECT COUNT(*) AS total FROM kelas");
if ($qKelas) {
    $total_kelas = $qKelas->fetch_assoc()['total'];
}

$qMapel = $conn->query("SELECT COUNT(*) AS total FROM mapel");
if ($qMapel) {
    $total_mapel = $qMapel->fetch_assoc()['total'];
}

$query = "
    SELECT 
        j.id_jadwal,
        j.hari,
        j.jam,
        g.nama AS nama_guru,
        k.nama_kelas,
        m.nama_mapel
    FROM jadwal j
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        j.jam,
        k.nama_kelas
";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Mengajar - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font & Icon -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS Navbar -->
    <link rel="stylesheet" href="/admin/components/admin-nav.css">

    <style>
        :root {
            --primary-teal: #0f766e;
            --soft-teal: #ecfdf5;
            --dark-text: #1f2937;
            --muted-text: #6b7280;
            --white: #ffffff;
            --border: #e5e7eb;
            --danger: #ef4444;
            --success: #22c55e;
            --success-soft: #dcfce7;
            --danger-soft: #fee2e2;
            --blue-soft: #eff6ff;
            --blue: #2563eb;
        }

        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: var(--dark-text);
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-teal), #115e59);
            color: white;
            padding: 26px;
            border-radius: 24px;
            margin-bottom: 24px;
            box-shadow: 0 14px 30px rgba(15, 118, 110, 0.20);
        }

        .page-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.88;
            max-width: 760px;
            line-height: 1.7;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--white);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--soft-teal);
            color: var(--primary-teal);
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-info span {
            font-size: 13px;
            color: var(--muted-text);
            font-weight: 500;
        }

        .stat-info h3 {
            margin: 4px 0 0;
            font-size: 26px;
            line-height: 1;
            color: var(--dark-text);
        }

        .action-panel {
            background: var(--white);
            border-radius: 22px;
            padding: 22px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .action-text h2 {
            margin: 0;
            font-size: 20px;
            color: var(--dark-text);
        }

        .action-text p {
            margin: 7px 0 0;
            color: var(--muted-text);
            font-size: 14px;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 14px;
            padding: 12px 17px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.25s ease;
        }

        .btn-primary {
            background: var(--primary-teal);
            color: white;
        }

        .btn-primary:hover {
            background: #115e59;
            transform: translateY(-1px);
        }

        .btn-light {
            background: #eef2f7;
            color: #334155;
        }

        .btn-light:hover {
            background: #e2e8f0;
        }

        .status-box {
            display: none;
            margin-top: 14px;
            padding: 13px 15px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-loading {
            display: block;
            background: var(--blue-soft);
            color: #1d4ed8;
        }

        .status-success {
            display: block;
            background: #dcfce7;
            color: #166534;
        }

        .status-error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }

        .table-card {
            background: var(--white);
            border-radius: 22px;
            padding: 22px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .table-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .search-box {
            position: relative;
            width: 280px;
            max-width: 100%;
        }

        .search-box i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: var(--muted-text);
            font-size: 14px;
        }

        .search-box input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 11px 14px 11px 38px;
            outline: none;
            font-size: 14px;
        }

        .search-box input:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.10);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
            background: white;
        }

        th {
            background: #f8fafc;
            color: #475569;
            font-size: 13px;
            font-weight: 700;
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f9fafb;
        }

        .badge-day {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 11px;
            border-radius: 999px;
            background: var(--soft-teal);
            color: var(--primary-teal);
            font-size: 12px;
            font-weight: 700;
        }

        .time-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #475569;
        }

        .empty-state {
            text-align: center;
            padding: 42px 20px;
            color: var(--muted-text);
        }

        .empty-state i {
            font-size: 38px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            margin: 0 0 6px;
            color: #334155;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        /* MODAL */
        .custom-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 20px;
        }

        .custom-modal-overlay.active {
            display: flex;
        }

        .custom-modal-box {
            width: 100%;
            max-width: 550px;
            background: #fff;
            border-radius: 22px;
            padding: 34px 28px 26px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
            animation: modalFadeIn 0.25s ease;
        }

        .custom-modal-icon {
            width: 94px;
            height: 94px;
            border-radius: 50%;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            border: 4px solid #d9f0d2;
            color: #8bc34a;
            background: #f7fff3;
        }

        .custom-modal-icon.loading {
            color: #2563eb;
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .custom-modal-icon.error {
            color: #ef4444;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .custom-modal-box h3 {
            margin: 0 0 8px;
            font-size: 24px;
            color: #444;
            font-weight: 700;
        }

        .custom-modal-box p {
            margin: 0;
            font-size: 15px;
            color: #666;
            line-height: 1.7;
        }

        .custom-modal-actions {
            margin-top: 26px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .modal-btn {
            min-width: 138px;
            height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .modal-btn.confirm {
            background: #22c55e;
            color: white;
        }

        .modal-btn.confirm:hover {
            background: #16a34a;
        }

        .modal-btn.cancel,
        .modal-btn.secondary {
            background: #e9eef1;
            color: #244;
        }

        .modal-btn.cancel:hover,
        .modal-btn.secondary:hover {
            background: #dbe3e8;
        }

        .modal-btn.danger {
            background: #ef4444;
            color: white;
        }

        .modal-btn.danger:hover {
            background: #dc2626;
        }

        .hidden {
            display: none !important;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(16px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 22px;
                border-radius: 20px;
            }

            .page-title h1 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-panel {
                align-items: stretch;
            }

            .action-buttons {
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .search-box {
                width: 100%;
            }

            .custom-modal-box {
                padding: 28px 20px 22px;
            }

            .custom-modal-actions {
                flex-direction: column;
            }

            .modal-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body data-page="jadwal">
    <div id="admin-nav-root"></div>

    <div class="container">
        <main class="main-content">
            <section class="page-header">
                <div class="page-header-top">
                    <div class="page-title">
                        <h1>Jadwal Mengajar</h1>
                        <p>
                            Halaman ini digunakan admin untuk generate dan memantau jadwal mengajar sebelum semester dimulai.
                            Jadwal yang sudah dibuat akan menjadi acuan untuk guru dan kelas.
                        </p>
                    </div>

                    <div class="header-badge">
                        <i class="fas fa-calendar-check"></i>
                        Admin Jadwal
                    </div>
                </div>
            </section>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Jadwal</span>
                        <h3><?php echo (int)$total_jadwal; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-user"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Guru</span>
                        <h3><?php echo (int)$total_guru; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Kelas</span>
                        <h3><?php echo (int)$total_kelas; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Mapel</span>
                        <h3><?php echo (int)$total_mapel; ?></h3>
                    </div>
                </div>
            </section>

            <section class="action-panel">
                <div class="action-text">
                    <h2>Generate Jadwal Otomatis</h2>
                    <p>
                        Klik tombol generate untuk menjalankan proses pembuatan jadwal
                    </p>
                    <div id="statusBox" class="status-box"></div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn btn-primary" onclick="openGenerateModal()">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        Generate Jadwal
                    </button>

                    <a href="/admin/penjadwalan/jadwal.php" class="btn btn-light">
                        <i class="fas fa-rotate-right"></i>
                        Refresh
                    </a>
                </div>
            </section>

            <section class="table-card">
                <div class="table-header">
                    <h2>Daftar Jadwal Saat Ini</h2>

                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari guru, kelas, mapel, hari...">
                    </div>
                </div>

                <div class="table-wrap">
                    <table id="jadwalTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Hari</th>
                                <th>Jam</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php $no = 1; ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>

                                        <td>
                                            <span class="badge-day">
                                                <?php echo htmlspecialchars($row['hari'] ?? '-'); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="time-pill">
                                                <i class="fas fa-clock"></i>
                                                <?php echo htmlspecialchars($row['jam'] ?? '-'); ?>
                                            </span>
                                        </td>

                                        <td><?php echo htmlspecialchars($row['nama_kelas'] ?? '-'); ?></td>

                                        <td><?php echo htmlspecialchars($row['nama_mapel'] ?? '-'); ?></td>

                                        <td>
                                            <?php
                                                if (!empty($row['nama_guru'])) {
                                                    echo htmlspecialchars($row['nama_guru']);
                                                } else {
                                                    echo '<span style="color:#ef4444;font-weight:600;">Belum ada guru</span>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-xmark"></i>
                                            <h3>Belum ada jadwal</h3>
                                            <p>Klik tombol Generate Jadwal untuk mulai membuat jadwal mengajar.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL KONFIRMASI -->
    <div class="custom-modal-overlay" id="generateModal">
        <div class="custom-modal-box">
            <div class="custom-modal-icon">
                <i class="fas fa-check"></i>
            </div>
            <h3>Generate Jadwal?</h3>
            <p>
                Jadwal lama akan diperbarui dan sistem akan menjalankan proses generate jadwal otomatis.
                Pastikan data guru, kelas, dan mata pelajaran sudah benar.
            </p>

            <div class="custom-modal-actions">
                <button type="button" class="modal-btn confirm" id="confirmGenerateBtn">Ya, Lanjutkan!</button>
                <button type="button" class="modal-btn cancel" onclick="closeGenerateModal()">Batal</button>
            </div>
        </div>
    </div>

    <!-- MODAL HASIL / INFO -->
    <div class="custom-modal-overlay" id="resultModal">
        <div class="custom-modal-box">
            <div class="custom-modal-icon" id="resultModalIcon">
                <i class="fas fa-check"></i>
            </div>
            <h3 id="resultModalTitle">Berhasil</h3>
            <p id="resultModalText">Proses berhasil dijalankan.</p>

            <div class="custom-modal-actions">
                <button type="button" class="modal-btn secondary" id="resultModalBtn" onclick="closeResultModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script src="/admin/components/admin-nav.js"></script>

    <script>
        function setStatus(type, message) {
            const statusBox = document.getElementById('statusBox');

            statusBox.className = 'status-box';

            if (type === 'loading') {
                statusBox.classList.add('status-loading');
            } else if (type === 'success') {
                statusBox.classList.add('status-success');
            } else {
                statusBox.classList.add('status-error');
            }

            statusBox.innerHTML = message;
        }

        function openGenerateModal() {
            document.getElementById('generateModal').classList.add('active');
        }

        function closeGenerateModal() {
            document.getElementById('generateModal').classList.remove('active');
        }

        function closeResultModal() {
            document.getElementById('resultModal').classList.remove('active');
        }

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

            if (reloadAfter) {
                setTimeout(() => {
                    window.location.reload();
                }, 1400);
            }
        }

        function runGenerateJadwal() {
            closeGenerateModal();
            setStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Sedang generate jadwal...');
            showResultModal('loading', 'Sedang Diproses', 'Mohon tunggu, sistem sedang membuat jadwal otomatis.');

            fetch('/admin/penjadwalan/generate_master.php', {
                method: 'POST'
            })
            .then(async response => {
                const text = await response.text();

                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    data = null;
                }

                if (!response.ok) {
                    throw new Error(text || 'Request generate gagal.');
                }

                if (data && data.success === false) {
                    throw new Error(data.message || 'Generate jadwal gagal.');
                }

                const msg = data && data.message
                    ? data.message
                    : 'Generate jadwal berhasil dijalankan.';

                setStatus('success', '<i class="fas fa-check-circle"></i> ' + msg);
                showResultModal('success', 'Generate Berhasil!', msg, true);
            })
            .catch(error => {
                setStatus('error', '<i class="fas fa-circle-exclamation"></i> Gagal generate jadwal: ' + error.message);
                showResultModal('error', 'Generate Gagal', error.message);
            });
        }

        document.getElementById('confirmGenerateBtn').addEventListener('click', runGenerateJadwal);

        document.getElementById('generateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGenerateModal();
            }
        });

        document.getElementById('resultModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResultModal();
            }
        });

        const searchInput = document.getElementById('searchInput');
        const jadwalTable = document.getElementById('jadwalTable');

        if (searchInput && jadwalTable) {
            searchInput.addEventListener('keyup', function () {
                const keyword = this.value.toLowerCase();
                const rows = jadwalTable.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(keyword) ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>