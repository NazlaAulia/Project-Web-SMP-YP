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
const uploadFoto = document.getElementById("uploadFoto");
const btnSimpanProfil = document.getElementById("btnSimpanProfil");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

let fileFotoDipilih = null;

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

/* Preview foto saat dipilih */
if (uploadFoto && previewFoto) {
    uploadFoto.addEventListener("change", function () {
        const file = this.files[0];

        if (!file) return;

        fileFotoDipilih = file;
        previewFoto.src = URL.createObjectURL(file);
    });
}

/* Simpan foto profil ke database */
if (btnSimpanProfil) {
    btnSimpanProfil.addEventListener("click", function () {
        const formDataProfil = new FormData();

        formDataProfil.append("id_guru", idGuruLogin);
        formDataProfil.append("role_id", roleIdLogin);
        formDataProfil.append("nama", namaGuruInput.value);
        formDataProfil.append("nip", nipGuruInput.value);
        formDataProfil.append("email", emailGuruInput.value);
        formDataProfil.append("id_mapel", mapelGuruInput.value);

        fetch("update_guru.php", {
            method: "POST",
            body: formDataProfil
        })
            .then(res => res.json())
            .then(result => {
                if (result.status !== "success") {
                    alert(result.message);
                    return;
                }

                if (!fileFotoDipilih) {
                    alert(result.message);
                    location.reload();
                    return;
                }

                const formDataFoto = new FormData();
                formDataFoto.append("id_guru", idGuruLogin);
                formDataFoto.append("role_id", roleIdLogin);
                formDataFoto.append("foto", fileFotoDipilih);

                return fetch("update_foto_guru.php", {
                    method: "POST",
                    body: formDataFoto
                })
                    .then(res => res.json())
                    .then(fotoResult => {
                        alert(fotoResult.message);

                        if (fotoResult.status === "success") {
                            fileFotoDipilih = null;
                            location.reload();
                        }
                    });
            })
            .catch(err => {
                console.error("Gagal menyimpan profil:", err);
                alert("Gagal menyimpan profil guru.");
            });
    });
}

/* Animasi klik */
const animatedItems = document.querySelectorAll(".click-animate");

animatedItems.forEach((item) => {
    item.addEventListener("click", function () {
        item.classList.remove("profile-active");
        void item.offsetWidth;
        item.classList.add("profile-active");
    });
});