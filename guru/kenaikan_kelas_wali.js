const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

const kelasWaliText = document.getElementById("kelasWaliText");

const jumlahNaik = document.getElementById("jumlahNaik");
const jumlahTidakNaik = document.getElementById("jumlahTidakNaik");
const jumlahLulus = document.getElementById("jumlahLulus");
const jumlahTotal = document.getElementById("jumlahTotal");

const searchKenaikan = document.getElementById("searchKenaikan");
const kenaikanTableBody = document.getElementById("kenaikanTableBody");
const paginationInfo = document.getElementById("paginationInfo");
const paginationBtns = document.getElementById("paginationBtns");

let semuaDataKenaikan = [];
let dataFilterKenaikan = [];
let currentPage = 1;
const rowsPerPage = 10;

document.addEventListener("DOMContentLoaded", () => {
    if (!idGuruLogin || roleIdLogin !== "2") {
        alert("Silakan login sebagai guru terlebih dahulu.");
        window.location.href = "../login.html";
        return;
    }

    loadKenaikanKelasWali();
});

async function loadKenaikanKelasWali() {
    try {
        const response = await fetch(`get_kenaikan_kelas_wali.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`);
        const result = await response.json();

        if (result.status !== "success") {
            tampilkanError(result.message || "Gagal memuat data kenaikan kelas.");
            return;
        }

        renderKelasWali(result.kelas_wali || []);
        renderSummary(result.summary || {});

        semuaDataKenaikan = Array.isArray(result.data) ? result.data : [];
        dataFilterKenaikan = [...semuaDataKenaikan];

        currentPage = 1;
        renderTableKenaikan();
    } catch (error) {
        console.error(error);
        tampilkanError("Terjadi kesalahan saat memuat data kenaikan kelas.");
    }
}

function renderKelasWali(kelasList) {
    if (!kelasWaliText) return;

    if (!kelasList.length) {
        kelasWaliText.textContent = "Belum menjadi wali kelas";
        return;
    }

    const namaKelas = kelasList.map(kelas => kelas.nama_kelas).join(", ");
    kelasWaliText.textContent = `Wali Kelas ${namaKelas}`;
}

function renderSummary(summary) {
    if (jumlahNaik) jumlahNaik.textContent = summary.naik_kelas || 0;
    if (jumlahTidakNaik) jumlahTidakNaik.textContent = summary.tidak_naik || 0;
    if (jumlahLulus) jumlahLulus.textContent = summary.lulus || 0;
    if (jumlahTotal) jumlahTotal.textContent = summary.total_siswa || 0;
}

function renderTableKenaikan() {
    if (!kenaikanTableBody) return;

    if (!dataFilterKenaikan.length) {
        kenaikanTableBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-cell">Data siswa tidak ditemukan.</td>
            </tr>
        `;

        updatePaginationInfo(0, 0, 0);
        clearPagination();
        return;
    }

    const totalData = dataFilterKenaikan.length;
    const totalPages = Math.ceil(totalData / rowsPerPage);

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const currentData = dataFilterKenaikan.slice(startIndex, endIndex);

    kenaikanTableBody.innerHTML = "";

    currentData.forEach(item => {
        const tr = document.createElement("tr");
        const statusClass = getStatusClass(item.status_kenaikan);

        tr.innerHTML = `
            <td>${escapeHtml(item.nama)}</td>
            <td>${escapeHtml(item.kelas_lama)}</td>
            <td>${escapeHtml(item.kelas_baru)}</td>
            <td>${escapeHtml(item.rata_rata)}</td>
            <td>${escapeHtml(item.mapel_tidak_lulus)}</td>
            <td>${escapeHtml(item.izin)}</td>
            <td>${escapeHtml(item.sakit)}</td>
            <td>${escapeHtml(item.alfa)}</td>
            <td>
                <span class="status-badge ${statusClass}">
                    ${escapeHtml(item.status_kenaikan)}
                </span>
            </td>
        `;

        kenaikanTableBody.appendChild(tr);
    });

    updatePaginationInfo(startIndex + 1, Math.min(endIndex, totalData), totalData);
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    if (!paginationBtns) return;

    paginationBtns.innerHTML = "";

    const prevBtn = document.createElement("button");
    prevBtn.type = "button";
    prevBtn.className = "page-btn";
    prevBtn.textContent = "Prev";
    prevBtn.disabled = currentPage === 1;
    prevBtn.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            renderTableKenaikan();
        }
    });
    paginationBtns.appendChild(prevBtn);

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let page = startPage; page <= endPage; page++) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = `page-btn ${page === currentPage ? "active" : ""}`;
        btn.textContent = page;

        btn.addEventListener("click", () => {
            currentPage = page;
            renderTableKenaikan();
        });

        paginationBtns.appendChild(btn);
    }

    const nextBtn = document.createElement("button");
    nextBtn.type = "button";
    nextBtn.className = "page-btn";
    nextBtn.textContent = "Next";
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.addEventListener("click", () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderTableKenaikan();
        }
    });
    paginationBtns.appendChild(nextBtn);
}

function updatePaginationInfo(start, end, total) {
    if (!paginationInfo) return;
    paginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari ${total} siswa`;
}

function clearPagination() {
    if (paginationBtns) {
        paginationBtns.innerHTML = "";
    }
}

if (searchKenaikan) {
    searchKenaikan.addEventListener("input", () => {
        const keyword = searchKenaikan.value.trim().toLowerCase();

        dataFilterKenaikan = semuaDataKenaikan.filter(item => {
            const text = [
                item.nama,
                item.kelas_lama,
                item.kelas_baru,
                item.rata_rata,
                item.mapel_tidak_lulus,
                item.izin,
                item.sakit,
                item.alfa,
                item.status_kenaikan
            ].join(" ").toLowerCase();

            return text.includes(keyword);
        });

        currentPage = 1;
        renderTableKenaikan();
    });
}

function getStatusClass(status) {
    if (status === "Naik Kelas") return "status-naik";
    if (status === "Tidak Naik Kelas") return "status-tidak-naik";
    if (status === "Lulus") return "status-lulus";
    return "";
}

function tampilkanError(message) {
    if (kelasWaliText) {
        kelasWaliText.textContent = message;
    }

    if (kenaikanTableBody) {
        kenaikanTableBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-cell">${escapeHtml(message)}</td>
            </tr>
        `;
    }

    renderSummary({
        naik_kelas: 0,
        tidak_naik: 0,
        lulus: 0,
        total_siswa: 0
    });

    updatePaginationInfo(0, 0, 0);
    clearPagination();
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}