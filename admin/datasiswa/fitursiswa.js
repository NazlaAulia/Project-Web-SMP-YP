let semuaSiswa = [];
let filteredSiswa = [];
let currentPage = 1;
const rowsPerPage = 10;

let semuaApproval = [];

const siswaTableBody = document.getElementById("siswaTableBody");
const searchSiswa = document.getElementById("searchSiswa");

const approvalModal = document.getElementById("approvalModal");
const openApprovalBtn = document.getElementById("openApprovalBtn");
const closeApprovalBtn = document.getElementById("closeApprovalBtn");
const approvalList = document.getElementById("approvalList");
const pendingApprovalCount = document.getElementById("pendingApprovalCount");

document.addEventListener("DOMContentLoaded", () => {
    loadSiswa();
    loadPendingApproval();
});

if (openApprovalBtn) {
    openApprovalBtn.addEventListener("click", () => {
        approvalModal.classList.add("active");
        renderApprovalList();
    });
}

if (closeApprovalBtn) {
    closeApprovalBtn.addEventListener("click", () => {
        approvalModal.classList.remove("active");
    });
}

if (approvalModal) {
    approvalModal.addEventListener("click", (e) => {
        if (e.target === approvalModal) {
            approvalModal.classList.remove("active");
        }
    });
}

if (searchSiswa) {
    searchSiswa.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();

        filteredSiswa = semuaSiswa.filter(siswa =>
            (siswa.nama || "").toLowerCase().includes(keyword) ||
            (siswa.nisn || "").toLowerCase().includes(keyword) ||
            (siswa.username || "").toLowerCase().includes(keyword) ||
            (siswa.jenis_kelamin || "").toLowerCase().includes(keyword) ||
            (siswa.nama_kelas || "").toLowerCase().includes(keyword) ||
            (siswa.tahun_ajaran || "").toLowerCase().includes(keyword)
        );

        currentPage = 1;
        renderSiswa();
    });
}

async function loadSiswa() {
    try {
        const response = await fetch("siswa_data.php");
        const raw = await response.text();

        let result;
        try {
            result = JSON.parse(raw);
        } catch {
            throw new Error("Response data siswa bukan JSON: " + raw);
        }

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal memuat data siswa.");
        }

        semuaSiswa = result.data || [];
        filteredSiswa = [...semuaSiswa];
        currentPage = 1;

        renderSiswa();
    } catch (error) {
        siswaTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">${error.message}</td>
            </tr>
        `;

        const paginationInfo = document.getElementById("paginationInfo");
        const paginationBtns = document.getElementById("paginationBtns");

        if (paginationInfo) {
            paginationInfo.textContent = "Menampilkan 0 sampai 0 dari 0 Siswa";
        }

        if (paginationBtns) {
            paginationBtns.innerHTML = "";
        }
    }
}

function renderSiswa() {
    const paginationInfo = document.getElementById("paginationInfo");
    const paginationBtns = document.getElementById("paginationBtns");

    if (!filteredSiswa.length) {
        siswaTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">Data siswa tidak ditemukan.</td>
            </tr>
        `;

        if (paginationInfo) {
            paginationInfo.textContent = "Menampilkan 0 sampai 0 dari 0 Siswa";
        }

        if (paginationBtns) {
            paginationBtns.innerHTML = "";
        }
        return;
    }

    const totalData = filteredSiswa.length;
    const totalPages = Math.ceil(totalData / rowsPerPage);
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pageData = filteredSiswa.slice(startIndex, endIndex);

    siswaTableBody.innerHTML = pageData.map(siswa => `
        <tr>
            <td>${escapeHtml(siswa.nama || "-")}</td>
            <td>${escapeHtml(siswa.nisn || "-")}</td>
            <td>${escapeHtml(siswa.username || "-")}</td>
            <td>${escapeHtml(formatGender(siswa.jenis_kelamin || "-"))}</td>
            <td>
                ${siswa.nama_kelas
                    ? `<span class="badge">${escapeHtml(siswa.nama_kelas)}</span>`
                    : `<span class="badge badge-empty">Belum ada</span>`}
            </td>
            <td>
                ${siswa.tahun_ajaran
                    ? `<span class="badge">${escapeHtml(siswa.tahun_ajaran)}</span>`
                    : `<span class="badge badge-empty">Belum ada</span>`}
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-edit" onclick="editSiswa(${siswa.id_siswa})">Edit</button>
                    <button class="btn-danger" onclick="hapusSiswa(${siswa.id_siswa}, '${escapeJs(siswa.nama)}')">Hapus</button>
                </div>
            </td>
        </tr>
    `).join("");

    const tampilAwal = totalData === 0 ? 0 : startIndex + 1;
    const tampilAkhir = Math.min(endIndex, totalData);

    if (paginationInfo) {
        paginationInfo.textContent = `Menampilkan ${tampilAwal} sampai ${tampilAkhir} dari ${totalData} Siswa`;
    }

    renderPagination(totalPages);
}

