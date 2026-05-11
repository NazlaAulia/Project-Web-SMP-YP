const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) {
  window.location.replace("../login.html");
}

const namaKelasEl = document.getElementById("namaKelas");
const avatarPlaceholderEl = document.getElementById("avatarPlaceholder");
const namaSiswaEl = document.getElementById("namaSiswa");
const welcomeTextEl = document.getElementById("welcomeText");
const barChartEl = document.getElementById("barChart") || document.querySelector(".chart-card .bar-chart");

const statusModal = document.getElementById("statusModal");
const statusModalBox = document.getElementById("statusModalBox");
const statusModalIcon = document.getElementById("statusModalIcon");
const statusModalTitle = document.getElementById("statusModalTitle");
const statusModalMessage = document.getElementById("statusModalMessage");
const statusModalClose = document.getElementById("statusModalClose");

function tampilkanPopupStatus(status, nama) {
  if (!statusModal) return;

  const statusLower = String(status || "").toLowerCase();

  if (statusLower !== "lulus" && statusLower !== "keluar") return;

  statusModalBox.classList.remove("modal-lulus", "modal-keluar");

  if (statusLower === "lulus") {
    statusModalBox.classList.add("modal-lulus");
    statusModalIcon.innerHTML = `<i class="fa-solid fa-graduation-cap"></i>`;
    statusModalTitle.textContent = "Selamat, Kamu Dinyatakan Lulus!";
    statusModalMessage.textContent = `Halo ${nama}, kamu telah dinyatakan lulus dari SMP YP 17 Surabaya. Terus semangat meraih cita-cita!`;
  }

  if (statusLower === "keluar") {
    statusModalBox.classList.add("modal-keluar");
    statusModalIcon.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i>`;
    statusModalTitle.textContent = "Status Siswa: Keluar";
    statusModalMessage.textContent = `Halo ${nama}, status kamu tercatat keluar dari SMP YP 17 Surabaya. Silakan hubungi pihak sekolah jika membutuhkan informasi lebih lanjut.`;
  }

  statusModal.classList.add("show");
}

if (statusModalClose) {
  statusModalClose.addEventListener("click", () => {
    statusModal.classList.remove("show");
  });
}

if (statusModal) {
  statusModal.addEventListener("click", (e) => {
    if (e.target === statusModal) {
      statusModal.classList.remove("show");
    }
  });
}

const nilai = {
  Matematika: 85,
  "Bahasa Indonesia": 64,
  "Bahasa Inggris": 92,
  IPA: 45,
  IPS: 75
};

function singkatNamaMapel(nama) {
  const mapping = {
    "Matematika": "Mat",
    "MAT": "Mat",
    "Bahasa Indonesia": "Ind",
    "BIN": "Ind",
    "Bahasa Inggris": "Ing",
    "BIG": "Ing",
    "IPA": "Ipa",
    "IPS": "Ips",
    "INFO/BK": "Info/BK",
    "BK": "BK",
    "Informatika": "Infor",
    "INFOR": "Infor",
    "PKN": "PKN",
    "B. JAWA": "B. Jawa",
    "Bahasa Jawa": "B. Jawa",
    "PAI/BHQ": "PAI",
    "PJOK": "PJOK"
  };

  return mapping[nama] || nama;
}

function renderChart(dataNilai = null) {
  const daftarBar = barChartEl;
  const avgEl = document.getElementById("avgValue");
  const maxEl = document.getElementById("maxValue");
  const minEl = document.getElementById("minValue");

  // Siapkan data nilai
  const nilaiFinal = Array.isArray(dataNilai) && dataNilai.length > 0
    ? dataNilai.map(item => ({
        nama_mapel: item.nama_mapel,
        nilai_angka: Number(item.nilai_angka) || 0
      }))
    : [];

  // Jika tidak ada data, tampilkan pesan kosong
  if (!nilaiFinal.length) {
    if (daftarBar) daftarBar.innerHTML = `<p class="chart-empty">Tidak ada data nilai.</p>`;
    if (avgEl) avgEl.textContent = "-";
    if (maxEl) maxEl.textContent = "-";
    if (minEl) minEl.textContent = "-";
    return;
  }

  // Set jumlah bar untuk CSS
  if (daftarBar) daftarBar.style.setProperty("--jumlah-bar", nilaiFinal.length);

  // Render bar chart
  if (daftarBar) {
    daftarBar.innerHTML = nilaiFinal.map(item => {
      const label = singkatNamaMapel(item.nama_mapel);
      const nilaiAngka = Number(item.nilai_angka);
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
  }

  // Filter hanya nilai > 0 untuk perhitungan statistik
  const nilaiValid = nilaiFinal.filter(item => Number(item.nilai_angka) > 0);

  if (!nilaiValid.length) {
    if (avgEl) avgEl.textContent = "0";
    if (maxEl) maxEl.textContent = "-";
    if (minEl) minEl.textContent = "-";
    return;
  }

  // Hitung rata-rata, tertinggi, dan terendah
  const total = nilaiValid.reduce((sum, item) => sum + Number(item.nilai_angka), 0);
  const rataRata = (total / nilaiValid.length).toFixed(1);

  const tertinggi = nilaiValid.reduce((a, b) => Number(b.nilai_angka) > Number(a.nilai_angka) ? b : a);
  const terendah = nilaiValid.reduce((a, b) => Number(b.nilai_angka) < Number(a.nilai_angka) ? b : a);

  if (avgEl) avgEl.textContent = rataRata;
  if (maxEl) maxEl.textContent = `${tertinggi.nama_mapel} (${tertinggi.nilai_angka})`;
  if (minEl) minEl.textContent = `${terendah.nama_mapel} (${terendah.nilai_angka})`;
}

  if (!barChartEl) return;

  if (nilaiFinal.length === 0) {
    barChartEl.innerHTML = `<p class="chart-empty">Tidak ada data nilai.</p>`;
    document.getElementById("avgValue").textContent = "0";
    document.getElementById("maxValue").textContent = "-";
    document.getElementById("minValue").textContent = "-";
    return;
  }

  barChartEl.style.setProperty("--jumlah-bar", nilaiFinal.length);

  barChartEl.innerHTML = nilaiFinal.map((item) => {
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

  const daftarNilai = nilaiFinal.filter((item) => Number(item.nilai_angka) > 0);

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

  daftarNilai.forEach((item) => {
    if (Number(item.nilai_angka) > Number(tertinggi.nilai_angka)) tertinggi = item;
    if (Number(item.nilai_angka) < Number(terendah.nilai_angka)) terendah = item;
  });

  document.getElementById("avgValue").textContent = rataRata;
  document.getElementById("maxValue").textContent = `${tertinggi.nama_mapel} (${tertinggi.nilai_angka})`;
  document.getElementById("minValue").textContent = `${terendah.nama_mapel} (${terendah.nilai_angka})`;
}

function renderJadwalHariIni(jadwal) {
  const scheduleCard = document.querySelector(".schedule-card");
  const oldScheduleItem = document.querySelector(".schedule-card .schedule-item");

  if (!scheduleCard) return;

  let container = document.getElementById("jadwalHariIniContainer");

  if (!container) {
    container = document.createElement("div");
    container.id = "jadwalHariIniContainer";

    if (oldScheduleItem) {
      oldScheduleItem.replaceWith(container);
    } else {
      scheduleCard.appendChild(container);
    }
  }

  if (!jadwal || jadwal.length === 0) {
    container.innerHTML = `
      <p class="schedule-empty">Tidak ada jadwal hari ini.</p>
    `;
    return;
  }

  container.innerHTML = jadwal.map((item) => {
    return `
      <div class="schedule-item">
        <span class="schedule-time">${item.jam || "-"}</span>
        <div class="schedule-details">
          <h4>${item.nama_mapel || "Mata Pelajaran"}</h4>
          <p>${item.nama_guru || "Guru belum ditentukan"}</p>
        </div>
      </div>
    `;
  }).join("");
}

async function loadDashboard() {
  renderChart();

  try {
    const response = await fetch(`siswa.php?id_siswa=${encodeURIComponent(idSiswa)}`);
    const text = await response.text();
    console.log("RESPON SISWA:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      throw new Error("Response bukan JSON: " + text);
    }

    if (result.status !== "success") {
      alert(result.message || "Gagal memuat data siswa.");
      return;
    }

    const s = result.data;
    const nama = s.nama || "Siswa";
    const hurufAwal = nama.charAt(0).toUpperCase();

    namaSiswaEl.textContent = nama;
    welcomeTextEl.textContent = `Halo, ${nama}!`;
    avatarPlaceholderEl.textContent = hurufAwal;
    namaKelasEl.textContent = s.nama_kelas || "-";
    tampilkanPopupStatus(s.status, nama);

    renderChart(s.nilai_akademik);
    renderJadwalHariIni(s.jadwal_hari_ini);

  } catch (error) {
    console.error("Error load dashboard:", error);
    alert(error.message || "Terjadi error saat memuat dashboard siswa.");
  }
}

loadDashboard();