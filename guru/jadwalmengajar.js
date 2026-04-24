const jadwalData = [
    {
        id_jadwal: 1,
        guru: "Guru 1",
        kelas: "7A",
        mapel: "BIN",
        hari: "Senin",
        jam: "07:00-08:00"
    },
    {
        id_jadwal: 2,
        guru: "Guru 7",
        kelas: "7A",
        mapel: "MAT",
        hari: "Senin",
        jam: "08:00-09:00"
    },
    {
        id_jadwal: 3,
        guru: "Guru 6",
        kelas: "7A",
        mapel: "INFOR",
        hari: "Selasa",
        jam: "07:00-08:00"
    },
    {
        id_jadwal: 4,
        guru: "Guru 4",
        kelas: "7A",
        mapel: "PKN",
        hari: "Rabu",
        jam: "09:00-10:00"
    },
    {
        id_jadwal: 5,
        guru: "Guru 2",
        kelas: "7B",
        mapel: "B. JAWA",
        hari: "Senin",
        jam: "07:00-08:00"
    },
    {
        id_jadwal: 6,
        guru: "Guru 10",
        kelas: "7B",
        mapel: "BIG",
        hari: "Senin",
        jam: "08:00-09:00"
    },
    {
        id_jadwal: 7,
        guru: "Guru 11",
        kelas: "7B",
        mapel: "IPA",
        hari: "Selasa",
        jam: "09:00-10:00"
    },
    {
        id_jadwal: 8,
        guru: "Belum Ditentukan",
        kelas: "7B",
        mapel: "PJOK",
        hari: "Kamis",
        jam: "07:00-08:00"
    },
    {
        id_jadwal: 9,
        guru: "Guru 1",
        kelas: "7C",
        mapel: "BIN",
        hari: "Senin",
        jam: "09:00-10:00"
    },
    {
        id_jadwal: 10,
        guru: "Guru 14",
        kelas: "7C",
        mapel: "IPS",
        hari: "Selasa",
        jam: "08:00-09:00"
    },
    {
        id_jadwal: 11,
        guru: "Belum Ditentukan",
        kelas: "7C",
        mapel: "PAI/BHQ",
        hari: "Rabu",
        jam: "07:00-08:00"
    },
    {
        id_jadwal: 12,
        guru: "Belum Ditentukan",
        kelas: "7C",
        mapel: "BK",
        hari: "Jumat",
        jam: "08:00-09:00"
    }
];

let requestData = [];

const scheduleContainer = document.getElementById("scheduleContainer");
const requestContainer = document.getElementById("requestContainer");
const filterHari = document.getElementById("filterHari");
const filterKelas = document.getElementById("filterKelas");
const requestModal = document.getElementById("requestModal");
const jadwalDipilih = document.getElementById("jadwalDipilih");
const hariBaru = document.getElementById("hariBaru");
const jamBaru = document.getElementById("jamBaru");
const alasanRequest = document.getElementById("alasanRequest");
const aiSuggestionText = document.getElementById("aiSuggestionText");
const requestForm = document.getElementById("requestForm");

document.addEventListener("DOMContentLoaded", () => {
    initFilter();
    renderJadwal(jadwalData);
    renderSelectJadwal();
    renderRequest();
});

function initFilter() {
    const hariList = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"];
    const kelasList = ["7A", "7B", "7C", "8A", "8B", "8C", "9A", "9B", "9C"];

    hariList.forEach(hari => {
        const option = document.createElement("option");
        option.value = hari;
        option.textContent = hari;
        filterHari.appendChild(option);
    });

    kelasList.forEach(kelas => {
        const option = document.createElement("option");
        option.value = kelas;
        option.textContent = `Kelas ${kelas}`;
        filterKelas.appendChild(option);
    });
}

