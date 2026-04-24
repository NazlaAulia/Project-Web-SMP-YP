const namaGuruEl = document.getElementById("namaGuru");
const avatarGuruEl = document.getElementById("avatarGuru");
const welcomeGuruEl = document.getElementById("welcomeGuru");

function tampilkanGuru(nama) {
    const namaFix = nama || "Bapak/Ibu Guru";
    const huruf = namaFix.charAt(0).toUpperCase();

    if (namaGuruEl) namaGuruEl.textContent = namaFix;
    if (avatarGuruEl) avatarGuruEl.textContent = huruf;
    if (welcomeGuruEl) welcomeGuruEl.textContent = `Halo, ${namaFix}! 🌟`;
}

fetch("get_guru.php")
    .then(res => res.json())
    .then(result => {
        console.log("Data guru dashboard:", result);

        if (result.status === "success") {
            tampilkanGuru(result.data.nama);
        } else {
            alert(result.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Gagal load data guru");
    });