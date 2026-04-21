document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        const activeItem = document.querySelector('.nav-item.active');
        if (activeItem) activeItem.classList.remove('active');
        this.classList.add('active');
    });
});

document.addEventListener("DOMContentLoaded", () => {
    // 1. KALENDER
    loadCalendar();

    // 2. ADMIN LOGIN / LIVE SERVER MODE
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
        isiCounterDummy();
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

    // 3. LOAD DATA DASHBOARD DARI DATABASE
    loadDashboardStats();
});

// =========================
// COUNTER
// =========================
function animateCounter(counter, target) {
    let current = 0;
    const speed = 100;
    const increment = Math.max(1, Math.ceil(target / speed));

    function update() {
        current += increment;

        if (current < target) {
            counter.innerText = current;
            requestAnimationFrame(update);
        } else {
            counter.innerText = target;
        }
    }

    counter.innerText = "0";
    update();
}

function isiCounterDummy() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target')) || 0;
        animateCounter(counter, target);
    });
}

async function loadDashboardStats() {
    try {
        const response = await fetch("dashboard_data.php");
        const raw = await response.text();

        let result;
        try {
            result = JSON.parse(raw);
        } catch (e) {
            console.error("Response dashboard bukan JSON:", raw);
            return;
        }

        if (result.status !== "success") {
            console.error("Gagal load dashboard:", result.message);
            return;
        }

        const counters = document.querySelectorAll('.counter');

        if (counters[0]) {
            counters[0].setAttribute('data-target', result.total_guru || 0);
            animateCounter(counters[0], result.total_guru || 0);
        }

        if (counters[1]) {
            counters[1].setAttribute('data-target', result.total_siswa || 0);
            animateCounter(counters[1], result.total_siswa || 0);
        }

        if (counters[2]) {
            counters[2].setAttribute('data-target', result.total_kelas || 0);
            animateCounter(counters[2], result.total_kelas || 0);
        }

        if (counters[3]) {
            counters[3].setAttribute('data-target', result.total_pending || 0);
            animateCounter(counters[3], result.total_pending || 0);
        }

    } catch (error) {
        console.error("Gagal mengambil data dashboard:", error);
    }
}

// =========================
// KALENDER
// =========================
function loadCalendar() {
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
}

// =========================
// SIDEBAR MOBILE
// =========================
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