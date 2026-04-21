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

document.addEventListener("DOMContentLoaded", () => {
    loadGuru();
    loadMapel();
});

if (openModalBtn) {
    openModalBtn.addEventListener("click", () => {
        guruModal.classList.add("active");
        formMessage.textContent = "";
        formMessage.className = "form-message";
        formTambahGuru.reset();
    });
}

if (closeModalBtn) {
    closeModalBtn.addEventListener("click", () => {
        guruModal.classList.remove("active");
    });
}

if (cancelModalBtn) {
    cancelModalBtn.addEventListener("click", () => {
        guruModal.classList.remove("active");
    });
}

if (guruModal) {
    guruModal.addEventListener("click", (e) => {
        if (e.target === guruModal) {
            guruModal.classList.remove("active");
        }
    });
}

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

if (formTambahGuru) {
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
}

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
        guruTableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-cell">${error.message}</td>
            </tr>
        `;

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

function renderGuru() {
    const paginationInfo = document.getElementById("paginationInfo");
    const paginationBtns = document.getElementById("paginationBtns");

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
                    <button class="btn-edit" onclick="editGuru(${guru.id_guru})">Edit</button>
                    <button class="btn-danger" onclick="hapusGuru(${guru.id_guru}, '${escapeJs(guru.nama)}')">Hapus</button>
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