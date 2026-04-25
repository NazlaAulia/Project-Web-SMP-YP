const usernameLamaInput = document.getElementById("usernameLama");
const usernameBaruInput = document.getElementById("usernameBaru");
const btnSimpanUsername = document.getElementById("btnSimpanUsername");

const passwordLamaInput = document.getElementById("passwordLama");
const passwordBaruInput = document.getElementById("passwordBaru");
const konfirmasiPasswordInput = document.getElementById("konfirmasiPassword");
const btnUpdatePassword = document.getElementById("btnUpdatePassword");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");
const usernameLogin = localStorage.getItem("username");

if (!idGuruLogin || roleIdLogin !== "2") {
    alert("Silakan login sebagai guru terlebih dahulu.");
    window.location.href = "../login.html";
} else {
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data settings guru:", result);

            if (result.status === "success") {
                const usernameDb = result.data.username || usernameLogin || "";

                if (usernameLamaInput) {
                    usernameLamaInput.value = usernameDb;
                }

                if (usernameDb) {
                    localStorage.setItem("username", usernameDb);
                }
            } else {
                alert(result.message);
            }
        })
        .catch(err => {
            console.error("Gagal load username settings:", err);

            if (usernameLamaInput) {
                usernameLamaInput.value = usernameLogin || "";
            }
        });
}

/* =========================
   SIMPAN USERNAME
========================= */
if (btnSimpanUsername) {
    btnSimpanUsername.addEventListener("click", function () {
        const usernameBaru = usernameBaruInput.value.trim();

        if (usernameBaru === "") {
            alert("Username baru wajib diisi.");
            return;
        }

        const formData = new FormData();
        formData.append("id_guru", idGuruLogin);
        formData.append("role_id", roleIdLogin);
        formData.append("username_baru", usernameBaru);

        fetch("update_username_guru.php", {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(result => {
                alert(result.message);

                if (result.status === "success") {
                    localStorage.setItem("username", usernameBaru);

                    usernameLamaInput.value = usernameBaru;
                    usernameBaruInput.value = "";
                }
            })
            .catch(err => {
                console.error("Gagal update username:", err);
                alert("Gagal update username.");
            });
    });
}

/* =========================
   UPDATE PASSWORD
========================= */
if (btnUpdatePassword) {
    btnUpdatePassword.addEventListener("click", function () {
        const passwordLama = passwordLamaInput.value.trim();
        const passwordBaru = passwordBaruInput.value.trim();
        const konfirmasiPassword = konfirmasiPasswordInput.value.trim();

        if (passwordLama === "") {
            alert("Password lama wajib diisi.");
            return;
        }

        if (passwordBaru === "") {
            alert("Password baru wajib diisi.");
            return;
        }

        if (konfirmasiPassword === "") {
            alert("Konfirmasi password baru wajib diisi.");
            return;
        }

        if (passwordBaru !== konfirmasiPassword) {
            alert("Konfirmasi password baru tidak sama.");
            return;
        }

        const formData = new FormData();
        formData.append("id_guru", idGuruLogin);
        formData.append("role_id", roleIdLogin);
        formData.append("password_lama", passwordLama);
        formData.append("password_baru", passwordBaru);
        formData.append("konfirmasi_password", konfirmasiPassword);

        fetch("update_password_guru.php", {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(result => {
                alert(result.message);

                if (result.status === "success") {
                    passwordLamaInput.value = "";
                    passwordBaruInput.value = "";
                    konfirmasiPasswordInput.value = "";
                }
            })
            .catch(err => {
                console.error("Gagal update password:", err);
                alert("Gagal update password.");
            });
    });
}

/* Animasi klik */
const animatedItems = document.querySelectorAll(".click-animate");

animatedItems.forEach((item) => {
    item.addEventListener("click", function () {
        item.classList.remove("settings-active");
        void item.offsetWidth;
        item.classList.add("settings-active");
    });
});