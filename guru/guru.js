document.addEventListener('DOMContentLoaded', () => {
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

    function tampilkanGuru(nama) {
        const namaFix = nama || "Bapak/Ibu Guru";
        const huruf = namaFix.charAt(0).toUpperCase();

        if (namaGuruEl) namaGuruEl.textContent = namaFix;
        if (avatarGuruEl) avatarGuruEl.textContent = huruf;
        if (welcomeGuruEl) welcomeGuruEl.textContent = `Halo, ${namaFix}! 🌟`;
    }

    const isLiveServer =
        location.hostname === "127.0.0.1" ||
        location.hostname === "localhost" ||
        location.port === "5500";

    if (isLiveServer) {
        // MODE EDIT / DESAIN
        tampilkanGuru("Bapak/Ibu Guru");
        console.log("Mode Live Server aktif, redirect login dimatikan.");
        return;
    }

    const idGuru = localStorage.getItem("id_guru");

    if (!idGuru) {
        window.location.replace("../login.html");
        return;
    }

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

    loadGuru();
});