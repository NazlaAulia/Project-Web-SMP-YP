let dataSiswa = [];

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

const totalUnggulEl = document.getElementById("totalUnggul");
const totalBaikEl = document.getElementById("totalBaik");
const totalPerhatianEl = document.getElementById("totalPerhatian");
const filterSemester = document.getElementById("filterSemester");

function renderSummary(summary) {
    if (!summary) return;
    if (totalUnggulEl) totalUnggulEl.textContent = summary.unggul || 0;
    if (totalBaikEl) totalBaikEl.textContent = summary.baik || 0;
    if (totalPerhatianEl) totalPerhatianEl.textContent = summary.perhatian || 0;
}

function renderPeringkat(data = dataSiswa) {
    const tbody = document.getElementById("rankingBody");
    if (!tbody) return;
    tbody.innerHTML = "";
    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">Data peringkat tidak ditemukan.</td></tr>`;
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
                <td><span class="status-badge ${colorClass}">${siswa.status}</span></td>
            </tr>
        `;
    });
}

function filterSearchPeringkat() {
    const keyword = document.getElementById("searchRanking")?.value.trim().toLowerCase() || "";
    const hasilFilter = dataSiswa.filter(s => `${s.rank}${s.nama}${s.kelas}${s.nilai}${s.status}`.toLowerCase().includes(keyword));
    renderPeringkat(hasilFilter);
}

function setupSearchPeringkat() {
    document.getElementById("searchRanking")?.addEventListener("input", filterSearchPeringkat);
}

function loadPeringkatDatabase() {
    if (!idGuruLogin || roleIdLogin !== "2") {
        alert("Silakan login sebagai guru terlebih dahulu.");
        window.location.href = "../login.html";
        return;
    }
    const semester = filterSemester?.value || "Semua";
    const angkatan = document.getElementById("filterAngkatan")?.value || 0;
    fetch(`peringkat.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&semester=${encodeURIComponent(semester)}&angkatan=${angkatan}`)
        .then(res => res.json())
        .then(result => {
            if (result.status === "success") {
                dataSiswa = result.data || [];
                renderSummary(result.summary);
                filterSearchPeringkat();
            } else {
                alert(result.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Gagal memuat data peringkat.");
        });
}

if (filterSemester) filterSemester.addEventListener("change", loadPeringkatDatabase);
if (document.getElementById("filterAngkatan")) document.getElementById("filterAngkatan").addEventListener("change", loadPeringkatDatabase);
document.addEventListener("DOMContentLoaded", function() {
    setupSearchPeringkat();
    loadPeringkatDatabase();
});