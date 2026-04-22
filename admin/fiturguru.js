let semuaGuru = [];
let filteredGuru = [];
let currentPage = 1;
const rowsPerPage = 10;

const guruTableBody = document.getElementById("guruTableBody");
const searchGuru = document.getElementById("searchGuru");

const guruModal = document.getElementById("guruModal");
const openModalBtn = document.getElementById("openModalBtn");
const closeModalBtn = document.getElementById("closeModalBtn");
const cancelModalBtn = document.getElementById("cancelModalBtn");

const formTambahGuru = document.getElementById("formTambahGuru");
const formMessage = document.getElementById("formMessage");
const mapelSelect = document.getElementById("id_mapel");

let confirmCallback = null;

document.addEventListener("DOMContentLoaded", () => {
    injectHiddenFieldsIfNeeded();
    injectCustomModalIfNeeded();
    loadGuru();
    loadMapel();
});

// ==========================
// INIT HELPER
// ==========================
function injectHiddenFieldsIfNeeded() {
    if (!document.getElementById("id_guru")) {
        const inputIdGuru = document.createElement("input");
        inputIdGuru.type = "hidden";
        inputIdGuru.id = "id_guru";
        inputIdGuru.name = "id_guru";
        formTambahGuru.prepend(inputIdGuru);
    }

    if (!document.getElementById("formMode")) {
        const inputMode = document.createElement("input");
        inputMode.type = "hidden";
        inputMode.id = "formMode";
        inputMode.value = "tambah";
        formTambahGuru.prepend(inputMode);
    }

    const modalHeaderTitle = document.querySelector(".modal-header h2");
    if (modalHeaderTitle) {
        modalHeaderTitle.id = "modalTitle";
    }

    const submitBtn = formTambahGuru?.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.id = "submitGuruBtn";
    }
}

function injectCustomModalIfNeeded() {
    if (!document.getElementById("confirmModal")) {
        const confirmModal = document.createElement("div");
        confirmModal.id = "confirmModal";
        confirmModal.className = "custom-popup-overlay";
        confirmModal.style.display = "none";
        confirmModal.innerHTML = `
            <div class="custom-popup-box">
                <div class="custom-popup-header">
                    <h3>Konfirmasi</h3>
                    <button type="button" class="custom-popup-close" id="confirmCloseBtn">&times;</button>
                </div>
                <div class="custom-popup-body">
                    <p id="confirmMessage">Apakah Anda yakin?</p>
                </div>
                <div class="custom-popup-footer">
                    <button type="button" class="custom-btn-secondary" id="confirmCancelBtn">Batal</button>
                    <button type="button" class="custom-btn-primary" id="confirmOkBtn">Ya, lanjut</button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModal);
    }

    if (!document.getElementById("customAlertModal")) {
        const alertModal = document.createElement("div");
        alertModal.id = "customAlertModal";
        alertModal.className = "custom-popup-overlay";
        alertModal.style.display = "none";
        alertModal.innerHTML = `
            <div class="custom-popup-box">
                <div class="custom-popup-header">
                    <h3>Informasi</h3>
                    <button type="button" class="custom-popup-close" id="alertCloseBtn">&times;</button>
                </div>
                <div class="custom-popup-body">
                    <p id="customAlertMessage">Pesan notifikasi</p>
                </div>
                <div class="custom-popup-footer">
                    <button type="button" class="custom-btn-primary" id="alertOkBtn">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(alertModal);
    }



    const confirmModal = document.getElementById("confirmModal");
    const alertModal = document.getElementById("customAlertModal");

    document.getElementById("confirmOkBtn")?.addEventListener("click", () => {
        const cb = confirmCallback;
        closeConfirmModal();
        if (typeof cb === "function") cb();
    });

    document.getElementById("confirmCancelBtn")?.addEventListener("click", closeConfirmModal);
    document.getElementById("confirmCloseBtn")?.addEventListener("click", closeConfirmModal);

    document.getElementById("alertOkBtn")?.addEventListener("click", closeAlertModal);
    document.getElementById("alertCloseBtn")?.addEventListener("click", closeAlertModal);

    confirmModal?.addEventListener("click", (e) => {
        if (e.target === confirmModal) closeConfirmModal();
    });

    alertModal?.addEventListener("click", (e) => {
        if (e.target === alertModal) closeAlertModal();
    });
}

