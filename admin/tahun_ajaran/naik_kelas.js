const tahunSelect = document.getElementById("id_tahun_ajaran");
const waliKelasGrid = document.getElementById("waliKelasGrid");
const naikKelasForm = document.getElementById("naikKelasForm");
const formMessage = document.getElementById("formMessage");

const jumlahSiswaBaru = document.getElementById("jumlahSiswaBaru");
const jumlahSiswaAktif = document.getElementById("jumlahSiswaAktif");
const jumlahKelas9 = document.getElementById("jumlahKelas9");

const previewNaikBody = document.getElementById("previewNaikBody");
const previewSummary = document.getElementById("previewSummary");
const searchPreview = document.getElementById("searchPreview");
const previewPaginationInfo = document.getElementById("previewPaginationInfo");
const previewPaginationBtns = document.getElementById("previewPaginationBtns");

const confirmPopup = document.getElementById("confirmPopup");
const closeConfirmPopup = document.getElementById("closeConfirmPopup");
const cancelConfirmBtn = document.getElementById("cancelConfirmBtn");
const confirmProcessBtn = document.getElementById("confirmProcessBtn");
const confirmText = document.getElementById("confirmText");
const confirmMessage = document.getElementById("confirmMessage");

const buatTahunBtn = document.getElementById("buatTahunBtn");
const tahunAjaranPopup = document.getElementById("tahunAjaranPopup");
const closeTahunAjaranPopup = document.getElementById("closeTahunAjaranPopup");
const cancelTahunAjaranBtn = document.getElementById("cancelTahunAjaranBtn");
const confirmTahunAjaranBtn = document.getElementById("confirmTahunAjaranBtn");
const tahunAjaranMessage = document.getElementById("tahunAjaranMessage");

let pendingFormData = null;

let previewData = [];
let filteredPreviewData = [];
let currentPreviewPage = 1;
const previewRowsPerPage = 10;

let currentIdTahunAjaran = 0;

document.addEventListener("DOMContentLoaded", () => {
    loadNaikKelasData();
    loadPreviewNaikKelas();
    
    if (tahunSelect) {
        tahunSelect.addEventListener("change", () => {
            loadNaikKelasData();
            loadPreviewNaikKelas();
        });
    }
});

async function loadNaikKelasData() {
    const idTahun = tahunSelect ? tahunSelect.value : 0;
    let url = "naik_kelas_data.php";
    if (idTahun) url += "?id_tahun_ajaran=" + idTahun;
    
    try {
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            showMessage(result.message || "Gagal memuat data.", "error");
            return;
        }

        currentIdTahunAjaran = result.id_tahun_ajaran_terpilih || 0;
        renderSummary(result.summary);
        renderTahunAjaran(result.tahun_ajaran, result.id_tahun_ajaran_terpilih);
        renderWaliKelas(result.kelas, result.guru);
    } catch (error) {
        showMessage("Terjadi kesalahan saat memuat data.", "error");
    }
}

async function loadPreviewNaikKelas() {
    if (!previewNaikBody || !previewSummary) return;

    const idTahun = tahunSelect ? tahunSelect.value : 0;
    let url = "preview_naik_kelas.php";
    if (idTahun) url += "?id_tahun_ajaran=" + idTahun;

    try {
        const response = await fetch(url);
        const result = await response.json();

        if (!result.success) {
            previewNaikBody.innerHTML = `<tr><td colspan="9" class="empty-cell">${result.message || "Gagal memuat preview."}</td></tr>`;
            previewSummary.textContent = "Preview kenaikan kelas gagal dimuat.";
            updatePreviewPaginationInfo(0, 0, 0);
            clearPreviewPaginationBtns();
            return;
        }

        previewData = Array.isArray(result.data) ? result.data : [];
        filteredPreviewData = [...previewData];
        currentPreviewPage = 1;

        renderPreviewSummary(result.summary);
        renderPreviewTable();
    } catch (error) {
        previewNaikBody.innerHTML = `<tr><td colspan="9" class="empty-cell">Terjadi kesalahan saat memuat preview.</td></tr>`;
        previewSummary.textContent = "Preview kenaikan kelas gagal dimuat.";
        updatePreviewPaginationInfo(0, 0, 0);
        clearPreviewPaginationBtns();
    }
}

function renderPreviewSummary(summary) {
    if (!previewSummary) return;
    previewSummary.innerHTML = `
        <strong>Ringkasan:</strong>
        Naik Kelas: ${summary?.naik_kelas || 0} siswa,
        Tidak Naik Kelas: ${summary?.tidak_naik || 0} siswa,
        Lulus: ${summary?.lulus || 0} siswa,
        Siswa Baru: ${summary?.siswa_baru || 0} siswa.
    `;
}

