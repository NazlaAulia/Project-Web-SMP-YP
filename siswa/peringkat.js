const tableBody = document.getElementById("tableBody");
const namaText = document.getElementById("namaText");
const kelasText = document.getElementById("kelasText");
const avatarText = document.getElementById("avatarText");

const peringkatSaatIni = document.getElementById("peringkatSaatIni");
const kelasCard = document.getElementById("kelasCard");
const nilaiRataRata = document.getElementById("nilaiRataRata");

let data = [];
let namaLogin = "";
let idLogin = "";

// Variabel untuk pagination
let currentPage = 1;
const itemsPerPage = 5; // Jumlah baris per halaman

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  if (namaText) namaText.textContent = nama;
  if (kelasText) kelasText.textContent = kelas;
  if (avatarText) avatarText.textContent = avatar;
}

async function loadTahunAjaran() {
  try {
    const response = await fetch(`get_peringkat.php?action=get_tahun_ajaran`, {
      method: "GET",
      credentials: "same-origin"
    });
    
    const result = await response.json();
    
    if (result.success && result.tahun_ajaran_list) {
      const tahunSelect = document.getElementById("tahunAjaran");
      if (tahunSelect) {
        tahunSelect.innerHTML = result.tahun_ajaran_list.map(ta => 
          `<option value="${ta.id_tahun_ajaran}" ${ta.status === 'aktif' ? 'selected' : ''}>
            ${ta.tahun_ajaran}
          </option>`
        ).join("");
        
        tahunSelect.addEventListener("change", () => {
          currentPage = 1;
          loadPeringkat();
        });
      }
    }
  } catch (error) {
    console.error("Error loading tahun ajaran:", error);
  }
}

async function loadPeringkat() {
  try {
    const kelas = localStorage.getItem("kelas_siswa") || "";
    const idSiswa = localStorage.getItem("id_siswa") || "";
    const tahunAjaran = document.getElementById("tahunAjaran")?.value || "";
    const semester = document.getElementById("semester")?.value || "2";

    const response = await fetch(
      `get_peringkat.php?id_siswa=${encodeURIComponent(idSiswa)}&kelas=${encodeURIComponent(kelas)}&semester=${encodeURIComponent(semester)}&tahun_ajaran=${encodeURIComponent(tahunAjaran)}`,
      {
        method: "GET",
        credentials: "same-origin"
      }
    );

    const text = await response.text();
    console.log("RAW get_peringkat:", text);

    const result = JSON.parse(text);
    console.log("JSON get_peringkat:", result);

    if (!result.success) {
      alert(result.message || "Gagal mengambil data peringkat");
      return;
    }

    const siswa = result.siswa || {};
    data = result.ranking || [];

    namaLogin = siswa.nama || "";
    idLogin = String(siswa.id_siswa || "");

    if (siswa.nama) {
      localStorage.setItem("nama_siswa", siswa.nama);
    }

    if (siswa.kelas) {
      localStorage.setItem("kelas_siswa", siswa.kelas);
    }

    if (namaText) namaText.textContent = siswa.nama || "-";
    if (kelasText) kelasText.textContent = siswa.kelas || "-";
    if (avatarText) avatarText.textContent = (siswa.nama || "S").charAt(0).toUpperCase();

    if (peringkatSaatIni) peringkatSaatIni.textContent = siswa.rank ? `#${siswa.rank}` : "#-";
    if (kelasCard) kelasCard.textContent = `Kelas ${siswa.kelas || "-"}`;
    if (nilaiRataRata) nilaiRataRata.textContent = siswa.nilai || "-";

    // Reset ke halaman 1 setiap kali data baru dimuat
    currentPage = 1;
    renderTableWithPagination();
  } catch (error) {
    console.error("Error:", error);
    alert("Terjadi kesalahan saat mengambil data dari server");
  }
}

function getStatusBadge(status) {
  if (status === "naik") return '<span class="status-badge status-up">↑ Naik</span>';
  if (status === "turun") return '<span class="status-badge status-down">↓ Turun</span>';
  if (status === "tetap") return '<span class="status-badge status-steady">↔ Tetap</span>';
  if (status === "baru") return '<span class="status-badge status-new">● Baru</span>';
  return '<span class="status-badge status-na">-</span>';
}

