document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');

    const form = document.getElementById("formPendaftaran");
    const alertBox = document.getElementById("formAlert");
    const modal = document.getElementById("successModal");
    const waBtn = document.getElementById("waAdminBtn");
    const waReminder = document.getElementById("waReminder");
    const waReminderLink = document.getElementById("waReminderLink");
    const submitBtn = document.querySelector(".btn-submit");

    const statHariIni = document.getElementById("statHariIni");
    const statKuota = document.getElementById("statKuota");
    const statPersen = document.getElementById("statPersen");
    const progressBar = document.getElementById("progressBar");

    if (!form) return;

    // ===== Input animation =====
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'translateX(10px)';
            input.parentElement.style.transition = 'all 0.3s ease';
        });

        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'translateX(0)';
        });
    });

    // ===== Update Kuota =====
    function updateKuotaDisplay(data) {
        if (statKuota) statKuota.textContent = data.kuota_tersisa;

        if (statPersen && progressBar && data.kuota_max) {
            const terisi = data.kuota_max - data.kuota_tersisa;
            const persen = Math.round((terisi / data.kuota_max) * 100);
            statPersen.textContent = `${persen}% Terisi`;
            progressBar.style.width = `${persen}%`;
        }

        if (statHariIni && typeof data.jumlah_pendaftar !== "undefined") {
            statHariIni.textContent = data.jumlah_pendaftar;
        }
    }

    // ===== Cek localStorage untuk modal persistent =====
    const savedData = localStorage.getItem('pendaftaranSuccess');
    if (savedData && waBtn && waReminder && waReminderLink && modal) {
        const data = JSON.parse(savedData);
        waBtn.href = data.waLink;
        waReminderLink.href = data.waLink;
        waReminder.style.display = "block";
        modal.classList.add("active");
    }

    async function loadStatPPDB() {
    try {
        const response = await fetch("get_stat_ppdb.php");
        const result = await response.json();

        if (result.status === "success") {
            updateKuotaDisplay(result.data);
        }
    } catch (error) {
        console.error("Gagal load stat PPDB:", error);
    }
}

loadStatPPDB();


    // ===== Submit Form =====
    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = "Sedang Memproses... ⏳";
        submitBtn.style.opacity = "0.8";
        submitBtn.style.pointerEvents = "none";

        const formData = new FormData(form);

        try {
            const response = await fetch("proses_pendaftaran.php", {
                method: "POST",
                body: formData
            });

            const raw = await response.text();
            let result;
            try {
                result = JSON.parse(raw);
            } catch (err) {
                throw new Error("Response bukan JSON: " + raw);
            }

            if (result.status !== "success") {
                alertBox.innerHTML = `<div class="alert error">${result.message}</div>`;
                return;
            }

            const nomorAdmin = "6283846311788";
            const pesanWa = `Halo Admin SMP YP 17 Surabaya,

Saya atas nama *${result.data.nama_lengkap}* telah melakukan pendaftaran PPDB.
ID Pendaftaran: ${result.data.id_pendaftaran}
NISN: ${result.data.nisn}
Asal Sekolah: ${result.data.asal_sekolah}
No HP Orang Tua/Wali: ${result.data.no_hp}
Nama Wali: ${result.data.nama_wali}

Jumlah Pendaftar: ${result.data.jumlah_pendaftar}
Kuota Tersisa: ${result.data.kuota_tersisa} dari ${result.data.kuota_max}

Mohon konfirmasi pendaftaran saya. Terima kasih.`;

            const waLink = `https://wa.me/${nomorAdmin}?text=${encodeURIComponent(pesanWa)}`;

            waBtn.href = waLink;
            waReminderLink.href = waLink;
            waReminder.style.display = "block";

            alertBox.innerHTML = `<div class="alert success">
                ${result.message}<br>
                Jumlah pendaftar: ${result.data.jumlah_pendaftar}<br>
                Kuota tersisa: ${result.data.kuota_tersisa} / ${result.data.kuota_max}
            </div>`;

            updateKuotaDisplay(result.data);

            modal.classList.add("active");

            // ===== Simpan ke localStorage untuk modal persistent =====
            localStorage.setItem('pendaftaranSuccess', JSON.stringify({ waLink }));

            form.reset();

        } catch (error) {
            console.error("ERROR DETAIL:", error);
            alertBox.innerHTML = `<div class="alert error">${error.message}</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            submitBtn.style.opacity = "1";
            submitBtn.style.pointerEvents = "auto";
        }
    });

    // ===== Klik WA → modal hilang permanen =====
    if (waBtn) {
        waBtn.addEventListener('click', () => {
            modal.classList.remove("active");
            localStorage.removeItem('pendaftaranSuccess');
        });
    }

});