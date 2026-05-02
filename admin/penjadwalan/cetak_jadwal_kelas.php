<?php
require_once __DIR__ . '/../koneksi.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

if ($id_kelas <= 0) {
    die('ID kelas tidak valid.');
}

$qKelas = $conn->prepare("
    SELECT id_kelas, nama_kelas 
    FROM kelas 
    WHERE id_kelas = ? 
    LIMIT 1
");

if (!$qKelas) {
    die('Query kelas gagal: ' . $conn->error);
}

$qKelas->bind_param("i", $id_kelas);
$qKelas->execute();
$kelasResult = $qKelas->get_result();
$kelas = $kelasResult->fetch_assoc();
$qKelas->close();

if (!$kelas) {
    die('Data kelas tidak ditemukan.');
}

$query = "
    SELECT 
        j.id_jadwal,
        j.hari,
        j.jam,
        j.jp_mulai,
        j.jp_selesai,
        j.jumlah_jp,
        g.nama AS nama_guru,
        m.nama_mapel
    FROM jadwal j
    LEFT JOIN guru g ON j.id_guru = g.id_guru
    LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
    WHERE j.id_kelas = ?
    ORDER BY 
        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
        COALESCE(j.jp_mulai, 0),
        j.jam ASC
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die('Query jadwal gagal: ' . $conn->error);
}

$stmt->bind_param("i", $id_kelas);
$stmt->execute();
$result = $stmt->get_result();

$jadwal_list = [];

while ($row = $result->fetch_assoc()) {
    $jadwal_list[] = $row;
}

$stmt->close();

$tanggal_cetak = date('d-m-Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Jadwal Kelas <?php echo e($kelas['nama_kelas']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #e5e7eb;
        }

        .print-toolbar {
            max-width: 900px;
            margin: 14px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 12px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-print {
            background: #0f766e;
            color: white;
        }

        .btn-back {
            background: #ffffff;
            color: #111827;
            border: 1px solid #d1d5db;
        }

        .page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto 14px;
            background: white;
            padding: 9mm;
            overflow: hidden;
        }

        .kop {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 6px;
            margin-bottom: 7px;
        }

        .kop h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.3px;
        }

        .kop h2 {
            margin: 3px 0 0;
            font-size: 12px;
            font-weight: normal;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 7px;
            font-size: 9.5px;
        }

        .info-row strong {
            display: inline-block;
            min-width: 72px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
            font-size: 8.5px;
        }

        .schedule-table th {
            background: #0f766e;
            color: white;
            border: 1px solid #111827;
            padding: 4px 3px;
            text-align: left;
            font-size: 8.5px;
        }

        .schedule-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: top;
            line-height: 1.18;
        }

        .col-no {
            width: 22px;
            text-align: center;
        }

        .col-hari {
            width: 58px;
            font-weight: bold;
            color: #0f766e;
        }

        .col-jam {
            width: 78px;
            font-weight: bold;
        }

        .col-mapel {
            width: 80px;
            font-weight: bold;
        }

        .col-jp {
            width: 52px;
            text-align: center;
            color: #0f766e;
            font-weight: bold;
        }

        .guru-text {
            font-size: 8px;
            color: #334155;
        }

        .day-separator td {
            background: #f1f5f9;
            color: #0f766e;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 8.5px;
        }

        .footer-area {
            margin-top: 7px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 9px;
        }

        .note {
            color: #475569;
            line-height: 1.35;
            max-width: 360px;
        }

        .signature-box {
            width: 190px;
            text-align: center;
            font-size: 9px;
        }

        .signature-space {
            height: 28px;
        }

        .empty-state {
            border: 1px solid #cbd5e1;
            padding: 20px;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }

        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        @media print {
            html,
            body {
                background: white;
                width: auto;
                height: auto;
                overflow: visible;
            }

            .print-toolbar {
                display: none !important;
            }

            .page {
                width: auto;
                height: auto;
                min-height: 0;
                margin: 0;
                padding: 0;
                box-shadow: none;
                overflow: hidden;
            }

            .kop,
            .info-row,
            .schedule-table,
            .footer-area {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="print-toolbar">
    <a href="/admin/penjadwalan/jadwal.php" class="btn btn-back">Kembali</a>
    <button onclick="window.print()" class="btn btn-print">Cetak / Simpan PDF</button>
</div>

<div class="page">
    <div class="kop">
        <h1>SMP YP 17 SURABAYA</h1>
        <h2>Jadwal Mengajar Kelas <?php echo e($kelas['nama_kelas']); ?></h2>
    </div>

    <div class="info-row">
        <div>
            <div><strong>Kelas</strong>: <?php echo e($kelas['nama_kelas']); ?></div>
            <div><strong>Semester</strong>: -</div>
        </div>

        <div>
            <div><strong>Tahun Ajaran</strong>: -</div>
            <div><strong>Tanggal Cetak</strong>: <?php echo e($tanggal_cetak); ?></div>
        </div>
    </div>

    <?php if (!empty($jadwal_list)): ?>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-hari">Hari</th>
                    <th class="col-jam">Jam</th>
                    <th class="col-mapel">Mapel</th>
                    <th>Guru</th>
                    <th class="col-jp">JP</th>
                </tr>
            </thead>

            <tbody>
                <?php 
                    $no = 1;
                    $hari_sebelumnya = '';
                ?>

                <?php foreach ($jadwal_list as $item): ?>
                    <?php if ($hari_sebelumnya !== $item['hari']): ?>
                        <tr class="day-separator">
                            <td colspan="6"><?php echo e($item['hari']); ?></td>
                        </tr>
                        <?php $hari_sebelumnya = $item['hari']; ?>
                    <?php endif; ?>

                    <tr>
                        <td class="col-no"><?php echo $no++; ?></td>
                        <td class="col-hari"><?php echo e($item['hari'] ?? '-'); ?></td>
                        <td class="col-jam"><?php echo e($item['jam'] ?? '-'); ?></td>
                        <td class="col-mapel"><?php echo e($item['nama_mapel'] ?? '-'); ?></td>
                        <td class="guru-text"><?php echo e($item['nama_guru'] ?? 'Belum ada guru'); ?></td>
                        <td class="col-jp">
                            <?php echo e($item['jp_mulai'] ?? '-'); ?>-<?php echo e($item['jp_selesai'] ?? '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            Belum ada jadwal untuk kelas ini.
        </div>
    <?php endif; ?>

    <div class="footer-area">
        <div class="note">
            <strong>Catatan:</strong><br>
            Jadwal dicetak otomatis dari sistem penjadwalan sekolah.
        </div>

        <div class="signature-box">
            <div>Surabaya, <?php echo e($tanggal_cetak); ?></div>
            <div>Admin / Wakil Kurikulum</div>
            <div class="signature-space"></div>
            <div>________________________</div>
        </div>
    </div>
</div>

</body>
</html>

<?php
if (isset($conn)) {
    $conn->close();
}
?>