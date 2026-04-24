function loadProfilGuru() {
  fetch("get_profil_guru.php")
    .then(response => response.json())
    .then(result => {
      console.log(result);

      if (result.status === "success") {
        const guru = result.data;
        const mapelAkademikGuru = document.getElementById("mapelAkademikGuru");

        if (displayNamaGuru) displayNamaGuru.textContent = guru.nama;
        if (displayMapelGuru) displayMapelGuru.textContent = `Guru ${guru.nama_mapel ?? "-"}`;
        if (displayNipGuru) displayNipGuru.textContent = guru.nip;
        if (displayEmailGuru) displayEmailGuru.textContent = guru.email;

        if (mapelAkademikGuru) mapelAkademikGuru.value = guru.nama_mapel ?? "-";
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
