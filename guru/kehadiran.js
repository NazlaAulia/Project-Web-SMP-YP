let dataKehadiran = [];

const filterSemester = document.getElementById("filterSemester");
const filterKelas = document.getElementById("filterKelas");
const filterMapel = document.getElementById("filterMapel");
const kelasContainer = document.getElementById("kelasContainer");
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

function renderKelasCards(data) {
  if (!kelasContainer) return;

  if (data.length === 0) {
    kelasContainer.innerHTML = `
      <div class="empty-kelas-card">
        <i class="bi bi-inbox"></i>
        <h3>Belum Ada Data Rekap</h3>
        <p>Data kehadiran belum tersedia untuk filter yang dipilih.</p>
      </div>
    `;
    return;
  }

  const daftarKelas = [...new Set(data.map(item => item.kelas))];

  kelasContainer.innerHTML = daftarKelas.map(kelas => {
    const dataKelas = data.filter(item => item.kelas === kelas);
    const total = hitungTotal(dataKelas);

    const totalSemua = total.hadir + total.izin + total.sakit + total.alfa;
    const persenHadir = totalSemua > 0 ? Math.round((total.hadir / totalSemua) * 100) : 0;

    return `
      <div class="kelas-card kelas-card-compact click-animate">
        <div class="kelas-card-head">
          <div>
            <h3>Kelas ${kelas}</h3>
            <p>Rekap data nilai siswa</p>
          </div>
          <span class="percent-badge">${persenHadir}% Hadir</span>
        </div>

        <div class="compact-stat">
          <span><strong>${total.hadir}</strong> Hadir</span>
          <span><strong>${total.izin}</strong> Izin</span>
          <span><strong>${total.sakit}</strong> Sakit</span>
          <span><strong>${total.alfa}</strong> Alfa</span>
        </div>
      </div>
    `;
  }).join("");

  setupCardAnimation();
}

function renderTable(data) {
  if (!detailTableBody) return;

  if (data.length === 0) {
    detailTableBody.innerHTML = `
      <tr>
        <td colspan="10">Tidak ada data kehadiran.</td>
      </tr>
    `;
    return;
  }

  detailTableBody.innerHTML = data.map((item, index) => {
    const status = getStatus(item);
    const statusClass = formatStatusClass(status);

    return `
      <tr>
        <td>${index + 1}</td>
        <td>${item.nama}</td>
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
}

function isiFilterDariDatabase() {
  if (filterKelas) {
    const kelasUnik = [...new Set(dataKehadiran.map(item => item.kelas).filter(Boolean))];

    filterKelas.innerHTML = `<option value="Semua">Semua Kelas</option>`;

    kelasUnik.forEach(kelas => {
      filterKelas.innerHTML += `<option value="${kelas}">Kelas ${kelas}</option>`;
    });
  }

  if (filterMapel) {
    const mapelUnik = [...new Set(dataKehadiran.map(item => item.mapel).filter(Boolean))];

    filterMapel.innerHTML = `<option value="Semua">Semua Mapel</option>`;

    mapelUnik.forEach(mapel => {
      filterMapel.innerHTML += `<option value="${mapel}">${mapel}</option>`;
    });
  }
}

function renderSemua() {
  const filteredData = getFilteredData();

  updateSummary(filteredData);
  renderTable(filteredData);
  setupCardAnimation();
}

function setupCardAnimation() {
  const cards = document.querySelectorAll(".click-animate");

  cards.forEach(card => {
    card.addEventListener("click", function () {
      card.classList.remove("card-active");
    });
  });
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

        isiFilterDariDatabase();
        renderSemua();
      } else {
        alert(result.message);
      }
    })
    .catch(err => {
      console.error("Gagal load kehadiran:", err);
      alert("Gagal memuat data kehadiran.");
    });
}

if (filterSemester) {
  filterSemester.addEventListener("change", renderSemua);
}

if (filterKelas) {
  filterKelas.addEventListener("change", renderSemua);
}

if (filterMapel) {
  filterMapel.addEventListener("change", renderSemua);
}

if (searchInput) {
  searchInput.addEventListener("input", renderSemua);
}

loadKehadiranDatabase();