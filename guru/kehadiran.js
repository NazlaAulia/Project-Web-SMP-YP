let dataKehadiran = [];
let kelasOptions = [];
let mapelOptions = [];

// Pagination state
let currentPage = 1;
const rowsPerPage = 10;

const filterSemester = document.getElementById("filterSemester");
const filterKelas = document.getElementById("filterKelas");
const filterMapel = document.getElementById("filterMapel");

const detailTableBody = document.getElementById("detailTableBody");
const searchInput = document.getElementById("searchInput");

const totalHadirEl = document.getElementById("totalHadir");
const totalIzinEl = document.getElementById("totalIzin");
const totalSakitEl = document.getElementById("totalSakit");
const totalAlfaEl = document.getElementById("totalAlfa");

const chartHadirValue = document.getElementById("chartHadirValue");
const chartIzinValue = document.getElementById("chartIzinValue");
const chartSakitValue = document.getElementById("chartSakitValue");
const chartAlfaValue = document.getElementById("chartAlfaValue");

const chartHadirBar = document.getElementById("chartHadirBar");
const chartIzinBar = document.getElementById("chartIzinBar");
const chartSakitBar = document.getElementById("chartSakitBar");
const chartAlfaBar = document.getElementById("chartAlfaBar");

// Pagination Elements
const prevPageBtn = document.getElementById("prevPageBtn");
const nextPageBtn = document.getElementById("nextPageBtn");
const paginationInfo = document.getElementById("paginationInfo");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

function getStatus(item) {
  if (item.alfa >= 2) return "Perlu Perhatian";
  if (item.sakit >= 2) return "Sering Sakit";
  if (item.izin >= 2) return "Banyak Izin";
  return "Aktif";
}

function formatStatusClass(status) {
  switch (status) {
    case "Aktif":
      return "status-hadir";
    case "Banyak Izin":
      return "status-izin";
    case "Sering Sakit":
      return "status-sakit";
    case "Perlu Perhatian":
      return "status-alfa";
    default:
      return "";
  }
}

function getFilteredData() {
  const semester = filterSemester ? filterSemester.value : "Semua";
  const kelas = filterKelas ? filterKelas.value : "Semua";
  const mapel = filterMapel ? filterMapel.value : "Semua";
  const keyword = searchInput ? searchInput.value.trim().toLowerCase() : "";

  return dataKehadiran.filter(item => {
    const status = getStatus(item);

    const matchSemester = semester === "Semua" || item.semester === semester;
    const matchKelas = kelas === "Semua" || item.kelas === kelas;
    const matchMapel = mapel === "Semua" || item.mapel === mapel;

    const searchableText = `
      ${item.nama}
      ${item.kelas}
      ${item.mapel}
      ${item.semester}
      ${item.hadir}
      ${item.izin}
      ${item.sakit}
      ${item.alfa}
      ${status}
    `.toLowerCase();

    const matchKeyword = keyword === "" || searchableText.includes(keyword);

    return matchSemester && matchKelas && matchMapel && matchKeyword;
  });
}

function hitungTotal(data) {
  return {
    hadir: data.reduce((sum, item) => sum + Number(item.hadir), 0),
    izin: data.reduce((sum, item) => sum + Number(item.izin), 0),
    sakit: data.reduce((sum, item) => sum + Number(item.sakit), 0),
    alfa: data.reduce((sum, item) => sum + Number(item.alfa), 0)
  };
}

function updateSummary(data) {
  const total = hitungTotal(data);

  if (totalHadirEl) totalHadirEl.textContent = total.hadir;
  if (totalIzinEl) totalIzinEl.textContent = total.izin;
  if (totalSakitEl) totalSakitEl.textContent = total.sakit;
  if (totalAlfaEl) totalAlfaEl.textContent = total.alfa;

  updateChart(total);
}

