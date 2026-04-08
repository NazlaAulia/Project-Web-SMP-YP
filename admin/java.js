document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelector('.nav-item.active').classList.remove('active');
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
            const speed = 100; // Semakin besar semakin lambat
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
    
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    monthYear.innerText = `${months[date.getMonth()]} ${date.getFullYear()}`;

    const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

    // Isi hari kosong
    for (let i = 0; i < firstDay; i++) {
        const emptyDiv = document.createElement("div");
        calendarBody.appendChild(emptyDiv);
    }

    // Isi tanggal
    for (let i = 1; i <= daysInMonth; i++) {
        const dayDiv = document.createElement("div");
        dayDiv.innerText = i;
        if (i === date.getDate()) dayDiv.classList.add("today");
        calendarBody.appendChild(dayDiv);
    }
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