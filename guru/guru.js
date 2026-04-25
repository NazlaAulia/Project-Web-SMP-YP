const namaGuruEl = document.getElementById("namaGuru");
const avatarGuruEl = document.getElementById("avatarGuru");
const namaGuruDashboardEl = document.getElementById("namaGuruDashboard");
const bannerGuru = document.getElementById("bannerGuru");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

function tampilkanGuru(guru) {
    const namaFix = guru.nama || "Bapak/Ibu Guru";
    const huruf = namaFix.charAt(0).toUpperCase();

    if (namaGuruEl) namaGuruEl.textContent = namaFix;
    if (avatarGuruEl) avatarGuruEl.textContent = huruf;
    if (namaGuruDashboardEl) namaGuruDashboardEl.textContent = namaFix;
}

if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
} else {
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data guru dashboard:", result);

            if (result.status === "success") {
                tampilkanGuru(result.data);
            } else {
                alert(result.message);
                localStorage.clear();
                window.location.href = "../login.html";
            }
        })
        .catch(err => {
            console.error(err);
            alert("Gagal load data guru.");
        });
}

if (bannerGuru) {
    bannerGuru.addEventListener("click", function () {
        bannerGuru.classList.remove("banner-active");
        void bannerGuru.offsetWidth;
        bannerGuru.classList.add("banner-active");
    });
}

const animatedCards = document.querySelectorAll(".click-animate");

animatedCards.forEach((card) => {
    card.addEventListener("click", function () {
        card.classList.remove("card-active");
        void card.offsetWidth;
        card.classList.add("card-active");
    });
});