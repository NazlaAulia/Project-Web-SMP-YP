<?php
require_once __DIR__ . '/admin/koneksi.php';

$pengumumanResult = $conn->query("
    SELECT *
    FROM pengumuman
    WHERE status = 'tampil'
    ORDER BY tanggal DESC, id_pengumuman DESC
    LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Informasi Akademik - SMP YP 17 Surabaya</title>
<link rel="icon" type="image/x-icon" href="img/images.webp">
<link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="components/include.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div id="navbar-container"></div>


<!-- HERO -->
<section class="hero">

<div class="hero-slider">

<img class="slide active" src="img/gerbang-cover-website.webp">
<img class="slide" src="img/PMR-3.webp">
<img class="slide" src="img/fotokita.webp">
<img class="slide" src="img/pramuka_4-800x500-1.webp">

<div class="hero-overlay"></div>

<div class="hero-content">

<h3><i class="fas fa-graduation-cap"></i> Selamat Datang Di Smp Yp 17 Surabaya!</h3>

<h1>
Mari Belajar <span>Bersama</span><br>
Untuk Menggapai  <span>Mimpi </span> Dimasa Depan
</h1>

<p>
Mendukung pengelolaan data akademik sekolah
</p>

<div class="hero-btns">
<a href="Profil.html" class="btn-main">TENTANG KAMI</a>
<a href="ekstrakurikuler.html" class="btn-sub">SELENGKAPNYA</a>
</div>

</div>
</div>


<!-- STATISTIK -->
<div class="stats-container container">

<div class="stat-card">
<div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
<div class="stat-num">01</div>
<h2 class="counter" data-target="714">0</h2>
<p>Siswa Aktif</p>
</div>

<div class="stat-card">
<div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
<div class="stat-num">02</div>
<h2 class="counter" data-target="60">0</h2>
<p>Guru & Karyawan</p>
</div>

<div class="stat-card">
<div class="stat-icon"><i class="fas fa-users"></i></div>
<div class="stat-num">03</div>
<h2 class="counter" data-target="8000">0</h2>
<p>Alumni Tersebar</p>
</div>

<div class="stat-card orange">
<div class="stat-icon"><i class="fas fa-award"></i></div>
<div class="stat-num">04</div>
<h2 class="counter" data-target="90">0</h2>
<p>Lulusan SMA Favorit</p>
</div>

</div>

</section>



<section id="sambutan" class="sambutan container">
    <div class="sambutan-wrapper">
        <div class="sambutan-img">
            <img src="img/kepsek-smpyp17-potrait.webp" alt="Kepala Sekolah SMP YP 17">
            <div class="name-tag">
                <h4>Nama Kepala Sekolah, S.Pd, M.Pd</h4>
                <p>Kepala Sekolah SMP YP 17 Surabaya</p>
            </div>
        </div>
        
        <div class="sambutan-text">
            <span>SAMBUTAN HANGAT</span>
            <h2>Selamat Datang di <br> <span>SMP YP 17 Surabaya</span></h2>
            <div class="line"></div>
            <p>
                "Salam Sehat, Salam Sejahtera Untuk Kita Semua.

Puji syukur kita panjatkan kehadirat Allah SWT atas rahmat , hidayah, inayah dan lindungan Nya sehingga SMP YP 17 Surabaya dapat meluncurkan kembali website sekolah"
            </p>
            <p>
                Keberadaan website sekolah dewasa ini dipandang sangat penting sebagai media publikasi dan komunikasi sekolah. 

Selain itu, website sekolah ini bisa dimanfaatkan sebagai sumber informasi bagi guru, karyawan, siswa, orang tua/wali murid serta masyarakat umum untuk dapat mengakses seluruh informasi tentang sekolah dengan segala kegiatan dalam melaksanakan proses pendidikan dan upaya untuk mewujudkan visi, misi sekolah
            </p>
            <p>
Diharapkan dapat meningkatkan mutu sekolah melalui pemberdayaan semua input yang ada serta bisa dijadikan media komunikasi yang positif antara sesama warga sekolah maupun masyarakat pemerhati pendidikan pada umumnya.

Akhirnya, saran dan kritik yang membangun sangat diharapkan demi peningkatan mutu SMP YP 17  Surabaya.

Wassalamu alaikum wr wb, salam sehat,  salam sejahtera untuk kita semua.
              </p>
            <div class="quote-icon">
                <i class="fas fa-quote-right"></i>
            </div>
        </div>
    </div>
</section>

<section id="pengumuman" class="pengumuman container">
    <div class="section-title">
        <span>INFORMASI TERBARU</span>
        <h2>Pengumuman Sekolah</h2>
        <div class="line"></div>
    </div>

    <div class="announcement-grid">
        <?php if ($pengumumanResult && $pengumumanResult->num_rows > 0) : ?>
            <?php while ($p = $pengumumanResult->fetch_assoc()) : ?>
                <?php
                    $gambar = !empty($p['gambar'])
                        ? 'admin/pengumuman/' . $p['gambar']
                        : 'img/images.webp';

                    $tanggal = date('d M Y', strtotime($p['tanggal']));
                    $isiPendek = mb_strimwidth(strip_tags($p['isi']), 0, 115, '...');
                ?>

                <div class="announcement-card">
                    <div class="a-img">
                        <img src="<?= htmlspecialchars($gambar); ?>" alt="<?= htmlspecialchars($p['judul']); ?>">
                        <span class="category-label"><?= htmlspecialchars($p['kategori']); ?></span>
                    </div>

                    <div class="a-content">
                        <div class="a-meta">
                            <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($tanggal); ?></span>
                        </div>

                        <h4><?= htmlspecialchars($p['judul']); ?></h4>
                        <p><?= htmlspecialchars($isiPendek); ?></p>

                        <button
                            type="button"
                            class="read-more pengumuman-open-modal"
                            data-judul="<?= htmlspecialchars($p['judul'], ENT_QUOTES); ?>"
                            data-tanggal="<?= htmlspecialchars($tanggal, ENT_QUOTES); ?>"
                            data-kategori="<?= htmlspecialchars($p['kategori'], ENT_QUOTES); ?>"
                            data-isi="<?= htmlspecialchars($p['isi'], ENT_QUOTES); ?>"
                            data-gambar="<?= htmlspecialchars($gambar, ENT_QUOTES); ?>"
                        >
                            Baca Selengkapnya →
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p>Belum ada pengumuman.</p>
        <?php endif; ?>
    </div>
</section>

<section id="alumni" class="alumni-section container">
    <div class="section-title">
        <span>TESTIMONIAL</span>
        <h2>Apa Kata Alumni Kami?</h2>
        <div class="line"></div>
    </div>

    <div class="alumni-slider">
        <div class="alumni-container">
            <div class="alumni-card active">
                <div class="quote-top"><i class="fas fa-quote-left"></i></div>
                <p class="alumni-text">"Belajar di SMP YP 17 Surabaya adalah pengalaman luar biasa. Guru-gurunya sangat suportif dan fasilitas labnya membantu saya memahami teknologi lebih dalam sejak dini."</p>
                <div class="alumni-profile">
                    <img src="img/mhs1.avif" alt="Alumni 1">
                    <div class="alumni-info">
                        <h4>Budi Santoso</h4>
                        <span>Alumni 2020 - Mahasiswa ITS</span>
                    </div>
                </div>
            </div>

            <div class="alumni-card">
                <div class="quote-top"><i class="fas fa-quote-left"></i></div>
                <p class="alumni-text">"Kedisiplinan dan karakter yang diajarkan di sini benar-benar menjadi bekal berharga bagi saya di bangku SMA dan dunia kerja sekarang."</p>
                <div class="alumni-profile">
                    <img src="img/mhs2.jpg" alt="Alumni 2">
                    <div class="alumni-info">
                        <h4>Siti Aminah</h4>
                        <span>Alumni 2018 - Graphic Designer</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="slider-dots">
            <span class="dot active" onclick="currentAlumni(0)"></span>
            <span class="dot" onclick="currentAlumni(1)"></span>
        </div>
    </div>
</section>

<section id="kontak" class="kontak-section container">
    <div class="section-title">
        <span>HUBUNGI KAMI</span>
        <h2>Kontak Kami</h2>
        <div class="line"></div>
    </div>

    <div class="kontak-wrapper">
        <div class="kontak-info-box">
            <div class="info-item">
                <i class="fas fa-map-marked-alt"></i>
                <div>
                    <h4>Alamat Sekolah</h4>
                    <p>Jl. Randu No.17, Sidotopo Wetan, Kec. Kenjeran, Surabaya, Jawa Timur 60128</p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <h4>Telepon </h4>
                    <p>(031) 376 3721 </p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-envelope-open-text"></i>
                <div>
                    <h4>Email Resmi</h4>
                    <p>smpyp17sby@gmail.com</p>
                </div>
            </div>

            <div class="map-container">
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3958.0700863901416!2d112.7663273748386!3d-7.232865992773223!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd2499be5967007%3A0x633d735165983758!2sSMP%20YP%2017%20Surabaya!5e0!3m2!1sid!2sid!4v1709456789012!5m2!1sid!2sid" 
        width="100%" 
        height="250" 
        style="border:0; border-radius: 15px;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>
        </div>

        <div class="kontak-form">
            <form action="#">
                <div class="input-row">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" placeholder="Masukkan nama..." required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" placeholder="Email aktif..." required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Subjek</label>
                    <input type="text" placeholder="Tujuan pesan (Misal: Tanya PPDB)">
                </div>
                <div class="form-group">
                    <label>Pesan Anda</label>
                    <textarea rows="5" placeholder="Tulis pesan lengkap di sini..."></textarea>
                </div>
                <button type="submit" class="btn-kirim">
                    KIRIM PESAN <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<div id="footer-container"></div>

<div class="pengumuman-modal-overlay" id="pengumumanModal">
    <div class="pengumuman-modal-box">
        <button type="button" class="pengumuman-modal-close" id="closePengumumanModal">
            &times;
        </button>

        <div class="pengumuman-modal-image">
            <img src="" alt="" id="modalPengumumanGambar">
            <span id="modalPengumumanKategori"></span>
        </div>

        <div class="pengumuman-modal-content">
            <div class="pengumuman-modal-date" id="modalPengumumanTanggal"></div>
            <h2 id="modalPengumumanJudul"></h2>
            <p id="modalPengumumanIsi"></p>
        </div>
    </div>
</div>

<script>
const pengumumanModal = document.getElementById("pengumumanModal");
const closePengumumanModal = document.getElementById("closePengumumanModal");

const modalPengumumanGambar = document.getElementById("modalPengumumanGambar");
const modalPengumumanKategori = document.getElementById("modalPengumumanKategori");
const modalPengumumanTanggal = document.getElementById("modalPengumumanTanggal");
const modalPengumumanJudul = document.getElementById("modalPengumumanJudul");
const modalPengumumanIsi = document.getElementById("modalPengumumanIsi");

document.querySelectorAll(".pengumuman-open-modal").forEach((button) => {
    button.addEventListener("click", () => {
        modalPengumumanGambar.src = button.dataset.gambar;
        modalPengumumanGambar.alt = button.dataset.judul;

        modalPengumumanKategori.textContent = button.dataset.kategori;
        modalPengumumanTanggal.innerHTML = `<i class="far fa-calendar-alt"></i> ${button.dataset.tanggal}`;
        modalPengumumanJudul.textContent = button.dataset.judul;
        modalPengumumanIsi.textContent = button.dataset.isi;

        pengumumanModal.classList.add("active");
    });
});

closePengumumanModal.addEventListener("click", () => {
    pengumumanModal.classList.remove("active");
});

pengumumanModal.addEventListener("click", (event) => {
    if (event.target === pengumumanModal) {
        pengumumanModal.classList.remove("active");
    }
});
</script>

<a href="#" class="back-to-top"><i class="fas fa-chevron-up"></i></a>


 <script src="components/include.js"></script>
  <script src="components/navbar.js"></script>
<script src="./script.js" defer></script>

</body>
</html>
