const guruTableBody = document.getElementById("guruTableBody");
const searchGuru = document.getElementById("searchGuru");

const guruModal = document.getElementById("guruModal");
const openModalBtn = document.getElementById("openModalBtn");
const closeModalBtn = document.getElementById("closeModalBtn");
const cancelModalBtn = document.getElementById("cancelModalBtn");

const formTambahGuru = document.getElementById("formTambahGuru");
const formMessage = document.getElementById("formMessage");
const mapelSelect = document.getElementById("id_mapel");

let semuaGuru = [];

document.addEventListener("DOMContentLoaded", () => {
    loadGuru();
    loadMapel();
});

openModalBtn.addEventListener("click", () => {
    guruModal.classList.add("active");
    formMessage.textContent = "";
    formMessage.className = "form-message";
    formTambahGuru.reset();
});

closeModalBtn.addEventListener("click", () => {
    guruModal.classList.remove("active");
});

cancelModalBtn.addEventListener("click", () => {
    guruModal.classList.remove("active");
});

guruModal.addEventListener("click", (e) => {
    if (e.target === guruModal) {
        guruModal.classList.remove("active");
    }
});

searchGuru.addEventListener("input", function () {
    const keyword = this.value.toLowerCase().trim();

    const filtered = semuaGuru.filter(guru =>
        (guru.nama || "").toLowerCase().includes(keyword) ||
        (guru.email || "").toLowerCase().includes(keyword) ||
        (guru.username || "").toLowerCase().includes(keyword) ||
        (guru.nip || "").toLowerCase().includes(keyword) ||
        (guru.nama_mapel || "").toLowerCase().includes(keyword) ||
        (guru.wali_kelas || "").toLowerCase().includes(keyword)
    );

    renderGuru(filtered);
});

formTambahGuru.addEventListener("submit", async function (e) {
    e.preventDefault();

    formMessage.textContent = "Menyimpan data guru...";
    formMessage.className = "form-message";

    const formData = new FormData(formTambahGuru);

    try {
        const response = await fetch("tambah_guru.php", {
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
            formMessage.textContent = result.message || "Gagal menambah guru.";
            formMessage.className = "form-message error";
            return;
        }

        formMessage.textContent = result.message;
        formMessage.className = "form-message success";

        await loadGuru();

        setTimeout(() => {
            guruModal.classList.remove("active");
            formTambahGuru.reset();
            formMessage.textContent = "";
            formMessage.className = "form-message";
        }, 900);

    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.className = "form-message error";
    }
});

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
        renderGuru(semuaGuru);

    } catch (error) {
        guruTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">${error.message}</td>
            </tr>
        `;
    }
}

function renderGuru(data) {
    if (!data.length) {
        guruTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">Data guru tidak ditemukan.</td>
            </tr>
        `;
        return;
    }

    guruTableBody.innerHTML = data.map(guru => `
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
                    <button class="btn-edit" onclick="editGuru(${guru.id_guru})">Edit</button>
                    <button class="btn-danger" onclick="hapusGuru(${guru.id_guru}, '${escapeJs(guru.nama)}')">Hapus</button>
                </div>
            </td>
        </tr>
    `).join("");
}

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

async function hapusGuru(idGuru, namaGuru) {
    const oke = confirm(`Yakin ingin menghapus guru ${namaGuru}?`);
    if (!oke) return;

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
            alert(result.message || "Gagal menghapus guru.");
            return;
        }

        alert(result.message);
        await loadGuru();

    } catch (error) {
        alert(error.message);
    }
}

function editGuru(idGuru) {
    alert("Fitur edit bisa kita lanjutkan setelah list dan tambah guru aman.");
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
    return String(text).replaceAll("\\", "\\\\").replaceAll("'", "\\'");
}