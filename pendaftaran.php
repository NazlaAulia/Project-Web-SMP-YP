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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap    = trim($_POST['nama_lengkap']);
    $nisn            = trim($_POST['nisn']);
    $jenis_kelamin   = trim($_POST['jenis_kelamin']);
    $tanggal_lahir   = trim($_POST['tanggal_lahir']);
    $alamat          = trim($_POST['alamat']);
    $asal_sekolah    = trim($_POST['asal_sekolah']);
    $no_hp           = trim($_POST['no_hp']);
    $email           = trim($_POST['email']);
    $pendapatan_ortu = trim($_POST['pendapatan_ortu']);

    $tanggal_daftar = date('Y-m-d');
    $status = 'menunggu';

    $cek = $conn->prepare("SELECT id_pendaftaran FROM pendaftaran WHERE nisn = ?");
    $cek->bind_param("s", $nisn);
    $cek->execute();
    $hasil = $cek->get_result();

    if ($hasil->num_rows > 0) {
        $pesan = "NISN sudah pernah didaftarkan.";
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
            $pesan = "Pendaftaran berhasil dikirim. Silakan tunggu ACC admin.";
        } else {
            $pesan = "Gagal menyimpan data: " . $stmt->error;
        }

        $stmt->close();
    }

    $cek->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran PPDB</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f5f5;
            margin:0;
            padding:0;
        }
        .container{
            width:90%;
            max-width:700px;
            margin:40px auto;
            background:#fff;
            padding:30px;
            border-radius:10px;
            box-shadow:0 2px 10px rgba(0,0,0,0.1);
        }
        h2{
            text-align:center;
            margin-bottom:25px;
        }
        .form-group{
            margin-bottom:15px;
        }
        label{
            display:block;
            margin-bottom:6px;
            font-weight:bold;
        }
        input, select, textarea{
            width:100%;
            padding:10px;
            border:1px solid #ccc;
            border-radius:6px;
            font-size:14px;
        }
        button{
            background:#0d6efd;
            color:white;
            border:none;
            padding:12px 20px;
            border-radius:6px;
            cursor:pointer;
            width:100%;
            font-size:16px;
        }
        button:hover{
            background:#0b5ed7;
        }
        .pesan{
            margin-bottom:20px;
            padding:12px;
            border-radius:6px;
            background:#eef6ff;
            color:#1d3557;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Form Pendaftaran PPDB</h2>

    <?php if (!empty($pesan)) : ?>
        <div class="pesan"><?php echo $pesan; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama_lengkap" required>
        </div>

        <div class="form-group">
            <label>NISN</label>
            <input type="text" name="nisn" maxlength="20" required>
        </div>

        <div class="form-group">
            <label>Jenis Kelamin</label>
            <select name="jenis_kelamin" required>
                <option value="">-- Pilih --</option>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal Lahir</label>
            <input type="date" name="tanggal_lahir" required>
        </div>

        <div class="form-group">
            <label>Alamat</label>
            <textarea name="alamat" rows="4" required></textarea>
        </div>

        <div class="form-group">
            <label>Asal Sekolah</label>
            <input type="text" name="asal_sekolah" required>
        </div>

        <div class="form-group">
            <label>No HP</label>
            <input type="text" name="no_hp" maxlength="20" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label>Pendapatan Orang Tua</label>
            <input type="number" step="0.01" name="pendapatan_ortu" required>
        </div>

        <button type="submit">Kirim Pendaftaran</button>
    </form>
</div>

</body>
</html>