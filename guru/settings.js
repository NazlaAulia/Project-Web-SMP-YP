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

const togglePasswordButtons = document.querySelectorAll(".toggle-password");

togglePasswordButtons.forEach((button) => {
    button.addEventListener("click", function () {
        const targetId = this.getAttribute("data-target");
        const input = document.getElementById(targetId);
        const icon = this.querySelector("i");

        if (!input || !icon) return;

        const isPassword = input.type === "password";

        input.type = isPassword ? "text" : "password";
        icon.className = isPassword ? "bi bi-eye-slash" : "bi bi-eye";
    });
});

function isiUsernameLama(username) {
    if (usernameLamaInput) {
        usernameLamaInput.value = username || "";
    }
}

console.log("Cek settings:");
console.log("id_guru:", idGuruLogin);
console.log("role_id:", roleIdLogin);
console.log("username localStorage:", usernameLogin);
console.log("input usernameLama:", usernameLamaInput);

isiUsernameLama(usernameLogin);

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

                isiUsernameLama(usernameDb);

                if (usernameDb) {
                    localStorage.setItem("username", usernameDb);
                }
            } else {
                console.warn(result.message);
            }
        })
        .catch(err => {
            console.error("Gagal load username settings:", err);
            isiUsernameLama(usernameLogin);
        });
}

/* =========================
   SIMPAN USERNAME
========================= */
if (btnSimpanUsername) {
    btnSimpanUsername.addEventListener("click", function () {
        console.log("Tombol Simpan Username diklik");

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
                console.log("Response update username:", result);
                alert(result.message);

                if (result.status === "success") {
                    localStorage.setItem("username", usernameBaru);

                    if (usernameLamaInput) usernameLamaInput.value = usernameBaru;
                    if (usernameBaruInput) usernameBaruInput.value = "";
                }
            })
            .catch(err => {
                console.error("Gagal update username:", err);
                alert("Gagal update username.");
            });
    });
} else {
    console.error("btnSimpanUsername tidak ditemukan. Cek id tombol di HTML.");
}

/* =========================
   UPDATE PASSWORD
========================= */
if (btnUpdatePassword) {
    btnUpdatePassword.addEventListener("click", function () {
        console.log("Tombol Update Password diklik");

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
                console.log("Response update password:", result);
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
} else {
    console.error("btnUpdatePassword tidak ditemukan. Cek id tombol di HTML.");
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