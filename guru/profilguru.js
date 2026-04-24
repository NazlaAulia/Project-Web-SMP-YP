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

function loadProfilGuru() {
  fetch("get_profil_guru.php")
    .then(response => response.json())
    .then(result => {
      console.log(result);

      if (result.status === "success") {
        const guru = result.data;

        displayNamaGuru.textContent = guru.nama;
        displayMapelGuru.textContent = `Guru ${guru.nama_mapel ?? "-"}`;
        displayNipGuru.textContent = guru.nip;
        displayEmailGuru.textContent = guru.email;

        if (namaGuru) namaGuru.value = guru.nama;
        if (nipGuru) nipGuru.value = guru.nip;
        if (emailGuru) emailGuru.value = guru.email;
        if (mapelGuru) mapelGuru.value = guru.nama_mapel ?? "-";
      } else {
        alert(result.message);
        window.location.href = "login.html";
      }
    })
    .catch(error => {
      console.error(error);
      alert("Gagal memuat profil guru");
    });
}

loadProfilGuru();