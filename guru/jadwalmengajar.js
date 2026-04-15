const jadwalData = [
    { hari: "Senin", jam: "08:00 - 09:30", mapel: "Fisika Terapan", kelas: "9A", ruang: "R. Lab 02" },
    { hari: "Senin", jam: "10:00 - 11:30", mapel: "Elektronika Dasar", kelas: "9C", ruang: "R. Teori 01" },
    { hari: "Selasa", jam: "07:30 - 09:00", mapel: "Fisika Terapan", kelas: "9B", ruang: "R. Lab 02" },
    { hari: "Rabu", jam: "09:00 - 10:30", mapel: "Projek STEM", kelas: "9A", ruang: "Bengkel Kreatif" }
];

function renderJadwal(data) {
    const container = document.getElementById('scheduleContainer');
    container.innerHTML = "";

    data.forEach(item => {
        const card = `
            <div class="card-schedule">
                <div class="card-header">
                    <span class="day-badge">${item.hari}</span>
                    <i class="bi bi-three-dots-vertical"></i>
                </div>
                <h2 class="time-text">${item.jam}</h2>
                <p class="subject-text">${item.mapel}</p>
                <div class="info-row">
                    <i class="bi bi-door-open-fill"></i>
                    <span>${item.ruang}</span>
                </div>
                <div class="info-row">
                    <i class="bi bi-people-fill"></i>
                    <span>Kelas ${item.kelas}</span>
                </div>
            </div>
        `;
        container.innerHTML += card;
    });
}

function filterData() {
    const hari = document.getElementById('filterHari').value;
    const kelas = document.getElementById('filterKelas').value;

    const filtered = jadwalData.filter(item => {
        const matchHari = hari === "Semua" || item.hari === hari;
        const matchKelas = kelas === "Semua" || item.kelas === kelas;
        return matchHari && matchKelas;
    });

    renderJadwal(filtered);
}

// Render awal
renderJadwal(jadwalData);

function openRequestModal() {
    alert("Fungsi pengajuan ganti jadwal sedang diproses oleh sistem admin.");
}