let semuaSiswa = [];
let filteredSiswa = [];
let currentPage = 1;
const rowsPerPage = 10;

let semuaApproval = [];

let idSiswaYangDihapus = null;
let namaSiswaYangDihapus = "";

const siswaTableBody = document.getElementById("siswaTableBody");
const searchSiswa = document.getElementById("searchSiswa");

const approvalModal = document.getElementById("approvalModal");
const openApprovalBtn = document.getElementById("openApprovalBtn");
const closeApprovalBtn = document.getElementById("closeApprovalBtn");
const approvalList = document.getElementById("approvalList");
const pendingApprovalCount = document.getElementById("pendingApprovalCount");

document.addEventListener("DOMContentLoaded", () => {
    buatPopupSiswa();
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

function buatPopupSiswa() {
    const popupHtml = `
        <div class="modal-overlay" id="detailSiswaModal">
            <div class="modal-box modal-detail-siswa">
                <div class="modal-header">
                    <h2>Detail Siswa</h2>
                    <button class="btn-close" id="closeDetailSiswaBtn" type="button">&times;</button>
                </div>

                <div class="detail-siswa-list">
                    <div class="detail-row">
                        <span>Nama</span>
                        <strong id="detailNamaSiswa">-</strong>
                    </div>
                    <div class="detail-row">
                        <span>NISN</span>
                        <strong id="detailNisnSiswa">-</strong>
                    </div>
                    <div class="detail-row">
                        <span>Nama Pengguna</span>
                        <strong id="detailUsernameSiswa">-</strong>
                    </div>
                    <div class="detail-row">
                        <span>Jenis Kelamin</span>
                        <strong id="detailGenderSiswa">-</strong>
                    </div>
                    <div class="detail-row">
                        <span>Kelas</span>
                        <strong id="detailKelasSiswa">-</strong>
                    </div>
                    <div class="detail-row">
                        <span>Tahun Ajaran</span>
                        <strong id="detailTahunSiswa">-</strong>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="okDetailSiswaBtn">Tutup</button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="hapusSiswaModal">
            <div class="modal-box modal-hapus-siswa">
                <div class="modal-header">
                    <h2>Hapus Siswa</h2>
                    <button class="btn-close" id="closeHapusSiswaBtn" type="button">&times;</button>
                </div>

                <p class="hapus-text">
                    Yakin ingin menghapus siswa <strong id="namaSiswaHapus">-</strong>?
                </p>

                <div class="form-message" id="hapusSiswaMessage"></div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelHapusSiswaBtn">Batal</button>
                    <button type="button" class="btn-danger" id="confirmHapusSiswaBtn">
                        <i class="fas fa-trash"></i>
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML("beforeend", popupHtml);

    const style = document.createElement("style");
    style.textContent = `
        .action-buttons {
            display: flex;
            gap: 6px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .btn-icon {
            width: 30px;
            height: 30px;
            padding: 0 !important;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .btn-icon i {
            pointer-events: none;
        }

        .btn-icon:hover {
            transform: translateY(-1px);
            filter: brightness(0.97);
        }

        .modal-detail-siswa,
        .modal-hapus-siswa {
            max-width: 480px;
        }

        .detail-siswa-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            background: var(--bg-light);
            font-size: 14px;
        }

        .detail-row span {
            color: var(--muted);
        }

        .detail-row strong {
            color: var(--text-dark);
            text-align: right;
            font-weight: 600;
        }

        .hapus-text {
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-dark);
        }

        .hapus-text strong {
            color: var(--danger-text);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: row;
                align-items: center;
            }

            .action-buttons button {
                width: 30px;
            }

            .detail-row {
                flex-direction: column;
                gap: 4px;
            }

            .detail-row strong {
                text-align: left;
            }
        }
    `;
    document.head.appendChild(style);

    const detailSiswaModal = document.getElementById("detailSiswaModal");
    const closeDetailSiswaBtn = document.getElementById("closeDetailSiswaBtn");
    const okDetailSiswaBtn = document.getElementById("okDetailSiswaBtn");

    const hapusSiswaModal = document.getElementById("hapusSiswaModal");
    const closeHapusSiswaBtn = document.getElementById("closeHapusSiswaBtn");
    const cancelHapusSiswaBtn = document.getElementById("cancelHapusSiswaBtn");
    const confirmHapusSiswaBtn = document.getElementById("confirmHapusSiswaBtn");

    if (closeDetailSiswaBtn) {
        closeDetailSiswaBtn.addEventListener("click", closeDetailModal);
    }

    if (okDetailSiswaBtn) {
        okDetailSiswaBtn.addEventListener("click", closeDetailModal);
    }

    if (detailSiswaModal) {
        detailSiswaModal.addEventListener("click", (e) => {
            if (e.target === detailSiswaModal) {
                closeDetailModal();
            }
        });
    }

    if (closeHapusSiswaBtn) {
        closeHapusSiswaBtn.addEventListener("click", closeHapusModal);
    }

    if (cancelHapusSiswaBtn) {
        cancelHapusSiswaBtn.addEventListener("click", closeHapusModal);
    }

    if (hapusSiswaModal) {
        hapusSiswaModal.addEventListener("click", (e) => {
            if (e.target === hapusSiswaModal) {
                closeHapusModal();
            }
        });
    }

    if (confirmHapusSiswaBtn) {
        confirmHapusSiswaBtn.addEventListener("click", prosesHapusSiswa);
    }
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
                    <button 
                        class="btn-edit btn-icon" 
                        title="Edit / Detail"
                        onclick="editSiswa(${siswa.id_siswa})">
                        <i class="fas fa-pen"></i>
                    </button>

                    <button 
                        class="btn-danger btn-icon" 
                        title="Hapus"
                        onclick="hapusSiswa(${siswa.id_siswa}, '${escapeJs(siswa.nama)}')">
                        <i class="fas fa-trash"></i>
                    </button>
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

function renderPagination(totalPages) {
    const paginationBtns = document.getElementById("paginationBtns");
    if (!paginationBtns) return;

    paginationBtns.innerHTML = "";

    const prevBtn = document.createElement("button");
    prevBtn.className = "btn-page";
    prevBtn.innerHTML = `<i class="fas fa-chevron-left"></i>`;
    prevBtn.disabled = currentPage === 1;
    prevBtn.addEventListener("click", () => {
        if (currentPage > 1) {
            currentPage--;
            renderSiswa();
        }
    });
    paginationBtns.appendChild(prevBtn);

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

function editSiswa(idSiswa) {
    const siswa = semuaSiswa.find(item => Number(item.id_siswa) === Number(idSiswa));

    if (!siswa) {
        alert("Data siswa tidak ditemukan.");
        return;
    }

    document.getElementById("detailNamaSiswa").textContent = siswa.nama || "-";
    document.getElementById("detailNisnSiswa").textContent = siswa.nisn || "-";
    document.getElementById("detailUsernameSiswa").textContent = siswa.username || "-";
    document.getElementById("detailGenderSiswa").textContent = formatGender(siswa.jenis_kelamin || "-");
    document.getElementById("detailKelasSiswa").textContent = siswa.nama_kelas || "-";
    document.getElementById("detailTahunSiswa").textContent = siswa.tahun_ajaran || "-";

    document.getElementById("detailSiswaModal").classList.add("active");
}

function closeDetailModal() {
    const modal = document.getElementById("detailSiswaModal");
    if (modal) {
        modal.classList.remove("active");
    }
}

function hapusSiswa(idSiswa, namaSiswa) {
    idSiswaYangDihapus = idSiswa;
    namaSiswaYangDihapus = namaSiswa || "-";

    document.getElementById("namaSiswaHapus").textContent = namaSiswaYangDihapus;

    const message = document.getElementById("hapusSiswaMessage");
    if (message) {
        message.textContent = "";
        message.className = "form-message";
    }

    document.getElementById("hapusSiswaModal").classList.add("active");
}

function closeHapusModal() {
    const modal = document.getElementById("hapusSiswaModal");
    if (modal) {
        modal.classList.remove("active");
    }

    idSiswaYangDihapus = null;
    namaSiswaYangDihapus = "";
}

async function prosesHapusSiswa() {
    const message = document.getElementById("hapusSiswaMessage");
    const confirmBtn = document.getElementById("confirmHapusSiswaBtn");

    if (!idSiswaYangDihapus) {
        if (message) {
            message.textContent = "ID siswa tidak valid.";
            message.className = "form-message error";
        }
        return;
    }

    try {
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menghapus...`;
        }

        const formData = new FormData();
        formData.append("id_siswa", idSiswaYangDihapus);

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
            if (message) {
                message.textContent = result.message || "Gagal menghapus siswa.";
                message.className = "form-message error";
            }
            return;
        }

        if (message) {
            message.textContent = result.message || "Data siswa berhasil dihapus.";
            message.className = "form-message success";
        }

        await loadSiswa();

        setTimeout(() => {
            closeHapusModal();
        }, 700);

    } catch (error) {
        if (message) {
            message.textContent = error.message;
            message.className = "form-message error";
        }
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = `<i class="fas fa-trash"></i> Hapus`;
        }
    }
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