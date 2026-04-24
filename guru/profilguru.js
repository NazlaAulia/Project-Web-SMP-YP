alert("profilguru.js kebaca");

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
  console.log("Data guru yang diisi:", guru);

  if (displayNamaGuru) displayNamaGuru.textContent = guru.nama || "-";
  if (displayMapelGuru) displayMapelGuru.textContent = "Guru " + (guru.nama_mapel || "-");
  if (displayNipGuru) displayNipGuru.textContent = guru.nip || "-";
  if (displayEmailGuru) displayEmailGuru.textContent = guru.email || "-";

  if (namaGuru) namaGuru.value = guru.nama || "";
  if (nipGuru) nipGuru.value = guru.nip || "";
  if (emailGuru) emailGuru.value = guru.email || "";
  if (mapelGuru) mapelGuru.value = guru.nama_mapel || "-";
  if (mapelAkademikGuru) mapelAkademikGuru.value = guru.nama_mapel || "-";
}

function loadProfilGuru() {
  fetch("get_profil_guru.php")
    .then(response => response.json())
    .then(result => {
      console.log("Hasil get_profil_guru.php:", result);

      if (result.status === "success") {
        isiProfilGuru(result.data);
        return;
      }

      const idGuru = localStorage.getItem("id_guru");
      console.log("id_guru dari localStorage:", idGuru);

      if (!idGuru) {
        alert("Session gagal dan id_guru localStorage kosong");
        return;
      }

      fetch("get_guru.php?id_guru=" + encodeURIComponent(idGuru))
        .then(response => response.json())
        .then(resultGuru => {
          console.log("Hasil get_guru.php:", resultGuru);

          if (resultGuru.status === "success") {
            isiProfilGuru(resultGuru.data);
          } else {
            alert(resultGuru.message);
          }
        });
    })
    .catch(error => {
      console.error("Error profil guru:", error);
      alert("Gagal memuat profil guru. Cek Console.");
    });
}

loadProfilGuru();