document.addEventListener("DOMContentLoaded", () => {
  const navLinks = document.querySelectorAll(".nav-link");

  navLinks.forEach((link) => {
    link.addEventListener("click", function () {
      this.classList.add("pulse-click");
      setTimeout(() => {
        this.classList.remove("pulse-click");
      }, 350);
    });
  });

  isiHeaderDariLocalStorage();
  loadJadwal();
});

// Variabel untuk pagination
let jadwalDataFull = [];
let currentPage = 1;
const itemsPerPage = 5; // Jumlah baris per halaman

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  const kelasBadge = document.getElementById("kelasBadge");
  const profileName = document.getElementById("profileName");
  const profileAvatar = document.getElementById("profileAvatar");

  if (kelasBadge) kelasBadge.textContent = kelas;
  if (profileName) profileName.textContent = nama;
  if (profileAvatar) profileAvatar.textContent = avatar;
}

async function loadJadwal() {
  try {
    const idSiswa = localStorage.getItem("id_siswa") || "";

    const response = await fetch(`get_jadwal.php?id_siswa=${encodeURIComponent(idSiswa)}`, {
      method: "GET",
      credentials: "same-origin"
    });

    const text = await response.text();

    console.log("RAW RESPONSE:", text);

    const result = JSON.parse(text);
    console.log("JSON RESULT:", result);

    if (!result.success) {
      renderError(result.message || "Gagal memuat data.");
      return;
    }

    if (result.siswa && result.siswa.nama) {
      localStorage.setItem("nama_siswa", result.siswa.nama);
    }

    if (result.siswa && result.siswa.kelas) {
      localStorage.setItem("kelas_siswa", result.siswa.kelas);
    }

    renderProfil(result.siswa);
    renderRingkasan(result.ringkasan);
    renderUpdate(result.update_terbaru);
    
    // Simpan data jadwal dan render dengan pagination
    jadwalDataFull = result.jadwal_minggu || [];
    currentPage = 1;
    renderTabelWithPagination(jadwalDataFull);
  } catch (error) {
    renderError("Terjadi kesalahan saat mengambil data jadwal.");
    console.error("ERROR FETCH / JSON:", error);
  }
}

function renderProfil(siswa) {
  document.getElementById("kelasBadge").textContent = siswa.kelas || "-";
  document.getElementById("profileName").textContent = siswa.nama || "-";
  document.getElementById("profileAvatar").textContent = siswa.inisial || "S";

  const semesterText = document.getElementById("semesterText");
  if (semesterText) {
    semesterText.textContent = `Sesuai dengan Kelas ${siswa.kelas}, Semester Ini`;
  }

  const kelasSelect = document.getElementById("kelasSelect");
  if (kelasSelect) {
    kelasSelect.innerHTML = `<option>${siswa.kelas || "-"}</option>`;
  }

  const semesterSelect = document.getElementById("semesterSelect");
  if (semesterSelect) {
    if (siswa.tahun_ajaran_list && siswa.tahun_ajaran_list.length > 0) {
      semesterSelect.innerHTML = siswa.tahun_ajaran_list.map((ta) => {
        const selected = ta.tahun_ajaran === siswa.tahun_ajaran ? "selected" : "";

        return `
          <option value="${ta.id_tahun_ajaran}" ${selected}>
            ${ta.tahun_ajaran} - ${siswa.semester || "-"}
          </option>
        `;
      }).join("");
    } else {
      semesterSelect.innerHTML =
        `<option>${siswa.tahun_ajaran || "-"} - ${siswa.semester || "-"}</option>`;
    }
  }

  document.getElementById("judulTabel").textContent =
    `Jadwal Pelajaran Kelas ${siswa.kelas} (Minggu Ini)`;
}

function renderRingkasan(ringkasan) {
  if (!ringkasan) return;

  const hariIni = document.getElementById("hariIni");
  const totalPelajaran = document.getElementById("totalPelajaran");
  const pelajaranUtama = document.getElementById("pelajaranUtama");

  if (hariIni) {
    hariIni.textContent = ringkasan.hari_ini || "-";
  }

  if (totalPelajaran) {
    totalPelajaran.textContent = `${ringkasan.total_pelajaran || 0} Jam`;
  }

  if (pelajaranUtama) {
    pelajaranUtama.textContent = ringkasan.pelajaran_utama || "-";
  }
}

