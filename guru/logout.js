const btnLogout = document.getElementById("btnLogout");

if (btnLogout) {
    btnLogout.addEventListener("click", function (e) {
        e.preventDefault();

        localStorage.removeItem("id_guru");
        localStorage.removeItem("role_id");
        localStorage.removeItem("username");
        localStorage.removeItem("id_siswa");
        localStorage.removeItem("id_user");

        window.location.replace("../login.html");
    });
}