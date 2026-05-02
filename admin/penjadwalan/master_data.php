<?php
require_once __DIR__ . '/../koneksi.php';

$success_message = '';
$error_message = '';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function redirectSelf($status, $message) {
    $url = '/admin/penjadwalan/master_data.php?' . http_build_query([
        'status' => $status,
        'message' => $message
    ]);

    header("Location: $url");
    exit;
}

if (isset($_GET['status'], $_GET['message'])) {
    if ($_GET['status'] === 'success') {
        $success_message = $_GET['message'];
    } else {
        $error_message = $_GET['message'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'tambah_jam') {
            $hari = trim($_POST['hari'] ?? '');
            $nomor_jp = (int)($_POST['nomor_jp'] ?? 0);
            $jam_mulai = trim($_POST['jam_mulai'] ?? '');
            $jam_selesai = trim($_POST['jam_selesai'] ?? '');
            $aktif = isset($_POST['aktif']) ? 1 : 0;

            if ($hari === '' || $nomor_jp <= 0 || $jam_mulai === '' || $jam_selesai === '') {
                throw new Exception('Data jam pelajaran belum lengkap.');
            }

            $stmt = $conn->prepare("
                INSERT INTO jam_pelajaran (hari, nomor_jp, jam_mulai, jam_selesai, aktif)
                VALUES (?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception('Gagal prepare tambah jam: ' . $conn->error);
            }

            $stmt->bind_param("sissi", $hari, $nomor_jp, $jam_mulai, $jam_selesai, $aktif);

            if (!$stmt->execute()) {
                throw new Exception('Gagal menambah jam pelajaran: ' . $stmt->error);
            }

            $stmt->close();

            redirectSelf('success', 'Jam pelajaran berhasil ditambahkan.');
        }

        if ($action === 'hapus_jam') {
            $id_jp = (int)($_POST['id_jp'] ?? 0);

            if ($id_jp <= 0) {
                throw new Exception('ID jam pelajaran tidak valid.');
            }

            $stmt = $conn->prepare("DELETE FROM jam_pelajaran WHERE id_jp = ?");
            if (!$stmt) {
                throw new Exception('Gagal prepare hapus jam: ' . $conn->error);
            }

            $stmt->bind_param("i", $id_jp);

            if (!$stmt->execute()) {
                throw new Exception('Gagal menghapus jam pelajaran: ' . $stmt->error);
            }

            $stmt->close();

            redirectSelf('success', 'Jam pelajaran berhasil dihapus.');
        }

        if ($action === 'reset_jam_default') {
            $conn->begin_transaction();

            if (!$conn->query("DELETE FROM jam_pelajaran")) {
                throw new Exception('Gagal menghapus jam lama: ' . $conn->error);
            }

            $stmt = $conn->prepare("
                INSERT INTO jam_pelajaran (hari, nomor_jp, jam_mulai, jam_selesai, aktif)
                VALUES (?, ?, ?, ?, 1)
            ");

            if (!$stmt) {
                throw new Exception('Gagal prepare reset jam: ' . $conn->error);
            }

            $data_jam = [
                ['Senin', 1, '07:00:00', '07:40:00'],
                ['Senin', 2, '07:40:00', '08:20:00'],
                ['Senin', 3, '08:20:00', '09:00:00'],
                ['Senin', 4, '09:20:00', '10:00:00'],
                ['Senin', 5, '10:00:00', '10:40:00'],
                ['Senin', 6, '10:40:00', '11:20:00'],
                ['Senin', 7, '11:20:00', '12:00:00'],
                ['Senin', 8, '12:20:00', '13:00:00'],
                ['Senin', 9, '13:00:00', '13:40:00'],
                ['Senin', 10, '13:40:00', '14:20:00'],
                ['Senin', 11, '14:20:00', '15:00:00'],

                ['Selasa', 1, '07:00:00', '07:40:00'],
                ['Selasa', 2, '07:40:00', '08:20:00'],
                ['Selasa', 3, '08:20:00', '09:00:00'],
                ['Selasa', 4, '09:20:00', '10:00:00'],
                ['Selasa', 5, '10:00:00', '10:40:00'],
                ['Selasa', 6, '10:40:00', '11:20:00'],
                ['Selasa', 7, '11:20:00', '12:00:00'],
                ['Selasa', 8, '12:20:00', '13:00:00'],
                ['Selasa', 9, '13:00:00', '13:40:00'],
                ['Selasa', 10, '13:40:00', '14:20:00'],
                ['Selasa', 11, '14:20:00', '15:00:00'],

                ['Rabu', 1, '07:00:00', '07:40:00'],
                ['Rabu', 2, '07:40:00', '08:20:00'],
                ['Rabu', 3, '08:20:00', '09:00:00'],
                ['Rabu', 4, '09:20:00', '10:00:00'],
                ['Rabu', 5, '10:00:00', '10:40:00'],
                ['Rabu', 6, '10:40:00', '11:20:00'],
                ['Rabu', 7, '11:20:00', '12:00:00'],
                ['Rabu', 8, '12:20:00', '13:00:00'],
                ['Rabu', 9, '13:00:00', '13:40:00'],
                ['Rabu', 10, '13:40:00', '14:20:00'],
                ['Rabu', 11, '14:20:00', '15:00:00'],

                ['Kamis', 1, '07:00:00', '07:40:00'],
                ['Kamis', 2, '07:40:00', '08:20:00'],
                ['Kamis', 3, '08:20:00', '09:00:00'],
                ['Kamis', 4, '09:20:00', '10:00:00'],
                ['Kamis', 5, '10:00:00', '10:40:00'],
                ['Kamis', 6, '10:40:00', '11:20:00'],
                ['Kamis', 7, '11:20:00', '12:00:00'],
                ['Kamis', 8, '12:20:00', '13:00:00'],
                ['Kamis', 9, '13:00:00', '13:40:00'],
                ['Kamis', 10, '13:40:00', '14:20:00'],
                ['Kamis', 11, '14:20:00', '15:00:00'],

                ['Jumat', 1, '07:00:00', '07:40:00'],
                ['Jumat', 2, '07:40:00', '08:20:00'],
                ['Jumat', 3, '08:20:00', '09:00:00'],
                ['Jumat', 4, '09:20:00', '10:00:00'],
                ['Jumat', 5, '10:00:00', '10:40:00'],
                ['Jumat', 6, '10:40:00', '11:20:00'],
                ['Jumat', 7, '11:20:00', '12:00:00'],
                ['Jumat', 8, '12:20:00', '13:00:00'],
            ];

            foreach ($data_jam as $row) {
                [$hari, $nomor_jp, $jam_mulai, $jam_selesai] = $row;
                $stmt->bind_param("siss", $hari, $nomor_jp, $jam_mulai, $jam_selesai);

                if (!$stmt->execute()) {
                    throw new Exception('Gagal insert jam default: ' . $stmt->error);
                }
            }

            $stmt->close();
            $conn->commit();

            redirectSelf('success', 'Jam pelajaran default berhasil dibuat ulang.');
        }

        if ($action === 'simpan_aturan') {
            $id_mapel = (int)($_POST['id_mapel'] ?? 0);
            $pertemuan = (int)($_POST['pertemuan_per_minggu'] ?? 0);
            $jp = (int)($_POST['jp_per_pertemuan'] ?? 0);
            $tingkat = trim($_POST['tingkat_kesulitan'] ?? 'sedang');
            $prioritas = isset($_POST['prioritas_pagi']) ? 1 : 0;

            if ($id_mapel <= 0 || $pertemuan <= 0 || $jp <= 0) {
                throw new Exception('Data aturan mapel belum lengkap.');
            }

            if (!in_array($tingkat, ['sulit', 'sedang', 'ringan'])) {
                $tingkat = 'sedang';
            }

            $cek = $conn->prepare("SELECT id_aturan FROM aturan_mapel WHERE id_mapel = ? LIMIT 1");
            if (!$cek) {
                throw new Exception('Gagal prepare cek aturan: ' . $conn->error);
            }

            $cek->bind_param("i", $id_mapel);
            $cek->execute();
            $cek->store_result();

            if ($cek->num_rows > 0) {
                $stmt = $conn->prepare("
                    UPDATE aturan_mapel
                    SET pertemuan_per_minggu = ?, jp_per_pertemuan = ?, tingkat_kesulitan = ?, prioritas_pagi = ?
                    WHERE id_mapel = ?
                ");

                if (!$stmt) {
                    throw new Exception('Gagal prepare update aturan: ' . $conn->error);
                }

                $stmt->bind_param("iisii", $pertemuan, $jp, $tingkat, $prioritas, $id_mapel);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO aturan_mapel
                    (id_mapel, pertemuan_per_minggu, jp_per_pertemuan, tingkat_kesulitan, prioritas_pagi)
                    VALUES (?, ?, ?, ?, ?)
                ");

                if (!$stmt) {
                    throw new Exception('Gagal prepare insert aturan: ' . $conn->error);
                }

                $stmt->bind_param("iiisi", $id_mapel, $pertemuan, $jp, $tingkat, $prioritas);
            }

            $cek->close();

            if (!$stmt->execute()) {
                throw new Exception('Gagal menyimpan aturan mapel: ' . $stmt->error);
            }

            $stmt->close();

            redirectSelf('success', 'Aturan mapel berhasil disimpan.');
        }

        if ($action === 'hapus_aturan') {
            $id_aturan = (int)($_POST['id_aturan'] ?? 0);

            if ($id_aturan <= 0) {
                throw new Exception('ID aturan tidak valid.');
            }

            $stmt = $conn->prepare("DELETE FROM aturan_mapel WHERE id_aturan = ?");
            if (!$stmt) {
                throw new Exception('Gagal prepare hapus aturan: ' . $conn->error);
            }

            $stmt->bind_param("i", $id_aturan);

            if (!$stmt->execute()) {
                throw new Exception('Gagal menghapus aturan mapel: ' . $stmt->error);
            }

            $stmt->close();

            redirectSelf('success', 'Aturan mapel berhasil dihapus.');
        }

        throw new Exception('Aksi tidak dikenal.');
    } catch (Exception $e) {
        if ($conn->errno) {
            $conn->rollback();
        }

        redirectSelf('error', $e->getMessage());
    }
}

$mapel_list = [];
$qMapel = $conn->query("SELECT id_mapel, nama_mapel FROM mapel ORDER BY id_mapel ASC");
if ($qMapel) {
    while ($row = $qMapel->fetch_assoc()) {
        $mapel_list[] = $row;
    }
}

$jam_list = [];
$qJam = $conn->query("
    SELECT id_jp, hari, nomor_jp, jam_mulai, jam_selesai, aktif
    FROM jam_pelajaran
    ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), nomor_jp ASC
");

if ($qJam) {
    while ($row = $qJam->fetch_assoc()) {
        $jam_list[] = $row;
    }
}

$aturan_list = [];
$qAturan = $conn->query("
    SELECT 
        a.id_aturan,
        a.id_mapel,
        a.pertemuan_per_minggu,
        a.jp_per_pertemuan,
        a.tingkat_kesulitan,
        a.prioritas_pagi,
        m.nama_mapel
    FROM aturan_mapel a
    LEFT JOIN mapel m ON a.id_mapel = m.id_mapel
    ORDER BY a.id_mapel ASC
");

if ($qAturan) {
    while ($row = $qAturan->fetch_assoc()) {
        $aturan_list[] = $row;
    }
}

$total_jam = count($jam_list);
$total_aturan = count($aturan_list);
$total_mapel = count($mapel_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Jadwal - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
            --success: #16a34a;
            --warning: #f59e0b;
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

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .card h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .card p {
            margin: 0 0 18px;
            font-size: 14px;
            color: var(--muted-text);
            line-height: 1.6;
        }

        .form-grid {
            display: grid;
            gap: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 11px 13px;
            outline: none;
            font-size: 14px;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.10);
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
        }

        .checkbox-row input {
            width: auto;
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

        .btn-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .btn-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-warning:hover {
            background: #fde68a;
        }

        .button-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 24px;
        }

        .table-card h2 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .table-card p {
            margin: 0 0 18px;
            font-size: 14px;
            color: var(--muted-text);
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
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
            padding: 13px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: #334155;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            background: var(--soft-teal);
            color: var(--primary-teal);
            font-size: 12px;
            font-weight: 700;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .inline-form {
            display: inline;
        }

        .empty-state {
            text-align: center;
            padding: 28px 20px;
            color: var(--muted-text);
        }

        @media (max-width: 992px) {
            .grid-2,
            .stats-grid {
                grid-template-columns: 1fr;
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

            .btn {
                width: 100%;
            }

            .header-actions {
                width: 100%;
            }
        }
    </style>
</head>

<body data-page="master_jadwal">
    <div id="admin-nav-root"></div>

    <div class="container">
        <main class="main-content">
            <section class="page-header">
                <div class="page-header-top">
                    <div class="page-title">
                        <h1>Master Jadwal</h1>
                        <p>
                            Halaman ini digunakan admin untuk mengatur jam pelajaran dan aturan mapel
                            sebelum melakukan generate jadwal mengajar.
                        </p>
                    </div>

                    <div class="header-actions">
                        <a href="/admin/penjadwalan/jadwal.php" class="btn btn-light">
                            <i class="fas fa-calendar-days"></i>
                            Kembali ke Jadwal
                        </a>
                    </div>
                </div>
            </section>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo e($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo e($error_message); ?>
                </div>
            <?php endif; ?>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <span>Total Jam Pelajaran</span>
                        <h3><?php echo (int)$total_jam; ?></h3>
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

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div class="stat-info">
                        <span>Aturan Mapel</span>
                        <h3><?php echo (int)$total_aturan; ?></h3>
                    </div>
                </div>
            </section>

            <section class="grid-2">
                <div class="card">
                    <h2>Tambah Jam Pelajaran</h2>
                    <p>Input JP berdasarkan hari. 1 JP biasanya 40 menit.</p>

                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="tambah_jam">

                        <div class="form-group">
                            <label>Hari</label>
                            <select name="hari" required>
                                <option value="">Pilih Hari</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nomor JP</label>
                            <input type="number" name="nomor_jp" min="1" required placeholder="Contoh: 1">
                        </div>

                        <div class="form-group">
                            <label>Jam Mulai</label>
                            <input type="time" name="jam_mulai" required>
                        </div>

                        <div class="form-group">
                            <label>Jam Selesai</label>
                            <input type="time" name="jam_selesai" required>
                        </div>

                        <label class="checkbox-row">
                            <input type="checkbox" name="aktif" checked>
                            Aktif digunakan untuk generate
                        </label>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Tambah Jam
                        </button>
                    </form>

                    <form method="POST" style="margin-top: 12px;" onsubmit="return confirm('Reset jam pelajaran ke default? Data jam lama akan dihapus.');">
                        <input type="hidden" name="action" value="reset_jam_default">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-rotate"></i>
                            Buat Jam Default
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h2>Tambah / Update Aturan Mapel</h2>
                    <p>Atur jumlah pertemuan, jumlah JP, tingkat kesulitan, dan prioritas pagi.</p>

                    <form method="POST" class="form-grid">
                        <input type="hidden" name="action" value="simpan_aturan">

                        <div class="form-group">
                            <label>Mata Pelajaran</label>
                            <select name="id_mapel" required>
                                <option value="">Pilih Mapel</option>
                                <?php foreach ($mapel_list as $mapel): ?>
                                    <option value="<?php echo (int)$mapel['id_mapel']; ?>">
                                        <?php echo e($mapel['nama_mapel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Pertemuan per Minggu</label>
                            <input type="number" name="pertemuan_per_minggu" min="1" required placeholder="Contoh: 3">
                        </div>

                        <div class="form-group">
                            <label>JP per Pertemuan</label>
                            <input type="number" name="jp_per_pertemuan" min="1" required placeholder="Contoh: 2">
                        </div>

                        <div class="form-group">
                            <label>Tingkat Kesulitan</label>
                            <select name="tingkat_kesulitan" required>
                                <option value="sulit">Sulit</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="ringan">Ringan</option>
                            </select>
                        </div>

                        <label class="checkbox-row">
                            <input type="checkbox" name="prioritas_pagi">
                            Prioritaskan pagi
                        </label>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Aturan
                        </button>
                    </form>
                </div>
            </section>

            <section class="table-card">
                <h2>Daftar Jam Pelajaran</h2>
                <p>Data ini dibaca oleh sistem generate untuk menentukan slot JP setiap hari.</p>

                <?php if (!empty($jam_list)): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hari</th>
                                    <th>JP</th>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jam_list as $jam): ?>
                                    <tr>
                                        <td><span class="badge"><?php echo e($jam['hari']); ?></span></td>
                                        <td>JP <?php echo (int)$jam['nomor_jp']; ?></td>
                                        <td><?php echo e(substr($jam['jam_mulai'], 0, 5)); ?></td>
                                        <td><?php echo e(substr($jam['jam_selesai'], 0, 5)); ?></td>
                                        <td>
                                            <?php if ((int)$jam['aktif'] === 1): ?>
                                                <span class="badge">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Hapus jam pelajaran ini?');">
                                                <input type="hidden" name="action" value="hapus_jam">
                                                <input type="hidden" name="id_jp" value="<?php echo (int)$jam['id_jp']; ?>">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        Belum ada data jam pelajaran. Klik tombol <strong>Buat Jam Default</strong>.
                    </div>
                <?php endif; ?>
            </section>

            <section class="table-card">
                <h2>Daftar Aturan Mapel</h2>
                <p>Data ini menentukan berapa kali mapel muncul dalam seminggu dan berapa JP setiap pertemuan.</p>

                <?php if (!empty($aturan_list)): ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mapel</th>
                                    <th>Pertemuan/Minggu</th>
                                    <th>JP/Pertemuan</th>
                                    <th>Total JP</th>
                                    <th>Kesulitan</th>
                                    <th>Prioritas Pagi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aturan_list as $aturan): ?>
                                    <?php
                                        $total_jp = (int)$aturan['pertemuan_per_minggu'] * (int)$aturan['jp_per_pertemuan'];
                                    ?>
                                    <tr>
                                        <td><strong><?php echo e($aturan['nama_mapel'] ?? '-'); ?></strong></td>
                                        <td><?php echo (int)$aturan['pertemuan_per_minggu']; ?> kali</td>
                                        <td><?php echo (int)$aturan['jp_per_pertemuan']; ?> JP</td>
                                        <td><span class="badge"><?php echo $total_jp; ?> JP</span></td>
                                        <td>
                                            <?php
                                                $tingkat = $aturan['tingkat_kesulitan'];
                                                $badgeClass = $tingkat === 'sulit' ? 'badge-danger' : ($tingkat === 'ringan' ? 'badge' : 'badge-warning');
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo e(ucfirst($tingkat)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo ((int)$aturan['prioritas_pagi'] === 1) ? 'Ya' : 'Tidak'; ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-form" onsubmit="return confirm('Hapus aturan mapel ini?');">
                                                <input type="hidden" name="action" value="hapus_aturan">
                                                <input type="hidden" name="id_aturan" value="<?php echo (int)$aturan['id_aturan']; ?>">
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        Belum ada aturan mapel. Tambahkan aturan dari form di atas.
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script src="/admin/components/admin-nav.js"></script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>