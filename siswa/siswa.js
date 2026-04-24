const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) {
  window.location.replace("../login.html");
}

const namaKelasEl = document.getElementById("namaKelas");
const avatarPlaceholderEl = document.getElementById("avatarPlaceholder");
const namaSiswaEl = document.getElementById("namaSiswa");
const welcomeTextEl = document.getElementById("welcomeText");

const nilai = {
  Matematika: 85,
  "Bahasa Indonesia": 64,
  "Bahasa Inggris": 92,
  IPA: 45,
  IPS: 75
};

function setBar(barId, textId, value) {
  const bar = document.getElementById(barId);
  const text = document.getElementById(textId);

  const angka = Number(value) || 0;

  if (bar) bar.style.height = `${angka}%`;
  if (text) text.textContent = angka;
}

function renderChart(dataNilai = null) {
  let nilaiFinal = nilai;

  if (dataNilai && dataNilai.length > 0) {
    nilaiFinal = {};

    dataNilai.forEach((item) => {
      nilaiFinal[item.nama_mapel] = Number(item.nilai_angka) || 0;
    });
  }

  const nilaiMat = nilaiFinal.MAT || nilaiFinal.Matematika || 0;
  const nilaiInd = nilaiFinal.BIN || nilaiFinal["Bahasa Indonesia"] || 0;
  const nilaiIng = nilaiFinal.BIG || nilaiFinal["Bahasa Inggris"] || 0;
  const nilaiIpa = nilaiFinal.IPA || 0;
  const nilaiIps = nilaiFinal.IPS || 0;

  setBar("barMat", "textMat", nilaiMat);
  setBar("barInd", "textInd", nilaiInd);
  setBar("barIng", "textIng", nilaiIng);
  setBar("barIpa", "textIpa", nilaiIpa);
  setBar("barIps", "textIps", nilaiIps);

  const daftarNilai = Object.entries(nilaiFinal).filter((item) => Number(item[1]) > 0);

  if (daftarNilai.length === 0) {
    document.getElementById("avgValue").textContent = "0";
    document.getElementById("maxValue").textContent = "-";
    document.getElementById("minValue").textContent = "-";
    return;
  }

  const total = daftarNilai.reduce((sum, item) => sum + Number(item[1]), 0);
  const rataRata = (total / daftarNilai.length).toFixed(1);

  let tertinggi = daftarNilai[0];
  let terendah = daftarNilai[0];

  daftarNilai.forEach((item) => {
    if (Number(item[1]) > Number(tertinggi[1])) tertinggi = item;
    if (Number(item[1]) < Number(terendah[1])) terendah = item;
  });

  document.getElementById("avgValue").textContent = rataRata;
  document.getElementById("maxValue").textContent = `${tertinggi[0]} (${tertinggi[1]})`;
  document.getElementById("minValue").textContent = `${terendah[0]} (${terendah[1]})`;
}

function renderJadwalHariIni(jadwal) {
  const scheduleCard = document.querySelector(".schedule-card");
  const oldScheduleItem = document.querySelector(".schedule-item");

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

    renderChart(s.nilai_akademik);
    renderJadwalHariIni(s.jadwal_hari_ini);

  } catch (error) {
    console.error("Error load dashboard:", error);
    alert(error.message || "Terjadi error saat memuat dashboard siswa.");
  }
}

loadDashboard();