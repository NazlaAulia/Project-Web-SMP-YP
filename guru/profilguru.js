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
function showModal(message, type = "info") {
  return new Promise((resolve) => {
    const modal = document.getElementById("customModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalMessage = document.getElementById("modalMessage");
    const modalHeader = document.getElementById("modalHeader");
    const modalOkBtn = document.getElementById("modalOkBtn");

    if (!modal) {
      alert(message);
      resolve();
      return;
    }

    modalHeader.setAttribute("data-type", type);
    
    let iconHtml = '<i class="bi bi-info-circle-fill"></i>';
    let titleText = "Informasi";
    
    if (type === "success") {
      iconHtml = '<i class="bi bi-check-circle-fill"></i>';
      titleText = "Berhasil";
    } else if (type === "error") {
      iconHtml = '<i class="bi bi-x-circle-fill"></i>';
      titleText = "Gagal";
    } else if (type === "warning") {
      iconHtml = '<i class="bi bi-exclamation-triangle-fill"></i>';
      titleText = "Peringatan";
    }

    modalHeader.innerHTML = `${iconHtml}<span id="modalTitle">${titleText}</span>`;
    modalMessage.textContent = message;

    modal.style.display = "flex";

    const handleOk = () => {
      modal.style.display = "none";
      modalOkBtn.removeEventListener("click", handleOk);
      resolve();
    };

    modalOkBtn.addEventListener("click", handleOk);
  });
}

function showLoading(show) {
  const loading = document.getElementById("loadingOverlay");
  if (loading) {
    loading.style.display = show ? "flex" : "none";
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

    if (previewFoto && guru.foto_profil) {
        previewFoto.src = guru.foto_profil;
    }
}

// ========== LOAD DATA ==========
if (!idGuruLogin || roleIdLogin !== "2") {
    showModal("Silakan login sebagai guru terlebih dahulu.", "warning").then(() => {
        window.location.href = "../login.html";
    });
} else {
    fetch(`get_guru.php?id_guru=${idGuruLogin}&role_id=${roleIdLogin}`)
        .then(res => res.json())
        .then(result => {
            console.log("Data profil guru:", result);

            if (result.status === "success") {
                isiProfilGuru(result.data);
            } else {
                showModal(result.message, "error").then(() => {
                    localStorage.clear();
                    window.location.href = "../login.html";
                });
            }
        })
        .catch(err => {
            console.error(err);
            showModal("Gagal load profil guru.", "error");
        });
}

// ========== PREVIEW FOTO ==========
if (uploadFoto && previewFoto) {
    uploadFoto.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;

        const allowedTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
        if (!allowedTypes.includes(file.type)) {
            showModal("Format foto harus JPG, JPEG, PNG, atau WEBP.", "error");
            this.value = "";
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showModal("Ukuran foto maksimal 2 MB.", "error");
            this.value = "";
            return;
        }

        fileFotoDipilih = file;
        previewFoto.src = URL.createObjectURL(file);
        showModal("Foto berhasil dipilih. Klik Simpan Perubahan untuk menyimpan.", "success");
    });
}

// ========== SIMPAN FOTO SAJA (TANPA UPDATE GURU) ==========
if (btnSimpanProfil) {
    btnSimpanProfil.addEventListener("click", async function () {
        // Cek apakah ada perubahan foto
        if (!fileFotoDipilih) {
            showModal("Silakan pilih foto terlebih dahulu dengan mengklik 'Ubah Foto'.", "warning");
            return;
        }

        showLoading(true);

        try {
            // LANGSUNG UPLOAD FOTO SAJA
            const formDataFoto = new FormData();
            formDataFoto.append("id_guru", idGuruLogin);
            formDataFoto.append("role_id", roleIdLogin);
            formDataFoto.append("foto", fileFotoDipilih);

            // Set timeout 10 detik
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            const fotoResponse = await fetch("update_foto_guru.php", {
                method: "POST",
                body: formDataFoto,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            const fotoResult = await fotoResponse.json();

            console.log("Response update_foto_guru:", fotoResult);

            // TUTUP LOADING DULU sebelum show modal
            showLoading(false);

            if (fotoResult.status === "success") {
                fileFotoDipilih = null;
                await showModal("Foto profil berhasil diubah!", "success");
                location.reload();
            } else {
                await showModal(fotoResult.message, "error");
            }
        } catch (err) {
            // TUTUP LOADING jika error
            showLoading(false);
            
            if (err.name === "AbortError") {
                await showModal("Proses terlalu lama. Silakan coba lagi.", "error");
            } else {
                console.error("Error:", err);
                await showModal("Terjadi kesalahan saat menyimpan foto.", "error");
            }
        }
    });
}