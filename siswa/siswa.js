const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) window.location.replace("../login.html");

const namaKelasEl = document.getElementById("namaKelas");
const avatarPlaceholderEl = document.getElementById("avatarPlaceholder");
const namaSiswaEl = document.getElementById("namaSiswa");
const welcomeTextEl = document.getElementById("welcomeText");
const barChartEl = document.getElementById("barChart");

function singkatNamaMapel(nama) {
  const mapping = {
    "Matematika": "Mat",
    "Bahasa Indonesia": "Ind",
    "Bahasa Inggris": "Ing",
    "IPA": "Ipa",
    "IPS": "Ips",
    "INFO/BK": "Info/BK",
    "BK": "BK",
    "Informatika": "Infor",
    "PKN": "PKN",
    "B. JAWA": "B. Jawa",
    "PAI/BHQ": "PAI",
    "PJOK": "PJOK"
  };
  return mapping[nama] || nama;
}

function renderChart(dataNilai = []) {
  if (!barChartEl) return;

  if (!dataNilai || dataNilai.length === 0) {
    barChartEl.innerHTML = `<p class="chart-empty">Tidak ada data nilai.</p>`;
    document.getElementById("avgValue").textContent = "0";
    document.getElementById("maxValue").textContent = "-";
    document.getElementById("minValue").textContent = "-";
    return;
  }

  barChartEl.style.setProperty("--jumlah-bar", dataNilai.length);

  barChartEl.innerHTML = dataNilai.map(item => {
    const nilaiAngka = Number(item.nilai_angka) || 0;
    const label = singkatNamaMapel(item.nama_mapel);
    return `
      <div class="bar-group">
        <div class="bar-value">${nilaiAngka}</div>
        <div class="bar-track">
          <div class="bar-fill" style="height: ${nilaiAngka}%;"></div>
        </div>
        <span class="bar-label" title="${item.nama_mapel}">${label}</span>
      </div>
    `;
  }).join("");

  const daftarNilai = dataNilai.filter(item => Number(item.nilai_angka) > 0);
  if (daftarNilai.length === 0) {
    document.getElementById("avgValue").textContent = "0";
    document.getElementById("maxValue").textContent = "-";
    document.getElementById("minValue").textContent = "-";
    return;
  }

  const total = daftarNilai.reduce((sum, item) => sum + Number(item.nilai_angka), 0);
  const rataRata = (total / daftarNilai.length).toFixed(1);

  let tertinggi = daftarNilai[0];
  let terendah = daftarNilai[0];

  daftarNilai.forEach(item => {
    if (Number(item.nilai_angka) > Number(tertinggi.nilai_angka)) tertinggi = item;
    if (Number(item.nilai_angka) < Number(terendah.nilai_angka)) terendah = item;
  });

  document.getElementById("avgValue").textContent = rataRata;
  document.getElementById("maxValue").textContent = `${tertinggi.nama_mapel} (${tertinggi.nilai_angka})`;
  document.getElementById("minValue").textContent = `${terendah.nama_mapel} (${terendah.nilai_angka})`;
}

async function loadDashboard() {
  try {
    const response = await fetch(`siswa.php?id_siswa=${encodeURIComponent(idSiswa)}`);
    const result = await response.json();

    if (result.status !== "success") throw new Error(result.message || "Gagal memuat data siswa.");

    const s = result.data;

    // Update info siswa
    namaSiswaEl.textContent = s.nama || "Siswa";
    welcomeTextEl.textContent = `Halo, ${s.nama || "Siswa"}!`;
    avatarPlaceholderEl.textContent = s.nama ? s.nama.charAt(0).toUpperCase() : "-";
    namaKelasEl.textContent = s.nama_kelas || "-";

    // Render chart & jadwal
    renderChart(s.nilai_akademik);
    renderJadwalHariIni(s.jadwal_hari_ini);

    // Tampilkan popup status jika ada
    tampilkanPopupStatus(s.status, s.nama);

  } catch (error) {
    console.error(error);
    alert(error.message || "Terjadi error saat memuat dashboard siswa.");
  }
}

loadDashboard();