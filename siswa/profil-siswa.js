const idSiswa = localStorage.getItem("id_siswa");

const FOTO_KEY = idSiswa ? `foto_profil_${idSiswa}` : "foto_profil_siswa";
const DEFAULT_PHOTO = "../img/default-profile.png";

const elNama = document.getElementById("nama");
const elNisn = document.getElementById("nisn");
const elKelas = document.getElementById("kelas");
const elEmail = document.getElementById("email");
const elNoHp = document.getElementById("noHp");
const elAlamat = document.getElementById("alamat");
const elJenisKelamin = document.getElementById("jenisKelamin");
const elTanggalLahir = document.getElementById("tanggalLahir");
const elFoto = document.getElementById("fotoProfil");

const elNamaSiswa = document.getElementById("namaSiswa");
const elAvatarPlaceholder = document.getElementById("avatarPlaceholder");
const elNamaKelas = document.getElementById("namaKelas");

const elInputFoto = document.getElementById("inputFoto");
const elBtnPilihFoto = document.getElementById("btnPilihFoto");
const elUploadToast = document.getElementById("uploadToast");

let toastTimer = null;

const kelasMap = {
  1: "7A", 2: "7B", 3: "7C",
  9: "8A", 10: "8B", 11: "8C",
  18: "9A", 19: "9B", 20: "9C"
};

function getKelasLabel(idKelas) {
  return kelasMap[idKelas] || `Kelas ${idKelas ?? "-"}`;
}

function setText(el, value) {
  if (el) el.textContent = value && value !== "null" ? value : "-";
}

function setImage(el, src) {
  if (el) el.src = src || DEFAULT_PHOTO;
}

function isiHeaderDariLocalStorage() {
  const nama = localStorage.getItem("nama_siswa") || "Siswa";
  const kelas = localStorage.getItem("kelas_siswa") || "-";
  const avatar = nama && nama !== "-" ? nama.charAt(0).toUpperCase() : "-";

  setText(elNamaSiswa, nama);
  setText(elNamaKelas, kelas);
  setText(elAvatarPlaceholder, avatar);
}

function showToast(message, type = "success") {
  if (!elUploadToast) return;

  clearTimeout(toastTimer);
  elUploadToast.textContent = message;
  elUploadToast.className = `upload-toast ${type} show`;

  toastTimer = setTimeout(() => {
    elUploadToast.className = "upload-toast";
    elUploadToast.textContent = "";
  }, 2500);
}

function setProfileUI(data) {
  const nama = data?.nama || "-";
  const nisn = data?.nis || data?.nisn
    ? `${data?.nis || "-"} / ${data?.nisn || "-"}`
    : "-";
  const kelas = data?.kelas || getKelasLabel(data?.id_kelas);
  const email = "-";
  const noHp = "-";
  const alamat = data?.alamat || "-";
  const jenisKelamin = data?.jenis_kelamin === "L"
    ? "Laki-laki"
    : data?.jenis_kelamin === "P"
      ? "Perempuan"
      : data?.jenis_kelamin || "-";
  const tanggalLahir = data?.tanggal_lahir || "-";

  setText(elNama, nama);
  setText(elNisn, nisn);
  setText(elKelas, kelas);
  setText(elEmail, email);
  setText(elNoHp, noHp);
  setText(elAlamat, alamat);
  setText(elJenisKelamin, jenisKelamin);
  setText(elTanggalLahir, tanggalLahir);

  setText(elNamaSiswa, nama);
  setText(elNamaKelas, kelas);
  setText(elAvatarPlaceholder, nama !== "-" ? nama.charAt(0).toUpperCase() : "-");

  if (nama && nama !== "-") {
    localStorage.setItem("nama_siswa", nama);
  }

  if (kelas && kelas !== "-") {
    localStorage.setItem("kelas_siswa", kelas);
  }

  if (data?.id_siswa) {
    localStorage.setItem("id_siswa", data.id_siswa);
  }
}

