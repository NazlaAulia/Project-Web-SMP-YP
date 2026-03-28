<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "sekolahyp");

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['role_id'] = $data['role_id'];

        if ($data['role_id'] == 1) {
            header("Location: admin.php");
            exit;
        } else if ($data['role_id'] == 2) {
            header("Location: guru.php");
            exit;
        } else if ($data['role_id'] == 3) {
            header("Location: siswa.php");
            exit;
        }
    } else {
        echo "<script>alert('Login Gagal! Username atau Password salah.'); window.location='login.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD - SMP YP 17 Surabaya</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-mulus-page">

    <div class="split-container container">
        
        <div class="login-form-panel">
            <div class="form-wrapper">
                
                <div class="form-header">
                    <img src="img/images.webp" alt="Logo SMP YP 17" class="form-logo">
                    <h2>Masuk SIAKAD</h2>
                    <p>Mulai pengalaman belajar barumu</p>
                </div>

          <form action="login.php" method="POST" class="siakad-form">

    <div class="input-group">
        <label>Username</label>
        <div class="input-field">
            <i class="fas fa-user-circle"></i>
            <input type="text" name="username" placeholder="Masukkan username" required>
        </div>
    </div>

    <div class="input-group">
        <label>Password</label>
        <div class="input-field">
            <i class="fas fa-key"></i>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>
    </div>

    <div class="form-footer">
        <label><input type="checkbox"> Ingat Saya</label>
        <a href="#" class="forgot-link">Lupa Password?</a>
    </div>

   <button type="submit" name="login" class="btn-login-main btn-yellow" id="loginBtn">
    <span class="btn-text">MASUK SEKARANG</span>
    <div class="spinner"></div>
</button>

</form>

                <a href="index.html" class="back-link">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>

        <div class="login-image-panel-fixed">
            
            <div class="background-overlay-mulus"></div>

            <div class="slider-wrapper">
                
                <div class="slide active">
                    <h1>Sambut Mimpimu.</h1>
                    <p>Selamat Datang di Portal Akademik SMP YP 17 Surabaya. Belajar, berkembang, dan gapai mimpimu bersama kami.</p>
                    <div class="line"></div>
                </div>

                <div class="slide">
                    <h1>Inovasi.</h1>
                    <p>SIAKAD mendukung proses belajar yang lebih modern, efisien, dan transparan untuk seluruh civitas akademika.</p>
                    <div class="line"></div>
                </div>

                <div class="slide">
                    <h1>Prestasi.</strong></h1>
                    <p>Jadilah bagian dari generasi unggul, berkarakter kuat, dan siap mengukir prestasi gemilang bersama SMP YP 17.</p>
                    <div class="line"></div>
                </div>

            </div>

        </div>

    </div>

    <script>
        // Milih kabeh slide lan miwiti slide dhisik
        let slides = document.querySelectorAll('.slide');
        let currentSlide = 0;

        function nextSlide() {
            // Mateni slide sing saiki aktif
            slides[currentSlide].classList.remove('active');
            
            // Ganti branch dadi main coba
            currentSlide = (currentSlide + 1) % slides.length;
            
            // Mung nampili slide sing aktif tok
            slides[currentSlide].classList.add('active');
        }

        // Teks Geser Otomatis (Setiap 5 Detik)
        setInterval(nextSlide, 5000);
        
        const loginBtn = document.getElementById('loginBtn');
const loginForm = document.querySelector('.siakad-form');

/*loginForm.addEventListener('submit', function(e) {
    //e.preventDefault(); // Mencegah halaman refresh langsung

    // Aktifkan efek loading
    loginBtn.classList.add('is-loading');

    // Simulasi loading 2 detik seolah-olah sedang cek database
    setTimeout(() => {
        // Setelah 2 detik, arahkan ke dashboard (contoh)
        // window.location.href = "dashboard.html"; 
        
        // Untuk sekarang kita lepas saja loadingnya buat tes
        alert("Login Berhasil! Mengalihkan ke dashboard...");
        loginBtn.classList.remove('is-loading');
    }, 2000);
}); */
    </script>

</body>
</html>

