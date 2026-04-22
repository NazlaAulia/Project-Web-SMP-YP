function $(id) {
    return document.getElementById(id);
}

// ======================
// DATA DUMMY
// ======================
const jadwalData = [
    {
        id_jadwal: 1,
        id_guru: 101,
        nama_mapel: "Matematika",
        nama_kelas: "7A",
        hari: "Senin",
        jam: "1-2",
        ruang: "R-01"
    },
    {
        id_jadwal: 2,
        id_guru: 101,
        nama_mapel: "Matematika",
        nama_kelas: "7B",
        hari: "Selasa",
        jam: "3-4",
        ruang: "R-02"
    },
    {
        id_jadwal: 3,
        id_guru: 101,
        nama_mapel: "Matematika",
        nama_kelas: "8A",
        hari: "Rabu",
        jam: "5-6",
        ruang: "R-03"
    },
    {
        id_jadwal: 4,
        id_guru: 101,
        nama_mapel: "Matematika",
        nama_kelas: "8B",
        hari: "Kamis",
        jam: "7-8",
        ruang: "R-04"
    },
    {
        id_jadwal: 5,
        id_guru: 101,
        nama_mapel: "Matematika",
        nama_kelas: "9A",
        hari: "Jumat",
        jam: "9-10",
        ruang: "R-05"
    }
];

let requestData = [];

// ======================
// SAAT HALAMAN DIBUKA
// ======================
document.addEventListener("DOMContentLoaded", function () {
    console.log("jadwal.js aktif");

    initFilterOptions();
    renderSchedule(jadwalData);
    renderRequest(requestData);
    populateScheduleOptions();

    const form = $("requestForm");
    if (form) {
        form.addEventListener("submit", submitRequestForm);
    }
});

// ======================
// MODAL
// ======================
function openRequestModal() {
    console.log("modal dibuka");
    const modal = $("requestModal");
    if (modal) {
        modal.style.display = "flex";
    }
}

function closeRequestModal() {
    const modal = $("requestModal");
    if (modal) {
        modal.style.display = "none";
    }
}

window.openRequestModal = openRequestModal;
window.closeRequestModal = closeRequestModal;

// klik area luar modal = tutup
window.addEventListener("click", function (e) {
    const modal = $("requestModal");
    if (e.target === modal) {
        closeRequestModal();
    }
});

// ======================
// FILTER
// ======================
function initFilterOptions() {
    const filterHari = $("filterHari");
    const filterKelas = $("filterKelas");

    if (!filterHari || !filterKelas) return;

    const daftarHari = ["Senin", "Selasa", "Rabu", "Kamis", "Jumat"];
    const kelasUnik = [...new Set(jadwalData.map(item => item.nama_kelas))];

    filterHari.innerHTML = `<option value="Semua">Semua Hari</option>`;
    filterKelas.innerHTML = `<option value="Semua">Semua Kelas</option>`;

    daftarHari.forEach(hari => {
        const option = document.createElement("option");
        option.value = hari;
        option.textContent = hari;
        filterHari.appendChild(option);
    });

    kelasUnik.forEach(kelas => {
        const option = document.createElement("option");
        option.value = kelas;
        option.textContent = kelas;
        filterKelas.appendChild(option);
    });
}
function filterData() {
    const selectedHari = $("filterHari").value;
    const selectedKelas = $("filterKelas").value;

    let filtered = [...jadwalData];

    if (selectedHari !== "Semua") {
        filtered = filtered.filter(item => item.hari === selectedHari);
    }

    if (selectedKelas !== "Semua") {
        filtered = filtered.filter(item => item.nama_kelas === selectedKelas);
    }

    renderSchedule(filtered);
}

window.filterData = filterData;

// ======================
// RENDER JADWAL
// ======================
function renderSchedule(data) {
    const container = $("scheduleContainer");
    if (!container) return;

    container.innerHTML = "";

    if (data.length === 0) {
        container.innerHTML = `<div style="background:#fff;padding:20px;border-radius:16px;">Tidak ada jadwal</div>`;
        return;
    }

    data.forEach(item => {
        container.innerHTML += `
            <div class="card-schedule">
                <span class="day-badge">${item.hari}</span>
                <h3>${item.nama_mapel}</h3>
                <p>Kelas ${item.nama_kelas}</p>
                <div class="info-row">
                    <i class="bi bi-clock"></i>
                    <span>Jam ${item.jam}</span>
                </div>
                <div class="info-row">
                    <i class="bi bi-door-open"></i>
                    <span>${item.ruang}</span>
                </div>
            </div>
        `;
    });
}

