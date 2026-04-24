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
        if (previewFoto) previewFoto.src = e.target.result;
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

  if (mapelGuru) mapelGuru.value = guru.id_mapel || "";

  if (mapelAkademikGuru) mapelAkademikGuru.value = guru.nama_mapel || "-";
}

function loadProfilGuru() {
  fetch("get_guru.php")
    .then(response => response.json())
    .then(result => {
      console.log("Data profil guru:", result);

      if (result.status === "success") {
        isiProfilGuru(result.data);
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

function setupProfileAnimation() {
  document.addEventListener("click", function (event) {
    const item = event.target.closest(".click-animate");

    if (!item) return;

    item.classList.remove("profile-active");

    void item.offsetWidth;

    item.classList.add("profile-active");
  });
}

function setupProfileAnimation() {
  document.addEventListener("click", function (event) {
    const item = event.target.closest(".click-animate");

    if (!item) return;

    item.classList.remove("profile-active");

    void item.offsetWidth;

    item.classList.add("profile-active");
  });
}

loadProfilGuru();
setupProfileAnimation();