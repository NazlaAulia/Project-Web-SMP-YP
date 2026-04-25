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