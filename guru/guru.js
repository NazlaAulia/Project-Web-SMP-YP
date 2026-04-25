const namaGuruEl = document.getElementById("namaGuru");
const avatarGuruEl = document.getElementById("avatarGuru");
const namaGuruDashboardEl = document.getElementById("namaGuruDashboard");
const bannerGuru = document.getElementById("bannerGuru");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

function tampilkanGuru(guru) {
    const namaFix = guru.nama || "Bapak/Ibu Guru";
    const huruf = namaFix.charAt(0).toUpperCase();

    if (namaGuruEl) namaGuruEl.textContent = namaFix;
    if (namaGuruDashboardEl) namaGuruDashboardEl.textContent = namaFix;

    if (avatarGuruEl) {
        if (guru.foto_profil) {
            avatarGuruEl.innerHTML = `<img src="${guru.foto_profil}" alt="Foto ${namaFix}">`;
        } else {
            avatarGuruEl.textContent = huruf;
        }
    }
}

if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
} else {
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data guru dashboard:", result);

            if (result.status === "success") {
                tampilkanGuru(result.data);
            } else {
                alert(result.message);
                localStorage.clear();
                window.location.href = "../login.html";
            }
        })
        .catch(err => {
            console.error(err);
            alert("Gagal load data guru.");
        });
}

if (bannerGuru) {
    bannerGuru.addEventListener("click", function () {
        bannerGuru.classList.remove("banner-active");
        void bannerGuru.offsetWidth;
        bannerGuru.classList.add("banner-active");
    });
}

const animatedCards = document.querySelectorAll(".click-animate");

animatedCards.forEach((card) => {
    card.addEventListener("click", function () {
        card.classList.remove("card-active");
        void card.offsetWidth;
        card.classList.add("card-active");
    });
});

/* =========================
   DASHBOARD DATABASE
   tempelan baru cukup 1 kali
========================= */

const courseGrid = document.getElementById("courseGrid");
const dashboardPersenHadir = document.getElementById("dashboardPersenHadir");
const dashboardKelasTerisi = document.getElementById("dashboardKelasTerisi");
const dashboardRankingList = document.getElementById("dashboardRankingList");
const dashboardSearchInput = document.getElementById("dashboardSearchInput");

let semuaMapelDashboard = [];
const iconMapel = {
    "BIN": "bi-book",
    "B. JAWA": "bi-journal-text",
    "PKN": "bi-shield-check",
    "INFOR": "bi-cpu",
    "MAT": "bi-calculator",
    "BIG": "bi-translate",
    "IPA": "bi-flask",
    "IPS": "bi-globe-asia-australia",
    "BK": "bi-person-heart",
    "INFO/BK": "bi-pc-display",
    "PAI/BHQ": "bi-moon-stars",
    "PJOK": "bi-dribbble"
};

const warnaMapel = [
    "#e7f0f0",
    "#fef4e6",
    "#eaf7ec",
    "#eef2ff",
    "#fff3e6",
    "#e8f7ff",
    "#edf7ed",
    "#fff7e6",
    "#f0ecff",
    "#e7f0f0",
    "#fff0f0",
    "#eef8f5"
];

function renderMapelDashboard(mapelList) {
    if (!courseGrid) return;

    if (!mapelList || mapelList.length === 0) {
        courseGrid.innerHTML = `
            <div class="course-card">
                <div class="course-info">
                    <h4>Belum Ada Mapel</h4>
                    <p>Data mata pelajaran belum tersedia.</p>
                </div>
            </div>
        `;
        return;
    }

    courseGrid.innerHTML = mapelList.map((mapel, index) => {
        const icon = iconMapel[mapel.nama_mapel] || "bi-book";
        const warna = warnaMapel[index % warnaMapel.length];

        return `
            <div class="course-card">
                <div class="course-thumb" style="background: ${warna};">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="course-info">
                    <h4>${mapel.nama_mapel}</h4>
                    <p>${mapel.deskripsi}</p>
                </div>
            </div>
        `;
    }).join("");
}

function setupSearchDashboard() {
    if (!dashboardSearchInput) return;

    dashboardSearchInput.addEventListener("input", function () {
        const keyword = this.value.trim().toLowerCase();

        const hasilFilter = semuaMapelDashboard.filter(mapel => {
            return `
                ${mapel.nama_mapel}
                ${mapel.deskripsi}
            `.toLowerCase().includes(keyword);
        });

        renderMapelDashboard(hasilFilter);
    });
}

function renderKehadiranDashboard(kehadiran) {
    if (!kehadiran) return;

    if (dashboardPersenHadir) {
        dashboardPersenHadir.textContent = `${kehadiran.persen_hadir}%`;
    }

    if (dashboardKelasTerisi) {
        dashboardKelasTerisi.textContent = `${kehadiran.kelas_terisi}/${kehadiran.total_kelas} Kelas`;
    }
}

function renderRankingDashboard(peringkatList) {
    if (!dashboardRankingList) return;

    if (!peringkatList || peringkatList.length === 0) {
        dashboardRankingList.innerHTML = `
            <div class="rank-item">
                <div class="rank-avatar">-</div>
                <div class="rank-info">
                    <span>Belum ada data</span>
                    <small>Data nilai belum tersedia</small>
                </div>
            </div>
        `;
        return;
    }

    dashboardRankingList.innerHTML = peringkatList.map(siswa => {
        return `
            <div class="rank-item">
                <div class="rank-avatar">${siswa.inisial}</div>
                <div class="rank-info">
                    <span>${siswa.nama}</span>
                    <small>Kelas ${siswa.kelas} • Rata-rata: ${siswa.rata_rata}</small>
                </div>
            </div>
        `;
    }).join("");
}

function loadDashboardDatabase() {
    if (!idGuruLogin || roleIdLogin !== "2") return;

    fetch(`get_dashboard_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data dashboard database:", result);

            if (result.status === "success") {
                semuaMapelDashboard = result.mapel || [];

                renderMapelDashboard(semuaMapelDashboard);
                renderKehadiranDashboard(result.kehadiran);
                renderRankingDashboard(result.peringkat);
                setupSearchDashboard();
            } else {
                console.warn(result.message);
            }
        })
        .catch(err => {
            console.error("Gagal load dashboard database:", err);
        });
}

loadDashboardDatabase();