import { db } from "./firebase-config.js";
import {
  doc,
  getDoc
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

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

async function getNamaKelas(idKelas) {
  try {
    const kelasRef = doc(db, "kelas", String(idKelas));
    const kelasSnap = await getDoc(kelasRef);

    if (kelasSnap.exists()) {
      const kelasData = kelasSnap.data();
      return kelasData.nama_kelas || "-";
    }

    return "-";
  } catch (error) {
    console.error("Gagal mengambil data kelas:", error);
    return "-";
  }
}

async function loadDashboard() {
  renderChart();

  if (!idSiswa) {
    namaSiswaEl.textContent = "Siswa";
    welcomeTextEl.textContent = "Halo, Siswa!";
    avatarPlaceholderEl.textContent = "S";
    namaKelasEl.textContent = "-";
    alert("id_siswa belum ada di localStorage. Untuk testing isi dulu localStorage.");
    return;
  }

  try {
    const siswaRef = doc(db, "siswa", String(idSiswa));
    const siswaSnap = await getDoc(siswaRef);

    if (!siswaSnap.exists()) {
      alert("Data siswa tidak ditemukan di Firestore.");
      namaSiswaEl.textContent = "Siswa";
      welcomeTextEl.textContent = "Halo, Siswa!";
      avatarPlaceholderEl.textContent = "S";
      namaKelasEl.textContent = "-";
      return;
    }

    const s = siswaSnap.data();
    const nama = s.nama || "Siswa";
    const hurufAwal = nama.charAt(0).toUpperCase();
    const namaKelas = await getNamaKelas(s.id_kelas);

    namaSiswaEl.textContent = nama;
    welcomeTextEl.textContent = `Halo, ${nama}!`;
    avatarPlaceholderEl.textContent = hurufAwal;
    namaKelasEl.textContent = namaKelas;
  } catch (error) {
    console.error("Error load dashboard:", error);
    alert("Terjadi error saat memuat dashboard siswa.");
  }
}

loadDashboard();