const uploadFoto = document.getElementById("uploadFoto");
const previewFoto = document.getElementById("previewFoto");

const displayNamaGuru = document.getElementById("displayNamaGuru");
const displayMapelGuru = document.getElementById("displayMapelGuru");
const displayUsername = document.getElementById("displayUsername");
const displayPassword = document.getElementById("displayPassword");

const namaGuru = document.getElementById("namaGuru");
const nipGuru = document.getElementById("nipGuru");
const emailGuru = document.getElementById("emailGuru");
const mapelGuru = document.getElementById("mapelGuru");

const btnSimpanProfil = document.getElementById("btnSimpanProfil");

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
      if (result.status === "success") {
        const guru = result.data;

        displayNamaGuru.textContent = guru.nama;
        displayMapelGuru.textContent = `Guru ${guru.nama_mapel ?? "-"}`;
        displayUsername.textContent = guru.nama;
        displayPassword.textContent = guru.nip;

        namaGuru.value = guru.nama;
        nipGuru.value = guru.nip;
        emailGuru.value = guru.email;
        mapelGuru.value = guru.nama_mapel ?? "-";
      } else {
        alert(result.message);
        window.location.href = "login.html";
      }
    })
    .catch(error => {
      console.error(error);
      alert("Gagal memuat data profil guru");
    });
}

if (btnSimpanProfil) {
  btnSimpanProfil.addEventListener("click", function () {
    const formData = new FormData();

    formData.append("nama", namaGuru.value);
    formData.append("nip", nipGuru.value);
    formData.append("email", emailGuru.value);

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
        alert("Gagal menyimpan perubahan profil");
      });
  });
}
const displayNamaGuru = document.getElementById("displayNamaGuru");
const displayMapelGuru = document.getElementById("displayMapelGuru");
const displayNipGuru = document.getElementById("displayNipGuru");
const displayEmailGuru = document.getElementById("displayEmailGuru");

const namaGuru = document.getElementById("namaGuru");
const nipGuru = document.getElementById("nipGuru");
const emailGuru = document.getElementById("emailGuru");
const mapelGuru = document.getElementById("mapelGuru");

function loadProfilGuru() {
  fetch("get_profil_guru.php")
    .then(response => response.json())
    .then(result => {
      if (result.status === "success") {
        const guru = result.data;

        displayNamaGuru.textContent = guru.nama;
        displayMapelGuru.textContent = `Guru ${guru.nama_mapel ?? "-"}`;
        displayNipGuru.textContent = guru.nip;
        displayEmailGuru.textContent = guru.email;

        namaGuru.value = guru.nama;
        nipGuru.value = guru.nip;
        emailGuru.value = guru.email;
        mapelGuru.value = guru.nama_mapel ?? "-";
      } else {
        alert(result.message);
        window.location.href = "../login.html";
      }
    })
    .catch(error => {
      console.error(error);
      alert("Gagal memuat profil guru");
    });
}

loadProfilGuru();