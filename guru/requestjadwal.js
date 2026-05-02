const titleRectangle = document.getElementById("titleRectangle");
const requestChip = document.getElementById("requestChip");
const requestFormCard = document.getElementById("requestFormCard");
const guideCard = document.getElementById("guideCard");
const aiBox = document.getElementById("aiBox");

const formGantiJadwal = document.getElementById("formGantiJadwal");
const jadwalLama = document.getElementById("jadwalLama");
const hariBaru = document.getElementById("hariBaru");
const jamBaru = document.getElementById("jamBaru");
const alasanGanti = document.getElementById("alasanGanti");
const btnGenerateAI = document.getElementById("btnGenerateAI");
const btnResetForm = document.getElementById("btnResetForm");
const btnKirimRequest = document.getElementById("btnKirimRequest");
const isiHasilAI = document.getElementById("isiHasilAI");

let jadwalLamaData = null;
let rekomendasiAI = [];
let rekomendasiDipilih = null;

function replayAnimation(element, className) {
    if (!element) return;
    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
}

window.addEventListener("load", function () {
    setTimeout(() => replayAnimation(titleRectangle, "rect-animate"), 150);
    setTimeout(() => replayAnimation(requestChip, "chip-pop"), 250);

    if (requestFormCard) requestFormCard.classList.add("section-show");
    if (guideCard) guideCard.classList.add("section-show");
});

if (titleRectangle) {
    titleRectangle.addEventListener("click", function () {
        replayAnimation(titleRectangle, "rect-animate");
    });
}

if (requestChip) {
    requestChip.addEventListener("click", function () {
        replayAnimation(requestChip, "chip-pop");
    });
}

if (aiBox) {
    aiBox.addEventListener("click", function () {
        replayAnimation(aiBox, "chip-pop");
    });
}

document.addEventListener("DOMContentLoaded", async () => {
    const roleId = localStorage.getItem("role_id");
    const idGuru = localStorage.getItem("id_guru");

    if (roleId !== "2") {
        alert("Akses ditolak. Halaman ini khusus guru.");
        window.location.href = "../login.html";
        return;
    }

    if (!idGuru) {
        alert("ID guru tidak ditemukan. Silakan login ulang.");
        window.location.href = "../login.html";
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const idJadwal = params.get("id_jadwal");

    if (!idJadwal) {
        tampilkanHasilAI("Pilih jadwal dari halaman Jadwal Mengajar terlebih dahulu.");
        disableForm();
        return;
    }

    await loadDetailJadwal(idJadwal, idGuru);
});

async function loadDetailJadwal(idJadwal, idGuru) {
    try {
        tampilkanHasilAI("Sedang memuat detail jadwal lama...");

        const response = await fetch(`get_detail_jadwal.php?id_jadwal=${encodeURIComponent(idJadwal)}&id_guru=${encodeURIComponent(idGuru)}`);
        const result = await response.json();

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal mengambil detail jadwal.");
        }

        jadwalLamaData = result.data;

        renderJadwalLama(jadwalLamaData);
        resetPilihanBaru();

        tampilkanHasilAI("Klik tombol Generate AI untuk mendapatkan rekomendasi jadwal pengganti.");

    } catch (error) {
        console.error(error);
        tampilkanHasilAI(error.message || "Terjadi kesalahan saat memuat jadwal lama.");
        disableForm();
    }
}

function renderJadwalLama(data) {
    if (!jadwalLama) return;

    jadwalLama.innerHTML = "";

    const option = document.createElement("option");
    option.value = data.id_jadwal;
    option.selected = true;
    option.textContent = `${data.hari}, ${data.jam} - ${data.mapel} - Kelas ${data.kelas}`;
    jadwalLama.appendChild(option);

    jadwalLama.disabled = true;
}

function resetPilihanBaru() {
    rekomendasiAI = [];
    rekomendasiDipilih = null;

    if (hariBaru) {
        hariBaru.innerHTML = `<option value="">Pilih dari rekomendasi AI</option>`;
        hariBaru.disabled = true;
    }

    if (jamBaru) {
        jamBaru.innerHTML = `<option value="">Pilih dari rekomendasi AI</option>`;
        jamBaru.disabled = true;
    }
}

