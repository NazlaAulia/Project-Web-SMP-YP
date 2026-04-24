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

  if (bar) bar.style.height = `${value}%`;
  if (text) text.textContent = value;
}

function renderChart() {
  setBar("barMat", "textMat", nilai.Matematika);
  setBar("barInd", "textInd", nilai["Bahasa Indonesia"]);
  setBar("barIng", "textIng", nilai["Bahasa Inggris"]);
  setBar("barIpa", "textIpa", nilai.IPA);
  setBar("barIps", "textIps", nilai.IPS);

  const daftarNilai = Object.entries(nilai);
  const total = daftarNilai.reduce((sum, item) => sum + item[1], 0);
  const rataRata = (total / daftarNilai.length).toFixed(1);

  let tertinggi = daftarNilai[0];
  let terendah = daftarNilai[0];

  daftarNilai.forEach((item) => {
    if (item[1] > tertinggi[1]) tertinggi = item;
    if (item[1] < terendah[1]) terendah = item;
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

    renderJadwalHariIni(s.jadwal_hari_ini);

  } catch (error) {
    console.error("Error load dashboard:", error);
    alert(error.message || "Terjadi error saat memuat dashboard siswa.");
  }
}

loadDashboard();