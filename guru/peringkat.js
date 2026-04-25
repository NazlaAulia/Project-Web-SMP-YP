const dataSiswa = [
    { rank: 1, nama: "Andi Wijaya", kelas: "9A", nilai: 95, status: "Excellent" },
    { rank: 2, nama: "Siti Aminah", kelas: "9B", nilai: 88, status: "Good" },
    { rank: 3, nama: "Budi Santoso", kelas: "9A", nilai: 72, status: "Need Attention" }
];

function renderPeringkat(data = dataSiswa) {
    const tbody = document.getElementById("rankingBody");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center; color:#667784;">
                    Data peringkat tidak ditemukan.
                </td>
            </tr>
        `;
        return;
    }

    data.forEach(siswa => {
        let colorClass = siswa.nilai >= 90 ? "bg-excellent" : (siswa.nilai >= 75 ? "bg-good" : "bg-warning");

        tbody.innerHTML += `
            <tr>
                <td>#${siswa.rank}</td>
                <td><strong>${siswa.nama}</strong></td>
                <td>${siswa.kelas}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-fill ${colorClass}" style="width: ${siswa.nilai}%">
                            ${siswa.nilai}%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status-badge ${colorClass}">
                        ${siswa.status}
                    </span>
                </td>
            </tr>
        `;
    });
}

function setupSearchPeringkat() {
    const searchInput = document.getElementById("searchRanking");
    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.trim().toLowerCase();

        const hasilFilter = dataSiswa.filter(siswa => {
            return `
                ${siswa.rank}
                ${siswa.nama}
                ${siswa.kelas}
                ${siswa.nilai}
                ${siswa.status}
            `.toLowerCase().includes(keyword);
        });

        renderPeringkat(hasilFilter);
    });
}

document.addEventListener("DOMContentLoaded", function () {
    renderPeringkat();
    setupSearchPeringkat();
});