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

function isiHeader(nama, kelas) {
  const namaFinal = nama || "Siswa";
  const kelasFinal = kelas || "-";
  const avatar = namaFinal && namaFinal !== "-" ? namaFinal.charAt(0).toUpperCase() : "S";

  if (namaSiswaEl) namaSiswaEl.textContent = namaFinal;
  if (namaKelasEl) namaKelasEl.textContent = kelasFinal;
  if (avatarHurufEl) avatarHurufEl.textContent = avatar;
}

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";

  isiHeader(nama, kelas);
}

async function loadSettingsSiswa() {
  try {
    const response = await fetch(
      `get_settings_siswa.php?id_siswa=${encodeURIComponent(idSiswa)}`,
      {
        method: "GET",
        credentials: "same-origin"
      }
    );

    const text = await response.text();
    console.log("RAW get_settings_siswa:", text);

    const result = JSON.parse(text);
    console.log("JSON get_settings_siswa:", result);

    if (!result.success) {
      showAlert("error", result.message || "Gagal mengambil data siswa.");
      return;
    }

    const siswa = result.siswa || {};
    const nama = siswa.nama || "Siswa";
    const kelas = siswa.kelas || "-";

    localStorage.setItem("nama_siswa", nama);
    localStorage.setItem("kelas_siswa", kelas);

    isiHeader(nama, kelas);
  } catch (error) {
    console.error("Gagal load settings siswa:", error);
    showAlert("error", "Terjadi kesalahan saat memuat data settings.");
  }
}

function showAlert(type, message) {
  if (!alertBox) return;

  alertBox.innerHTML = `
    <div class="settings-alert ${type === "success" ? "success-alert" : "error-alert"}">
      ${message}
    </div>
  `;
}

function clearAlert() {
  if (!alertBox) return;
  alertBox.innerHTML = "";
}

function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  const icon = button.querySelector("i");

  if (!input || !icon) return;

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
    if (strengthFill) strengthFill.style.width = "0%";
    if (strengthText) strengthText.textContent = "Minimal 8 karakter";
  } else if (score <= 2) {
    if (strengthFill) strengthFill.style.width = "35%";
    if (strengthText) strengthText.textContent = "Lemah";
  } else if (score <= 4) {
    if (strengthFill) strengthFill.style.width = "70%";
    if (strengthText) strengthText.textContent = "Sedang";
  } else {
    if (strengthFill) strengthFill.style.width = "100%";
    if (strengthText) strengthText.textContent = "Sangat kuat";
  }
}

if (formPassword) {
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

    if (btnSimpanPassword) {
      btnSimpanPassword.disabled = true;
      btnSimpanPassword.textContent = "Menyimpan...";
    }

    try {
      const formData = new FormData();
      formData.append("id_siswa", idSiswa);
      formData.append("password_lama", passwordLama);
      formData.append("password_baru", passwordBaru);
      formData.append("konfirmasi_password", konfirmasiPassword);

      const response = await fetch("update_password_siswa.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin"
      });

      const text = await response.text();
      console.log("RAW update_password_siswa:", text);

      const result = JSON.parse(text);

      if (!result.success) {
        showAlert("error", result.message || "Gagal mengubah password.");
        return;
      }

      formPassword.reset();
      checkPasswordStrength("");
      showAlert("success", result.message || "Password berhasil diubah.");
    } catch (error) {
      console.error("Gagal update password:", error);
      showAlert("error", "Terjadi kesalahan saat mengubah password.");
    } finally {
      if (btnSimpanPassword) {
        btnSimpanPassword.disabled = false;
        btnSimpanPassword.textContent = "Simpan Password";
      }
    }
  });
}

document.querySelectorAll(".eye-btn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const target = btn.getAttribute("data-target");
    togglePassword(target, btn);
  });
});

if (passwordBaruEl) {
  passwordBaruEl.addEventListener("input", (e) => {
    checkPasswordStrength(e.target.value);
  });
}

isiHeaderDariLocalStorage();
checkPasswordStrength("");
loadSettingsSiswa();