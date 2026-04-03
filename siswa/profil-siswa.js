import { db, storage } from "./firebase-config.js";
import {
  doc,
  getDoc,
  updateDoc
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";
import {
  ref,
  uploadBytes,
  getDownloadURL
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-storage.js";

const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) {
  alert("Silakan login terlebih dahulu.");
  window.location.href = "../login.html";
}

const elNama = document.getElementById("nama");
const elNisn = document.getElementById("nisn");
const elKelas = document.getElementById("kelas");
const elEmail = document.getElementById("email");
const elNoHp = document.getElementById("noHp");
const elAlamat = document.getElementById("alamat");
const elFoto = document.getElementById("fotoProfil");

const elNamaSiswa = document.getElementById("namaSiswa");
const elAvatarPlaceholder = document.getElementById("avatarPlaceholder");
const elNamaKelas = document.getElementById("namaKelas");

const elInputFoto = document.getElementById("inputFoto");
const elBtnUploadFoto = document.getElementById("btnUploadFoto");

const DEFAULT_PHOTO = "https://via.placeholder.com/150x150.png?text=Foto";
let selectedFile = null;

const kelasMap = {
  1: "7A",
  2: "7B",
  3: "7C",
  9: "8A",
  10: "8B",
  11: "8C",
  18: "9A",
  19: "9B",
  20: "9C"
};

function getKelasLabel(idKelas) {
  return kelasMap[idKelas] || `Kelas ${idKelas ?? "-"}`;
}

function setText(el, value) {
  if (el) el.textContent = value || "-";
}

function setImage(el, src) {
  if (el) el.src = src || DEFAULT_PHOTO;
}

function setProfileUI(data) {
  const nama = data?.nama || "-";
  const nisn = data?.nisn || data?.nis || "-";
  const kelas = data?.kelas || getKelasLabel(data?.id_kelas);
  const email = data?.email || "-";
  const noHp = data?.no_hp || data?.noHp || "-";
  const alamat = data?.alamat || "-";
  const foto = data?.foto_profil || data?.fotoProfil || DEFAULT_PHOTO;

  setText(elNama, nama);
  setText(elNisn, nisn);
  setText(elKelas, kelas);
  setText(elEmail, email);
  setText(elNoHp, noHp);
  setText(elAlamat, alamat);
  setImage(elFoto, foto);

  setText(elNamaSiswa, nama);
  setText(elNamaKelas, kelas);
  setText(elAvatarPlaceholder, nama !== "-" ? nama.charAt(0).toUpperCase() : "-");
}

async function loadProfilSiswa() {
  try {
    const siswaRef = doc(db, "siswa", String(idSiswa));
    const siswaSnap = await getDoc(siswaRef);

    if (!siswaSnap.exists()) {
      alert("Data siswa tidak ditemukan.");
      return;
    }

    const siswaData = siswaSnap.data();
    setProfileUI(siswaData);
  } catch (error) {
    console.error("Gagal load profil siswa:", error);
    alert("Gagal memuat profil siswa.");
  }
}

function handlePreviewFoto(event) {
  const file = event.target.files[0];

  if (!file) {
    selectedFile = null;
    return;
  }

  if (!file.type.startsWith("image/")) {
    alert("File harus berupa gambar.");
    event.target.value = "";
    selectedFile = null;
    return;
  }

  selectedFile = file;
  const previewURL = URL.createObjectURL(file);
  setImage(elFoto, previewURL);
}

async function uploadFotoProfil() {
  try {
    if (!idSiswa) {
      alert("Data siswa belum siap.");
      return;
    }

    if (!selectedFile) {
      alert("Pilih foto dulu dari galeri.");
      return;
    }

    if (elBtnUploadFoto) {
      elBtnUploadFoto.disabled = true;
      elBtnUploadFoto.textContent = "Mengupload...";
    }

    const fileExt = selectedFile.name.split(".").pop();
    const fileName = `foto-${Date.now()}.${fileExt}`;
    const storageRef = ref(storage, `foto-profil/${idSiswa}/${fileName}`);

    await uploadBytes(storageRef, selectedFile);
    const downloadURL = await getDownloadURL(storageRef);

    const siswaRef = doc(db, "siswa", String(idSiswa));
    await updateDoc(siswaRef, {
      foto_profil: downloadURL
    });

    setImage(elFoto, downloadURL);
    alert("Foto profil berhasil diupload.");

    if (elInputFoto) elInputFoto.value = "";
    selectedFile = null;
  } catch (error) {
    console.error("Gagal upload foto profil:", error);
    alert("Gagal upload foto profil.");
  } finally {
    if (elBtnUploadFoto) {
      elBtnUploadFoto.disabled = false;
      elBtnUploadFoto.textContent = "Upload Foto";
    }
  }
}

if (elInputFoto) elInputFoto.addEventListener("change", handlePreviewFoto);
if (elBtnUploadFoto) elBtnUploadFoto.addEventListener("click", uploadFotoProfil);

loadProfilSiswa();