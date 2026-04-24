function loadProfilGuru() {
  fetch("get_guru.php")
    .then(response => response.json())
    .then(result => {
      console.log("Data profil guru:", result);

      if (result.status === "success") {
        isiProfilGuru(result.data);
      } else {
        alert(result.message);
        window.location.href = "../login.html";
      }
    })
    .catch(error => {
      console.error(error);
      alert("Gagal memuat data profil guru");
    });
}