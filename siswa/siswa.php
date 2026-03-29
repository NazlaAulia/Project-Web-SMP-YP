<?php 
session_start();
include 'koneksi.php';

// 🔒 PROTEKSI: kalau belum login / bukan siswa → balik ke login
if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit;
}

// 🎯 AMBIL DATA SESUAI USER YANG LOGIN
$id = $_SESSION['id_user'];

$querySiswa = mysqli_query($conn, "SELECT * FROM siswa WHERE user_id = '$id'");
$s = mysqli_fetch_assoc($querySiswa);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Smart School SMP YP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/dist/css/all.min.css">
</head>
<body class="dashboard-body">

    <div class="dashboard-wrapper">
        <nav class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Smart School</span>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="siswa.php"><i class="fas fa-th-large"></i> Dashboard Siswa</a></li>
                <li><a href="performa.php"><i class="fas fa-chart-line"></i> Performa Akademik</a></li>
                <li><a href="nilai.php"><i class="fas fa-file-invoice"></i> Nilai</a></li>
                <li><a href="jadwal.php"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
                <li><a href="peringkat.php"><i class="fas fa-medal"></i> Peringkat</a></li>
                <li><a href="profil.php"><i class="fas fa-user-circle"></i> Profil Siswa</a></li>
            </ul>
            <div class="sidebar-footer">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
        </nav>

        <main class="main-dashboard">
            <header class="dashboard-header">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Cari info akademik...">
                </div>
                <div class="user-nav">
                    <div class="user-profile">
                        <span><?php echo $s['nama']; ?></span>
                        <div class="avatar-placeholder"></div>
                    </div>
                </div>
            </header>

            <section class="welcome-banner">
                <div class="welcome-text">
                    <h1>Halo, <?php echo $s['nama']; ?>!</h1>
                    <p>Selamat datang kembali di dashboard siswa SMP YP. Cek performa akademikmu hari ini.</p>
                </div>
                <div class="welcome-illustration">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </section>

            <div class="dashboard-grid">
                <div class="grid-card">
                    <div class="card-header">
                        <h3>Performa Akademik</h3>
                    </div>
                    <div class="bar-chart-container">
                        <div class="bar" style="height: 85%"><span class="label">85</span></div>
                        <div class="bar" style="height: 70%"><span class="label">70</span></div>
                        <div class="bar" style="height: 95%"><span class="label">95</span></div>
                        <div class="bar" style="height: 60%"><span class="label">60</span></div>
                        <div class="bar" style="height: 80%"><span class="label">80</span></div>
                    </div>
                    <div class="chart-footer">
                        <span>Mat</span><span>Ind</span><span>Ing</span><span>Ipa</span><span>Ips</span>
                    </div>
                </div>

                <div class="grid-card">
                    <h3>Jadwal Hari Ini</h3>
                    <div class="schedule-list">
                        <?php 
                        $queryJadwal = mysqli_query($conn, "SELECT * FROM jadwal ORDER BY jam ASC");
                        if(mysqli_num_rows($queryJadwal) > 0) {
                            while($j = mysqli_fetch_assoc($queryJadwal)) { ?>
                                <div class="schedule-item">
                                    <span class="time"><?php echo $j['jam']; ?></span>
                                    <div class="task-info">
                                        <h4><?php echo $j['nama_pelajaran']; ?></h4>
                                        <p><?php echo $j['ruangan']; ?></p>
                                    </div>
                                </div>
                            <?php } 
                        } else {
                            echo "<p>Tidak ada jadwal hari ini.</p>";
                        } ?>
                    </div>
                </div>

                <div class="grid-card">
                    <h3>Progress Belajar</h3>
                    <div class="progress-circle-container">
                        <div class="progress-circle">
                            <div class="donut-percent"><?php echo $s['progress']; ?>%</div>
                            <svg width="150" height="150">
                                <circle cx="75" cy="75" r="60" stroke="#eee" stroke-width="12" fill="none" />
                                <circle cx="75" cy="75" r="60" stroke="#07484a" stroke-width="12" fill="none" 
                                        stroke-dasharray="377" 
                                        stroke-dashoffset="<?php echo 377 - (377 * $s['progress'] / 100); ?>" 
                                        stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>