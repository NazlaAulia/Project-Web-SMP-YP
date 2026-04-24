import { db } from "./firebase-config.js";
import {
  doc,
  getDoc,
  collection,
  query,
  where,
  getDocs,
  updateDoc
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";
import { supabase } from "./supabase-config.js";

const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) {
  window.location.replace("../login.html");
}

const namaKelasEl = document.getElementById("namaKelas");
const avatarHurufEl = document.getElementById("avatarHuruf");
const namaSiswaEl = document.getElementById("namaSiswa");
const alertBox = document.getElementById("alertBox");

const formPassword = document.getElementById("formPassword");
const passwordLamaEl = document.getElementById("password_lama");
const passwordBaruEl = document.getElementById("password_baru");
const konfirmasiPasswordEl = document.getElementById("konfirmasi_password");
const btnSimpanPassword = document.getElementById("btnSimpanPassword");

const strengthFill = document.getElementById("strengthFill");
const strengthText = document.getElementById("strengthText");

let currentSiswa = null;
let currentUserDocId = null;
let currentUserData = null;

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "S";

  if (namaSiswaEl) namaSiswaEl.textContent = nama;
  if (namaKelasEl) namaKelasEl.textContent = kelas;
  if (avatarHurufEl) avatarHurufEl.textContent = avatar;
}

function showAlert(type, message) {
  alertBox.innerHTML = `
    <div class="settings-alert ${type === "success" ? "success-alert" : "error-alert"}">
      ${message}
    </div>
  `;
}

function clearAlert() {
  alertBox.innerHTML = "";
}

function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const icon = button.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

function checkPasswordStrength(password) {
  let score = 0;

  if (password.length >= 8) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[a-z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  if (password.length === 0) {
    strengthFill.style.width = "0%";
    strengthText.textContent = "Minimal 8 karakter";
  } else if (score <= 2) {
    strengthFill.style.width = "35%";
    strengthText.textContent = "Lemah";
  } else if (score <= 4) {
    strengthFill.style.width = "70%";
    strengthText.textContent = "Sedang";
  } else {
    strengthFill.style.width = "100%";
    strengthText.textContent = "Sangat kuat";
  }
}

async function getNamaKelas(idKelas) {
  if (!idKelas) return "-";

  try {
    const kelasRef = doc(db, "kelas", String(idKelas));
    const kelasSnap = await getDoc(kelasRef);

    if (kelasSnap.exists()) {
      const data = kelasSnap.data();
      return data.nama_kelas || "-";
    }

    const q = query(collection(db, "kelas"), where("id_kelas", "==", Number(idKelas)));
    const snap = await getDocs(q);

    if (!snap.empty) {
      return snap.docs[0].data().nama_kelas || "-";
    }

    return "-";
  } catch (error) {
    console.error("Gagal ambil kelas:", error);
    return "-";
  }
}

async function loadSiswaDanUser() {
  if (!idSiswa) {
    showAlert("error", "Sesi login tidak ditemukan. Silakan login dulu.");
    namaSiswaEl.textContent = "Siswa";
    avatarHurufEl.textContent = "S";
    namaKelasEl.textContent = "-";
    return;
  }

  try {
    const siswaRef = doc(db, "siswa", String(idSiswa));
    const siswaSnap = await getDoc(siswaRef);

    if (!siswaSnap.exists()) {
      showAlert("error", "Data siswa tidak ditemukan.");
      return;
    }

    currentSiswa = siswaSnap.data();

    const nama = currentSiswa.nama || "Siswa";
    const hurufAwal = nama.charAt(0).toUpperCase();
    const namaKelas = await getNamaKelas(currentSiswa.id_kelas);

    namaSiswaEl.textContent = nama;
    avatarHurufEl.textContent = hurufAwal;
    namaKelasEl.textContent = namaKelas;

    if (nama && nama !== "Siswa") {
      localStorage.setItem("nama_siswa", nama);
    }

    if (namaKelas && namaKelas !== "-") {
      localStorage.setItem("kelas_siswa", namaKelas);
    }

    const qUser = query(collection(db, "user"), where("id_siswa", "==", Number(idSiswa)));
    const userSnap = await getDocs(qUser);

    if (userSnap.empty) {
      showAlert("error", "Data user untuk siswa ini tidak ditemukan.");
      return;
    }

    currentUserDocId = userSnap.docs[0].id;
    currentUserData = userSnap.docs[0].data();
  } catch (error) {
    console.error(error);
    showAlert("error", "Terjadi kesalahan saat memuat data settings.");
  }
}

formPassword.addEventListener("submit", async (e) => {
  e.preventDefault();
  clearAlert();

  const passwordLama = passwordLamaEl.value.trim();
  const passwordBaru = passwordBaruEl.value.trim();
  const konfirmasiPassword = konfirmasiPasswordEl.value.trim();

  if (!passwordLama || !passwordBaru || !konfirmasiPassword) {
    showAlert("error", "Semua field wajib diisi.");
    return;
  }

  if (passwordBaru.length < 8) {
    showAlert("error", "Password baru minimal 8 karakter.");
    return;
  }

  if (passwordBaru !== konfirmasiPassword) {
    showAlert("error", "Konfirmasi password baru tidak cocok.");
    return;
  }

  if (!currentUserDocId || !currentUserData) {
    showAlert("error", "Data user belum siap.");
    return;
  }

  if (passwordLama !== currentUserData.password) {
    showAlert("error", "Password lama salah.");
    return;
  }

  btnSimpanPassword.disabled = true;
  btnSimpanPassword.textContent = "Menyimpan...";

  try {
    // 1. Pastikan session Supabase aktif
    const { data: userData, error: userError } = await supabase.auth.getUser();

    if (userError || !userData.user) {
      throw new Error("Sesi Supabase tidak ditemukan. Silakan login ulang.");
    }

    // 2. Update password di Supabase Auth
    const { error: updateAuthError } = await supabase.auth.updateUser({
      password: passwordBaru
    });

    if (updateAuthError) {
      throw new Error("Gagal update password di Supabase: " + updateAuthError.message);
    }

    // 3. Update password di Firestore
    const userRef = doc(db, "user", currentUserDocId);
    await updateDoc(userRef, {
      password: passwordBaru
    });

    currentUserData.password = passwordBaru;

    formPassword.reset();
    checkPasswordStrength("");
    showAlert("success", "Password berhasil diubah.");
  } catch (error) {
    console.error(error);
    showAlert("error", error.message || "Gagal mengubah password.");
  } finally {
    btnSimpanPassword.disabled = false;
    btnSimpanPassword.textContent = "Simpan Password";
  }
});

document.querySelectorAll(".eye-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const target = btn.getAttribute("data-target");
    togglePassword(target, btn);
  });
});

passwordBaruEl.addEventListener("input", (e) => {
  checkPasswordStrength(e.target.value);
});

isiHeaderDariLocalStorage();
checkPasswordStrength("");
loadSiswaDanUser();