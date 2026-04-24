const uploadFoto = document.getElementById("uploadFoto");
const previewFoto = document.getElementById("previewFoto");

const displayNamaGuru = document.getElementById("displayNamaGuru");
const displayMapelGuru = document.getElementById("displayMapelGuru");
const displayNipGuru = document.getElementById("displayNipGuru");
const displayEmailGuru = document.getElementById("displayEmailGuru");

const namaGuru = document.getElementById("namaGuru");
const nipGuru = document.getElementById("nipGuru");
const emailGuru = document.getElementById("emailGuru");
const mapelGuru = document.getElementById("mapelGuru");
const mapelAkademikGuru = document.getElementById("mapelAkademikGuru");

if (uploadFoto) {
  uploadFoto.addEventListener("change", function () {
    const file = this.files[0];

    if (file) {
      const reader = new FileReader();

      reader.onload = function (e) {
        previewFoto.src = e.target.result;
      };

      reader.readAsDataURL(file);
    }
  });
}

function isiProfilGuru(guru) {
  if (displayNamaGuru) displayNamaGuru.textContent = guru.nama || "-";
  if (displayMapelGuru) displayMapelGuru.textContent = "Guru " + (guru.nama_mapel || "-");
  if (displayNipGuru) displayNipGuru.textContent = guru.nip || "-";
  if (displayEmailGuru) displayEmailGuru.textContent = guru.email || "-";

  if (namaGuru) namaGuru.value = guru.nama || "";
  if (nipGuru) nipGuru.value = guru.nip || "";
  if (emailGuru) emailGuru.value = guru.email || "";

  // karena mapelGuru adalah <select>, value-nya harus id_mapel
  if (mapelGuru) mapelGuru.value = guru.id_mapel || "";

  // ini hanya untuk tampilan nama mapel di Informasi Akademik
  if (mapelAkademikGuru) mapelAkademikGuru.value = guru.nama_mapel || "-";
}

function loadProfilGuru() {
  const idGuru = localStorage.getItem("id_guru");

  if (!idGuru) {
    alert("Data login guru tidak ditemukan. Silakan login ulang.");
    window.location.href = "login.html";
    return;
  }

  fetch("get_guru.php?id_guru=" + encodeURIComponent(idGuru))
    .then(response => response.json())
    .then(result => {
      console.log("Hasil get_guru.php:", result);

      if (result.status === "success") {
        isiProfilGuru(result.data);
      } else {
        alert(result.message || "Data guru tidak ditemukan");
      }
    })
    .catch(error => {
      console.error(error);
      alert("Gagal memuat data profil guru");
    });
}

loadProfilGuru();
const btnSimpanProfil = document.getElementById("btnSimpanProfil");

if (btnSimpanProfil) {
  btnSimpanProfil.addEventListener("click", function () {
    const idGuru = localStorage.getItem("id_guru");

    if (!idGuru) {
      alert("ID guru tidak ditemukan. Silakan login ulang.");
      return;
    }

    const formData = new FormData();
    formData.append("id_guru", idGuru);
    formData.append("nama", namaGuru.value);
    formData.append("nip", nipGuru.value);
    formData.append("email", emailGuru.value);
    formData.append("id_mapel", mapelGuru.value);

    fetch("update_profil_guru.php", {
      method: "POST",
      body: formData
    })
      .then(response => response.json())
      .then(result => {
        alert(result.message);

        if (result.status === "success") {
          loadProfilGuru();
        }
      })
      .catch(error => {
        console.error(error);
        alert("Gagal menyimpan profil guru");
      });
  });
}