import { db } from "./firebase-config.js";
import {
  doc,
  getDoc,
  updateDoc
} from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

import { supabase } from "./supabase-config.js";

const idSiswa = localStorage.getItem("id_siswa");

if (!idSiswa) {
  alert("Silakan login terlebih dahulu.");
  window.location.href = "../login.html";
}

const FOTO_KEY = `foto_profil_${idSiswa}`;
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
  if (el) el.textContent = value || "-";
}

function setImage(el, src) {
  if (el) el.src = src || DEFAULT_PHOTO;
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
  const nisn = data?.nisn || data?.nis || "-";
  const kelas = data?.kelas || getKelasLabel(data?.id_kelas);
  const email = data?.email || "-";
  const noHp = data?.no_hp || data?.noHp || "-";
  const alamat = data?.alamat || "-";
  const jenisKelamin = data?.jenis_kelamin || data?.gender || "-";
  const tanggalLahir = data?.tanggal_lahir || data?.tgl_lahir || "-";

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

    if (siswaData?.foto_profil) {
      setImage(elFoto, siswaData.foto_profil);
      localStorage.setItem(FOTO_KEY, siswaData.foto_profil);
    } else {
      const fotoLocal = localStorage.getItem(FOTO_KEY);
      setImage(elFoto, fotoLocal || DEFAULT_PHOTO);
    }
  } catch (error) {
    console.error("Gagal load profil siswa:", error);
    const fotoLocal = localStorage.getItem(FOTO_KEY);
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

    const { data: userData, error: userError } = await supabase.auth.getUser();
    const user = userData?.user;

    if (userError || !user) {
      throw new Error("Supabase Auth tidak mendeteksi login. Silakan login ulang.");
    }

    const fileExt = (file.name.split(".").pop() || "jpg").toLowerCase();
    const fileName = `foto-${Date.now()}.${fileExt}`;
    const filePath = `${user.id}/${fileName}`;

    const { error: uploadError } = await supabase.storage
      .from("foto-profil")
      .upload(filePath, file, {
        upsert: true,
        cacheControl: "3600"
      });

    if (uploadError) {
      throw uploadError;
    }

    const { data: publicData } = supabase.storage
      .from("foto-profil")
      .getPublicUrl(filePath);

    const publicUrl = publicData?.publicUrl;
    if (!publicUrl) {
      throw new Error("Gagal mendapatkan URL foto profil.");
    }

    localStorage.setItem(FOTO_KEY, publicUrl);

    const siswaRef = doc(db, "siswa", String(idSiswa));
    await updateDoc(siswaRef, {
      foto_profil: publicUrl
    });

    setImage(elFoto, publicUrl);
    showToast("Foto profil berhasil disimpan.", "success");
  } catch (error) {
    console.error("Gagal upload:", error);

    const fotoLocal = localStorage.getItem(FOTO_KEY);
    if (fotoLocal) {
      setImage(elFoto, fotoLocal);
    } else {
      setImage(elFoto, DEFAULT_PHOTO);
    }

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

loadProfilSiswa();