function updateChart(total) {
  const maxValue = Math.max(total.hadir, total.izin, total.sakit, total.alfa, 1);

  if (chartHadirValue) chartHadirValue.textContent = total.hadir;
  if (chartIzinValue) chartIzinValue.textContent = total.izin;
  if (chartSakitValue) chartSakitValue.textContent = total.sakit;
  if (chartAlfaValue) chartAlfaValue.textContent = total.alfa;

  if (chartHadirBar) chartHadirBar.style.width = `${(total.hadir / maxValue) * 100}%`;
  if (chartIzinBar) chartIzinBar.style.width = `${(total.izin / maxValue) * 100}%`;
  if (chartSakitBar) chartSakitBar.style.width = `${(total.sakit / maxValue) * 100}%`;
  if (chartAlfaBar) chartAlfaBar.style.width = `${(total.alfa / maxValue) * 100}%`;
}

function renderTablePaginated(filteredData) {
  if (!detailTableBody) return;

  const totalItems = filteredData.length;
  const totalPages = Math.ceil(totalItems / rowsPerPage);
  
  // Validate current page
  if (currentPage > totalPages && totalPages > 0) {
    currentPage = totalPages;
  }
  if (currentPage < 1) {
    currentPage = 1;
  }
  
  const startIndex = (currentPage - 1) * rowsPerPage;
  const endIndex = startIndex + rowsPerPage;
  const paginatedData = filteredData.slice(startIndex, endIndex);

  if (paginatedData.length === 0 && filteredData.length > 0) {
    currentPage = 1;
    renderTablePaginated(filteredData);
    return;
  }

  if (paginatedData.length === 0) {
    detailTableBody.innerHTML = `
      <tr>
        <td colspan="10" style="text-align: center;">Tidak ada data kehadiran.</td>
      </tr>
    `;
    updatePaginationControls(totalPages, totalItems);
    return;
  }

  // MENAMPILKAN DATA DENGAN NOMOR URUT YANG BENAR
  detailTableBody.innerHTML = paginatedData.map((item, index) => {
    // NOMOR URUT = (halaman - 1) * jumlah per halaman + index + 1
    const globalIndex = (currentPage - 1) * rowsPerPage + index + 1;
    const status = getStatus(item);
    const statusClass = formatStatusClass(status);

    return `
      <tr>
        <td>${globalIndex}</td>
        <td>${escapeHtml(item.nama)}</td>
        <td>${item.kelas}</td>
        <td>${item.mapel}</td>
        <td>${item.semester}</td>
        <td>${item.hadir}</td>
        <td>${item.izin}</td>
        <td>${item.sakit}</td>
        <td>${item.alfa}</td>
        <td>
          <span class="status ${statusClass}">
            ${status}
          </span>
        </td>
      </tr>
    `;
  }).join("");
  
  updatePaginationControls(totalPages, totalItems);
}

function escapeHtml(text) {
  if (!text) return '';
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function updatePaginationControls(totalPages, totalItems) {
  // Update info teks
  if (paginationInfo) {
    const displayTotalPages = totalPages === 0 ? 1 : totalPages;
    paginationInfo.textContent = `Halaman ${currentPage} dari ${displayTotalPages} (${totalItems} data)`;
  }
  
  // Update tombol prev/next
  if (prevPageBtn) {
    prevPageBtn.disabled = currentPage === 1 || totalItems === 0;
  }
  
  if (nextPageBtn) {
    nextPageBtn.disabled = currentPage === totalPages || totalItems === 0 || totalPages === 0;
  }
  
  // Generate nomor halaman dengan kotak
  generatePageNumbers(totalPages);
}

function generatePageNumbers(totalPages) {
  const container = document.getElementById('paginationNumbers');
  if (!container) return;
  
  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }
  
  let html = '';
  const maxVisible = 5; // Maksimal 5 nomor halaman yang terlihat
  let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
  let endPage = Math.min(totalPages, startPage + maxVisible - 1);
  
  // Adjust startPage jika endPage terlalu dekat dengan akhir
  if (endPage - startPage + 1 < maxVisible) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }
  
  // Tombol ke halaman pertama (jika tidak di awal)
  if (startPage > 1) {
    html += `<div class="page-number" data-page="1">1</div>`;
    if (startPage > 2) {
      html += `<div class="page-number-dots">...</div>`;
    }
  }
  
  // Nomor halaman utama
  for (let i = startPage; i <= endPage; i++) {
    const activeClass = i === currentPage ? 'active' : '';
    html += `<div class="page-number ${activeClass}" data-page="${i}">${i}</div>`;
  }
  
  // Tombol ke halaman terakhir (jika tidak di akhir)
  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      html += `<div class="page-number-dots">...</div>`;
    }
    html += `<div class="page-number" data-page="${totalPages}">${totalPages}</div>`;
  }
  
  container.innerHTML = html;
  
  // Tambahkan event listener untuk setiap nomor halaman
  document.querySelectorAll('.page-number').forEach(el => {
    el.addEventListener('click', () => {
      const page = parseInt(el.dataset.page);
      if (page && page !== currentPage) {
        currentPage = page;
        renderSemua();
      }
    });
  });
}

