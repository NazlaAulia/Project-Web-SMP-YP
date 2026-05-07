const tahunSelect = document.getElementById("id_tahun_ajaran");
const waliKelasGrid = document.getElementById("waliKelasGrid");
const naikKelasForm = document.getElementById("naikKelasForm");
const formMessage = document.getElementById("formMessage");

const jumlahSiswaBaru = document.getElementById("jumlahSiswaBaru");
const jumlahSiswaAktif = document.getElementById("jumlahSiswaAktif");
const jumlahKelas9 = document.getElementById("jumlahKelas9");

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

document.addEventListener("DOMContentLoaded", () => {
    loadNaikKelasData();
});

async function loadNaikKelasData() {
    try {
        const response = await fetch("naik_kelas_data.php");
        const result = await response.json();

        if (!result.success) {
            showMessage(result.message || "Gagal memuat data.", "error");
            return;
        }

        renderSummary(result.summary);
        renderTahunAjaran(result.tahun_ajaran);
        renderWaliKelas(result.kelas, result.guru);
    } catch (error) {
        showMessage("Terjadi kesalahan saat memuat data.", "error");
    }
}

function renderSummary(summary) {
    jumlahSiswaBaru.textContent = summary.siswa_baru || 0;
    jumlahSiswaAktif.textContent = summary.siswa_aktif || 0;
    jumlahKelas9.textContent = summary.kelas_9 || 0;
}

function renderTahunAjaran(data) {
    tahunSelect.innerHTML = `<option value="">-- Pilih Tahun Ajaran Baru --</option>`;

    const tahunAktif = data.find((item) => item.status === "aktif");
    const awalTahunAktif = tahunAktif ? getAwalTahunAjaran(tahunAktif.tahun_ajaran) : 0;

    const calonTahunBaru = data.filter((item) => {
        const awalTahun = getAwalTahunAjaran(item.tahun_ajaran);
        return item.status !== "aktif" && awalTahun > awalTahunAktif;
    });

    if (!calonTahunBaru.length) {
        const option = document.createElement("option");
        option.value = "";
        option.textContent = "Belum ada tahun ajaran berikutnya";
        option.disabled = true;
        tahunSelect.appendChild(option);
        return;
    }

    calonTahunBaru.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.id_tahun_ajaran;
        option.textContent = item.tahun_ajaran;
        tahunSelect.appendChild(option);
    });
}

function getAwalTahunAjaran(tahunAjaran) {
    const match = String(tahunAjaran || "").match(/^(\d{4})[/-](\d{4})$/);

    if (!match) {
        return 0;
    }

    return Number(match[1]);
}

function renderWaliKelas(kelas, guru) {
    if (!kelas.length) {
        waliKelasGrid.innerHTML = `<div class="empty-cell">Data kelas belum tersedia.</div>`;
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

        group.appendChild(label);
        group.appendChild(select);

        waliKelasGrid.appendChild(group);
    });
}

naikKelasForm.addEventListener("submit", (event) => {
    event.preventDefault();

    if (!tahunSelect.value) {
        showMessage("Tahun ajaran baru wajib dipilih.", "error");
        return;
    }

    pendingFormData = new FormData(naikKelasForm);
    openPopup();
});

confirmProcessBtn.addEventListener("click", async () => {
    if (!pendingFormData) return;

    if (!confirmText || confirmText.value.trim().toUpperCase() !== "NAIKKELAS") {
        showConfirmMessage("Ketik NAIKKELAS dulu untuk melanjutkan.");
        return;
    }

    confirmProcessBtn.disabled = true;
    confirmProcessBtn.textContent = "Memproses...";

    try {
        const response = await fetch("proses_naik_kelas.php", {
            method: "POST",
            body: pendingFormData
        });

        const result = await response.json();

        if (result.success) {
            closePopup();
            showMessage(result.message || "Proses naik kelas berhasil.", "success");
            loadNaikKelasData();
        } else {
            showConfirmMessage(result.message || "Proses naik kelas gagal.");
        }
    } catch (error) {
        showConfirmMessage("Terjadi kesalahan server saat proses naik kelas.");
    } finally {
        confirmProcessBtn.disabled = false;
        confirmProcessBtn.textContent = "Ya, Proses";
        pendingFormData = null;
    }
});

closeConfirmPopup.addEventListener("click", closePopup);
cancelConfirmBtn.addEventListener("click", closePopup);

function openPopup() {
    confirmPopup.classList.add("active");

    if (confirmText) {
        confirmText.value = "";
        setTimeout(() => confirmText.focus(), 100);
    }

    if (confirmMessage) {
        confirmMessage.textContent = "";
    }
}

function closePopup() {
    confirmPopup.classList.remove("active");
    pendingFormData = null;

    if (confirmText) {
        confirmText.value = "";
    }

    if (confirmMessage) {
        confirmMessage.textContent = "";
    }
}

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}

function showConfirmMessage(message) {
    if (confirmMessage) {
        confirmMessage.textContent = message;
    } else {
        showMessage(message, "error");
    }
}

function openTahunAjaranPopup() {
    if (tahunAjaranMessage) {
        tahunAjaranMessage.textContent = "";
        tahunAjaranMessage.className = "confirm-message";
    }

    if (tahunAjaranPopup) {
        tahunAjaranPopup.classList.add("active");
    }
}

function closeTahunAjaranModal() {
    if (tahunAjaranPopup) {
        tahunAjaranPopup.classList.remove("active");
    }
}

if (buatTahunBtn) {
    buatTahunBtn.addEventListener("click", openTahunAjaranPopup);
}

if (closeTahunAjaranPopup) {
    closeTahunAjaranPopup.addEventListener("click", closeTahunAjaranModal);
}

if (cancelTahunAjaranBtn) {
    cancelTahunAjaranBtn.addEventListener("click", closeTahunAjaranModal);
}

if (tahunAjaranPopup) {
    tahunAjaranPopup.addEventListener("click", (event) => {
        if (event.target === tahunAjaranPopup) {
            closeTahunAjaranModal();
        }
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

            const response = await fetch("buat_tahun_ajaran.php", {
                method: "POST"
            });

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

            setTimeout(() => {
                closeTahunAjaranModal();
            }, 800);
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