function getIdGuruInput() {
    return document.getElementById("id_guru");
}

function getFormModeInput() {
    return document.getElementById("formMode");
}

function getModalTitle() {
    return document.getElementById("modalTitle");
}

function getSubmitGuruBtn() {
    return document.getElementById("submitGuruBtn");
}

function resetFormToTambahMode() {
    formTambahGuru?.reset();

    if (formMessage) {
        formMessage.textContent = "";
        formMessage.className = "form-message";
    }

    const idGuruInput = getIdGuruInput();
    const formMode = getFormModeInput();
    const modalTitle = getModalTitle();
    const submitGuruBtn = getSubmitGuruBtn();

    if (idGuruInput) idGuruInput.value = "";
    if (formMode) formMode.value = "tambah";
    if (modalTitle) modalTitle.textContent = "Tambah Guru";
    if (submitGuruBtn) submitGuruBtn.textContent = "Simpan Guru";
}

// ==========================
// CUSTOM POPUP
// ==========================
function openConfirmModal(message, callback) {
    const el = document.getElementById("confirmMessage");
    const modal = document.getElementById("confirmModal");

    if (!el || !modal) return;

    el.textContent = message;
    confirmCallback = callback;
    modal.style.display = "flex";
}

function closeConfirmModal() {
    const modal = document.getElementById("confirmModal");
    if (modal) modal.style.display = "none";
    confirmCallback = null;
}

function showAlertModal(message) {
    const el = document.getElementById("customAlertMessage");
    const modal = document.getElementById("customAlertModal");

    if (!el || !modal) {
        window.alert(message);
        return;
    }

    el.textContent = message;
    modal.style.display = "flex";
}

function closeAlertModal() {
    const modal = document.getElementById("customAlertModal");
    if (modal) modal.style.display = "none";
}

// ==========================
// MODAL GURU
// ==========================
if (openModalBtn) {
    openModalBtn.addEventListener("click", () => {
        resetFormToTambahMode();
        guruModal?.classList.add("active");
    });
}

if (closeModalBtn) {
    closeModalBtn.addEventListener("click", () => {
        guruModal?.classList.remove("active");
        resetFormToTambahMode();
    });
}

if (cancelModalBtn) {
    cancelModalBtn.addEventListener("click", () => {
        guruModal?.classList.remove("active");
        resetFormToTambahMode();
    });
}

if (guruModal) {
    guruModal.addEventListener("click", (e) => {
        if (e.target === guruModal) {
            guruModal.classList.remove("active");
            resetFormToTambahMode();
        }
    });
}

// ==========================
// SEARCH
// ==========================
if (searchGuru) {
    searchGuru.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();

        filteredGuru = semuaGuru.filter(guru =>
            (guru.nama || "").toLowerCase().includes(keyword) ||
            (guru.email || "").toLowerCase().includes(keyword) ||
            (guru.username || "").toLowerCase().includes(keyword) ||
            (guru.nip || "").toLowerCase().includes(keyword) ||
            (guru.nama_mapel || "").toLowerCase().includes(keyword) ||
            (guru.wali_kelas || "").toLowerCase().includes(keyword)
        );

        currentPage = 1;
        renderGuru();
    });
}

