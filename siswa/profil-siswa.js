import { auth, db } from "./firebase-config.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
import { doc, getDoc } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

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

const DEFAULT_PHOTO = "https://via.placeholder.com/150x150.png?text=Foto";

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
  if (el) el.textContent = value;
}

function setImage(el, src) {
  if (el) el.src = src;
}

function setProfileUI(data, authUser) {
  const nama = data?.nama || "-";
  const nisn = data?.nisn || data?.nis || "-";
  const kelas = data?.kelas || getKelasLabel(data?.id_kelas);
  const email = data?.email || authUser?.email || "-";
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

async function loadProfilSiswa(user) {
  try {
    const userRef = doc(db, "users", user.uid);
    const userSnap = await getDoc(userRef);

    if (!userSnap.exists()) {
      alert("Data user tidak ditemukan di collection users.");
      return;
    }

    const userData = userSnap.data();

    if (userData.role !== "siswa") {
      alert("Akun ini bukan akun siswa.");
      return;
    }

    const siswaId = String(userData.siswaId || "");
    if (!siswaId) {
      alert("siswaId pada data user belum diisi.");
      return;
    }

    const siswaRef = doc(db, "siswa", siswaId);
    const siswaSnap = await getDoc(siswaRef);

    if (!siswaSnap.exists()) {
      alert("Data siswa tidak ditemukan.");
      return;
    }

    const siswaData = siswaSnap.data();
    setProfileUI(siswaData, user);
  } catch (error) {
    console.error("Gagal load profil siswa:", error);
    alert("Gagal memuat profil siswa.");
  }
}

onAuthStateChanged(auth, async (user) => {
  if (!user) return;
  await loadProfilSiswa(user);
});