function renderPreviewTable() {
    if (!previewNaikBody) return;

    if (!filteredPreviewData.length) {
        previewNaikBody.innerHTML = `<tr><td colspan="9" class="empty-cell">Data siswa tidak ditemukan.</td></tr>`;
        updatePreviewPaginationInfo(0, 0, 0);
        clearPreviewPaginationBtns();
        return;
    }

    const totalData = filteredPreviewData.length;
    const totalPages = Math.ceil(totalData / previewRowsPerPage);
    if (currentPreviewPage > totalPages) currentPreviewPage = totalPages;

    const startIndex = (currentPreviewPage - 1) * previewRowsPerPage;
    const endIndex = startIndex + previewRowsPerPage;
    const paginatedData = filteredPreviewData.slice(startIndex, endIndex);

    previewNaikBody.innerHTML = "";
    paginatedData.forEach((item) => {
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
            <td><span class="status-badge ${statusClass}">${escapeHtml(item.status_kenaikan)}</span></td>
        `;
        previewNaikBody.appendChild(tr);
    });

    updatePreviewPaginationInfo(startIndex + 1, Math.min(endIndex, totalData), totalData);
    renderPreviewPaginationBtns(totalPages);
}

function updatePreviewPaginationInfo(start, end, total) {
    if (previewPaginationInfo) {
        previewPaginationInfo.textContent = `Menampilkan ${start} sampai ${end} dari ${total} siswa`;
    }
}

function clearPreviewPaginationBtns() {
    if (previewPaginationBtns) previewPaginationBtns.innerHTML = "";
}

function renderPreviewPaginationBtns(totalPages) {
    if (!previewPaginationBtns) return;
    previewPaginationBtns.innerHTML = "";

    const prevBtn = document.createElement("button");
    prevBtn.type = "button";
    prevBtn.className = "preview-page-btn";
    prevBtn.textContent = "Prev";
    prevBtn.disabled = currentPreviewPage === 1;
    prevBtn.addEventListener("click", () => {
        if (currentPreviewPage > 1) {
            currentPreviewPage--;
            renderPreviewTable();
        }
    });
    previewPaginationBtns.appendChild(prevBtn);

    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPreviewPage - 2);
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let page = startPage; page <= endPage; page++) {
        const pageBtn = document.createElement("button");
        pageBtn.type = "button";
        pageBtn.className = `preview-page-btn ${page === currentPreviewPage ? "active" : ""}`;
        pageBtn.textContent = page;
        pageBtn.addEventListener("click", () => {
            currentPreviewPage = page;
            renderPreviewTable();
        });
        previewPaginationBtns.appendChild(pageBtn);
    }

    const nextBtn = document.createElement("button");
    nextBtn.type = "button";
    nextBtn.className = "preview-page-btn";
    nextBtn.textContent = "Next";
    nextBtn.disabled = currentPreviewPage === totalPages;
    nextBtn.addEventListener("click", () => {
        if (currentPreviewPage < totalPages) {
            currentPreviewPage++;
            renderPreviewTable();
        }
    });
    previewPaginationBtns.appendChild(nextBtn);
}

if (searchPreview) {
    searchPreview.addEventListener("input", () => {
        const keyword = searchPreview.value.trim().toLowerCase();
        filteredPreviewData = previewData.filter((item) => {
            const searchableText = [
                item.nama, item.kelas_lama, item.kelas_baru, item.rata_rata,
                item.mapel_tidak_lulus, item.izin, item.sakit, item.alfa, item.status_kenaikan
            ].join(" ").toLowerCase();
            return searchableText.includes(keyword);
        });
        currentPreviewPage = 1;
        renderPreviewTable();
    });
}

function getStatusClass(status) {
    if (status === "Naik Kelas") return "status-naik";
    if (status === "Tidak Naik Kelas") return "status-tidak-naik";
    if (status === "Lulus") return "status-lulus";
    if (status === "Siswa Baru") return "status-baru";
    return "";
}

function escapeHtml(value) {
    return String(value ?? "").replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
}

function renderSummary(summary) {
    jumlahSiswaBaru.textContent = summary.siswa_baru || 0;
    jumlahSiswaAktif.textContent = summary.siswa_aktif || 0;
    jumlahKelas9.textContent = summary.kelas_9 || 0;
}

function renderTahunAjaran(data, selectedId) {
    if (!tahunSelect) return;
    tahunSelect.innerHTML = '<option value="">-- Pilih Tahun Ajaran Baru --</option>';
    data.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.id_tahun_ajaran;
        option.textContent = item.tahun_ajaran + (item.status === "aktif" ? " (Aktif)" : "");
        if (selectedId && selectedId == item.id_tahun_ajaran) option.selected = true;
        tahunSelect.appendChild(option);
    });
}

async function hapusKelas(idKelas, namaKelas) {
    const result = await Swal.fire({
        title: 'Hapus Kelas?',
        text: `Apakah Anda yakin ingin menghapus kelas ${namaKelas}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch('ajax_hapus_kelas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_kelas=${idKelas}`
        });
        const data = await response.json();
        if (data.success) {
            Swal.fire('Terhapus', data.message, 'success');
            loadNaikKelasData(); // reload grid
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Terjadi kesalahan saat menghapus kelas.', 'error');
    }
}

function renderWaliKelas(kelas, guru) {
    if (!waliKelasGrid) return;
    if (!kelas.length) {
        waliKelasGrid.innerHTML = `<div class="empty-cell">Data kelas belum tersedia untuk tahun ajaran ini.</div>`;
        return;
    }

    waliKelasGrid.innerHTML = "";
    kelas.forEach((item) => {
        const group = document.createElement("div");
        group.className = "form-group";

        const label = document.createElement("label");
        label.textContent = `Wali Kelas ${item.nama_kelas}`;

        const select = document.createElement("select");
        select.name = `wali_kelas[${item.id_kelas}]`;
        select.required = true;
        select.setAttribute("data-kelas-id", item.id_kelas);

        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = "-- Pilih Guru --";
        select.appendChild(defaultOption);

        guru.forEach((g) => {
            const option = document.createElement("option");
            option.value = g.id_guru;
            option.textContent = g.nip ? `${g.nama} - ${g.nip}` : g.nama;
            if (String(item.id_wali_kelas || "") === String(g.id_guru)) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        const kapasitasInput = document.createElement("input");
        kapasitasInput.type = "number";
        kapasitasInput.name = `kapasitas[${item.id_kelas}]`;
        kapasitasInput.value = item.kapasitas || 30;
        kapasitasInput.min = 1;
        kapasitasInput.max = 60;
        kapasitasInput.placeholder = "Kapasitas Siswa";
        kapasitasInput.className = "kapasitas-input";
        kapasitasInput.style.width = "100%";
        kapasitasInput.style.marginTop = "8px";
        kapasitasInput.style.padding = "8px";
        kapasitasInput.style.borderRadius = "8px";
        kapasitasInput.style.border = "1px solid #ccc";

        // Tombol hapus
        const deleteBtn = document.createElement("button");
        deleteBtn.type = "button";
        deleteBtn.textContent = "Hapus Kelas";
        deleteBtn.className = "btn-hapus-kelas";
        deleteBtn.style.width = "100%";
        deleteBtn.style.marginTop = "8px";
        deleteBtn.style.padding = "8px";
        deleteBtn.style.borderRadius = "8px";
        deleteBtn.style.border = "1px solid #dc3545";
        deleteBtn.style.backgroundColor = "#fff";
        deleteBtn.style.color = "#dc3545";
        deleteBtn.style.cursor = "pointer";
        deleteBtn.setAttribute("data-id-kelas", item.id_kelas);
        deleteBtn.setAttribute("data-nama-kelas", item.nama_kelas);
        deleteBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            hapusKelas(item.id_kelas, item.nama_kelas);
        });

        group.appendChild(label);
        group.appendChild(select);
        group.appendChild(kapasitasInput);
        group.appendChild(deleteBtn);
        waliKelasGrid.appendChild(group);
    });
}

naikKelasForm.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!tahunSelect || !tahunSelect.value) {
        showMessage("Tahun ajaran baru wajib dipilih.", "error");
        return;
    }
    pendingFormData = new FormData(naikKelasForm);
    //openPopup();
     confirmProcessBtn.click(); 
});

