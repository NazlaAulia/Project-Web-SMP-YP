const displayNamaGuru = document.getElementById("displayNamaGuru");
const displayMapelGuru = document.getElementById("displayMapelGuru");
const displayNipGuru = document.getElementById("displayNipGuru");
const displayEmailGuru = document.getElementById("displayEmailGuru");

const namaGuruInput = document.getElementById("namaGuru");
const nipGuruInput = document.getElementById("nipGuru");
const emailGuruInput = document.getElementById("emailGuru");
const mapelGuruInput = document.getElementById("mapelGuru");
const usernameGuruInput = document.getElementById("usernameGuru");
const previewFoto = document.getElementById("previewFoto");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

function isiProfilGuru(guru) {
    const nama = guru.nama || "-";
    const nip = guru.nip || "-";
    const email = guru.email || "-";
    const mapel = guru.nama_mapel || "Belum ada mapel";
    const username = guru.username || "";

    if (displayNamaGuru) displayNamaGuru.textContent = nama;
    if (displayMapelGuru) displayMapelGuru.textContent = mapel;
    if (displayNipGuru) displayNipGuru.textContent = nip;
    if (displayEmailGuru) displayEmailGuru.textContent = email;

    if (namaGuruInput) namaGuruInput.value = nama;
    if (nipGuruInput) nipGuruInput.value = nip;
    if (emailGuruInput) emailGuruInput.value = email;
    if (mapelGuruInput) mapelGuruInput.value = guru.id_mapel || "";
    if (usernameGuruInput) usernameGuruInput.value = username;

    if (previewFoto && guru.foto_profil) {
        previewFoto.src = guru.foto_profil;
    }
}

if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
} else {
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data profil guru:", result);

            if (result.status === "success") {
                isiProfilGuru(result.data);
            } else {
                alert(result.message);
                localStorage.clear();
                window.location.href = "../login.html";
            }
        })
        .catch(err => {
            console.error(err);
            alert("Gagal load profil guru.");
        });
}

const uploadFoto = document.getElementById("uploadFoto");

if (uploadFoto && previewFoto) {
    uploadFoto.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        previewFoto.src = URL.createObjectURL(file);
    });
}

const animatedItems = document.querySelectorAll(".click-animate");

animatedItems.forEach((item) => {
    item.addEventListener("click", function () {
        item.classList.remove("profile-active");
        void item.offsetWidth;
        item.classList.add("profile-active");
    });
});