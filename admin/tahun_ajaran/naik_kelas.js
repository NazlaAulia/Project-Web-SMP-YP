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
    tahunSelect.innerHTML = `<option value="">-- Pilih Tahun Ajaran --</option>`;

    data.forEach((item) => {
        const option = document.createElement("option");
        option.value = item.id_tahun_ajaran;
        option.textContent = item.tahun_ajaran + (item.status === "aktif" ? " - Aktif sekarang" : "");
        tahunSelect.appendChild(option);
    });
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
            showMessage(result.message || "Proses naik kelas gagal.", "error");
        }
    } catch (error) {
        showMessage("Terjadi kesalahan server saat proses naik kelas.", "error");
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
}

function closePopup() {
    confirmPopup.classList.remove("active");
}

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}