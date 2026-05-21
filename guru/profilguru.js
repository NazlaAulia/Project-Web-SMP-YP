const displayNamaGuru = document.getElementById("displayNamaGuru");
const displayMapelGuru = document.getElementById("displayMapelGuru");
const displayNipGuru = document.getElementById("displayNipGuru");
const displayEmailGuru = document.getElementById("displayEmailGuru");

const namaGuruInput = document.getElementById("nama");
const nipGuruInput = document.getElementById("nip");
const emailGuruInput = document.getElementById("email");
const mapelGuruInput = document.getElementById("namaMapel");
const jenisKelaminGuruInput = document.getElementById("jenisKelamin");
const usernameGuruInput = document.getElementById("username");

const previewFoto = document.getElementById("previewFoto");
const uploadFoto = document.getElementById("uploadFoto");
const btnSimpanProfil = document.getElementById("btnSimpanProfil");

const idGuruLogin = localStorage.getItem("id_guru");
const roleIdLogin = localStorage.getItem("role_id");

let fileFotoDipilih = null;

// ========== FUNGSI MODAL ==========
function showMessage(title, message, type) {
    return Swal.fire({
        title: title,
        text: message,
        icon: type,
        confirmButtonColor: "#07484a",
        confirmButtonText: "OK"
    });
}

// ========== LOADING ==========
function showLoading(show, text = "Mengupload foto...") {
    if (show) {
        Swal.fire({
            title: text,
            text: "Mohon tunggu sebentar",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
            showConfirmButton: false
        });
    } else {
        Swal.close();
    }
}

// ========== ISI PROFIL ==========
function isiProfilGuru(guru) {
    const nama = guru.nama || "-";
    const nip = guru.nip || "-";
    const email = guru.email || "-";
    const mapel = guru.nama_mapel || "Belum ada mapel";
    const username = guru.username || "";
    const jenisKelamin = guru.jenis_kelamin || "Belum diisi";

    if (displayNamaGuru) displayNamaGuru.textContent = nama;
    if (displayMapelGuru) displayMapelGuru.textContent = mapel;
    if (displayNipGuru) displayNipGuru.textContent = nip;
    if (displayEmailGuru) displayEmailGuru.textContent = email;

    if (namaGuruInput) namaGuruInput.value = nama;
    if (nipGuruInput) nipGuruInput.value = nip;
    if (emailGuruInput) emailGuruInput.value = email;
    if (mapelGuruInput) mapelGuruInput.value = mapel;
    if (jenisKelaminGuruInput) jenisKelaminGuruInput.value = jenisKelamin;
    if (usernameGuruInput) usernameGuruInput.value = username;

    // Tambahkan cache buster pada foto
    if (previewFoto && guru.foto_profil) {
        const fotoUrl = guru.foto_profil;
        // Tambahkan timestamp agar tidak cache
        const cacheBuster = "?t=" + new Date().getTime();
        previewFoto.src = fotoUrl + cacheBuster;
    }
}

// ========== LOAD DATA ==========
if (!idGuruLogin || roleIdLogin !== "2") {
    showMessage("Peringatan", "Silakan login sebagai guru terlebih dahulu.", "warning").then(() => {
        window.location.href = "../login.html";
    });
} else {
    showLoading(true, "Memuat data profil...");
    
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}&t=${new Date().getTime()}`)
        .then(res => res.json())
        .then(result => {
            showLoading(false);
            
            if (result.status === "success") {
                isiProfilGuru(result.data);
            } else {
                showMessage("Gagal", result.message, "error").then(() => {
                    localStorage.clear();
                    window.location.href = "../login.html";
                });
            }
        })
        .catch(err => {
            showLoading(false);
            showMessage("Error", "Gagal load profil guru.", "error");
        });
}

// ========== PREVIEW FOTO ==========
if (uploadFoto && previewFoto) {
    uploadFoto.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        const allowedTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
        if (!allowedTypes.includes(file.type)) {
            showMessage("Error", "Format foto harus JPG, JPEG, PNG, atau WEBP.", "error");
            this.value = "";
            return;
        }

        if (file.size > 1 * 1024 * 1024) {
            showMessage("Error", "Ukuran foto maksimal 1 MB.", "error");
            this.value = "";
            return;
        }

        fileFotoDipilih = file;
        // Preview dengan cache buster
        const previewUrl = URL.createObjectURL(file);
        previewFoto.src = previewUrl;
        showMessage("Berhasil", "Foto berhasil dipilih. Klik Simpan Perubahan.", "success");
    });
}

// ========== SIMPAN FOTO ==========
if (btnSimpanProfil) {
    btnSimpanProfil.addEventListener("click", async function () {
        if (!fileFotoDipilih) {
            showMessage("Peringatan", "Silakan pilih foto terlebih dahulu.", "warning");
            return;
        }

        showLoading(true, "Mengupload foto...");

        try {
            const formDataFoto = new FormData();
            formDataFoto.append("id_guru", idGuruLogin);
            formDataFoto.append("role_id", roleIdLogin);
            formDataFoto.append("foto", fileFotoDipilih);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 20000);

            const fotoResponse = await fetch("update_foto_guru.php", {
                method: "POST",
                body: formDataFoto,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            const fotoResult = await fotoResponse.json();

            showLoading(false);

            if (fotoResult.status === "success") {
                fileFotoDipilih = null;
                
                // Reset input file
                uploadFoto.value = "";
                
                // Update foto dengan cache buster
                if (fotoResult.foto_url) {
                    previewFoto.src = fotoResult.foto_url;
                }
                
                await showMessage("Berhasil!", "Foto profil berhasil diubah!", "success");
                
                // Reload paksa dengan cache buster
                window.location.href = window.location.href.split('?')[0] + "?t=" + new Date().getTime();
            } else {
                await showMessage("Gagal", fotoResult.message, "error");
            }
        } catch (err) {
            showLoading(false);
            
            if (err.name === "AbortError") {
                await showMessage("Timeout", "Proses terlalu lama. Silakan coba lagi.", "error");
            } else {
                console.error("Error:", err);
                await showMessage("Error", "Terjadi kesalahan saat menyimpan foto.", "error");
            }
        }
    });
}