<?php
include '../koneksi.php';

function redirectAdmin()
{
    header("Location: /admin/admin_pendaftaran.php");
    exit;
}

function formatNomorWa($nomor)
{
    $nomor = preg_replace('/[^0-9]/', '', $nomor);

    if ($nomor === '') {
        return '';
    }

    if (substr($nomor, 0, 1) === '0') {
        return '62' . substr($nomor, 1);
    }

    if (substr($nomor, 0, 2) === '62') {
        return $nomor;
    }

    return $nomor;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    redirectAdmin();
}

$id_pendaftaran = (int) $_GET['id'];
$status = trim($_GET['status']);

if (!in_array($status, ['diterima', 'ditolak'])) {
    redirectAdmin();
}

/*
    Ambil data pendaftar dulu
    Catatan:
    - Kolom nomor WA ortu diasumsikan ada di field `no_hp`
    - Kalau di database kamu nama kolomnya beda (misal no_hp_wali),
      tinggal ganti di query SELECT bagian `no_hp`
*/
$cek = mysqli_prepare($conn, "SELECT id_pendaftaran, nama_lengkap, no_hp, status FROM pendaftaran WHERE id_pendaftaran = ? LIMIT 1");

if (!$cek) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($cek, "i", $id_pendaftaran);
mysqli_stmt_execute($cek);
$result = mysqli_stmt_get_result($cek);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($cek);

if (!$data) {
    redirectAdmin();
}

$namaSiswa = $data['nama_lengkap'];
$nomorWa = formatNomorWa($data['no_hp']);
$statusLama = $data['status'];

$judulModal = '';
$isiModal = '';
$iconModal = '✓';
$iconClass = 'success';
$labelButtonWa = 'Kirim ke WhatsApp';
$linkWa = '';
$showWaButton = true;

/*
    Supaya tidak dobel proses:
    kalau status sudah bukan menunggu, tidak di-update lagi
*/
if ($statusLama !== 'menunggu') {
    $judulModal = 'Data Sudah Diproses';
    $isiModal = 'Pendaftaran atas nama ' . $namaSiswa . ' sudah diproses sebelumnya dengan status "' . ucfirst($statusLama) . '".';
    $iconModal = '!';
    $iconClass = 'warning';
    $showWaButton = false;
} else {
    $stmt = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ? WHERE id_pendaftaran = ?");

    if (!$stmt) {
        die("Query gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "si", $status, $id_pendaftaran);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        die("Gagal update status: " . $error);
    }

    mysqli_stmt_close($stmt);

    if ($status === 'diterima') {
        $judulModal = 'Pendaftaran Diterima';
        $isiModal = 'Data pendaftaran berhasil diterima. Silakan kirim pemberitahuan ke WhatsApp orang tua / wali.';
        $iconModal = '✓';
        $iconClass = 'success';

        $pesanWa = "Assalamu'alaikum Bapak/Ibu Wali dari *{$namaSiswa}*.\n\n"
            . "Kami informasikan bahwa hasil pendaftaran peserta didik baru di *SMP YP 17 Surabaya* adalah *DITERIMA*.\n\n"
            . "Status saat ini tercatat sebagai *siswa baru* dan pembagian kelas akan dilakukan pada tahun ajaran baru.\n\n"
            . "Terima kasih.";
    } else {
        $judulModal = 'Pendaftaran Ditolak';
        $isiModal = 'Data pendaftaran berhasil ditolak. Silakan kirim pemberitahuan ke WhatsApp orang tua / wali.';
        $iconModal = '✕';
        $iconClass = 'danger';

        $pesanWa = "Assalamu'alaikum Bapak/Ibu Wali dari *{$namaSiswa}*.\n\n"
            . "Kami informasikan bahwa hasil pendaftaran peserta didik baru di *SMP YP 17 Surabaya* adalah *DITOLAK*.\n\n"
            . "Terima kasih telah melakukan pendaftaran.\n\n"
            . "Hormat kami,\nAdmin SMP YP 17 Surabaya";
    }

    if ($nomorWa !== '') {
        $linkWa = "https://wa.me/" . $nomorWa . "?text=" . rawurlencode($pesanWa);
    } else {
        $showWaButton = false;
        $isiModal .= "\n\nNomor WhatsApp wali tidak ditemukan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulModal); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: rgba(8, 84, 84, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(6, 56, 56, 0.35);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .popup-box {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            border-radius: 24px;
            padding: 34px 28px 26px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
            text-align: center;
            animation: popIn 0.25s ease;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .popup-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 54px;
            font-weight: 700;
        }

        .popup-icon.success {
            background: #dff7e7;
            color: #22c55e;
        }

        .popup-icon.danger {
            background: #fee2e2;
            color: #ef4444;
        }

        .popup-icon.warning {
            background: #fef3c7;
            color: #d97706;
        }

        .popup-box h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0f5d5d;
            margin-bottom: 14px;
        }

        .popup-box p {
            font-size: 15px;
            line-height: 1.8;
            color: #475569;
            margin-bottom: 26px;
            white-space: pre-line;
        }

        .popup-actions {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-popup {
            flex: 1;
            min-width: 160px;
            text-decoration: none;
            border: none;
            border-radius: 16px;
            padding: 16px 18px;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .btn-popup.secondary {
            background: #eef2f3;
            color: #0f5d5d;
            border: 1px solid #d9e2e5;
        }

        .btn-popup.secondary:hover {
            background: #e4eaec;
        }

        .btn-popup.primary {
            background: #22c55e;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.25);
        }

        .btn-popup.primary:hover {
            background: #16a34a;
        }

        @media (max-width: 520px) {
            .popup-box {
                padding: 28px 20px 22px;
                border-radius: 20px;
            }

            .popup-icon {
                width: 86px;
                height: 86px;
                font-size: 46px;
            }

            .popup-box h2 {
                font-size: 20px;
            }

            .popup-actions {
                flex-direction: column;
            }

            .btn-popup {
                width: 100%;
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="popup-overlay">
        <div class="popup-box">
            <div class="popup-icon <?= $iconClass; ?>">
                <?= $iconModal; ?>
            </div>

            <h2><?= htmlspecialchars($judulModal); ?></h2>
            <p><?= htmlspecialchars($isiModal); ?></p>

            <div class="popup-actions">
                <a href="/admin/admin_pendaftaran.php" class="btn-popup secondary">
                    Nanti Saja
                </a>

                <?php if ($showWaButton && $linkWa !== ''): ?>
                    <a href="<?= htmlspecialchars($linkWa); ?>" target="_blank" class="btn-popup primary">
                        Kirim ke WhatsApp
                    </a>
                <?php else: ?>
                    <a href="/admin/admin_pendaftaran.php" class="btn-popup primary">
                        Kembali ke Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>