function goToPrevPage() {
  if (currentPage > 1) {
    currentPage--;
    renderSemua();
  }
}

function goToNextPage() {
  const filteredData = getFilteredData();
  const totalPages = Math.ceil(filteredData.length / rowsPerPage);
  
  if (currentPage < totalPages) {
    currentPage++;
    renderSemua();
  }
}

function isiFilterDariDatabase() {
  if (filterSemester) {
    filterSemester.value = "Semua";
  }

  if (filterKelas) {
    filterKelas.innerHTML = '<option value="Semua">Semua Kelas</option>';
    
    if (kelasOptions && kelasOptions.length > 0) {
      kelasOptions.forEach(kelas => {
        filterKelas.innerHTML += `<option value="${kelas}">Kelas ${kelas}</option>`;
      });
    }

    filterKelas.value = "Semua";
  }

  if (filterMapel) {
    if (mapelOptions && mapelOptions.length === 1) {
      const mapelGuru = mapelOptions[0];
      filterMapel.innerHTML = `<option value="${mapelGuru}">${mapelGuru}</option>`;
      filterMapel.value = mapelGuru;
      filterMapel.disabled = true;
      filterMapel.classList.add("readonly-mapel");
    } else {
      filterMapel.innerHTML = '<option value="Semua">Semua Mapel</option>';
      
      if (mapelOptions && mapelOptions.length > 0) {
        mapelOptions.forEach(mapel => {
          filterMapel.innerHTML += `<option value="${mapel}">${mapel}</option>`;
        });
      }

      filterMapel.value = "Semua";
      filterMapel.disabled = false;
      filterMapel.classList.remove("readonly-mapel");
    }
  }
}

function renderSemua() {
  const filteredData = getFilteredData();
  
  updateSummary(filteredData);
  renderTablePaginated(filteredData);
  setupCardAnimation();
}

function setupCardAnimation() {
  const cards = document.querySelectorAll(".click-animate");
  
  cards.forEach(card => {
    card.removeEventListener("click", handleCardClick);
    card.addEventListener("click", handleCardClick);
  });
}

function handleCardClick() {
  this.classList.add("card-active");
  setTimeout(() => {
    this.classList.remove("card-active");
  }, 550);
}

function loadKehadiranDatabase() {
  if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
    return;
  }

  fetch(`get_kehadiran.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
    .then(res => res.json())
    .then(result => {
      console.log("Data kehadiran:", result);

      if (result.status === "success") {
        dataKehadiran = result.data || [];
        kelasOptions = result.kelas_options || [];
        mapelOptions = result.mapel_options || [];

        isiFilterDariDatabase();
        currentPage = 1;
        renderSemua();
      } else {
        alert(result.message || "Gagal memuat data kehadiran.");
      }
    })
    .catch(err => {
      console.error("Gagal load kehadiran:", err);
      alert("Gagal memuat data kehadiran. Periksa koneksi atau file get_kehadiran.php");
    });
}

// Event Listeners
if (filterSemester) {
  filterSemester.addEventListener("change", () => {
    currentPage = 1;
    renderSemua();
  });
}

if (filterKelas) {
  filterKelas.addEventListener("change", () => {
    currentPage = 1;
    renderSemua();
  });
}

if (filterMapel) {
  filterMapel.addEventListener("change", () => {
    currentPage = 1;
    renderSemua();
  });
}

if (searchInput) {
  searchInput.addEventListener("input", () => {
    currentPage = 1;
    renderSemua();
  });
}

if (prevPageBtn) {
  prevPageBtn.addEventListener("click", goToPrevPage);
}

if (nextPageBtn) {
  nextPageBtn.addEventListener("click", goToNextPage);
}

// Load data when page loads
loadKehadiranDatabase();