function renderJadwal(data) {
    scheduleContainer.innerHTML = "";

    if (data.length === 0) {
        scheduleContainer.innerHTML = `
            <div class="empty-card">
                <i class="bi bi-calendar-x"></i>
                <p>Tidak ada jadwal yang sesuai filter.</p>
            </div>
        `;
        return;
    }

    data.forEach(item => {
        const card = document.createElement("div");
        card.className = "card-schedule click-animate";

        card.innerHTML = `
            <div class="schedule-top">
                <span class="day-badge">${item.hari}</span>
                <span class="time-badge">${item.jam}</span>
            </div>

            <h3>${item.mapel}</h3>

            <div class="info-row">
                <i class="bi bi-person-badge"></i>
                <span>${item.guru}</span>
            </div>

            <div class="info-row">
                <i class="bi bi-building"></i>
                <span>Kelas ${item.kelas}</span>
            </div>

            <div class="schedule-actions">
                <button type="button" onclick="pilihJadwal(${item.id_jadwal})">
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

function renderSelectJadwal() {
    jadwalDipilih.innerHTML = `<option value="">Pilih jadwal</option>`;

    jadwalData.forEach(item => {
        const option = document.createElement("option");
        option.value = item.id_jadwal;
        option.textContent = `${item.hari}, ${item.jam} - ${item.mapel} - Kelas ${item.kelas} - ${item.guru}`;
        jadwalDipilih.appendChild(option);
    });
}

function openRequestModal() {
    requestModal.style.display = "flex";
}

function closeRequestModal() {
    requestModal.style.display = "none";
    requestForm.reset();
    aiSuggestionText.textContent = "Klik tombol generate untuk mendapatkan saran jadwal otomatis.";
}

function pilihJadwal(idJadwal) {
    openRequestModal();
    jadwalDipilih.value = idJadwal;
}

function generateAIJadwal() {
    const jadwalId = Number(jadwalDipilih.value);

    if (!jadwalId) {
        alert("Pilih jadwal terlebih dahulu sebelum generate AI.");
        return;
    }

    const jadwal = jadwalData.find(item => item.id_jadwal === jadwalId);

    const opsiHari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"];
    const opsiJam = ["07:00-08:00", "08:00-09:00", "09:00-10:00", "10:00-11:00", "11:00-12:00"];

    const rekomendasi = opsiHari
        .flatMap(hari => opsiJam.map(jam => ({ hari, jam })))
        .find(slot => {
            const bentrok = jadwalData.some(item =>
                item.kelas === jadwal.kelas &&
                item.hari === slot.hari &&
                item.jam === slot.jam
            );

            return !bentrok && !(slot.hari === jadwal.hari && slot.jam === jadwal.jam);
        });

    if (!rekomendasi) {
        aiSuggestionText.textContent = "AI belum menemukan slot kosong yang cocok.";
        return;
    }

    hariBaru.value = rekomendasi.hari;
    jamBaru.value = rekomendasi.jam;

    aiSuggestionText.textContent =
        `AI menyarankan jadwal baru pada hari ${rekomendasi.hari}, jam ${rekomendasi.jam}, karena slot tersebut tidak bentrok dengan jadwal kelas ${jadwal.kelas}.`;
}

requestForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const jadwalId = Number(jadwalDipilih.value);
    const jadwal = jadwalData.find(item => item.id_jadwal === jadwalId);

    if (!jadwal) {
        alert("Jadwal tidak ditemukan.");
        return;
    }

    const request = {
        id_request: requestData.length + 1,
        jadwalLama: `${jadwal.hari}, ${jadwal.jam}`,
        guru: jadwal.guru,
        kelas: jadwal.kelas,
        mapel: jadwal.mapel,
        hari_baru: hariBaru.value,
        jam_baru: jamBaru.value,
        alasan: alasanRequest.value,
        status: "Menunggu",
        tanggal_request: new Date().toLocaleDateString("id-ID")
    };

    requestData.unshift(request);
    renderRequest();
    closeRequestModal();

    alert("Pengajuan ganti jadwal berhasil dibuat secara UI. Belum tersimpan ke database.");
});

function renderRequest() {
    if (requestData.length === 0) {
        requestContainer.innerHTML = `
            <div class="request-empty">
                <i class="bi bi-inbox"></i>
                <p>Belum ada pengajuan ganti jadwal.</p>
            </div>
        `;
        return;
    }

    requestContainer.innerHTML = requestData.map(item => `
        <div class="request-card click-animate">
            <div class="request-header">
                <div>
                    <h3>${item.mapel} - Kelas ${item.kelas}</h3>
                    <p>${item.guru}</p>
                </div>

                <span class="status-badge status-menunggu">${item.status}</span>
            </div>

            <div class="request-grid">
                <div>
                    <span>Jadwal Lama</span>
                    <strong>${item.jadwalLama}</strong>
                </div>

                <div>
                    <span>Jadwal Baru</span>
                    <strong>${item.hari_baru}, ${item.jam_baru}</strong>
                </div>

                <div>
                    <span>Tanggal Request</span>
                    <strong>${item.tanggal_request}</strong>
                </div>
            </div>

            <div class="request-reason">
                <span>Alasan</span>
                <p>${item.alasan}</p>
            </div>
        </div>
    `).join("");

    setupClickAnimation();
}

function setupClickAnimation() {
    const animatedCards = document.querySelectorAll(".click-animate");

    animatedCards.forEach(card => {
        card.onclick = function () {
            card.classList.remove("card-active");
            void card.offsetWidth;
            card.classList.add("card-active");
        };
    });
}