async function generateAIJadwal() {
    const idGuru = localStorage.getItem("id_guru");

    if (!jadwalLamaData || !jadwalLamaData.id_jadwal) {
        alert("Jadwal lama belum dimuat.");
        return;
    }

    try {
        btnGenerateAI.disabled = true;
        btnGenerateAI.innerHTML = `<i class="bi bi-hourglass-split"></i> Memproses...`;

        tampilkanHasilAI("Sistem sedang mencari slot kosong. Jika penuh, sistem akan mencari opsi tukar jadwal...");

        const response = await fetch(
            `get_rekomendasi_ganti_jadwal.php?id_jadwal=${encodeURIComponent(jadwalLamaData.id_jadwal)}&id_guru=${encodeURIComponent(idGuru)}`
        );

        const result = await response.json();

        if (result.status !== "success") {
            throw new Error(result.message || "Gagal membuat rekomendasi.");
        }

        rekomendasiAI = result.data || [];

        if (rekomendasiAI.length === 0) {
            tampilkanHasilAI("Tidak ada rekomendasi jadwal yang tersedia.");
            return;
        }

        renderRekomendasiAI(rekomendasiAI, result.mode);

    } catch (error) {
        console.error(error);
        tampilkanHasilAI(error.message || "Terjadi kesalahan saat Generate AI.");
    } finally {
        btnGenerateAI.disabled = false;
        btnGenerateAI.innerHTML = `<i class="bi bi-magic"></i> Generate AI`;
    }
}

function renderRekomendasiAI(list, mode) {
    if (!isiHasilAI) return;

    const modeText = mode === "tukar"
        ? "Tidak ada slot kosong. Berikut opsi tukar jadwal yang aman:"
        : "Berikut slot kosong yang aman dan tidak bentrok:";

    isiHasilAI.innerHTML = `
        <div style="margin-bottom:12px; font-size:13px; color:#0f766e; font-weight:700;">
            ${escapeHtml(modeText)}
        </div>

        <div style="display:grid; gap:12px;">
            ${list.map((item, index) => {
                const tipe = item.tipe_request || "slot_kosong";
                const isTukar = tipe === "tukar";

                return `
                    <label 
                        class="ai-option-card"
                        style="
                            display:block;
                            padding:14px;
                            border:1px solid #d1d5db;
                            border-radius:16px;
                            background:#ffffff;
                            cursor:pointer;
                            transition:.2s ease;
                        "
                    >
                        <div style="display:flex; gap:10px; align-items:flex-start;">
                            <input 
                                type="radio" 
                                name="pilihan_rekomendasi_ai" 
                                value="${index}"
                                style="margin-top:4px;"
                            >

                            <div>
                                <strong style="display:block; color:#0f766e; margin-bottom:4px;">
                                    ${isTukar ? "Tukar Jadwal" : "Slot Kosong"}: 
                                    ${escapeHtml(item.hari)}, ${escapeHtml(item.jam)}
                                </strong>

                                <div style="font-size:13px; color:#374151; margin-bottom:5px;">
                                    JP ${escapeHtml(item.jp_mulai)}-${escapeHtml(item.jp_selesai)}
                                    | ${escapeHtml(item.jumlah_jp)} JP
                                </div>

                                ${isTukar ? `
                                    <div style="font-size:13px; color:#374151; margin-bottom:5px;">
                                        Ditukar dengan: 
                                        <strong>${escapeHtml(item.mapel_tukar || "-")}</strong>
                                        oleh ${escapeHtml(item.guru_tukar || "-")}
                                    </div>
                                ` : ""}

                                <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.5;">
                                    ${escapeHtml(item.pesan_ai || "Rekomendasi ini aman dan tidak bentrok.")}
                                </p>
                            </div>
                        </div>
                    </label>
                `;
            }).join("")}
        </div>
    `;

    document.querySelectorAll("input[name='pilihan_rekomendasi_ai']").forEach(radio => {
        radio.addEventListener("change", function () {
            const index = Number(this.value);
            pilihRekomendasi(index);
        });
    });
}

