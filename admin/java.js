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

let currDate = new Date();

function renderCalendar() {
    const monthYear = document.getElementById("month-year");
    const calendarBody = document.getElementById("calendar-body");
    calendarBody.innerHTML = "";

    const month = currDate.getMonth();
    const year = currDate.getFullYear();

    const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    monthYear.innerText = `${months[month]}  ${year}`;

    // Cari hari pertama bulan ini (0 = Minggu, 1 = Senin, dst)
    let firstDay = new Date(year, month, 1).getDay();
    // Sesuaikan jika kalender kamu mulai dari Senin (M T W T F S S)
    let shiftDay = firstDay === 0 ? 6 : firstDay - 1;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const prevLastDay = new Date(year, month, 0).getDate();

    // 1. Tampilkan sisa hari bulan lalu
    for (let i = shiftDay; i > 0; i--) {
        const div = document.createElement("div");
        div.innerText = prevLastDay - i + 1;
        div.classList.add("other-month");
        calendarBody.appendChild(div);
    }

    // 2. Tampilkan hari bulan sekarang
    for (let i = 1; i <= daysInMonth; i++) {
        const div = document.createElement("div");
        div.innerText = i;
        if (i === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear()) {
            div.classList.add("today");
        }
        calendarBody.appendChild(div);
    }
}