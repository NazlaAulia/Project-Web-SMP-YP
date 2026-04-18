document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.form-group input, .form-group select, .form-group textarea');

    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'translateX(10px)';
            input.parentElement.style.transition = 'all 0.3s ease';
        });

        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'translateX(0)';
        });
    });

    const form = document.getElementById("formPendaftaran");
    const alertBox = document.getElementById("formAlert");
    const modal = document.getElementById("successModal");
    const waBtn = document.getElementById("waAdminBtn");
    const waReminder = document.getElementById("waReminder");
    const waReminderLink = document.getElementById("waReminderLink");
    const submitBtn = document.querySelector(".btn-submit");

    if (!form) return;

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

            const result = await response.json();

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
No HP: ${result.data.no_hp}

Mohon konfirmasi pendaftaran saya. Terima kasih.`;

            const waLink = `https://wa.me/${nomorAdmin}?text=${encodeURIComponent(pesanWa)}`;

            waBtn.href = waLink;
            waReminderLink.href = waLink;
            waReminder.style.display = "block";

            alertBox.innerHTML = `<div class="alert success">${result.message}</div>`;
            modal.classList.add("active");
            form.reset();

        } catch (error) {
            console.error("Error:", error);
            alertBox.innerHTML = `<div class="alert error">Gagal terhubung ke server.</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            submitBtn.style.opacity = "1";
            submitBtn.style.pointerEvents = "auto";
        }
    });
});