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

$hari_urutan = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
$jadwal_harian = [];

foreach ($hari_urutan as $hari) {
    $jadwal_harian[$hari] = [];
}

while ($row = $result->fetch_assoc()) {
    $hari = $row['hari'];

    if (!isset($jadwal_harian[$hari])) {
        $jadwal_harian[$hari] = [];
    }

    $jadwal_harian[$hari][] = $row;
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
            max-width: 1200px;
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
            width: 297mm;
            height: 210mm;
            margin: 0 auto 14px;
            background: white;
            padding: 5mm;
            overflow: hidden;
        }

        .kop {
            text-align: center;
            border-bottom: 1.5px solid #111827;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }

        .kop h1 {
            margin: 0;
            font-size: 15px;
            letter-spacing: 0.3px;
        }

        .kop h2 {
            margin: 2px 0 0;
            font-size: 11px;
            font-weight: normal;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 5px;
            font-size: 8.5px;
        }

        .info-row strong {
            display: inline-block;
            min-width: 64px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #111827;
        }

        .schedule-table th {
            background: #0f766e;
            color: white;
            border: 1px solid #111827;
            padding: 3px 2px;
            font-size: 9px;
            text-align: center;
        }

        .schedule-table td {
            border: 1px solid #94a3b8;
            vertical-align: top;
            padding: 2px;
            height: 156mm;
        }

        .lesson {
            border-bottom: 1px solid #e5e7eb;
            padding: 1.8px 1px 2px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .lesson:last-child {
            border-bottom: none;
        }

        .lesson-time {
            font-size: 7px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 0.5px;
            line-height: 1.05;
        }

        .lesson-mapel {
            font-size: 7.5px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 0.5px;
            line-height: 1.05;
        }

        .lesson-guru {
            font-size: 6.4px;
            color: #475569;
            line-height: 1.08;
        }

        .lesson-jp {
            margin-top: 0.5px;
            font-size: 6.3px;
            color: #0f766e;
            font-weight: bold;
            line-height: 1.05;
        }

        .empty {
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            padding-top: 10px;
        }

        .footer-area {
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 8.5px;
        }

        .note {
            color: #475569;
            line-height: 1.25;
            max-width: 360px;
        }

        .signature-box {
            width: 180px;
            text-align: center;
            font-size: 8.5px;
        }

        .signature-space {
            height: 20px;
        }

        @page {
            size: A4 landscape;
            margin: 4mm;
        }

        @media print {
            html,
            body {
                background: white;
                width: 297mm;
                height: 210mm;
                overflow: hidden;
            }

            .print-toolbar {
                display: none;
            }

            .page {
                width: 100%;
                height: 100%;
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

    <table class="schedule-table">
        <thead>
            <tr>
                <?php foreach ($hari_urutan as $hari): ?>
                    <th><?php echo e($hari); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <tr>
                <?php foreach ($hari_urutan as $hari): ?>
                    <td>
                        <?php if (!empty($jadwal_harian[$hari])): ?>
                            <?php foreach ($jadwal_harian[$hari] as $item): ?>
                                <div class="lesson">
                                    <div class="lesson-time">
                                        <?php echo e($item['jam'] ?? '-'); ?>
                                    </div>

                                    <div class="lesson-mapel">
                                        <?php echo e($item['nama_mapel'] ?? '-'); ?>
                                    </div>

                                    <div class="lesson-guru">
                                        <?php echo e($item['nama_guru'] ?? 'Belum ada guru'); ?>
                                    </div>

                                    <div class="lesson-jp">
                                        JP <?php echo e($item['jp_mulai'] ?? '-'); ?>-<?php echo e($item['jp_selesai'] ?? '-'); ?>
                                        | <?php echo (int)($item['jumlah_jp'] ?? 1); ?> JP
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty">Tidak ada jadwal</div>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>

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