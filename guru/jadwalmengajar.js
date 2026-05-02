let jadwalData = [];

const scheduleContainer = document.getElementById("scheduleContainer");
const filterHari = document.getElementById("filterHari");
const filterKelas = document.getElementById("filterKelas");

document.addEventListener("DOMContentLoaded", async () => {
    const roleId = localStorage.getItem("role_id");
    const idGuru = localStorage.getItem("id_guru");

    if (roleId !== "2") {
        alert("Akses ditolak. Halaman ini khusus guru.");
        window.location.href = "../login.html";
        return;
    }

    if (!idGuru) {
        alert("ID guru tidak ditemukan. Silakan login ulang.");
        window.location.href = "../login.html";
        return;
    }

    await loadJadwalGuru(idGuru);
});

async function loadJadwalGuru(idGuru) {
    try {
        showLoading();

        const response = await fetch(`get_jadwal_guru.php?id_guru=${encodeURIComponent(idGuru)}`);
        const result = await response.json();

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal mengambil jadwal guru.");
        }

        jadwalData = result.data || [];

        updateHeaderGuru(result.guru);
        initFilter();
        renderJadwal(jadwalData);

    } catch (error) {
        console.error(error);
        scheduleContainer.innerHTML = `
            <div class="empty-card">
                <i class="bi bi-exclamation-triangle"></i>
                <p>${error.message || "Terjadi kesalahan saat memuat jadwal."}</p>
            </div>
        `;
    }
}

function showLoading() {
    scheduleContainer.innerHTML = `
        <div class="empty-card">
            <i class="bi bi-hourglass-split"></i>
            <p>Sedang memuat jadwal mengajar...</p>
        </div>
    `;
}

function updateHeaderGuru(guru) {
    const headerTitle = document.querySelector(".header-title p");

    if (headerTitle && guru && guru.nama) {
        headerTitle.textContent = `Jadwal mengajar ${guru.nama}`;
    }
}

function initFilter() {
    if (!filterHari || !filterKelas) return;

    filterHari.innerHTML = `<option value="Semua">Semua Hari</option>`;
    filterKelas.innerHTML = `<option value="Semua">Semua Kelas</option>`;

    const hariUrutan = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const hariSet = new Set();
    const kelasSet = new Set();

    jadwalData.forEach(item => {
        if (item.hari) hariSet.add(item.hari);
        if (item.kelas) kelasSet.add(item.kelas);
    });

    hariUrutan.forEach(hari => {
        if (hariSet.has(hari)) {
            const option = document.createElement("option");
            option.value = hari;
            option.textContent = hari;
            filterHari.appendChild(option);
        }
    });

    [...kelasSet].sort().forEach(kelas => {
        const option = document.createElement("option");
        option.value = kelas;
        option.textContent = `Kelas ${kelas}`;
        filterKelas.appendChild(option);
    });
}

function renderJadwal(data) {
    scheduleContainer.innerHTML = "";

    if (!data || data.length === 0) {
        scheduleContainer.innerHTML = `
            <div class="empty-card">
                <i class="bi bi-calendar-x"></i>
                <p>Belum ada jadwal mengajar untuk guru ini.</p>
            </div>
        `;
        return;
    }

    data.forEach(item => {
        const card = document.createElement("div");
        card.className = "card-schedule click-animate";

        const jpText = item.jp_mulai && item.jp_selesai
            ? `JP ${item.jp_mulai}-${item.jp_selesai} | ${item.jumlah_jp} JP`
            : `${item.jumlah_jp || 1} JP`;

        card.innerHTML = `
            <div class="schedule-top">
                <span class="day-badge">${escapeHtml(item.hari)}</span>
                <span class="time-badge">${escapeHtml(item.jam)}</span>
            </div>

            <h3>${escapeHtml(item.mapel)}</h3>

            <div class="info-row">
                <i class="bi bi-person-badge"></i>
                <span>${escapeHtml(item.guru)}</span>
            </div>

            <div class="info-row">
                <i class="bi bi-building"></i>
                <span>Kelas ${escapeHtml(item.kelas)}</span>
            </div>

            <div class="info-row">
                <i class="bi bi-clock-history"></i>
                <span>${escapeHtml(jpText)}</span>
            </div>

            <div class="schedule-actions">
                <button type="button" onclick="ajukanGantiJadwal(${item.id_jadwal})">
                    <i class="bi bi-arrow-repeat"></i>
                    Ajukan Ganti
                </button>
            </div>
        `;

        scheduleContainer.appendChild(card);
    });

    setupClickAnimation();
}

function filterData() {
    const hari = filterHari.value;
    const kelas = filterKelas.value;

    const filtered = jadwalData.filter(item => {
        const cocokHari = hari === "Semua" || item.hari === hari;
        const cocokKelas = kelas === "Semua" || item.kelas === kelas;

        return cocokHari && cocokKelas;
    });

    renderJadwal(filtered);
}

function ajukanGantiJadwal(idJadwal) {
    if (!idJadwal) {
        alert("ID jadwal tidak valid.");
        return;
    }

    window.location.href = `requestjadwal.html?id_jadwal=${encodeURIComponent(idJadwal)}`;
}

function setupClickAnimation() {
    const animatedCards = document.querySelectorAll(".click-animate");

    animatedCards.forEach(card => {
        card.addEventListener("click", function (e) {
            if (e.target.closest("button")) return;

            card.classList.remove("card-active");
            void card.offsetWidth;
            card.classList.add("card-active");
        });
    });
}

function escapeHtml(value) {
    if (value === null || value === undefined) return "-";

    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}