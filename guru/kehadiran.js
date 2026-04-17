const dataKehadiran = [
  { nama: "Alya Putri", kelas: "7A", status: "Hadir", keterangan: "Masuk tepat waktu", hari: "Senin" },
  { nama: "Bagas Pratama", kelas: "7A", status: "Izin", keterangan: "Acara keluarga", hari: "Senin" },
  { nama: "Citra Lestari", kelas: "7A", status: "Sakit", keterangan: "Demam", hari: "Senin" },
  { nama: "Dimas Saputra", kelas: "7A", status: "Alfa", keterangan: "Tanpa keterangan", hari: "Senin" },
  { nama: "Eka Rahma", kelas: "7A", status: "Hadir", keterangan: "Aktif di kelas", hari: "Senin" },

  { nama: "Farel Akbar", kelas: "7B", status: "Hadir", keterangan: "Masuk tepat waktu", hari: "Selasa" },
  { nama: "Gina Maharani", kelas: "7B", status: "Hadir", keterangan: "Lengkap", hari: "Selasa" },
  { nama: "Hana Putri", kelas: "7B", status: "Izin", keterangan: "Izin orang tua", hari: "Selasa" },
  { nama: "Iqbal Ramadhan", kelas: "7B", status: "Sakit", keterangan: "Flu", hari: "Selasa" },
  { nama: "Jovan Kurnia", kelas: "7B", status: "Alfa", keterangan: "Tanpa keterangan", hari: "Selasa" },

  { nama: "Kirana Salsabila", kelas: "7C", status: "Hadir", keterangan: "Hadir", hari: "Rabu" },
  { nama: "Lutfi Maulana", kelas: "7C", status: "Hadir", keterangan: "Hadir", hari: "Rabu" },
  { nama: "Mira Anjani", kelas: "7C", status: "Hadir", keterangan: "Masuk tepat waktu", hari: "Rabu" },
  { nama: "Naufal Rizky", kelas: "7C", status: "Izin", keterangan: "Acara keluarga", hari: "Rabu" },
  { nama: "Ocha Permata", kelas: "7C", status: "Sakit", keterangan: "Pusing", hari: "Rabu" }
];

const filterHari = document.getElementById("filterHari");
const filterKelas = document.getElementById("filterKelas");
const kelasContainer = document.getElementById("kelasContainer");
const detailTableBody = document.getElementById("detailTableBody");
const searchInput = document.getElementById("searchInput");

function formatStatusClass(status) {
  switch (status) {
    case "Hadir": return "status-hadir";
    case "Izin": return "status-izin";
    case "Sakit": return "status-sakit";
    case "Alfa": return "status-alfa";
    default: return "";
  }
}

function getFilteredData() {
  const hari = filterHari.value;
  const kelas = filterKelas.value;
  const keyword = searchInput.value.toLowerCase();

  return dataKehadiran.filter(item => {
    const matchHari = hari === "Semua" || item.hari === hari;
    const matchKelas = kelas === "Semua" || item.kelas === kelas;
    const matchKeyword = item.nama.toLowerCase().includes(keyword);
    return matchHari && matchKelas && matchKeyword;
  });
}

function updateSummary(data) {
  const totalHadir = data.filter(item => item.status === "Hadir").length;
  const totalIzin = data.filter(item => item.status === "Izin").length;
  const totalSakit = data.filter(item => item.status === "Sakit").length;
  const totalAlfa = data.filter(item => item.status === "Alfa").length;

  document.getElementById("totalHadir").textContent = totalHadir;
  document.getElementById("totalIzin").textContent = totalIzin;
  document.getElementById("totalSakit").textContent = totalSakit;
  document.getElementById("totalAlfa").textContent = totalAlfa;
}

function renderKelasCards(data) {
  const daftarKelas = ["7A", "7B", "7C"];

  kelasContainer.innerHTML = daftarKelas.map(kelas => {
    const dataKelas = data.filter(item => item.kelas === kelas);
    const hadir = dataKelas.filter(item => item.status === "Hadir").length;
    const izin = dataKelas.filter(item => item.status === "Izin").length;
    const sakit = dataKelas.filter(item => item.status === "Sakit").length;
    const alfa = dataKelas.filter(item => item.status === "Alfa").length;

    return `
      <div class="kelas-card">
        <h3>Kelas ${kelas}</h3>
        <p>Rekap kehadiran siswa berdasarkan data yang tersedia</p>
        <div class="kelas-stat">
          <div class="stat-item">
            <span>Hadir</span>
            <strong>${hadir}</strong>
          </div>
          <div class="stat-item">
            <span>Izin</span>
            <strong>${izin}</strong>
          </div>
          <div class="stat-item">
            <span>Sakit</span>
            <strong>${sakit}</strong>
          </div>
          <div class="stat-item">
            <span>Alfa</span>
            <strong>${alfa}</strong>
          </div>
        </div>
      </div>
    `;
  }).join("");
}

function renderTable(data) {
  if (data.length === 0) {
    detailTableBody.innerHTML = `
      <tr>
        <td colspan="6">Tidak ada data kehadiran.</td>
      </tr>
    `;
    return;
  }

  detailTableBody.innerHTML = data.map((item, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${item.nama}</td>
      <td>${item.kelas}</td>
      <td><span class="status ${formatStatusClass(item.status)}">${item.status}</span></td>
      <td>${item.keterangan}</td>
      <td>${item.hari}</td>
    </tr>
  `).join("");
}

function renderSemua() {
  const filteredData = getFilteredData();
  updateSummary(filteredData);
  renderKelasCards(filteredData);
  renderTable(filteredData);
}

filterHari.addEventListener("change", renderSemua);
filterKelas.addEventListener("change", renderSemua);
searchInput.addEventListener("input", renderSemua);

renderSemua();