// Fungsi baru untuk render tabel dengan pagination
function renderTableWithPagination() {
  if (!tableBody) return;
  
  const paginationContainer = document.getElementById("paginationContainer");

  if (data.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" style="text-align:center;">Data peringkat tidak ditemukan</td>
      </tr>
    `;
    if (paginationContainer) paginationContainer.style.display = "none";
    return;
  }

  // Hitung total halaman
  const totalPages = Math.ceil(data.length / itemsPerPage);
  
  // Tampilkan pagination hanya jika lebih dari 1 halaman
  if (paginationContainer) {
    if (totalPages > 1) {
      paginationContainer.style.display = "flex";
    } else {
      paginationContainer.style.display = "none";
    }
  }

  // Tentukan data yang akan ditampilkan di halaman ini
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentPageData = data.slice(startIndex, endIndex);

  // Render tabel untuk halaman saat ini
  tableBody.innerHTML = "";

  currentPageData.forEach((item) => {
    const row = document.createElement("tr");

    const isLoginUser =
      String(item.id_siswa) === idLogin || item.nama === namaLogin;

    const statusValue = item.status || "-";
    
    row.innerHTML = `
      <td>${item.rank}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td>${item.nilai}</td>
      <td>${getStatusBadge(statusValue)}</td>
    `;

    if (isLoginUser) {
      row.classList.add("active-row");
    }

    tableBody.appendChild(row);
  });

  // Update info pagination
  updatePaginationInfo(startIndex, endIndex, data.length);
  
  // Render tombol pagination
  renderPaginationButtons(totalPages);
}

function updatePaginationInfo(startIndex, endIndex, totalData) {
  const paginationInfo = document.getElementById("paginationInfo");
  if (!paginationInfo) return;
  
  const start = startIndex + 1;
  const end = Math.min(endIndex, totalData);
  
  paginationInfo.textContent = `Menampilkan ${start}-${end} dari ${totalData} data`;
}

function renderPaginationButtons(totalPages) {
  const pageNumbersDiv = document.getElementById("pageNumbers");
  const prevBtn = document.getElementById("prevPageBtn");
  const nextBtn = document.getElementById("nextPageBtn");
  
  if (!pageNumbersDiv) return;
  
  // Update state tombol prev/next
  if (prevBtn) prevBtn.disabled = (currentPage === 1);
  if (nextBtn) nextBtn.disabled = (currentPage === totalPages);
  
  // Generate tombol halaman
  let pageButtonsHtml = "";
  
  // Tentukan range halaman yang ditampilkan
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, currentPage + 2);
  
  // Tambahkan halaman pertama jika perlu
  if (startPage > 1) {
    pageButtonsHtml += `<button class="page-number" data-page="1">1</button>`;
    if (startPage > 2) {
      pageButtonsHtml += `<span class="pagination-ellipsis">...</span>`;
    }
  }
  
  // Halaman dalam range
  for (let i = startPage; i <= endPage; i++) {
    const activeClass = (i === currentPage) ? "active" : "";
    pageButtonsHtml += `<button class="page-number ${activeClass}" data-page="${i}">${i}</button>`;
  }
  
  // Tambahkan halaman terakhir jika perlu
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      pageButtonsHtml += `<span class="pagination-ellipsis">...</span>`;
    }
    pageButtonsHtml += `<button class="page-number" data-page="${totalPages}">${totalPages}</button>`;
  }
  
  pageNumbersDiv.innerHTML = pageButtonsHtml;
  
  // Tambahkan event listener ke tombol halaman
  document.querySelectorAll(".page-number").forEach(btn => {
    btn.addEventListener("click", (e) => {
      const page = parseInt(btn.getAttribute("data-page"));
      if (!isNaN(page) && page !== currentPage) {
        changePage(page);
      }
    });
  });
}

function changePage(newPage) {
  currentPage = newPage;
  renderTableWithPagination();
  
  // Scroll ke bagian tabel
  const tableSection = document.querySelector(".table-responsive");
  if (tableSection) {
    tableSection.scrollIntoView({ behavior: "smooth", block: "start" });
  }
}

// Event listener untuk tombol prev/next
document.addEventListener("DOMContentLoaded", () => {
  // Event delegation untuk tombol yang mungkin belum ada saat load
  document.addEventListener("click", (e) => {
    const prevBtn = document.getElementById("prevPageBtn");
    const nextBtn = document.getElementById("nextPageBtn");
    
    if (e.target === prevBtn || prevBtn?.contains(e.target)) {
      if (currentPage > 1) {
        changePage(currentPage - 1);
      }
    }
    
    if (e.target === nextBtn || nextBtn?.contains(e.target)) {
      const totalPages = Math.ceil(data.length / itemsPerPage);
      if (currentPage < totalPages) {
        changePage(currentPage + 1);
      }
    }
  });
});

function aktifkanFilter() {
  const semester = document.getElementById("semester");
  const tahunAjaran = document.getElementById("tahunAjaran");

  if (semester) {
    semester.addEventListener("change", async () => {
      currentPage = 1;
      await loadPeringkat();
    });
  }
  
  if (tahunAjaran) {
    tahunAjaran.addEventListener("change", async () => {
      currentPage = 1;
      await loadPeringkat();
    });
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  isiHeaderDariLocalStorage();
  await loadTahunAjaran();
  aktifkanFilter();
  await loadPeringkat();
});