function renderPagination() {
    const totalPages = Math.ceil(filteredData.length / rowsPerPage);
    paginationBtns.innerHTML = "";

    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, currentPage + 2);

    for (let i = start; i <= end; i++) {
        const btn = document.createElement("button");
        btn.className = "btn-page" + (i === currentPage ? " active" : "");
        btn.textContent = i;

        btn.onclick = () => {
            currentPage = i;
            renderTable();
            renderPagination();
        };

        paginationBtns.appendChild(btn);
    }

    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement("button");
        pageBtn.className = "btn-page";
        if (i === currentPage) pageBtn.classList.add("active");
        pageBtn.textContent = i;

        pageBtn.addEventListener("click", () => {
            currentPage = i;
            renderSiswa();
        });

        paginationBtns.appendChild(pageBtn);
    }

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn-page";
    nextBtn.innerHTML = `<i class="fas fa-chevron-right"></i>`;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    nextBtn.addEventListener("click", () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderSiswa();
        }
    });
    paginationBtns.appendChild(nextBtn);
}

async function loadPendingApproval() {
    try {
        const response = await fetch("pending_siswa.php");
        const raw = await response.text();

        let result;
        try {
            result = JSON.parse(raw);
        } catch {
            throw new Error("Response pending bukan JSON: " + raw);
        }

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal memuat data approval siswa.");
        }

        semuaApproval = result.data || [];
        updatePendingCount();
    } catch (error) {
        semuaApproval = [];
        updatePendingCount();

        if (approvalList) {
            approvalList.innerHTML = `<div class="empty-cell">${error.message}</div>`;
        }
    }
}

function updatePendingCount() {
    if (pendingApprovalCount) {
        pendingApprovalCount.textContent = semuaApproval.length;
    }
}

function renderApprovalList() {
    if (!approvalList) return;

    if (!semuaApproval.length) {
        approvalList.innerHTML = `
            <div class="empty-cell">Tidak ada pendaftaran yang menunggu approval.</div>
        `;
        return;
    }

    approvalList.innerHTML = semuaApproval.map(item => `
        <div class="approval-item">
            <div class="approval-item-top">
                <div>
                    <div class="approval-name">${escapeHtml(item.nama_lengkap || "-")}</div>
                    <div class="approval-meta">
                        NISN: ${escapeHtml(item.nisn || "-")}<br>
                        Asal Sekolah: ${escapeHtml(item.asal_sekolah || "-")}<br>
                        No HP: ${escapeHtml(item.no_hp || "-")}<br>
                        Tanggal Daftar: ${escapeHtml(item.tanggal_daftar || "-")}
                    </div>
                </div>
            </div>

            <div class="approval-actions">
                <button class="btn-detail" onclick="lihatDetailPendaftaran(${item.id_pendaftaran})">
                    Lihat Detail
                </button>
            </div>
        </div>
    `).join("");
}

function lihatDetailPendaftaran(idPendaftaran) {
    window.location.href = `/admin/pendaftaran.html?id=${idPendaftaran}`;
}

async function hapusSiswa(idSiswa, namaSiswa) {
    const oke = confirm(`Yakin ingin menghapus siswa ${namaSiswa}?`);
    if (!oke) return;

    try {
        const formData = new FormData();
        formData.append("id_siswa", idSiswa);

        const response = await fetch("hapus_siswa.php", {
            method: "POST",
            body: formData
        });

        const raw = await response.text();
        let result;

        try {
            result = JSON.parse(raw);
        } catch {
            throw new Error("Response bukan JSON: " + raw);
        }

        if (result.status !== "success") {
            alert(result.message || "Gagal menghapus siswa.");
            return;
        }

        alert(result.message);
        await loadSiswa();
    } catch (error) {
        alert(error.message);
    }
}

function editSiswa(idSiswa) {
    alert("Fitur edit siswa menyusul.");
}

function formatGender(gender) {
    if (gender === "L") return "Laki-laki";
    if (gender === "P") return "Perempuan";
    return gender;
}

function escapeHtml(text) {
    return String(text)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function escapeJs(text) {
    return String(text)
        .replaceAll("\\", "\\\\")
        .replaceAll("'", "\\'");
}