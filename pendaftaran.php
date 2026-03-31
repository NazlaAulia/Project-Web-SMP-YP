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
</head>
<body>

<div class="page-wrap">
    <div class="register-card">
        <div class="dot-pattern dot-1"></div>
        <div class="dot-pattern dot-2"></div>

        <div class="register-left">
            <div class="brand-row">
                <div class="brand-mark">co.</div>
                <div class="top-login-text">Sudah pernah daftar? <a href="#">Sign in here!</a></div>
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

                <button type="submit" class="btn-submit">Kirim Pendaftaran</button>

                <div class="form-note">
                    Data akan masuk ke tabel pendaftaran dengan status menunggu.
                </div>
            </form>
        </div>

        <div class="register-right">
            <div class="right-box">
                <div class="right-title-box">
                    <h3>Let’s Make<br>It Happen<br>Together!</h3>
                </div>

                <div class="right-image-box">
                    <div class="right-placeholder">
                        <strong>PPDB SMP YP 17 Surabaya</strong><br><br>
                        Pendaftaran online lebih mudah, cepat, dan langsung masuk ke sistem sekolah.
                    </div>
                </div>

                <div class="right-wa-note">Ping us for any inquiries!</div>
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
            <button class="btn-modal" onclick="closeModal()">Nanti Saja</button>
            <a href="<?php echo htmlspecialchars($waLink); ?>" class="btn-wa" target="_blank">Konfirmasi ke Admin</a>
        </div>
    </div>
</div>

<script>
function closeModal() {
    document.getElementById('successModal').classList.remove('active');
}
</script>

</body>
</html>