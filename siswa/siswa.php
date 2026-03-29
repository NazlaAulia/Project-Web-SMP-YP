<?php
session_start();
include 'koneksi.php';

// 🔒 Cek login
if (!isset($_SESSION['id_siswa'])) {
    header("Location: login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$querySiswa = mysqli_query($conn, "SELECT * FROM siswa WHERE id_siswa = $id_siswa");
$s = mysqli_fetch_assoc($querySiswa);

// Default progress nek neng database kosong
$progress = isset($s['progress']) ? (int)$s['progress'] : 85;
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
                <h2 class="brand-text">Smart School</h2>
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
                    <span class="notif-badge">3</span>
                    <div class="user-profile-top">
                        <img src="img/avatar.png" alt="Avatar" class="avatar-img">
                        <span class="user-name"><?php echo $s['nama']; ?></span>
                    </div>
                </div>
            </header>

            <section class="banner-welcome">
                <div class="banner-content">
                    <h1>Halo, <?php echo $s['nama']; ?>!</h1>
                    <p>Ada 3 tugas baru hari ini. Semangat belajarnya ya!</p>
                    <a href="#" class="btn-cek">Cek Sekarang</a>
                </div>
                <img src="img/student-illustration.png" alt="Study" class="banner-img">
            </section>

            <div class="dashboard-grid">
                <div class="card chart-card">
                    <div class="card-header">
                        <h3>Performa Akademik</h3>
                        <span class="year-label">Tahun Ini</span>
                    </div>
                    <div class="bar-chart">
                        <div class="bar-group">
                            <div class="bar-fill" style="height: 85%;"><span>85</span></div>
                            <span class="bar-label">Mat</span>
                        </div>
                        <div class="bar-group">
                            <div class="bar-fill" style="height: 64%;"><span>64</span></div>
                            <span class="bar-label">Ind</span>
                        </div>
                        <div class="bar-group">
                            <div class="bar-fill" style="height: 92%;"><span>92</span></div>
                            <span class="bar-label">Ing</span>
                        </div>
                        <div class="bar-group">
                            <div class="bar-fill" style="height: 45%;"><span>45</span></div>
                            <span class="bar-label">Ipa</span>
                        </div>
                        <div class="bar-group">
                            <div class="bar-fill" style="height: 75%;"><span>75</span></div>
                            <span class="bar-label">Ips</span>
                        </div>
                    </div>

                    <div class="mentor-section">
                        <h4>Guru Pengampu</h4>
                        <div class="mentor-card">
                            <img src="img/teacher.png" alt="Mentor">
                            <div class="mentor-info">
                                <strong>Mary Johnson (Mentor)</strong>
                                <p>Sains & Biologi</p>
                            </div>
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

                    <div class="card progress-card">
                        <h3>Progress Belajar</h3>
                        <div class="circle-chart">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $progress; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <text x="18" y="20.35" class="percentage"><?php echo $progress; ?>%</text>
                            </svg>
                        </div>
                        <p class="progress-footer">Tugas Selesai</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>