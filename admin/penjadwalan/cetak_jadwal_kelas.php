<?php
require_once __DIR__ . '/../koneksi.php';

function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

$id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

if ($id_kelas <= 0) {
    die('ID kelas tidak valid.');
}

$qKelas = $conn->prepare("SELECT id_kelas, nama_kelas FROM kelas WHERE id_kelas = ? LIMIT 1");
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
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: #f1f5f9;
            color: #111827;
        }

        .page {
            max-width: 1100px;
            margin: 24px auto;
            background: white;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        }

        .top-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 18px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
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
            background: #e5e7eb;
            color: #111827;
        }

        .kop {
            text-align: center;
            border-bottom: 3px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .kop h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.4px;
        }

        .kop h2 {
            margin: 6px 0 0;
            font-size: 17px;
            font-weight: normal;
        }

        .info {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .info strong {
            display: inline-block;
            min-width: 95px;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border: 1px solid #111827;
        }

        .day-column {
            border-right: 1px solid #111827;
            min-height: 500px;
        }

        .day-column:last-child {
            border-right: none;
        }

        .day-title {
            background: #0f766e;
            color: white;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #111827;
        }

        .lesson {
            padding: 10px;
            border-bottom: 1px solid #cbd5e1;
            min-height: 82px;
        }

        .lesson-time {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 5px;
        }

        .lesson-mapel {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .lesson-guru {
            font-size: 12px;
            color: #475569;
            line-height: 1.4;
        }

        .lesson-jp {
            margin-top: 5px;
            font-size: 11px;
            color: #0f766e;
            font-weight: bold;
        }

        .empty {
            padding: 14px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        .signature {
            margin-top: 34px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 260px;
            text-align: center;
            font-size: 14px;
        }

        .signature-space {
            height: 70px;
        }

        @media print {
            body {
                background: white;
            }

            .page {
                margin: 0;
                padding: 14mm;
                border-radius: 0;
                box-shadow: none;
                max-width: none;
            }

            .top-actions {
                display: none;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            .schedule-grid {
                page-break-inside: avoid;
            }
        }

        @media (max-width: 900px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .day-column {
                border-right: none;
                border-bottom: 1px solid #111827;
            }

            .info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <div class="top-actions">
        <a href="/admin/penjadwalan/jadwal.php" class="btn btn-back">Kembali</a>
        <button onclick="window.print()" class="btn btn-print">Cetak / Simpan PDF</button>
    </div>

    <div class="kop">
        <h1>SMP YP 17 SURABAYA</h1>
        <h2>Jadwal Mengajar Kelas <?php echo e($kelas['nama_kelas']); ?></h2>
    </div>

    <div class="info">
        <div>
            <div><strong>Kelas</strong>: <?php echo e($kelas['nama_kelas']); ?></div>
            <div><strong>Semester</strong>: -</div>
        </div>
        <div>
            <div><strong>Tahun Ajaran</strong>: -</div>
            <div><strong>Tanggal Cetak</strong>: <?php echo date('d-m-Y'); ?></div>
        </div>
    </div>

    <div class="schedule-grid">
        <?php foreach ($hari_urutan as $hari): ?>
            <div class="day-column">
                <div class="day-title"><?php echo e($hari); ?></div>

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
                                Guru: <?php echo e($item['nama_guru'] ?? 'Belum ada guru'); ?>
                            </div>

                            <div class="lesson-jp">
                                JP <?php echo e($item['jp_mulai'] ?? '-'); ?>
                                -
                                <?php echo e($item['jp_selesai'] ?? '-'); ?>
                                |
                                <?php echo (int)($item['jumlah_jp'] ?? 1); ?> JP
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty">Tidak ada jadwal</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="signature">
        <div class="signature-box">
            <div>Surabaya, <?php echo date('d-m-Y'); ?></div>
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