async function loadProfilSiswa() {
  const idSiswaTerbaru = localStorage.getItem("id_siswa");
  const fotoLocal = idSiswaTerbaru
    ? localStorage.getItem(`foto_profil_${idSiswaTerbaru}`)
    : localStorage.getItem(FOTO_KEY);

  setImage(elFoto, fotoLocal || DEFAULT_PHOTO);

  try {
    let url = "./get_profil_siswa.php";

    if (idSiswaTerbaru) {
      url += `?id_siswa=${encodeURIComponent(idSiswaTerbaru)}`;
    }

    const response = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const text = await response.text();
    console.log("RESPON get_profil_siswa.php:", text);

    let result;

    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error("PHP bukan JSON:", text);
      return;
    }

    if (!result.success) {
      console.error(result.message || "Data siswa tidak ditemukan.");
      return;
    }

    const siswaData = result.data;

    setProfileUI(siswaData);

    if (siswaData?.foto_profil) {
      const fotoUrl = siswaData.foto_profil + "?t=" + Date.now();
      setImage(elFoto, fotoUrl);
      localStorage.setItem(`foto_profil_${siswaData.id_siswa}`, siswaData.foto_profil);
    } else {
      setImage(elFoto, fotoLocal || DEFAULT_PHOTO);
    }
  } catch (error) {
    console.error("Gagal load profil siswa:", error);
    setImage(elFoto, fotoLocal || DEFAULT_PHOTO);
  }
}

async function pilihDanUploadFoto(event) {
  const file = event.target.files[0];
  if (!file) return;

  try {
    if (!file.type || !file.type.startsWith("image/")) {
      showToast("File harus berupa gambar.", "error");
      elInputFoto.value = "";
      return;
    }

    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
      showToast("Ukuran gambar maksimal 2 MB.", "error");
      elInputFoto.value = "";
      return;
    }

    const previewURL = URL.createObjectURL(file);
    setImage(elFoto, previewURL);

    if (elBtnPilihFoto) {
      elBtnPilihFoto.disabled = true;
      elBtnPilihFoto.textContent = "Menyimpan...";
    }

    const formData = new FormData();
    formData.append("foto", file);

    if (idSiswa) {
      formData.append("id_siswa", idSiswa);
    }

    const response = await fetch("upload-foto-profil.php", {
      method: "POST",
      body: formData,
      credentials: "same-origin"
    });

    const text = await response.text();
    console.log("RESPON upload-foto-profil.php:", text);

    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      throw new Error(text);
    }

    if (!result.success) {
      throw new Error(result.message || "Upload gagal.");
    }

    const fotoBaru = result.foto_url;
    const idFinal = result.id_siswa || idSiswa;

    if (idFinal) {
      localStorage.setItem("id_siswa", idFinal);
      localStorage.setItem(`foto_profil_${idFinal}`, fotoBaru);
    }

    setImage(elFoto, fotoBaru + "?t=" + Date.now());
    showToast("Foto profil berhasil disimpan.", "success");
  } catch (error) {
    console.error("Gagal upload:", error);

    const fotoLocal = localStorage.getItem(FOTO_KEY);
    setImage(elFoto, fotoLocal || DEFAULT_PHOTO);

    showToast(
      "Gagal upload: " + (error?.message || "Terjadi kesalahan."),
      "error"
    );
  } finally {
    if (elBtnPilihFoto) {
      elBtnPilihFoto.disabled = false;
      elBtnPilihFoto.textContent = "Pilih Foto";
    }

    elInputFoto.value = "";
  }
}

if (elBtnPilihFoto && elInputFoto) {
  elBtnPilihFoto.addEventListener("click", () => {
    elInputFoto.click();
  });

  elInputFoto.addEventListener("change", pilihDanUploadFoto);
}

isiHeaderDariLocalStorage();
loadProfilSiswa();