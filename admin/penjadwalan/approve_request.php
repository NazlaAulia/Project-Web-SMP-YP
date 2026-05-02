<?php
require_once __DIR__ . '/../koneksi.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function redirectWith($status, $message) {
    header("Location: /admin/penjadwalan/approve_request.php?" . http_build_query([
        "status" => $status,
        "message" => $message
    ]));
    exit;
}

$alert_status = $_GET['status'] ?? '';
$alert_message = $_GET['message'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_request = isset($_POST['id_request']) ? (int)$_POST['id_request'] : 0;

    if ($id_request <= 0) {
        redirectWith('error', 'ID request tidak valid.');
    }

    try {
        $conn->begin_transaction();

        $stmtReq = $conn->prepare("
            SELECT 
                r.id_request,
                r.id_guru,
                r.id_kelas,
                r.id_jadwal,
                r.hari_baru,
                r.jam_baru,
                r.jp_mulai_baru,
                r.jp_selesai_baru,
                r.jumlah_jp_baru,
                r.alasan,
                r.pesan_ai,
                r.tipe_request,
                r.id_jadwal_tukar,
                r.status,
                r.tanggal_request
            FROM request_jadwal r
            WHERE r.id_request = ?
            LIMIT 1
        ");

        if (!$stmtReq) {
            throw new Exception('Query request gagal: ' . $conn->error);
        }

        $stmtReq->bind_param("i", $id_request);
        $stmtReq->execute();
        $resultReq = $stmtReq->get_result();

        if ($resultReq->num_rows === 0) {
            throw new Exception('Data request tidak ditemukan.');
        }

        $request = $resultReq->fetch_assoc();
        $stmtReq->close();

        if ($request['status'] !== 'menunggu') {
            throw new Exception('Request ini sudah diproses sebelumnya.');
        }

        if ($action === 'tolak') {
            $stmtTolak = $conn->prepare("
                UPDATE request_jadwal 
                SET status = 'ditolak'
                WHERE id_request = ?
            ");

            if (!$stmtTolak) {
                throw new Exception('Query tolak gagal: ' . $conn->error);
            }

            $stmtTolak->bind_param("i", $id_request);

            if (!$stmtTolak->execute()) {
                throw new Exception('Gagal menolak request: ' . $stmtTolak->error);
            }

            $stmtTolak->close();
            $conn->commit();

            redirectWith('success', 'Request jadwal berhasil ditolak.');
        }

        if ($action !== 'terima') {
            throw new Exception('Aksi tidak dikenali.');
        }

        $id_jadwal_lama = (int)$request['id_jadwal'];
        $id_guru = (int)$request['id_guru'];
        $id_kelas = (int)$request['id_kelas'];
        $tipe_request = $request['tipe_request'] ?: 'slot_kosong';

        $stmtLama = $conn->prepare("
            SELECT 
                id_jadwal,
                id_guru,
                id_kelas,
                id_mapel,
                hari,
                jam,
                jp_mulai,
                jp_selesai,
                jumlah_jp
            FROM jadwal
            WHERE id_jadwal = ?
            LIMIT 1
        ");

        if (!$stmtLama) {
            throw new Exception('Query jadwal lama gagal: ' . $conn->error);
        }

        $stmtLama->bind_param("i", $id_jadwal_lama);
        $stmtLama->execute();
        $resultLama = $stmtLama->get_result();

        if ($resultLama->num_rows === 0) {
            throw new Exception('Jadwal lama tidak ditemukan.');
        }

        $jadwal_lama = $resultLama->fetch_assoc();
        $stmtLama->close();

        if ($tipe_request === 'slot_kosong') {
            $hari_baru = $request['hari_baru'];
            $jam_baru = $request['jam_baru'];
            $jp_mulai_baru = (int)$request['jp_mulai_baru'];
            $jp_selesai_baru = (int)$request['jp_selesai_baru'];
            $jumlah_jp_baru = (int)$request['jumlah_jp_baru'];

            $stmtBentrok = $conn->prepare("
                SELECT id_jadwal
                FROM jadwal
                WHERE hari = ?
                  AND id_jadwal != ?
                  AND (id_guru = ? OR id_kelas = ?)
                  AND jp_mulai <= ?
                  AND jp_selesai >= ?
                LIMIT 1
            ");

            if (!$stmtBentrok) {
                throw new Exception('Query cek bentrok gagal: ' . $conn->error);
            }

            $stmtBentrok->bind_param(
                "siiiii",
                $hari_baru,
                $id_jadwal_lama,
                $id_guru,
                $id_kelas,
                $jp_selesai_baru,
                $jp_mulai_baru
            );

            $stmtBentrok->execute();
            $resultBentrok = $stmtBentrok->get_result();

            if ($resultBentrok->num_rows > 0) {
                throw new Exception('Request tidak bisa diterima karena slot baru sudah bentrok.');
            }

            $stmtBentrok->close();

            $stmtUpdateJadwal = $conn->prepare("
                UPDATE jadwal
                SET hari = ?,
                    jam = ?,
                    jp_mulai = ?,
                    jp_selesai = ?,
                    jumlah_jp = ?
                WHERE id_jadwal = ?
            ");

            if (!$stmtUpdateJadwal) {
                throw new Exception('Query update jadwal gagal: ' . $conn->error);
            }

            $stmtUpdateJadwal->bind_param(
                "ssiiii",
                $hari_baru,
                $jam_baru,
                $jp_mulai_baru,
                $jp_selesai_baru,
                $jumlah_jp_baru,
                $id_jadwal_lama
            );

            if (!$stmtUpdateJadwal->execute()) {
                throw new Exception('Gagal update jadwal: ' . $stmtUpdateJadwal->error);
            }

            $stmtUpdateJadwal->close();
        }

        if ($tipe_request === 'tukar') {
            $id_jadwal_tukar = (int)$request['id_jadwal_tukar'];

            if ($id_jadwal_tukar <= 0) {
                throw new Exception('ID jadwal tukar tidak valid.');
            }

            $stmtTukar = $conn->prepare("
                SELECT 
                    id_jadwal,
                    id_guru,
                    id_kelas,
                    id_mapel,
                    hari,
                    jam,
                    jp_mulai,
                    jp_selesai,
                    jumlah_jp
                FROM jadwal
                WHERE id_jadwal = ?
                LIMIT 1
            ");

            if (!$stmtTukar) {
                throw new Exception('Query jadwal tukar gagal: ' . $conn->error);
            }

            $stmtTukar->bind_param("i", $id_jadwal_tukar);
            $stmtTukar->execute();
            $resultTukar = $stmtTukar->get_result();

            if ($resultTukar->num_rows === 0) {
                throw new Exception('Jadwal tukar tidak ditemukan.');
            }

            $jadwal_tukar = $resultTukar->fetch_assoc();
            $stmtTukar->close();

            if ((int)$jadwal_tukar['id_kelas'] !== (int)$jadwal_lama['id_kelas']) {
                throw new Exception('Jadwal tukar harus berada di kelas yang sama.');
            }

            if ((int)$jadwal_tukar['jumlah_jp'] !== (int)$jadwal_lama['jumlah_jp']) {
                throw new Exception('Jumlah JP jadwal tukar harus sama.');
            }

            $id_guru_lama = (int)$jadwal_lama['id_guru'];
            $id_guru_tukar = (int)$jadwal_tukar['id_guru'];

            $stmtCekGuruLama = $conn->prepare("
                SELECT id_jadwal
                FROM jadwal
                WHERE hari = ?
                  AND id_guru = ?
                  AND id_jadwal NOT IN (?, ?)
                  AND jp_mulai <= ?
                  AND jp_selesai >= ?
                LIMIT 1
            ");

            if (!$stmtCekGuruLama) {
                throw new Exception('Query cek guru lama gagal: ' . $conn->error);
            }

            $stmtCekGuruLama->bind_param(
                "siiiii",
                $jadwal_tukar['hari'],
                $id_guru_lama,
                $id_jadwal_lama,
                $id_jadwal_tukar,
                $jadwal_tukar['jp_selesai'],
                $jadwal_tukar['jp_mulai']
            );

            $stmtCekGuruLama->execute();
            $resultCekGuruLama = $stmtCekGuruLama->get_result();

            if ($resultCekGuruLama->num_rows > 0) {
                throw new Exception('Guru pemohon bentrok di slot jadwal tukar.');
            }

            $stmtCekGuruLama->close();

            $stmtCekGuruTukar = $conn->prepare("
                SELECT id_jadwal
                FROM jadwal
                WHERE hari = ?
                  AND id_guru = ?
                  AND id_jadwal NOT IN (?, ?)
                  AND jp_mulai <= ?
                  AND jp_selesai >= ?
                LIMIT 1
            ");

            if (!$stmtCekGuruTukar) {
                throw new Exception('Query cek guru tukar gagal: ' . $conn->error);
            }

            $stmtCekGuruTukar->bind_param(
                "siiiii",
                $jadwal_lama['hari'],
                $id_guru_tukar,
                $id_jadwal_lama,
                $id_jadwal_tukar,
                $jadwal_lama['jp_selesai'],
                $jadwal_lama['jp_mulai']
            );

            $stmtCekGuruTukar->execute();
            $resultCekGuruTukar = $stmtCekGuruTukar->get_result();

            if ($resultCekGuruTukar->num_rows > 0) {
                throw new Exception('Guru jadwal tukar bentrok di slot jadwal lama.');
            }

            $stmtCekGuruTukar->close();

            $stmtUpdateLama = $conn->prepare("
                UPDATE jadwal
                SET hari = ?,
                    jam = ?,
                    jp_mulai = ?,
                    jp_selesai = ?,
                    jumlah_jp = ?
                WHERE id_jadwal = ?
            ");

            if (!$stmtUpdateLama) {
                throw new Exception('Query update jadwal lama gagal: ' . $conn->error);
            }

            $stmtUpdateLama->bind_param(
                "ssiiii",
                $jadwal_tukar['hari'],
                $jadwal_tukar['jam'],
                $jadwal_tukar['jp_mulai'],
                $jadwal_tukar['jp_selesai'],
                $jadwal_tukar['jumlah_jp'],
                $id_jadwal_lama
            );

            if (!$stmtUpdateLama->execute()) {
                throw new Exception('Gagal update jadwal lama: ' . $stmtUpdateLama->error);
            }

            $stmtUpdateLama->close();

            $stmtUpdateTukar = $conn->prepare("
                UPDATE jadwal
                SET hari = ?,
                    jam = ?,
                    jp_mulai = ?,
                    jp_selesai = ?,
                    jumlah_jp = ?
                WHERE id_jadwal = ?
            ");

            if (!$stmtUpdateTukar) {
                throw new Exception('Query update jadwal tukar gagal: ' . $conn->error);
            }

            $stmtUpdateTukar->bind_param(
                "ssiiii",
                $jadwal_lama['hari'],
                $jadwal_lama['jam'],
                $jadwal_lama['jp_mulai'],
                $jadwal_lama['jp_selesai'],
                $jadwal_lama['jumlah_jp'],
                $id_jadwal_tukar
            );

            if (!$stmtUpdateTukar->execute()) {
                throw new Exception('Gagal update jadwal tukar: ' . $stmtUpdateTukar->error);
            }

            $stmtUpdateTukar->close();
        }

        $stmtStatus = $conn->prepare("
            UPDATE request_jadwal
            SET status = 'diterima'
            WHERE id_request = ?
        ");

        if (!$stmtStatus) {
            throw new Exception('Query update status gagal: ' . $conn->error);
        }

        $stmtStatus->bind_param("i", $id_request);

        if (!$stmtStatus->execute()) {
            throw new Exception('Gagal update status request: ' . $stmtStatus->error);
        }

        $stmtStatus->close();

        $conn->commit();
        redirectWith('success', 'Request jadwal berhasil diterima dan jadwal sudah diperbarui.');

    } catch (Exception $e) {
        $conn->rollback();
        redirectWith('error', $e->getMessage());
    }
}

$query = "
    SELECT 
        r.id_request,
        r.id_guru,
        r.id_kelas,
        r.id_jadwal,
        r.hari_baru,
        r.jam_baru,
        r.jp_mulai_baru,
        r.jp_selesai_baru,
        r.jumlah_jp_baru,
        r.alasan,
        r.pesan_ai,
        r.tipe_request,
        r.id_jadwal_tukar,
        r.status,
        r.tanggal_request,

        gp.nama AS nama_guru_pemohon,
        k.nama_kelas,

        jl.hari AS hari_lama,
        jl.jam AS jam_lama,
        jl.jp_mulai AS jp_mulai_lama,
        jl.jp_selesai AS jp_selesai_lama,
        jl.jumlah_jp AS jumlah_jp_lama,
        ml.nama_mapel AS mapel_lama,

        jt.hari AS hari_tukar,
        jt.jam AS jam_tukar,
        jt.jp_mulai AS jp_mulai_tukar,
        jt.jp_selesai AS jp_selesai_tukar,
        mt.nama_mapel AS mapel_tukar,
        gt.nama AS guru_tukar

    FROM request_jadwal r
    LEFT JOIN guru gp ON r.id_guru = gp.id_guru
    LEFT JOIN kelas k ON r.id_kelas = k.id_kelas

    LEFT JOIN jadwal jl ON r.id_jadwal = jl.id_jadwal
    LEFT JOIN mapel ml ON jl.id_mapel = ml.id_mapel

    LEFT JOIN jadwal jt ON r.id_jadwal_tukar = jt.id_jadwal
    LEFT JOIN mapel mt ON jt.id_mapel = mt.id_mapel
    LEFT JOIN guru gt ON jt.id_guru = gt.id_guru

    ORDER BY 
        FIELD(r.status, 'menunggu', 'diterima', 'ditolak'),
        r.tanggal_request DESC
";

$result = $conn->query($query);

$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$total_menunggu = 0;
$total_diterima = 0;
$total_ditolak = 0;

foreach ($requests as $r) {
    if ($r['status'] === 'menunggu') $total_menunggu++;
    if ($r['status'] === 'diterima') $total_diterima++;
    if ($r['status'] === 'ditolak') $total_ditolak++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Approve Request Jadwal - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/components/admin-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #1f2937;
        }

        .page-header {
            background: linear-gradient(135deg, #0f766e, #115e59);
            color: white;
            padding: 26px;
            border-radius: 24px;
            margin-bottom: 24px;
            box-shadow: 0 14px 30px rgba(15, 118, 110, 0.20);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .page-header p {
            margin: 8px 0 0;
            opacity: .9;
            line-height: 1.6;
            font-size: 14px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #ecfdf5;
            color: #0f766e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card span {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-card h3 {
            margin: 4px 0 0;
            font-size: 26px;
        }

        .request-list {
            display: grid;
            gap: 18px;
        }

        .request-card {
            background: white;
            border-radius: 22px;
            border: 1px solid #e5e7eb;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .request-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .request-top h2 {
            margin: 0;
            font-size: 20px;
        }

        .request-top p {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-diterima {
            background: #dcfce7;
            color: #166534;
        }

        .badge-ditolak {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-tukar {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .grid-detail {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }

        .detail-box {
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            border-radius: 18px;
            padding: 16px;
        }

        .detail-box h3 {
            margin: 0 0 12px;
            font-size: 15px;
            color: #0f766e;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .detail-row span {
            color: #64748b;
        }

        .detail-row strong {
            color: #0f172a;
            text-align: right;
        }

        .ai-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 18px;
            padding: 15px;
            margin-bottom: 16px;
            color: #78350f;
            font-size: 14px;
            line-height: 1.7;
        }

        .reason-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 15px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.7;
            color: #374151;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn {
            border: none;
            border-radius: 13px;
            padding: 11px 15px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-accept {
            background: #16a34a;
            color: white;
        }

        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            background: white;
            border-radius: 22px;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
            border: 1px solid #e5e7eb;
        }

        .custom-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 5000;
            padding: 20px;
        }

        .custom-modal-overlay.active {
            display: flex;
        }

        .custom-modal-box {
            width: 100%;
            max-width: 540px;
            background: #ffffff;
            border-radius: 24px;
            padding: 34px 28px 26px;
            text-align: center;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.22);
            animation: modalFadeIn 0.25s ease;
        }

        .custom-modal-icon {
            width: 96px;
            height: 96px;
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

        .custom-modal-icon.reject {
            color: #ef4444;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .custom-modal-box h3 {
            margin: 0 0 8px;
            font-size: 25px;
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

        .modal-btn.reject {
            background: #ef4444;
            color: white;
        }

        .modal-btn.reject:hover {
            background: #dc2626;
        }

        .modal-btn.cancel {
            background: #e9eef1;
            color: #244;
        }

        .modal-btn.cancel:hover {
            background: #dbe3e8;
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

        @media (max-width: 900px) {
            .stats-grid,
            .grid-detail {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body data-page="request_jadwal">
    <div id="admin-nav-root"></div>

    <div class="container">
        <main class="main-content">
            <section class="page-header">
                <h1>Approve Request Jadwal</h1>
                <p>
                    Halaman ini digunakan admin untuk meninjau, menerima, atau menolak pengajuan ganti jadwal dari guru.
                    Jika request bertipe tukar, sistem akan menukar dua slot jadwal secara otomatis saat diterima.
                </p>
            </section>

            <?php if ($alert_message): ?>
                <div class="alert <?php echo $alert_status === 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo e($alert_message); ?>
                </div>
            <?php endif; ?>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <span>Menunggu</span>
                        <h3><?php echo (int)$total_menunggu; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <span>Diterima</span>
                        <h3><?php echo (int)$total_diterima; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-xmark"></i>
                    </div>
                    <div>
                        <span>Ditolak</span>
                        <h3><?php echo (int)$total_ditolak; ?></h3>
                    </div>
                </div>
            </section>

            <?php if (!empty($requests)): ?>
                <section class="request-list">
                    <?php foreach ($requests as $r): ?>
                        <?php
                            $statusClass = 'badge-menunggu';
                            if ($r['status'] === 'diterima') $statusClass = 'badge-diterima';
                            if ($r['status'] === 'ditolak') $statusClass = 'badge-ditolak';

                            $tipeText = $r['tipe_request'] === 'tukar' ? 'Tukar Jadwal' : 'Slot Kosong';
                        ?>

                        <article class="request-card">
                            <div class="request-top">
                                <div>
                                    <h2><?php echo e($r['nama_guru_pemohon'] ?? '-'); ?></h2>
                                    <p>
                                        Kelas <?php echo e($r['nama_kelas'] ?? '-'); ?> •
                                        <?php echo e(date('d-m-Y H:i', strtotime($r['tanggal_request']))); ?>
                                    </p>
                                </div>

                                <div style="display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap;">
                                    <span class="badge badge-tukar"><?php echo e($tipeText); ?></span>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo e(ucfirst($r['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="grid-detail">
                                <div class="detail-box">
                                    <h3>Jadwal Lama</h3>

                                    <div class="detail-row">
                                        <span>Mapel</span>
                                        <strong><?php echo e($r['mapel_lama'] ?? '-'); ?></strong>
                                    </div>

                                    <div class="detail-row">
                                        <span>Hari</span>
                                        <strong><?php echo e($r['hari_lama'] ?? '-'); ?></strong>
                                    </div>

                                    <div class="detail-row">
                                        <span>Jam</span>
                                        <strong><?php echo e($r['jam_lama'] ?? '-'); ?></strong>
                                    </div>

                                    <div class="detail-row">
                                        <span>JP</span>
                                        <strong>
                                            <?php echo e($r['jp_mulai_lama'] ?? '-'); ?>
                                            -
                                            <?php echo e($r['jp_selesai_lama'] ?? '-'); ?>
                                        </strong>
                                    </div>
                                </div>

                                <div class="detail-box">
                                    <h3><?php echo $r['tipe_request'] === 'tukar' ? 'Jadwal Tukar / Tujuan' : 'Jadwal Baru'; ?></h3>

                                    <?php if ($r['tipe_request'] === 'tukar'): ?>
                                        <div class="detail-row">
                                            <span>Ditukar Dengan</span>
                                            <strong><?php echo e($r['mapel_tukar'] ?? '-'); ?></strong>
                                        </div>

                                        <div class="detail-row">
                                            <span>Guru</span>
                                            <strong><?php echo e($r['guru_tukar'] ?? '-'); ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <div class="detail-row">
                                        <span>Hari Baru</span>
                                        <strong><?php echo e($r['hari_baru'] ?? '-'); ?></strong>
                                    </div>

                                    <div class="detail-row">
                                        <span>Jam Baru</span>
                                        <strong><?php echo e($r['jam_baru'] ?? '-'); ?></strong>
                                    </div>

                                    <div class="detail-row">
                                        <span>JP Baru</span>
                                        <strong>
                                            <?php echo e($r['jp_mulai_baru'] ?? '-'); ?>
                                            -
                                            <?php echo e($r['jp_selesai_baru'] ?? '-'); ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div class="reason-box">
                                <strong>Alasan Guru:</strong><br>
                                <?php echo nl2br(e($r['alasan'] ?? '-')); ?>
                            </div>

                            <div class="ai-box">
                                <strong>Catatan / Rekomendasi AI:</strong><br>
                                <?php echo nl2br(e($r['pesan_ai'] ?? 'Tidak ada catatan AI.')); ?>
                            </div>

                            <?php if ($r['status'] === 'menunggu'): ?>
                                <div class="actions">
                                    <form method="POST" id="form-tolak-<?php echo (int)$r['id_request']; ?>">
                                        <input type="hidden" name="id_request" value="<?php echo (int)$r['id_request']; ?>">
                                        <input type="hidden" name="action" value="tolak">
                                        <button 
                                            type="button" 
                                            class="btn btn-reject"
                                            onclick="openActionModal('tolak', 'form-tolak-<?php echo (int)$r['id_request']; ?>')"
                                        >
                                            <i class="fas fa-xmark"></i>
                                            Tolak
                                        </button>
                                    </form>

                                    <form method="POST" id="form-terima-<?php echo (int)$r['id_request']; ?>">
                                        <input type="hidden" name="id_request" value="<?php echo (int)$r['id_request']; ?>">
                                        <input type="hidden" name="action" value="terima">
                                        <button 
                                            type="button" 
                                            class="btn btn-accept"
                                            onclick="openActionModal('terima', 'form-terima-<?php echo (int)$r['id_request']; ?>')"
                                        >
                                            <i class="fas fa-check"></i>
                                            Terima
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <div class="empty-state">
                    Belum ada request jadwal dari guru.
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div class="custom-modal-overlay" id="actionModal">
        <div class="custom-modal-box">
            <div class="custom-modal-icon" id="actionModalIcon">
                <i class="fas fa-check"></i>
            </div>

            <h3 id="actionModalTitle">Konfirmasi</h3>
            <p id="actionModalText">Apakah Anda yakin ingin memproses request ini?</p>

            <div class="custom-modal-actions">
                <button type="button" class="modal-btn confirm" id="actionModalConfirm">
                    Ya, Lanjutkan!
                </button>
                <button type="button" class="modal-btn cancel" onclick="closeActionModal()">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script src="/admin/components/admin-nav.js?v=99"></script>

    <script>
        let selectedActionFormId = null;

        function openActionModal(type, formId) {
            selectedActionFormId = formId;

            const modal = document.getElementById('actionModal');
            const icon = document.getElementById('actionModalIcon');
            const title = document.getElementById('actionModalTitle');
            const text = document.getElementById('actionModalText');
            const confirmBtn = document.getElementById('actionModalConfirm');

            icon.className = 'custom-modal-icon';
            confirmBtn.className = 'modal-btn confirm';

            if (type === 'tolak') {
                icon.classList.add('reject');
                icon.innerHTML = '<i class="fas fa-xmark"></i>';
                title.textContent = 'Tolak Request?';
                text.textContent = 'Request jadwal ini akan ditolak dan statusnya berubah menjadi ditolak.';
                confirmBtn.textContent = 'Ya, Tolak!';
                confirmBtn.className = 'modal-btn reject';
            } else {
                icon.innerHTML = '<i class="fas fa-check"></i>';
                title.textContent = 'Terima Request?';
                text.textContent = 'Jadwal akan diperbarui otomatis sesuai request yang diajukan.';
                confirmBtn.textContent = 'Ya, Terima!';
                confirmBtn.className = 'modal-btn confirm';
            }

            modal.classList.add('active');
        }

        function closeActionModal() {
            const modal = document.getElementById('actionModal');
            modal.classList.remove('active');
            selectedActionFormId = null;
        }

        document.getElementById('actionModalConfirm').addEventListener('click', function () {
            if (!selectedActionFormId) return;

            const form = document.getElementById(selectedActionFormId);
            if (form) {
                form.submit();
            }
        });

        document.getElementById('actionModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeActionModal();
            }
        });
    </script>
</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>