let idSiswa = localStorage.getItem("id_siswa");

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

function getFotoLocal() {
  const id = localStorage.getItem("id_siswa");

  if (id) {
    const fotoId = localStorage.getItem(`foto_profil_${id}`);
    if (fotoId) return fotoId;
  }

  const fotoUmum = localStorage.getItem("foto_profil_siswa");
  if (fotoUmum) return fotoUmum;

  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);

    if (key && key.match(/^foto_profil_\d+$/)) {
      return localStorage.getItem(key);
    }
  }

  return "";
}

function ambilIdentitasLogin() {
  const data = {
    id_siswa: localStorage.getItem("id_siswa") || "",
    id_user: localStorage.getItem("id_user") || "",
    username: localStorage.getItem("username") || ""
  };

  const fotoLocal = getFotoLocal();

  if (!data.id_siswa && fotoLocal) {
    const match = fotoLocal.match(/siswa_(\d+)_/);

    if (match && match[1]) {
      data.id_siswa = match[1];
      localStorage.setItem("id_siswa", match[1]);
      localStorage.setItem(`foto_profil_${match[1]}`, fotoLocal);
    }
  }

  for (let i = 0; i < localStorage.length; i++) {
    const key = localStorage.key(i);
    const value = localStorage.getItem(key);

    if (!key || !value) continue;

    if (!data.id_siswa && key.match(/^foto_profil_\d+$/)) {
      data.id_siswa = key.replace("foto_profil_", "");
      localStorage.setItem("id_siswa", data.id_siswa);
    }

    try {
      const json = JSON.parse(value);

      if (!data.id_siswa && json.id_siswa) data.id_siswa = json.id_siswa;
      if (!data.id_user && json.id_user) data.id_user = json.id_user;
      if (!data.username && json.username) data.username = json.username;
    } catch (e) {}
  }

  return data;
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
  const nisn = `${data?.nis || "-"} / ${data?.nisn || "-"}`;
  const kelas = data?.kelas || getKelasLabel(data?.id_kelas);
  const email = data?.email || "-";
  const noHp = data?.no_hp || "-";
  const alamat = data?.alamat || "-";
  const jenisKelamin = data?.jenis_kelamin === "L"
    ? "Laki-laki"
    : data?.jenis_kelamin === "P"
      ? "Perempuan"
      : "-";
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

  if (nama !== "-") localStorage.setItem("nama_siswa", nama);
  if (kelas !== "-") localStorage.setItem("kelas_siswa", kelas);
  if (data?.id_siswa) localStorage.setItem("id_siswa", data.id_siswa);
  if (data?.username) localStorage.setItem("username", data.username);
}

async function loadProfilSiswa() {
  const fotoLocalAwal = getFotoLocal();
  setImage(elFoto, fotoLocalAwal || DEFAULT_PHOTO);

  try {
    const identitas = ambilIdentitasLogin();

    let url = "./get-profil-siswa.php";
    const params = new URLSearchParams();

    if (identitas.id_siswa) {
      params.append("id_siswa", identitas.id_siswa);
    } else {
      params.append("id_siswa", "1293");
    }

    if (identitas.id_user) params.append("id_user", identitas.id_user);
    if (identitas.username) params.append("username", identitas.username);

    url += "?" + params.toString();

    const response = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store"
    });

    const text = await response.text();
    console.log("RESPON get-profil-siswa.php:", text);

    let result;

    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error("PHP bukan JSON:", text);
      return;
    }

    if (!result.success) {
      console.error(result.message || "Data siswa tidak ditemukan.");
      setImage(elFoto, fotoLocalAwal || DEFAULT_PHOTO);
      return;
    }

    const siswaData = result.data;

    setProfileUI(siswaData);

    if (siswaData.id_siswa) {
      idSiswa = siswaData.id_siswa;
      localStorage.setItem("id_siswa", siswaData.id_siswa);
    }

    if (siswaData.username) {
      localStorage.setItem("username", siswaData.username);
    }

    if (siswaData.foto_profil) {
      const fotoUrl = siswaData.foto_profil + "?t=" + Date.now();

      setImage(elFoto, fotoUrl);

      localStorage.setItem(`foto_profil_${siswaData.id_siswa}`, siswaData.foto_profil);
      localStorage.setItem("foto_profil_siswa", siswaData.foto_profil);
    } else {
      setImage(elFoto, fotoLocalAwal || DEFAULT_PHOTO);
    }
  } catch (error) {
    console.error("Gagal load profil siswa:", error);
    setImage(elFoto, fotoLocalAwal || DEFAULT_PHOTO);
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

    const identitas = ambilIdentitasLogin();
    idSiswa = localStorage.getItem("id_siswa") || identitas.id_siswa || idSiswa;

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
      idSiswa = idFinal;
      localStorage.setItem("id_siswa", idFinal);
      localStorage.setItem(`foto_profil_${idFinal}`, fotoBaru);
    }

    localStorage.setItem("foto_profil_siswa", fotoBaru);

    setImage(elFoto, fotoBaru + "?t=" + Date.now());
    showToast("Foto profil berhasil disimpan.", "success");

    loadProfilSiswa();
  } catch (error) {
    console.error("Gagal upload:", error);

    const fotoLocal = getFotoLocal();
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