document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById("formPendaftaran");
    const modal = document.getElementById("successModal");
    const waBtn = document.getElementById("waAdminBtn");
    const waReminder = document.getElementById("waReminder");
    const waReminderLink = document.getElementById("waReminderLink");
    const submitBtn = document.querySelector(".btn-submit");
    const alertBox = document.getElementById("formAlert");

    if (!form) return;

    function showModal() {
        modal.classList.add("active");
    }

    function hideModalPermanent() {
        modal.classList.remove("active");
        localStorage.removeItem('pendaftaranSuccess');
    }

    // Cek localStorage → modal muncul otomatis setelah refresh
    const savedData = localStorage.getItem('pendaftaranSuccess');
    if (savedData) {
        const data = JSON.parse(savedData);
        waBtn.href = data.waLink;
        waReminderLink.href = data.waLink;
        waReminder.style.display = "block";
        showModal();
    }

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
            let result = JSON.parse(raw);

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

            localStorage.setItem('pendaftaranSuccess', JSON.stringify({ waLink }));

            showModal();
            form.reset();

        } catch (err) {
            console.error(err);
            alertBox.innerHTML = `<div class="alert error">${err.message}</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            submitBtn.style.opacity = "1";
            submitBtn.style.pointerEvents = "auto";
        }
    });

    // Klik WA → modal hilang permanen
    waBtn.addEventListener('click', () => {
        hideModalPermanent();
    });
});