// ======================
// ISI DROPDOWN JADWAL
// ======================
function populateScheduleOptions() {
    const select = $("id_jadwal");
    if (!select) return;

    select.innerHTML = `<option value="">-- Pilih Jadwal --</option>`;

    jadwalData.forEach(item => {
        const option = document.createElement("option");
        option.value = item.id_jadwal;
        option.textContent = `${item.nama_mapel} - ${item.nama_kelas} | ${item.hari}, Jam ${item.jam}`;
        select.appendChild(option);
    });
}

// ======================
// AUTOFILL JADWAL LAMA
// ======================
function autoFillOldSchedule() {
    const selectedId = $("id_jadwal").value;
    const selectedJadwal = jadwalData.find(j => j.id_jadwal == selectedId);

    if (!selectedJadwal) return;

    $("hari_lama_view").value = selectedJadwal.hari;
    $("jam_lama_view").value = selectedJadwal.jam;
}

window.autoFillOldSchedule = autoFillOldSchedule;

// ======================
// JENIS PENGAJUAN
// ======================
function handleJenisPengajuan() {
    const tipe = $("tipe_pengajuan").value;
    const wrap = $("guruTujuanWrap");

    if (!wrap) return;
    wrap.style.display = tipe === "tukar" ? "block" : "none";
}

window.handleJenisPengajuan = handleJenisPengajuan;

// ======================
// VALIDASI SLOT
// ======================
function cekKetersediaanSlot() {
    const idJadwal = $("id_jadwal").value;
    const hariBaru = $("hari_baru").value;
    const jamBaru = $("jam_baru").value;
    const info = $("validationInfo");

    if (!idJadwal || !hariBaru || !jamBaru) {
        info.innerHTML = "";
        return;
    }

    const jadwalDipilih = jadwalData.find(j => j.id_jadwal == idJadwal);
    if (!jadwalDipilih) return;

    const bentrok = jadwalData.some(j =>
        j.id_jadwal != idJadwal &&
        (
            (j.hari === hariBaru && j.jam === jamBaru && j.nama_kelas === jadwalDipilih.nama_kelas)
        )
    );

    if (bentrok) {
        info.innerHTML = `<div class="alert-error">Slot bentrok, pilih jam lain.</div>`;
    } else {
        info.innerHTML = `<div class="alert-success">Slot tersedia.</div>`;
    }
}

window.cekKetersediaanSlot = cekKetersediaanSlot;

// ======================
// SUBMIT FORM
// ======================
function submitRequestForm(e) {
    e.preventDefault();

    const idJadwal = $("id_jadwal").value;
    const hariBaru = $("hari_baru").value;
    const jamBaru = $("jam_baru").value;
    const alasan = $("alasan").value.trim();

    if (!idJadwal || !hariBaru || !jamBaru || !alasan) {
        alert("Lengkapi dulu formnya");
        return;
    }

    const jadwalDipilih = jadwalData.find(j => j.id_jadwal == idJadwal);
    if (!jadwalDipilih) return;

    requestData.unshift({
        id_request: Date.now(),
        nama_mapel: jadwalDipilih.nama_mapel,
        nama_kelas: jadwalDipilih.nama_kelas,
        hari_lama: jadwalDipilih.hari,
        jam_lama: jadwalDipilih.jam,
        hari_baru: hariBaru,
        jam_baru: jamBaru,
        alasan: alasan,
        status: "menunggu",
        tanggal_request: new Date().toLocaleString("id-ID")
    });

    renderRequest(requestData);
    closeRequestModal();
    $("requestForm").reset();
    $("validationInfo").innerHTML = "";
}

function renderRequest(data) {
    const container = $("requestContainer");
    if (!container) return;

    container.innerHTML = "";

    if (data.length === 0) {
        container.innerHTML = `<div style="background:#fff;padding:20px;border-radius:16px;">Belum ada riwayat pengajuan.</div>`;
        return;
    }

    data.forEach(item => {
        container.innerHTML += `
            <div class="card-schedule" style="margin-bottom:15px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <span class="status-badge status-menunggu">${item.status}</span>
                    <small>${item.tanggal_request}</small>
                </div>
                <div style="font-weight:700;">${item.nama_mapel} (${item.nama_kelas})</div>
                <div style="margin:10px 0;">
                    <span style="text-decoration:line-through;color:#999;">${item.hari_lama} (${item.jam_lama})</span>
                    <i class="bi bi-arrow-right" style="margin:0 8px;"></i>
                    <span style="font-weight:700;">${item.hari_baru} (${item.jam_baru})</span>
                </div>
                <div class="info-row">
                    <i class="bi bi-chat-left-dots"></i>
                    <span>"${item.alasan}"</span>
                </div>
            </div>
        `;
    });
}