function pilihRekomendasi(index) {
    const item = rekomendasiAI[index];

    if (!item) {
        alert("Rekomendasi tidak valid.");
        return;
    }

    rekomendasiDipilih = item;

    if (hariBaru) {
        hariBaru.innerHTML = `<option value="${escapeHtml(item.hari)}">${escapeHtml(item.hari)}</option>`;
        hariBaru.disabled = true;
    }

    if (jamBaru) {
        const labelJam = item.tipe_request === "tukar"
            ? `${item.jam} (Tukar jadwal)`
            : item.jam;

        jamBaru.innerHTML = `<option value="${escapeHtml(item.jam)}">${escapeHtml(labelJam)}</option>`;
        jamBaru.disabled = true;
    }
}

async function kirimPengajuan(e) {
    e.preventDefault();

    const idGuru = localStorage.getItem("id_guru");

    if (!jadwalLamaData) {
        alert("Jadwal lama belum dimuat.");
        return;
    }

    if (!rekomendasiDipilih) {
        alert("Pilih salah satu rekomendasi terlebih dahulu.");
        return;
    }

    const alasan = alasanGanti ? alasanGanti.value.trim() : "";

    if (alasan === "") {
        alert("Alasan ganti jadwal wajib diisi.");
        return;
    }

    const payload = {
        id_guru: Number(idGuru),
        id_jadwal: Number(jadwalLamaData.id_jadwal),
        id_kelas: Number(jadwalLamaData.id_kelas),

        hari_baru: rekomendasiDipilih.hari,
        jam_baru: rekomendasiDipilih.jam,
        jp_mulai_baru: Number(rekomendasiDipilih.jp_mulai),
        jp_selesai_baru: Number(rekomendasiDipilih.jp_selesai),
        jumlah_jp_baru: Number(rekomendasiDipilih.jumlah_jp),

        alasan: alasan,
        pesan_ai: rekomendasiDipilih.pesan_ai || "",

        tipe_request: rekomendasiDipilih.tipe_request || "slot_kosong",
        id_jadwal_tukar: rekomendasiDipilih.id_jadwal_tukar || null
    };

    try {
        btnKirimRequest.disabled = true;
        btnKirimRequest.innerHTML = `<i class="bi bi-hourglass-split"></i> Mengirim...`;

        const response = await fetch("simpan_request_jadwal.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (result.status !== "success") {
            throw new Error(result.message || "Pengajuan gagal dikirim.");
        }

        alert("Pengajuan ganti jadwal berhasil dikirim ke admin.");
        window.location.href = "jadwalmengajar.html";

    } catch (error) {
        console.error(error);
        alert(error.message || "Terjadi kesalahan saat mengirim pengajuan.");
    } finally {
        btnKirimRequest.disabled = false;
        btnKirimRequest.innerHTML = `<i class="bi bi-send"></i> Kirim Pengajuan`;
    }
}

function resetFormRequest() {
    if (alasanGanti) alasanGanti.value = "";
    resetPilihanBaru();
    tampilkanHasilAI("Klik tombol Generate AI untuk mendapatkan rekomendasi jadwal pengganti.");
}

function tampilkanHasilAI(text) {
    if (isiHasilAI) {
        isiHasilAI.textContent = text;
    }
}

function disableForm() {
    if (btnGenerateAI) btnGenerateAI.disabled = true;
    if (btnKirimRequest) btnKirimRequest.disabled = true;
    if (jadwalLama) jadwalLama.disabled = true;
    if (hariBaru) hariBaru.disabled = true;
    if (jamBaru) jamBaru.disabled = true;
    if (alasanGanti) alasanGanti.disabled = true;
}

function escapeHtml(value) {
    if (value === null || value === undefined) return "-";

    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

if (btnGenerateAI) {
    btnGenerateAI.addEventListener("click", generateAIJadwal);
}

if (btnResetForm) {
    btnResetForm.addEventListener("click", resetFormRequest);
}

if (formGantiJadwal) {
    formGantiJadwal.addEventListener("submit", kirimPengajuan);
}