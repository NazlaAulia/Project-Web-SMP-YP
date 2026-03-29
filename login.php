<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "sekolahyp");

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

$error_login = false; // Supaya warning orange ilang

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    $data = mysqli_fetch_assoc($query);

   if ($data) {
    $_SESSION['username'] = $data['username'];
    $_SESSION['role_id'] = $data['role_id'];
    $_SESSION['id_siswa'] = $data['id_siswa'] ?? null;
    $_SESSION['id_guru'] = $data['id_guru'] ?? null;
    $_SESSION['id_user'] = $data['id_user'];

   if ($data['role_id'] == 1) {
    header("Location: admin.php");
    exit;
} else if ($data['role_id'] == 2) {
    header("Location: guru/guru.php");
    exit;
} else if ($data['role_id'] == 3) {
    header("Location: siswa/siswa.php");
    exit;
}
}else {
        $error_login = true; 
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

                <form action="login.php" method="POST" class="siakad-form" id="mainLoginForm">
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
                            <input type="password" name="password" id="password" placeholder="Masukkan password" required>
                            <i class="fas fa-eye" id="togglePassword" style="cursor: pointer; margin-left: auto; color: #aaa;"></i>
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
                    <h1>Prestasi.</h1>
                    <p>Jadilah bagian dari generasi unggul, berkarakter kuat, dan siap mengukir prestasi gemilang bersama SMP YP 17.</p>
                    <div class="line"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 1. Fitur Mata Password
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // 2. Slider Otomatis
        let slides = document.querySelectorAll('.slide');
        let currentSlide = 0;
        setInterval(() => {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }, 5000);

        // 3. Loading Spinner
        document.getElementById('mainLoginForm').onsubmit = function() {
            document.getElementById('loginBtn').classList.add('is-loading');
        };
    </script>

    <?php if($error_login): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal!',
            text: 'Username Atau Passwoard salah',
            confirmButtonColor: '#07484a'
        });
    </script>
    <?php endif; ?>

</body>
</html>