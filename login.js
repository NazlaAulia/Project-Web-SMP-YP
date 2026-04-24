const forgotPasswordLink = document.getElementById('forgotPasswordLink');

if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener('click', async function (e) {
        e.preventDefault();

        const { value: username } = await Swal.fire({
            title: 'Lupa Sandi',
            text: 'Masukkan username akunmu. Permintaan reset akan dikirim ke admin.',
            input: 'text',
            inputPlaceholder: 'Masukkan username',
            confirmButtonText: 'Kirim',
            cancelButtonText: 'Batal',
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value || value.trim() === '') {
                    return 'Username wajib diisi.';
                }
            }
        });

        if (!username) return;

        try {
            const response = await fetch('forgot_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username.trim()
                })
            });

            const text = await response.text();
            let result;

            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Response server tidak valid.');
            }

            Swal.fire({
                icon: result.status === 'success' ? 'success' : 'error',
                title: result.status === 'success' ? 'Berhasil' : 'Gagal',
                text: result.message
            });

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: error.message || 'Tidak bisa terhubung ke server.'
            });
        }
    });
}