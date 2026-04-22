let jadwalData = [];
let requestData = [];
const idGuruLogin = 3; // ganti sesuai session login

document.addEventListener("DOMContentLoaded", async () => {
    await loadJadwal();
    await loadRequest();
});

async function loadJadwal() {
    const res = await fetch(`get_jadwal_guru.php?id_guru=${idGuruLogin}`);
    jadwalData = await res.json();

    renderJadwal(jadwalData);
    loadFilterOptions();
    loadJadwalOptionsToModal();
}

async function loadRequest() {
    const res = await fetch(`get_request_jadwal.php?id_guru=${idGuruLogin}`);
    requestData = await res.json();
    renderRequest(requestData);
}

function renderJadwal(data) {
    const container = document.getElementById("scheduleContainer");
    container.innerHTML = "";

    if (data.length === 0) {
        container.innerHTML = `<p>Tidak ada jadwal.</p>`;
        return;
    }

    data.forEach(item => {
        container.innerHTML += `
            <div class="card-schedule">
                <span class="day-badge">${item.hari}</span>
                <span class="time-text">${item.jam_mulai} - ${item.jam_selesai}</span>
                <span class="subject-text">${item.nama_mapel}</span>

                <div class="info-row">
                    <i class="bi bi-building"></i>
                    <span>${item.nama_kelas}</span>
                </div>
                <div class="info-row">
                    <i class="bi bi-geo-alt"></i>
                    <span>${item.ruang}</span>
                </div>
            </div>
        `;
    });
}

function renderRequest(data) {
    const container = document.getElementById("requestContainer");
    container.innerHTML = "";

    if (data.length === 0) {
        container.innerHTML = `<p>Belum ada pengajuan.</p>`;
        return;
    }

    data.forEach(item => {
        container.innerHTML += `
            <div class="card-schedule" style="margin-bottom: 16px;">
                <span class="day-badge">${item.status}</span>
                <span class="time-text">${item.hari_lama} (${item.jam_lama}) → ${item.hari_baru} (${item.jam_baru})</span>
                <span class="subject-text">${item.nama_mapel} - ${item.nama_kelas}</span>

                <div class="info-row">
                    <i class="bi bi-chat-left-text"></i>
                    <span>${item.alasan}</span>
                </div>
                <div class="info-row">
                    <i class="bi bi-calendar-event"></i>
                    <span>${item.tanggal_request}</span>
                </div>
            </div>
        `;
    });
}

function loadFilterOptions() {
    const hariSet = [...new Set(jadwalData.map(j => j.hari))];
    const kelasSet = [...new Set(jadwalData.map(j => j.nama_kelas))];

    const filterHari = document.getElementById("filterHari");
    const filterKelas = document.getElementById("filterKelas");

    hariSet.forEach(hari => {
        filterHari.innerHTML += `<option value="${hari}">${hari}</option>`;
    });

    kelasSet.forEach(kelas => {
        filterKelas.innerHTML += `<option value="${kelas}">${kelas}</option>`;
    });
}

function filterData() {
    const hari = document.getElementById("filterHari").value;
    const kelas = document.getElementById("filterKelas").value;

    let filtered = jadwalData.filter(item => {
        return (hari === "Semua" || item.hari === hari) &&
               (kelas === "Semua" || item.nama_kelas === kelas);
    });

    renderJadwal(filtered);
}

function loadJadwalOptionsToModal() {
    const select = document.getElementById("id_jadwal");
    select.innerHTML = `<option value="">-- Pilih Jadwal --</option>`;

    jadwalData.forEach(item => {
        select.innerHTML += `
            <option value="${item.id_jadwal}">
                ${item.hari} | ${item.jam_mulai}-${item.jam_selesai} | ${item.nama_mapel} | ${item.nama_kelas}
            </option>
        `;
    });
}

function openRequestModal() {
    document.getElementById("requestModal").style.display = "flex";
}

function closeRequestModal() {
    document.getElementById("requestModal").style.display = "none";
}

document.getElementById("requestForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append("id_guru", idGuruLogin);

    const res = await fetch("simpan_request_jadwal.php", {
        method: "POST",
        body: formData
    });

    const result = await res.json();
    alert(result.message);

    if (result.success) {
        closeRequestModal();
        this.reset();
        loadRequest();
    }
});