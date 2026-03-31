<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sekolahyp";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$pesan = "";
$tipe_pesan = "";
$showModal = false;
$waLink = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
    $nisn            = trim($_POST['nisn'] ?? '');
    $jenis_kelamin   = trim($_POST['jenis_kelamin'] ?? '');
    $tanggal_lahir   = trim($_POST['tanggal_lahir'] ?? '');
    $alamat          = trim($_POST['alamat'] ?? '');
    $asal_sekolah    = trim($_POST['asal_sekolah'] ?? '');
    $no_hp           = trim($_POST['no_hp'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $pendapatan_ortu = trim($_POST['pendapatan_ortu'] ?? '');

    $tanggal_daftar = date('Y-m-d');
    $status = 'menunggu';

    if (
        $nama_lengkap === "" || $nisn === "" || $jenis_kelamin === "" ||
        $tanggal_lahir === "" || $alamat === "" || $asal_sekolah === "" ||
        $no_hp === "" || $pendapatan_ortu === ""
    ) {
        $pesan = "Semua field wajib diisi kecuali email.";
        $tipe_pesan = "error";
    } else {
        $cek = $conn->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE nisn = ?");
        $cek->bind_param("s", $nisn);
        $cek->execute();
        $hasil = $cek->get_result();

        if ($hasil->num_rows > 0) {
            $pesan = "NISN sudah pernah didaftarkan.";
            $tipe_pesan = "error";
        } else {
            $sql = "INSERT INTO pendaftaran
                    (nama_lengkap, nisn, jenis_kelamin, tanggal_lahir, alamat, asal_sekolah, no_hp, email, tanggal_daftar, status, pendapatan_ortu)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssd",
                $nama_lengkap,
                $nisn,
                $jenis_kelamin,
                $tanggal_lahir,
                $alamat,
                $asal_sekolah,
                $no_hp,
                $email,
                $tanggal_daftar,
                $status,
                $pendapatan_ortu
            );

            if ($stmt->execute()) {
                $pesan = "Pendaftaran berhasil dikirim.";
                $tipe_pesan = "success";
                $showModal = true;

                $nomorAdmin = "6283846311788"; // ganti nomor admin
                $pesanWa = "Halo Admin SMP YP 17 Surabaya,%0A%0ASaya atas nama *{$nama_lengkap}* telah melakukan pendaftaran PPDB.%0A"
                         . "NISN: {$nisn}%0A"
                         . "Asal Sekolah: {$asal_sekolah}%0A"
                         . "No HP: {$no_hp}%0A%0A"
                         . "Mohon konfirmasi pendaftaran saya. Terima kasih.";
                $waLink = "https://wa.me/" . $nomorAdmin . "?text=" . $pesanWa;
            } else {
                $pesan = "Gagal menyimpan data: " . $stmt->error;
                $tipe_pesan = "error";
            }

            $stmt->close();
        }

        $cek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPDB Online - SMP YP 17 Surabaya</title>
    <link rel="stylesheet" href="pendaftaran.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="page-wrap">
    <div class="register-card">
        <div class="dot-pattern dot-1"></div>
        <div class="dot-pattern dot-2"></div>

        <div class="register-left" data-aos="fade-right">
            <div class="brand-row">
             <div class="brand-logo">
    <img src="img/logo.webp" alt="Logo SMP YP 17 Surabaya">

    
</div>
                <div class="top-login-text">Sudah pernah daftar? <a href="login.php">Masuk Sekarang!</a></div>
            </div>

            <div class="form-heading">
                <h2>Form Pendaftaran</h2>
                <p>Isi formulir sesuai data resmi dan kirim pendaftaranmu sekarang.</p>
            </div>

            <?php if (!empty($pesan)) : ?>
                <div class="alert <?php echo $tipe_pesan; ?>">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <?php if ($showModal && !empty($waLink)) : ?>
    <div class="wa-reminder-box">
        <strong>Silakan lanjut konfirmasi ke admin.</strong>
        <a href="<?php echo htmlspecialchars($waLink); ?>" target="_blank">
            Chat Admin via WhatsApp
        </a>
    </div>
<?php endif; ?>

            <form method="POST" action="">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>NISN</label>
                        <input type="text" name="nisn" maxlength="20" required value="<?php echo htmlspecialchars($_POST['nisn'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin" required>
                            <option value="">-- Pilih --</option>
                            <option value="L" <?php echo (($_POST['jenis_kelamin'] ?? '') === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo (($_POST['jenis_kelamin'] ?? '') === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" required value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" maxlength="20" required value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Asal Sekolah</label>
                        <input type="text" name="asal_sekolah" required value="<?php echo htmlspecialchars($_POST['asal_sekolah'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Pendapatan Orang Tua</label>
                        <input type="number" step="0.01" name="pendapatan_ortu" required value="<?php echo htmlspecialchars($_POST['pendapatan_ortu'] ?? ''); ?>">
                    </div>
                </div>

                <div class="agreement">
                    <input type="checkbox" required>
                    <span>Saya mengisi data dengan benar dan siap menunggu verifikasi admin.</span>
                </div>
 <div class="form-note">
                    Data akan masuk dengan status menunggu.
                </div>
                
                <button type="submit" class="btn-submit">Kirim Pendaftaran</button>

               
                <a href="index.html" class="btn-back-dashboard">Kembali ke Dashboard</a>
            </form>
            
        </div>

        <div class="register-right" data-aos="fade-left" data-aos-delay="200">
            <div class="right-box">
                <div class="right-title-box">
                    <h3>Let’s Make<br>It Happen<br>Together!</h3>
                </div>

                <div class="right-image-box">
                </div>
<div class="right-placeholder">
    <p style="margin-bottom: 20px;">Pendaftaran online lebih mudah, cepat, dan langsung masuk ke sistem sekolah.</p>
    
    <div class="school-features">
        <div class="feat-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 8px;">✓</span>
            <span>Akreditasi A</span>
        </div>
        <div class="feat-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 8px;">✓</span>
            <span>Ekstrakurikuler Lengkap</span>
        </div>
        <div class="feat-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
            <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 8px;">✓</span>
            <span>Laboratorium Komputer Modern</span>
        </div>
    </div>
</div>
<div class="fasilitas-action" style="margin-top: 20px; text-align: left;">
    <a href="fasilitas.php" class="btn-fasilitas">
        <span>Lihat Detail Fasilitas</span>
        <i class="icon-arrow">→</i>
    </a>
    <p style="font-size: 11px; opacity: 0.7; margin-top: 8px; font-style: italic;">
        lihat fasilitas lebih lanjut
    </p>
</div>

<div class="live-stats-container" style="margin-top: 35px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
    <div class="stat-card" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.15); text-align: center;">
        <span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-bottom: 5px;">Pendaftar Hari Ini</span>
        <strong style="font-size: 24px; color: var(--secondary);">12</strong>
        <span style="font-size: 12px; display: block; opacity: 0.7;">Siswa</span>
    </div>

    <div class="stat-card" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.15); text-align: center;">
        <span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-bottom: 5px;">Kuota Tersedia</span>
        <strong style="font-size: 24px; color: #ffffff;">45</strong>
        <span style="font-size: 12px; display: block; opacity: 0.7;">Kursi</span>
    </div>
</div>

<div class="quota-progress" style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 5px; opacity: 0.8;">
        <span>Pengisian Kuota</span>
        <span>75% Terisi</span>
    </div>
    <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden;">
        <div style="width: 75%; height: 100%; background: var(--secondary); border-radius: 10px; animation: loadProgress 2s ease-out;"></div>
    </div>

</div>

<div class="contact-info-box" style="margin-top: 25px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 12px; border-left: 4px solid var(--secondary); text-align: left;">
    <span style="display: block; font-size: 11px; text-transform: uppercase; color: var(--secondary); font-weight: 700; margin-bottom: 5px;">Informasi Bantuan</span>
    <p style="font-size: 13px; margin: 0; opacity: 0.9; line-height: 1.5;">
        Senin - Sabtu (08:00 - 14:00 WIB)<br>
        Gedung Pusat Informasi Lantai 1
    </p>
</div>

<div class="testi-running" style="margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px;">
    <div class="marquee-wrap" style="overflow: hidden; white-space: nowrap;">
        <p class="animate-text" style="display: inline-block; font-size: 13px; font-style: italic; opacity: 0.8; animation: mlakuKiri 15s linear infinite;">
            "Belajar di SMP YP 17 Surabaya seru banget, fasilitasnya lengkap! - Alumni 2025" &nbsp;&nbsp;&nbsp; • &nbsp;&nbsp;&nbsp; 
            "Sekolahnya nyaman dan gurunya ramah-ramah! - Siswa Kelas 8" &nbsp;&nbsp;&nbsp; • &nbsp;&nbsp;&nbsp;
        </p>
    </div>
</div>
                <div class="right-wa-note">PPDB ONLINE</div>

                <div class="social-media-footer" style="margin-top: 30px; text-align: center;">
    <p style="font-size: 11px; opacity: 0.6; margin-bottom: 12px; letter-spacing: 1px; text-transform: uppercase;">Follow Us On</p>
    <div class="social-icons" style="display: flex; justify-content: center; gap: 20px;">
        <a href="https://instagram.com/smpyp17sby" target="_blank" class="soc-link">
            <i class="fab fa-instagram"></i>
        </a>
        <a href="https://wa.me/62xxxxxxxxxx" target="_blank" class="soc-link">
            <i class="fab fa-whatsapp"></i>
        </a>
        <a href="https://youtube.com/c/smpyp17sby" target="_blank" class="soc-link">
            <i class="fab fa-youtube"></i>
        </a>
    </div>
</div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay <?php echo $showModal ? 'active' : ''; ?>" id="successModal">
    <div class="modal-box">
        <div class="modal-icon">✓</div>
        <h3>Pendaftaran Berhasil</h3>
        <p>
            Terima kasih telah mendaftar.<br>
            Silakan konfirmasi ke admin melalui WhatsApp agar pendaftaranmu segera dicek.
        </p>

        <div class="modal-actions">
            <button type="button" class="btn-modal" onclick="closeModal()">Nanti Saja</button>
           <a href="<?php echo htmlspecialchars($waLink); ?>" class="btn-wa" target="_blank" onclick="closeModal();">
    Konfirmasi ke Admin
</a>
        </div>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('successModal').classList.remove('active');
}
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1000,
    once: true
  });
</script>
<script src="pendaftaran.js"></script>
</body>
</html>