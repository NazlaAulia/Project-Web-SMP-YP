<?php
session_start();
include 'koneksi.php';

// 🔒 Cek login
if (!isset($_SESSION['id_siswa'])) {
    header("Location: login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

$querySiswa = mysqli_query($conn, "
    SELECT siswa.*, kelas.nama_kelas
    FROM siswa
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    WHERE siswa.id_siswa = $id_siswa
");

$s = mysqli_fetch_assoc($querySiswa);

// Default progress nek neng database kosong
$progress = isset($s['progress']) ? (int)$s['progress'] : 85;

// Nama kelas asli dari tabel kelas
$nama_kelas = !empty($s['nama_kelas']) ? $s['nama_kelas'] : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Smart School</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">

    <div class="dashboard-wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <img src="../img/logo.webp" alt="Logo" class="logo">
            </div>
            
            <ul class="sidebar-menu">
                <li class="active"><a href="#"><i class="fas fa-th-large"></i> Dashboard Siswa</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Performa Akademik</a></li>
                <li><a href="#"><i class="fas fa-file-alt"></i> Nilai</a></li>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
                <li><a href="#"><i class="fas fa-medal"></i> Peringkat</a></li>
                <li><a href="#"><i class="fas fa-user"></i> Profil Siswa</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">Log Out</a>
            </div>
        </nav>

        <main class="main-content">
            <header class="top-header">
                <div class="search-container">
                    <input type="text" placeholder="Cari info akademik, tugas, atau jadwal...">
                </div>
                <div class="user-info">
                    <span class="notif-badge"><?php echo $nama_kelas; ?></span>
                    <div class="user-profile-top">
                        <div class="avatar-placeholder">
                            <?php echo strtoupper(substr($s['nama'], 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo $s['nama']; ?></span>
                    </div>
                </div>
            </header>

            <section class="banner-welcome">
                <div class="banner-content">
                    <h1>Halo, <?php echo $s['nama']; ?>!</h1>
                    <p>Selamat datang di dashboard siswa. Semangat belajar hari ini!</p>
                </div>

                <div class="banner-image-wrap">
                    <img src="../img/siswa.webp" alt="Study" class="banner-img">
                </div>
            </section>

            <div class="dashboard-grid">
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>Performa Akademik</h3>
                        <span class="year-label">Tahun Ini</span>
                    </div>

                    <?php
                    $mat = 85;
                    $ind = 64;
                    $ing = 92;
                    $ipa = 45;
                    $ips = 75;

                    $rata_rata = round(($mat + $ind + $ing + $ipa + $ips) / 5, 1);

                    $nilai_mapel = [
                        "Matematika" => $mat,
                        "Bahasa Indonesia" => $ind,
                        "Bahasa Inggris" => $ing,
                        "IPA" => $ipa,
                        "IPS" => $ips
                    ];

                    $mapel_tertinggi = array_search(max($nilai_mapel), $nilai_mapel);
                    $nilai_tertinggi = max($nilai_mapel);

                    $mapel_terendah = array_search(min($nilai_mapel), $nilai_mapel);
                    $nilai_terendah = min($nilai_mapel);
                    ?>

                    <div class="bar-chart">
                        <div class="bar-group">
                            <div class="bar-fill" style="height: <?php echo $mat; ?>%;">
                                <span><?php echo $mat; ?></span>
                            </div>
                            <span class="bar-label">Mat</span>
                        </div>

                        <div class="bar-group">
                            <div class="bar-fill" style="height: <?php echo $ind; ?>%;">
                                <span><?php echo $ind; ?></span>
                            </div>
                            <span class="bar-label">Ind</span>
                        </div>

                        <div class="bar-group">
                            <div class="bar-fill" style="height: <?php echo $ing; ?>%;">
                                <span><?php echo $ing; ?></span>
                            </div>
                            <span class="bar-label">Ing</span>
                        </div>

                        <div class="bar-group">
                            <div class="bar-fill" style="height: <?php echo $ipa; ?>%;">
                                <span><?php echo $ipa; ?></span>
                            </div>
                            <span class="bar-label">Ipa</span>
                        </div>

                        <div class="bar-group">
                            <div class="bar-fill" style="height: <?php echo $ips; ?>%;">
                                <span><?php echo $ips; ?></span>
                            </div>
                            <span class="bar-label">Ips</span>
                        </div>
                    </div>

                    <div class="academic-summary">
                        <div class="summary-box">
                            <h4>Rata-rata</h4>
                            <p><?php echo $rata_rata; ?></p>
                        </div>
                        <div class="summary-box">
                            <h4>Tertinggi</h4>
                            <p><?php echo $mapel_tertinggi; ?> (<?php echo $nilai_tertinggi; ?>)</p>
                        </div>
                        <div class="summary-box">
                            <h4>Terendah</h4>
                            <p><?php echo $mapel_terendah; ?> (<?php echo $nilai_terendah; ?>)</p>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <div class="card schedule-card">
                        <div class="card-header">
                            <h3>Jadwal Hari Ini</h3>
                            <span class="day-label">Hari Ini</span>
                        </div>
                        <div class="schedule-item">
                            <span class="schedule-time">09:00</span>
                            <div class="schedule-details">
                                <h4>Elektronika Dasar</h4>
                                <p>R. Lab 01, Pertemuan 12</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>