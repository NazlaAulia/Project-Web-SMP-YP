let semuaSiswa = [];
let filteredSiswa = [];
let currentPage = 1;
const rowsPerPage = 10;

let semuaApproval = [];
let deleteTargetId = null;

const siswaTableBody = document.getElementById("siswaTableBody");
const searchSiswa = document.getElementById("searchSiswa");

const approvalModal = document.getElementById("approvalModal");
const openApprovalBtn = document.getElementById("openApprovalBtn");
const closeApprovalBtn = document.getElementById("closeApprovalBtn");
const approvalList = document.getElementById("approvalList");
const pendingApprovalCount = document.getElementById("pendingApprovalCount");

const deleteModal = document.getElementById("deleteModal");
const deleteSiswaName = document.getElementById("deleteSiswaName");
const closeDeleteBtn = document.getElementById("closeDeleteBtn");
const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

/* TAMBAHAN UNTUK MODAL EDIT */
const editModal = document.getElementById("editModal");
const closeEditBtn = document.getElementById("closeEditBtn");
const cancelEditBtn = document.getElementById("cancelEditBtn");
const editSiswaForm = document.getElementById("editSiswaForm");

const editIdSiswa = document.getElementById("editIdSiswa");
const editNisn = document.getElementById("editNisn");
const editNama = document.getElementById("editNama");
const editJenisKelamin = document.getElementById("editJenisKelamin");
const editFormMessage = document.getElementById("editFormMessage");

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
                    <button 
                        type="button"
                        class="btn-edit btn-icon" 
                        onclick="editSiswa(${siswa.id_siswa})" 
                        title="Edit"
                        aria-label="Edit"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>

                    <button 
                        type="button"
                        class="btn-danger btn-icon" 
                        onclick="openDeleteModal(${siswa.id_siswa}, '${escapeJs(siswa.nama || "-")}')" 
                        title="Hapus"
                        aria-label="Hapus"
                    >
                        <i class="fa-solid fa-trash"></i>
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

    const maxVisiblePages = 5;

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

    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = startPage + maxVisiblePages - 1;

    if (endPage > totalPages) {
        endPage = totalPages;
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement("button");
        pageBtn.className = "btn-page";

        if (i === currentPage) {
            pageBtn.classList.add("active");
        }

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

function openDeleteModal(idSiswa, namaSiswa) {
    deleteTargetId = idSiswa;

    if (deleteSiswaName) {
        deleteSiswaName.textContent = namaSiswa;
    }

    if (deleteModal) {
        deleteModal.classList.add("active");
    }
}

function closeDeleteModal() {
    deleteTargetId = null;

    if (deleteModal) {
        deleteModal.classList.remove("active");
    }
}

if (closeDeleteBtn) {
    closeDeleteBtn.addEventListener("click", closeDeleteModal);
}

if (cancelDeleteBtn) {
    cancelDeleteBtn.addEventListener("click", closeDeleteModal);
}

if (deleteModal) {
    deleteModal.addEventListener("click", (e) => {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });
}

if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", async () => {
        if (!deleteTargetId) return;

        try {
            const formData = new FormData();
            formData.append("id_siswa", deleteTargetId);

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

            closeDeleteModal();
            await loadSiswa();
        } catch (error) {
            alert(error.message);
        }
    });
}

/* TAMBAHAN EVENT UNTUK MODAL EDIT */
if (closeEditBtn) {
    closeEditBtn.addEventListener("click", closeEditModal);
}

if (cancelEditBtn) {
    cancelEditBtn.addEventListener("click", closeEditModal);
}

if (editModal) {
    editModal.addEventListener("click", (e) => {
        if (e.target === editModal) {
            closeEditModal();
        }
    });
}

function editSiswa(idSiswa) {
    const siswa = semuaSiswa.find(item => Number(item.id_siswa) === Number(idSiswa));

    if (!siswa) {
        alert("Data siswa tidak ditemukan.");
        return;
    }

    editIdSiswa.value = siswa.id_siswa || "";
    editNisn.value = siswa.nisn || "";
    editNama.value = siswa.nama || "";
    editJenisKelamin.value = siswa.jenis_kelamin || "";
    editFormMessage.textContent = "";
    editFormMessage.className = "form-message";

    if (editModal) {
        editModal.classList.add("active");
    }
}

function closeEditModal() {
    if (editModal) {
        editModal.classList.remove("active");
    }

    if (editSiswaForm) {
        editSiswaForm.reset();
    }

    if (editFormMessage) {
        editFormMessage.textContent = "";
        editFormMessage.className = "form-message";
    }
}

if (editSiswaForm) {
    editSiswaForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        if (editFormMessage) {
            editFormMessage.textContent = "Menyimpan perubahan...";
            editFormMessage.className = "form-message";
        }

        try {
            const formData = new FormData();
            formData.append("id_siswa", editIdSiswa.value);
            formData.append("nisn", editNisn.value.trim());
            formData.append("nama", editNama.value.trim());
            formData.append("jenis_kelamin", editJenisKelamin.value);

            const response = await fetch("edit_siswa.php", {
                method: "POST",
                body: formData
            });

            const raw = await response.text();

            let result;
            try {
                result = JSON.parse(raw);
            } catch {
                throw new Error("Response edit bukan JSON: " + raw);
            }

            if (result.status !== "success") {
                if (editFormMessage) {
                    editFormMessage.textContent = result.message || "Gagal memperbarui data siswa.";
                    editFormMessage.className = "form-message error";
                }
                return;
            }

            if (editFormMessage) {
                editFormMessage.textContent = result.message || "Data siswa berhasil diperbarui.";
                editFormMessage.className = "form-message success";
            }

            await loadSiswa();

            setTimeout(() => {
                closeEditModal();
            }, 800);

        } catch (error) {
            if (editFormMessage) {
                editFormMessage.textContent = error.message;
                editFormMessage.className = "form-message error";
            }
        }
    });
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