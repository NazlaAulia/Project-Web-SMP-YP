const dataKehadiran = [
  { nama: "Alya Putri", kelas: "7A", mapel: "BIN", semester: "Genap", hadir: 14, izin: 1, sakit: 0, alfa: 0 },
  { nama: "Bagas Pratama", kelas: "7A", mapel: "BIN", semester: "Genap", hadir: 12, izin: 2, sakit: 1, alfa: 0 },
  { nama: "Citra Lestari", kelas: "7B", mapel: "MAT", semester: "Genap", hadir: 15, izin: 0, sakit: 0, alfa: 0 },
  { nama: "Dimas Saputra", kelas: "7C", mapel: "IPA", semester: "Genap", hadir: 10, izin: 1, sakit: 2, alfa: 2 },

  { nama: "Eka Rahma", kelas: "8A", mapel: "BIG", semester: "Genap", hadir: 13, izin: 1, sakit: 1, alfa: 0 },
  { nama: "Farel Akbar", kelas: "8B", mapel: "IPS", semester: "Genap", hadir: 11, izin: 2, sakit: 1, alfa: 1 },
  { nama: "Gina Maharani", kelas: "8C", mapel: "PKN", semester: "Genap", hadir: 15, izin: 0, sakit: 0, alfa: 0 },

  { nama: "Hana Putri", kelas: "9A", mapel: "INFOR", semester: "Genap", hadir: 14, izin: 0, sakit: 1, alfa: 0 },
  { nama: "Iqbal Ramadhan", kelas: "9B", mapel: "PJOK", semester: "Genap", hadir: 11, izin: 1, sakit: 2, alfa: 1 },
  { nama: "Jovan Kurnia", kelas: "9C", mapel: "PAI/BHQ", semester: "Genap", hadir: 13, izin: 1, sakit: 0, alfa: 1 },

  { nama: "Kirana Salsabila", kelas: "7A", mapel: "MAT", semester: "Ganjil", hadir: 13, izin: 1, sakit: 1, alfa: 0 },
  { nama: "Lutfi Maulana", kelas: "8C", mapel: "BK", semester: "Ganjil", hadir: 12, izin: 2, sakit: 0, alfa: 1 },
  { nama: "Mira Anjani", kelas: "9A", mapel: "IPS", semester: "Ganjil", hadir: 14, izin: 1, sakit: 0, alfa: 0 }
];

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
  const semester = filterSemester.value;
  const kelas = filterKelas.value;
  const mapel = filterMapel.value;
  const keyword = searchInput.value.trim().toLowerCase();

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
    hadir: data.reduce((sum, item) => sum + item.hadir, 0),
    izin: data.reduce((sum, item) => sum + item.izin, 0),
    sakit: data.reduce((sum, item) => sum + item.sakit, 0),
    alfa: data.reduce((sum, item) => sum + item.alfa, 0)
  };
}

function updateSummary(data) {
  const total = hitungTotal(data);

  totalHadirEl.textContent = total.hadir;
  totalIzinEl.textContent = total.izin;
  totalSakitEl.textContent = total.sakit;
  totalAlfaEl.textContent = total.alfa;

  updateChart(total);
}

function updateChart(total) {
  const maxValue = Math.max(total.hadir, total.izin, total.sakit, total.alfa, 1);

  chartHadirValue.textContent = total.hadir;
  chartIzinValue.textContent = total.izin;
  chartSakitValue.textContent = total.sakit;
  chartAlfaValue.textContent = total.alfa;

  chartHadirBar.style.width = `${(total.hadir / maxValue) * 100}%`;
  chartIzinBar.style.width = `${(total.izin / maxValue) * 100}%`;
  chartSakitBar.style.width = `${(total.sakit / maxValue) * 100}%`;
  chartAlfaBar.style.width = `${(total.alfa / maxValue) * 100}%`;
}
function renderKelasCards(data) {
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

function renderSemua() {
  const filteredData = getFilteredData();

  updateSummary(filteredData);
  renderTable(filteredData);
  setupCardAnimation();
}

function setupCardAnimation() {
  const cards = document.querySelectorAll(".click-animate");

  cards.forEach(card => {
    card.addEventListener("click", function (event) {
      card.classList.remove("card-active");

      void card.offsetWidth;

      card.classList.add("card-active");
    });
  });
}
filterSemester.addEventListener("change", renderSemua);
filterKelas.addEventListener("change", renderSemua);
filterMapel.addEventListener("change", renderSemua);
searchInput.addEventListener("input", renderSemua);

renderSemua();