// ==========================
// SUBMIT FORM
// ==========================
if (formTambahGuru) {
    formTambahGuru.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formMode = getFormModeInput();
        const mode = formMode ? formMode.value : "tambah";

        const pesanKonfirmasi = mode === "edit"
            ? "Apakah Anda yakin ingin mengedit data guru ini?"
            : "Apakah Anda yakin ingin menambahkan data guru ini?";

        openConfirmModal(pesanKonfirmasi, async function () {
            if (formMessage) {
                formMessage.textContent = mode === "edit"
                    ? "Menyimpan perubahan data guru..."
                    : "Menyimpan data guru...";
                formMessage.className = "form-message";
            }

            const formData = new FormData(formTambahGuru);
            const url = mode === "edit" ? "edit_guru.php" : "tambah_guru.php";

            try {
                const response = await fetch(url, {
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
                    if (formMessage) {
                        formMessage.textContent = result.message || "Proses gagal.";
                        formMessage.className = "form-message error";
                    }
                    return;
                }

                if (formMessage) {
                    formMessage.textContent = result.message;
                    formMessage.className = "form-message success";
                }

                await loadGuru();

                setTimeout(() => {
                    guruModal?.classList.remove("active");
                    resetFormToTambahMode();
                }, 900);

            } catch (error) {
                if (formMessage) {
                    formMessage.textContent = error.message;
                    formMessage.className = "form-message error";
                }
            }
        });
    });
}

