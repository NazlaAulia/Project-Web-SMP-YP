const params = new URLSearchParams(window.location.search);
const token = params.get('token');

if (!token) {
    Swal.fire({
        icon: 'error',
        title: 'Link Tidak Valid',
        text: 'Token reset password tidak ditemukan.',
        confirmButtonText: 'Kembali ke Login'
    }).then(() => {
        window.location.href = 'login.html';
    });
} else {
    Swal.fire({
        title: 'Ganti Password',
        html: `
            <input id="new_password" type="password" class="swal2-input" placeholder="Password baru">
            <input id="confirm_password" type="password" class="swal2-input" placeholder="Ulangi password baru">
        `,
        confirmButtonText: 'Simpan Password',
        showCancelButton: false,
        allowOutsideClick: false,
        preConfirm: () => {
            const password = document.getElementById('new_password').value.trim();
            const confirmPassword = document.getElementById('confirm_password').value.trim();

            if (!password || !confirmPassword) {
                Swal.showValidationMessage('Password wajib diisi.');
                return false;
            }

            if (password.length < 6) {
                Swal.showValidationMessage('Password minimal 6 karakter.');
                return false;
            }

            if (password !== confirmPassword) {
                Swal.showValidationMessage('Konfirmasi password tidak sama.');
                return false;
            }

            return password;
        }
    }).then(async (result) => {
        if (!result.value) return;

        try {
            const response = await fetch('reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    password: result.value
                })
            });

            const text = await response.text();
            let data;

            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Balasan server:', text);
                throw new Error('Response server tidak valid.');
            }

            Swal.fire({
                icon: data.status === 'success' ? 'success' : 'error',
                title: data.status === 'success' ? 'Berhasil' : 'Gagal',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                if (data.status === 'success') {
                    window.location.href = 'login.html';
                }
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