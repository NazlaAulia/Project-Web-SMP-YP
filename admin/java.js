document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        const activeItem = document.querySelector('.nav-item.active');
        if (activeItem) activeItem.classList.remove('active');
        this.classList.add('active');
    });
});

document.addEventListener("DOMContentLoaded", () => {
    // 1. ANIMASI ANGKA BERGERAK
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute('data-target');
            const count = +counter.innerText;
            const speed = 100;
            const inc = target / speed;

            if (count < target) {
                counter.innerText = Math.ceil(count + inc);
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = target;
            }
        };
        updateCount();
    });

    // 2. LOGIKA KALENDER SEDERHANA
    const date = new Date();
    const monthYear = document.getElementById("month-year");
    const calendarBody = document.getElementById("calendar-body");

    if (monthYear && calendarBody) {
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        monthYear.innerText = `${months[date.getMonth()]} ${date.getFullYear()}`;

        const firstDayRaw = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
        const firstDay = firstDayRaw === 0 ? 6 : firstDayRaw - 1; // mulai Senin
        const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

        calendarBody.innerHTML = "";

        for (let i = 0; i < firstDay; i++) {
            const emptyDiv = document.createElement("div");
            calendarBody.appendChild(emptyDiv);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement("div");
            dayDiv.innerText = i;
            if (i === date.getDate()) dayDiv.classList.add("today");
            calendarBody.appendChild(dayDiv);
        }
    }

    // 3. ADMIN LOGIN / LIVE SERVER MODE
    const namaAdminEl = document.getElementById("namaAdmin");
    const avatarAdminEl = document.getElementById("avatarAdmin");

    function tampilkanAdmin(nama) {
        const namaFix = nama || "Admin";
        const huruf = namaFix.charAt(0).toUpperCase();

        if (namaAdminEl) namaAdminEl.textContent = namaFix;
        if (avatarAdminEl) avatarAdminEl.textContent = huruf;
    }

    const isLiveServer =
        location.hostname === "127.0.0.1" ||
        location.hostname === "localhost" ||
        location.port === "5500";

    if (isLiveServer) {
        // MODE DESAIN
        tampilkanAdmin("Admin Demo");
        console.log("Mode Live Server aktif, redirect login admin dimatikan.");
        return;
    }

    // MODE HOSTING / LOGIN ASLI
    const roleId = localStorage.getItem("role_id");
    const username = localStorage.getItem("username");

    if (roleId !== "1") {
        window.location.replace("login.html");
        return;
    }

    tampilkanAdmin(username || "Admin");
});

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.querySelector(".sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");

if (menuToggle && sidebar && sidebarOverlay) {
    menuToggle.addEventListener("click", function () {
        sidebar.classList.toggle("active");
        sidebarOverlay.classList.toggle("active");
    });

    sidebarOverlay.addEventListener("click", function () {
        sidebar.classList.remove("active");
        sidebarOverlay.classList.remove("active");
    });
}