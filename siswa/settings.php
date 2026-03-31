<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['id_siswa'])) {
    header("Location: login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

$querySiswa = mysqli_query($conn, "
    SELECT siswa.*, kelas.nama_kelas, user.id_user, user.username, user.password
    FROM siswa
    LEFT JOIN kelas ON siswa.id_kelas = kelas.id_kelas
    LEFT JOIN user ON user.id_siswa = siswa.id_siswa
    WHERE siswa.id_siswa = $id_siswa
");

$s = mysqli_fetch_assoc($querySiswa);

if (!$s) {
    die("Data siswa tidak ditemukan.");
}

$nama_kelas = !empty($s['nama_kelas']) ? $s['nama_kelas'] : '-';
$success = "";
$error = "";

// Proses ubah password
if (isset($_POST['ubah_password'])) {
    $password_lama = trim($_POST['password_lama']);
    $password_baru = trim($_POST['password_baru']);
    $konfirmasi_password = trim($_POST['konfirmasi_password']);

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $error = "Semua field wajib diisi.";
    } elseif (strlen($password_baru) < 8) {
        $error = "Password baru minimal 8 karakter.";
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = "Konfirmasi password baru tidak cocok.";
    } else {
        if ($password_lama === $s['password']) {
            $password_baru_aman = mysqli_real_escape_string($conn, $password_baru);

            $updatePassword = mysqli_query($conn, "
                UPDATE user
                SET password = '$password_baru_aman'
                WHERE id_siswa = $id_siswa
            ");

            if ($updatePassword) {
                $success = "Password berhasil diubah.";
            } else {
                $error = "Gagal mengubah password.";
            }
        } else {
            $error = "Password lama salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Siswa | Smart School</title>
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
            <li><a href="siswa.php"><i class="fas fa-th-large"></i> Dashboard Siswa</a></li>
            <li><a href="#"><i class="fas fa-chart-line"></i> Performa Akademik</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i> Nilai</a></li>
            <li><a href="#"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
            <li><a href="#"><i class="fas fa-medal"></i> Peringkat</a></li>
            <li><a href="#"><i class="fas fa-user"></i> Profil Siswa</a></li>
            <li class="active"><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
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

        <section class="banner-welcome settings-banner">
            <div class="banner-content settings-banner-content">
                <h1>Settings</h1>
                <p>Kelola keamanan akunmu di sini. Gunakan password minimal 8 karakter.</p>
            </div>
        </section>

        <?php if (!empty($success)) : ?>
            <div class="settings-alert success-alert"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="settings-alert error-alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <section class="settings-modern-wrap">
            <div class="settings-modern-card">
                <h3 class="settings-modern-title">Ubah Password</h3>

                <form method="POST" class="settings-modern-form">
                    <div class="settings-modern-grid">

                        <div class="settings-box">
                            <div class="settings-box-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>

                            <h4>Password Lama</h4>

                            <div class="settings-input-wrap">
                                <input type="password" id="password_lama" name="password_lama" placeholder="Masukkan password lama" required>
                                <button type="button" class="eye-btn" onclick="togglePassword('password_lama', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <p>Masukkan password akunmu saat ini.</p>
                        </div>

                        <div class="settings-box">
                            <div class="settings-box-icon">
                                <i class="fas fa-lock"></i>
                            </div>

                            <h4>Password Baru</h4>

                            <div class="settings-input-wrap">
                                <input type="password" id="password_baru" name="password_baru" placeholder="Masukkan password baru" required onkeyup="checkPasswordStrength(this.value)">
                                <button type="button" class="eye-btn" onclick="togglePassword('password_baru', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <div class="strength-row">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-text" id="strengthText">Minimal 8 karakter</span>
                            </div>
                        </div>

                        <div class="settings-box">
                            <div class="settings-box-icon">
                                <i class="fas fa-key"></i>
                            </div>

                            <h4>Konfirmasi Password Baru</h4>

                            <div class="settings-input-wrap">
                                <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password baru" required>
                                <button type="button" class="eye-btn" onclick="togglePassword('konfirmasi_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <p>Harus sama dengan password baru.</p>
                        </div>

                    </div>

                    <button type="submit" name="ubah_password" class="settings-submit-btn">
                        Simpan Password
                    </button>
                </form>
            </div>
        </section>
    </main>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function checkPasswordStrength(password) {
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');

    let score = 0;

    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    if (password.length === 0) {
        fill.style.width = '0%';
        text.textContent = 'Minimal 8 karakter';
    } else if (score <= 2) {
        fill.style.width = '35%';
        text.textContent = 'Lemah';
    } else if (score <= 4) {
        fill.style.width = '70%';
        text.textContent = 'Sedang';
    } else {
        fill.style.width = '100%';
        text.textContent = 'Sangat kuat';
    }
}
</script>

</body>
</html>