document.addEventListener('DOMContentLoaded', () => {
    // --- LOGIKA ASLI ANDA: ANIMASI CARDS ---
    const cards = document.querySelectorAll('.card, .glass-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease ' + (index * 0.1) + 's';

        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });

    // --- LOGIKA ASLI ANDA: NAVIGASI ---
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });

    const namaGuruEl = document.getElementById("namaGuru");
    const avatarGuruEl = document.getElementById("avatarGuru");
    const welcomeGuruEl = document.getElementById("welcomeGuru");

    // --- LOGIKA ASLI ANDA: FUNGSI TAMPILKAN ---
    function tampilkanGuru(nama) {
        const namaFix = nama || "Bapak/Ibu Guru";
        const huruf = namaFix.charAt(0).toUpperCase();

        if (namaGuruEl) namaGuruEl.textContent = namaFix;
        if (avatarGuruEl) avatarGuruEl.textContent = huruf;
        if (welcomeGuruEl) welcomeGuruEl.textContent = `Halo, ${namaFix}! 🌟`;
    }

    // --- LOGIKA TAMBAHAN: UPDATE PROFIL (Agar tidak berantakan) ---
    const btnSimpan = document.getElementById("btnSimpan");
    const editNamaInput = document.getElementById("editNamaGuru");

    if (btnSimpan) {
        btnSimpan.addEventListener('click', async () => {
            const namaBaru = editNamaInput.value.trim();
            const idGuru = localStorage.getItem("id_guru");

            if (!namaBaru) {
                alert("Masukkan nama terlebih dahulu!");
                return;
            }

            try {
                const response = await fetch('update_guru.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_guru=${encodeURIComponent(idGuru)}&nama=${encodeURIComponent(namaBaru)}`
                });
                
                const result = await response.json();
                if (result.status === "success") {
                    alert("Profil berhasil diperbarui!");
                    tampilkanGuru(namaBaru); // Update Dashboard & Header seketika
                } else {
                    alert("Gagal: " + result.message);
                }
            } catch (err) {
                console.error("Error update:", err);
            }
        });
    }

    // --- LOGIKA ASLI ANDA: CEK SERVER ---
    const isLiveServer =
        location.hostname === "127.0.0.1" ||
        location.hostname === "localhost" ||
        location.port === "5500";

    if (isLiveServer) {
        tampilkanGuru("Bapak/Ibu Guru");
        console.log("Mode Live Server aktif.");
        // Saya tidak menghapus return agar logika asli Anda tetap terjaga
        return; 
    }

    const idGuru = localStorage.getItem("id_guru");
    if (!idGuru) {
        window.location.replace("../login.html");
        return;
    }

    // --- LOGIKA ASLI ANDA: LOAD DATA DARI DATABASE ---
    async function loadGuru() {
        try {
            const res = await fetch(`get_guru.php?id_guru=${encodeURIComponent(idGuru)}`);
            const text = await res.text();
            console.log("RESPON GURU:", text);

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error("Response bukan JSON: " + text);
            }

            if (result.status !== "success") {
                throw new Error(result.message || "Gagal memuat data guru.");
            }

            const g = result.data;
            tampilkanGuru(g.nama || "Bapak/Ibu Guru");
        } catch (err) {
            console.error("Error load guru:", err);
            alert(err.message || "Gagal load data guru");
        }
    }

    // 1. Logika Navigasi Tombol
const btnMulaiMengajar = document.querySelector('.btn-primary, .btn-mulai'); // Sesuaikan class tombol di banner
const kartuMatpel = document.querySelectorAll('.card-matpel'); // Sesuaikan class blok matpel
const widgetKehadiran = document.querySelector('.kehadiran-stats'); // Sesuaikan class widget absen

// Arahkan "Mulai Mengajar" ke Jadwal
if (btnMulaiMengajar) {
    btnMulaiMengajar.addEventListener('click', () => {
        window.location.href = 'jadwal.html'; 
    });
}

// Arahkan Klik Matpel ke halaman Nilai
kartuMatpel.forEach(card => {
    card.addEventListener('click', () => {
        window.location.href = 'nilai.html';
    });
});

// 2. Animasi Hover/Gerak untuk "Blok-Blok Putih" (Rectangle)
// Kita bisa menambah efek transisi smooth saat kursor mendekat
const allWhiteCards = document.querySelectorAll('.card, .stat-card, .schedule-item');

allWhiteCards.forEach(card => {
    card.style.transition = "transform 0.3s ease, box-shadow 0.3s ease";
    card.style.cursor = "pointer";

    card.addEventListener('mouseenter', () => {
        card.style.transform = "translateY(-5px)"; // Bergerak sedikit ke atas
        card.style.boxShadow = "0 10px 20px rgba(0,0,0,0.1)";
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = "translateY(0)";
        card.style.boxShadow = "none";
    });
});
    loadGuru();
});