// ==========================
// LOAD DATA GURU
// ==========================
async function loadGuru() {
    try {
        const response = await fetch("guru_data.php");
        const raw = await response.text();

        let result;
        try {
            result = JSON.parse(raw);
        } catch {
            throw new Error("Response guru_data bukan JSON: " + raw);
        }

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal memuat data guru.");
        }

        semuaGuru = result.data || [];
        filteredGuru = [...semuaGuru];
        currentPage = 1;

        renderGuru();

    } catch (error) {
        if (guruTableBody) {
            guruTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-cell">${escapeHtml(error.message)}</td>
                </tr>
            `;
        }

        const paginationInfo = document.getElementById("paginationInfo");
        const paginationBtns = document.getElementById("paginationBtns");

        if (paginationInfo) {
            paginationInfo.textContent = "Menampilkan 0 sampai 0 dari 0 Guru";
        }

        if (paginationBtns) {
            paginationBtns.innerHTML = "";
        }
    }
}

// ==========================
// RENDER TABLE
// ==========================
function renderGuru() {
    const paginationInfo = document.getElementById("paginationInfo");
    const paginationBtns = document.getElementById("paginationBtns");

    if (!guruTableBody) return;

    if (!filteredGuru.length) {
        guruTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">Data guru tidak ditemukan.</td>
            </tr>
        `;

        if (paginationInfo) {
            paginationInfo.textContent = "Menampilkan 0 sampai 0 dari 0 Guru";
        }

        if (paginationBtns) {
            paginationBtns.innerHTML = "";
        }
        return;
    }

    const totalData = filteredGuru.length;
    const totalPages = Math.ceil(totalData / rowsPerPage);
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pageData = filteredGuru.slice(startIndex, endIndex);

    guruTableBody.innerHTML = pageData.map(guru => `
        <tr>
            <td>${escapeHtml(guru.nama || "-")}</td>
            <td>${escapeHtml(guru.email || "-")}</td>
            <td>${escapeHtml(guru.username || "-")}</td>
            <td>${escapeHtml(guru.nip || "-")}</td>
            <td>
                ${guru.nama_mapel
                    ? `<span class="badge">${escapeHtml(guru.nama_mapel)}</span>`
                    : `<span class="badge badge-empty">Belum ada</span>`}
            </td>
            <td>
                ${guru.wali_kelas
                    ? `<span class="badge">${escapeHtml(guru.wali_kelas)}</span>`
                    : `<span class="badge badge-empty">Bukan wali</span>`}
            </td>
            <td>
                <div class="action-buttons">
                    <button
                        class="btn-edit icon-only"
                        title="Edit"
                        onclick="editGuru('${escapeJs(guru.id_guru)}')"
                    >
                        <i class="fas fa-pen-to-square"></i>
                    </button>

                    <button
                        class="btn-danger icon-only"
                        title="Hapus"
                        onclick="hapusGuru('${escapeJs(guru.id_guru)}', '${escapeJs(guru.nama)}')"
                    >
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join("");

    const tampilAwal = totalData === 0 ? 0 : startIndex + 1;
    const tampilAkhir = Math.min(endIndex, totalData);

    if (paginationInfo) {
        paginationInfo.textContent = `Menampilkan ${tampilAwal} sampai ${tampilAkhir} dari ${totalData} Guru`;
    }

    renderPagination(totalPages);
}

// ==========================
// PAGINATION
// ==========================
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
            renderGuru();
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
            renderGuru();
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
            renderGuru();
        }
    });
    paginationBtns.appendChild(nextBtn);
}

// ==========================
// LOAD MAPEL
// ==========================
async function loadMapel() {
    try {
        const response = await fetch("guru_data.php?mode=mapel");
        const raw = await response.text();

        let result;
        try {
            result = JSON.parse(raw);
        } catch {
            throw new Error("Response mapel bukan JSON: " + raw);
        }

        if (result.status !== "success") return;
        if (!mapelSelect) return;

        mapelSelect.innerHTML = `<option value="">-- Pilih Pelajaran --</option>`;

        result.data.forEach(mapel => {
            const option = document.createElement("option");
            option.value = mapel.id_mapel;
            option.textContent = mapel.nama_mapel;
            mapelSelect.appendChild(option);
        });
    } catch (error) {
        console.error(error);
    }
}

// ==========================
// HAPUS GURU
// ==========================
async function hapusGuru(idGuru, namaGuru) {
    openConfirmModal(`Apakah Anda yakin ingin menghapus guru ${namaGuru}?`, async function () {
        try {
            const formData = new FormData();
            formData.append("id_guru", idGuru);

            const response = await fetch("hapus_guru.php", {
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
                showAlertModal(result.message || "Gagal menghapus guru.");
                return;
            }

            showAlertModal(result.message || "Data guru berhasil dihapus.");
            await loadGuru();

        } catch (error) {
            showAlertModal(error.message);
        }
    });
}

// ==========================
// EDIT GURU
// ==========================
function editGuru(idGuru) {
    const guru = semuaGuru.find(item => String(item.id_guru) === String(idGuru));

    if (!guru) {
        showAlertModal("Data guru tidak ditemukan.");
        return;
    }

    const nipInput = document.getElementById("nip");
    const namaInput = document.getElementById("nama");
    const emailInput = document.getElementById("email");
    const mapelInput = document.getElementById("id_mapel");

    if (nipInput) nipInput.value = guru.nip || "";
    if (namaInput) namaInput.value = guru.nama || "";
    if (emailInput) emailInput.value = guru.email || "";
    if (mapelInput) mapelInput.value = guru.id_mapel || "";

    const idGuruInput = getIdGuruInput();
    const formMode = getFormModeInput();
    const modalTitle = getModalTitle();
    const submitGuruBtn = getSubmitGuruBtn();

    if (idGuruInput) idGuruInput.value = guru.id_guru;
    if (formMode) formMode.value = "edit";
    if (modalTitle) modalTitle.textContent = "Edit Guru";
    if (submitGuruBtn) submitGuruBtn.textContent = "Simpan Perubahan";

    if (formMessage) {
        formMessage.textContent = "";
        formMessage.className = "form-message";
    }

    guruModal?.classList.add("active");
}

// ==========================
// ESCAPE
// ==========================
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

// ==========================
// GLOBAL EXPORT
// ==========================
window.editGuru = editGuru;
window.hapusGuru = hapusGuru;
window.openConfirmModal = openConfirmModal;
window.closeConfirmModal = closeConfirmModal;
window.showAlertModal = showAlertModal;
window.closeAlertModal = closeAlertModal;