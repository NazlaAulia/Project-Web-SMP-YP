const dataSiswa = [
    { rank: 1, nama: "Andi Wijaya", kelas: "9A", nilai: 95, status: "Excellent" },
    { rank: 2, nama: "Siti Aminah", kelas: "9B", nilai: 88, status: "Good" },
    { rank: 3, nama: "Budi Santoso", kelas: "9A", nilai: 72, status: "Need Attention" }
];

function renderPeringkat() {
    const tbody = document.getElementById('rankingBody');
    tbody.innerHTML = '';

    dataSiswa.forEach(siswa => {
        let colorClass = siswa.nilai >= 90 ? 'bg-excellent' : (siswa.nilai >= 75 ? 'bg-good' : 'bg-warning');

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

document.addEventListener('DOMContentLoaded', renderPeringkat);