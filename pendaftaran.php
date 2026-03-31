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
                $pesan = "Pendaftaran berhasil dikirim. Silakan tunggu verifikasi admin.";
                $tipe_pesan = "success";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="page-wrap">
    <div class="register-card">

        <div class="register-left">
            <div class="left-overlay"></div>
            <div class="left-top-image">
                <img src="img/sekolah-cover.jpg" alt="PPDB SMP YP 17">
            </div>

            <div class="shape shape-dark"></div>
            <div class="shape shape-main"></div>
            <div class="shape shape-accent"></div>
            <div class="shape shape-small"></div>

            <div class="left-content">
                <span class="left-badge">PPDB ONLINE</span>
                <h1>Let’s Make<br>It Happen<br>Together!</h1>
                <p>
                    Bergabunglah bersama SMP YP 17 Surabaya dan isi formulir pendaftaran
                    secara online. Data akan masuk ke sistem dan menunggu ACC admin.
                </p>
            </div>
        </div>

        <div class="register-right">
            <div class="brand-mark">SMP YP 17</div>
            <div class="top-login-text">
                Sudah pernah daftar? <a href="#">Cek status di admin</a>
            </div>

            <div class="form-heading">
                <h2>Form Pendaftaran</h2>
                <p>Isi data dengan lengkap sesuai dokumen resmi.</p>
            </div>

            <?php if (!empty($pesan)) : ?>
                <div class="alert <?php echo $tipe_pesan; ?>">
                    <?php echo htmlspecialchars($pesan); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required
                               value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>NISN</label>
                        <input type="text" name="nisn" maxlength="20" required
                               value="<?php echo htmlspecialchars($_POST['nisn'] ?? ''); ?>">
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
                        <input type="date" name="tanggal_lahir" required
                               value="<?php echo htmlspecialchars($_POST['tanggal_lahir'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>No HP</label>
                        <input type="text" name="no_hp" maxlength="20" required
                               value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="4" required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Asal Sekolah</label>
                        <input type="text" name="asal_sekolah" required
                               value="<?php echo htmlspecialchars($_POST['asal_sekolah'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row full">
                    <div class="form-group">
                        <label>Pendapatan Orang Tua</label>
                        <input type="number" step="0.01" name="pendapatan_ortu" required
                               value="<?php echo htmlspecialchars($_POST['pendapatan_ortu'] ?? ''); ?>">
                    </div>
                </div>

                <div class="agreement">
                    <input type="checkbox" required>
                    <span>Saya mengisi data dengan benar dan siap menunggu verifikasi admin.</span>
                </div>

                <button type="submit" class="btn-submit">Kirim Pendaftaran</button>

                <div class="form-note">
                    Setelah submit, data akan masuk ke tabel <strong>pendaftaran</strong>
                    dengan status <strong>menunggu</strong>.
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>