function renderUpdate(updateList) {
  const container = document.getElementById("updateJadwal");

  if (!updateList || updateList.length === 0) {
    container.innerHTML = "<p>- Tidak ada jadwal hari ini</p>";
    return;
  }

  container.innerHTML = updateList
    .map(item => `<p>- Jam ${item.jam_mulai} ${item.mapel}</p>`)
    .join("");
}

// Fungsi baru untuk render tabel dengan pagination
function renderTabelWithPagination(jadwalList) {
  const tbody = document.getElementById("jadwalTableBody");
  const paginationContainer = document.getElementById("paginationContainer");
  const kelas = localStorage.getItem("kelas_siswa") || "-";

  if (!jadwalList || jadwalList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="empty-state">Belum ada jadwal untuk kelas ${kelas}.</td>
      </tr>
    `;
    paginationContainer.style.display = "none";
    return;
  }

  // Hitung total halaman
  const totalPages = Math.ceil(jadwalList.length / itemsPerPage);
  
  // Tampilkan pagination hanya jika lebih dari 1 halaman
  if (totalPages > 1) {
    paginationContainer.style.display = "flex";
  } else {
    paginationContainer.style.display = "none";
  }

  // Tentukan data yang akan ditampilkan di halaman ini
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const currentPageData = jadwalList.slice(startIndex, endIndex);

  // Render tabel untuk halaman saat ini
  tbody.innerHTML = currentPageData.map((item) => {
    let statusClass = "status-waiting";

    if (item.status && item.status.toLowerCase() === "selesai") {
      statusClass = "status-done";
    } else if (item.status && item.status.toLowerCase() === "berlangsung") {
      statusClass = "status-live";
    }

    return `
      <tr class="show-row">
        <td>
          <strong>${item.hari}</strong><br>
          ${item.jam}
         </td>
         <td>${item.mata_pelajaran}</td>
         <td>${item.guru}</td>
         <td>
          <span class="status-badge ${statusClass}">${item.status || "Mendatang"}</span>
         </td>
       </tr>
    `;
  }).join("");

  // Update info pagination
  updatePaginationInfo(startIndex, endIndex, jadwalList.length);
  
  // Render tombol pagination
  renderPaginationButtons(totalPages);
}

function updatePaginationInfo(startIndex, endIndex, totalData) {
  const paginationInfo = document.getElementById("paginationInfo");
  const start = startIndex + 1;
  const end = Math.min(endIndex, totalData);
  
  paginationInfo.textContent = `Menampilkan ${start}-${end} dari ${totalData} data`;
}

function renderPaginationButtons(totalPages) {
  const pageNumbersDiv = document.getElementById("pageNumbers");
  const prevBtn = document.getElementById("prevPageBtn");
  const nextBtn = document.getElementById("nextPageBtn");
  
  // Update state tombol prev/next
  prevBtn.disabled = (currentPage === 1);
  nextBtn.disabled = (currentPage === totalPages);
  
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
  renderTabelWithPagination(jadwalDataFull);
  
  // Scroll ke bagian tabel
  const jadwalSection = document.querySelector(".jadwal-section");
  if (jadwalSection) {
    jadwalSection.scrollIntoView({ behavior: "smooth", block: "start" });
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
      const totalPages = Math.ceil(jadwalDataFull.length / itemsPerPage);
      if (currentPage < totalPages) {
        changePage(currentPage + 1);
      }
    }
  });
});

function renderTabel(jadwalList, kelas) {
  const tbody = document.getElementById("jadwalTableBody");

  if (!jadwalList || jadwalList.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="empty-state">Belum ada jadwal untuk kelas ${kelas}.</td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = jadwalList.map((item) => {
    let statusClass = "status-waiting";

    if (item.status && item.status.toLowerCase() === "selesai") {
      statusClass = "status-done";
    } else if (item.status && item.status.toLowerCase() === "berlangsung") {
      statusClass = "status-live";
    }

    return `
      <tr class="show-row">
        <td>
          <strong>${item.hari}</strong><br>
          ${item.jam}
         </td>
         <td>${item.mata_pelajaran}</td>
         <td>${item.guru}</td>
         <td>
          <span class="status-badge ${statusClass}">${item.status || "Mendatang"}</span>
         </td>
       </tr>
    `;
  }).join("");
}

function renderError(message) {
  const paginationContainer = document.getElementById("paginationContainer");
  if (paginationContainer) {
    paginationContainer.style.display = "none";
  }
  
  document.getElementById("jadwalTableBody").innerHTML = `
    <tr>
      <td colspan="4" class="empty-state">${message}</td>
    </tr>
  `;
}