confirmProcessBtn.addEventListener("click", async () => {
    if (!pendingFormData) {
        Swal.fire({
            icon: 'warning',
            title: 'Peringatan',
            text: 'Data tidak lengkap. Silakan isi form terlebih dahulu.',
            confirmButtonColor: '#064e4b'
        });
        return;
    }
    
    Swal.fire({
        title: 'Proses Naik Kelas?',
        html: `
            <div style="text-align:left">
                <p>Pastikan:</p>
                <ul style="text-align:left">
                    <li>✓ Semua wali kelas sudah dipilih</li>
                    <li>✓ Data nilai siswa sudah lengkap</li>
                    <li>✓ Kapasitas kelas sudah sesuai</li>
                </ul>
                <p style="color:red"><strong>⚠️ Tindakan ini TIDAK BISA DIBATALKAN!</strong></p>
                <div style="margin-top:15px">
                    <label>Ketik <span style="color:#d33">NAIKKELAS</span> untuk melanjutkan:</label>
                    <input type="text" id="swalConfirmText" class="swal2-input" placeholder="NAIKKELAS">
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Proses Sekarang!',
        cancelButtonText: 'Batal',
        preConfirm: () => {
            const input = document.getElementById('swalConfirmText');
            if (!input || input.value !== 'NAIKKELAS') {
                Swal.showValidationMessage('Harus ketik NAIKKELAS!');
                return false;
            }
            return true;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            
            try {
                const response = await fetch("proses_naik_kelas.php", {
                    method: "POST",
                    body: pendingFormData
                });
                const resultData = await response.json();
                
                if (resultData.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: resultData.message,
                        confirmButtonColor: '#064e4b'
                    }).then(() => {
                        pendingFormData = null;
                        loadNaikKelasData();
                        loadPreviewNaikKelas();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: resultData.message,
                        confirmButtonColor: '#d33'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan: ' + error,
                    confirmButtonColor: '#d33'
                });
            }
        }
    });
});

closeConfirmPopup.addEventListener("click", closePopup);
cancelConfirmBtn.addEventListener("click", closePopup);

function openPopup() {
    confirmPopup.classList.add("active");
    if (confirmText) {
        confirmText.value = "";
        setTimeout(() => confirmText.focus(), 100);
    }
    if (confirmMessage) confirmMessage.textContent = "";
}

function closePopup() {
    confirmPopup.classList.remove("active");
    pendingFormData = null;
    if (confirmText) confirmText.value = "";
    if (confirmMessage) confirmMessage.textContent = "";
}

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}

function showConfirmMessage(message) {
    if (confirmMessage) confirmMessage.textContent = message;
    else showMessage(message, "error");
}

function openTahunAjaranPopup() {
    if (tahunAjaranMessage) {
        tahunAjaranMessage.textContent = "";
        tahunAjaranMessage.className = "confirm-message";
    }
    if (tahunAjaranPopup) tahunAjaranPopup.classList.add("active");
}

function closeTahunAjaranModal() {
    if (tahunAjaranPopup) tahunAjaranPopup.classList.remove("active");
}

if (buatTahunBtn) buatTahunBtn.addEventListener("click", openTahunAjaranPopup);
if (closeTahunAjaranPopup) closeTahunAjaranPopup.addEventListener("click", closeTahunAjaranModal);
if (cancelTahunAjaranBtn) cancelTahunAjaranBtn.addEventListener("click", closeTahunAjaranModal);
if (tahunAjaranPopup) {
    tahunAjaranPopup.addEventListener("click", (event) => {
        if (event.target === tahunAjaranPopup) closeTahunAjaranModal();
    });
}
if (confirmTahunAjaranBtn) {
    confirmTahunAjaranBtn.addEventListener("click", async () => {
        try {
            confirmTahunAjaranBtn.disabled = true;
            confirmTahunAjaranBtn.textContent = "Membuat...";
            if (tahunAjaranMessage) {
                tahunAjaranMessage.textContent = "Sedang membuat tahun ajaran berikutnya...";
                tahunAjaranMessage.className = "confirm-message";
            }
            const response = await fetch("buat_tahun_ajaran.php", { method: "POST" });
            const result = await response.json();
            if (!result.success) {
                if (tahunAjaranMessage) {
                    tahunAjaranMessage.textContent = result.message || "Gagal membuat tahun ajaran.";
                    tahunAjaranMessage.className = "confirm-message error";
                }
                return;
            }
            if (tahunAjaranMessage) {
                tahunAjaranMessage.textContent = result.message || "Tahun ajaran berhasil dibuat.";
                tahunAjaranMessage.className = "confirm-message success";
            }
            await loadNaikKelasData();
            await loadPreviewNaikKelas();
            setTimeout(() => closeTahunAjaranModal(), 800);
        } catch (error) {
            if (tahunAjaranMessage) {
                tahunAjaranMessage.textContent = "Gagal membuat tahun ajaran: " + error.message;
                tahunAjaranMessage.className = "confirm-message error";
            }
        } finally {
            confirmTahunAjaranBtn.disabled = false;
            confirmTahunAjaranBtn.textContent = "Ya, Buat";
        }
    });
}