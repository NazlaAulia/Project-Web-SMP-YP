const togglePasswordText = document.querySelector('#togglePasswordText');
const passwordInput = document.querySelector('#password');
const form = document.querySelector('.siakad-form');
const loginBtn = document.getElementById('loginBtn');
const forgotPasswordLink = document.getElementById('forgotPasswordLink');

if (togglePasswordText && passwordInput) {
    togglePasswordText.addEventListener('click', function () {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        this.textContent = isHidden ? 'Sembunyikan' : 'Tampilkan';
    });
}

const slides = document.querySelectorAll('.slide');
let currentSlide = 0;

if (slides.length > 0) {
    setInterval(() => {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }, 5000);
}

if (form && loginBtn) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        loginBtn.classList.add('is-loading');
        loginBtn.disabled = true;

        const username = document.querySelector("[name='username']").value.trim();
        const password = document.querySelector("[name='password']").value;

        try {
            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            });

            const text = await response.text();
            let result;

            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Response server tidak valid.');
            }

            if (result.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Gagal',
                    text: result.message || 'Periksa kembali username dan password.',
                    confirmButtonText: 'Coba Lagi'
                });
                return;
            }

            localStorage.setItem('username', result.user.username);
            localStorage.setItem('role_id', result.user.role_id);

            if (result.user.id_siswa) {
                localStorage.setItem('id_siswa', result.user.id_siswa);
            }

            if (result.user.id_guru) {
                localStorage.setItem('id_guru', result.user.id_guru);
            }

            Swal.fire({
                icon: 'success',
                title: 'Login Berhasil',
                text: 'Selamat datang kembali!',
                timer: 1200,
                showConfirmButton: false
            }).then(() => {
                if (result.user.role_id == 3) {
                    window.location.href = 'siswa/siswa.html';
                } else if (result.user.role_id == 2) {
                    window.location.href = 'guru/guru.html';
                } else if (result.user.role_id == 1) {
                    window.location.href = 'admin/index.html';
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Role Tidak Dikenali',
                        text: 'Akun ini tidak memiliki akses yang valid.'
                    });
                }
            });

        } catch (error) {
            console.error('Error saat login:', error);
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: error.message || 'Tidak bisa terhubung ke server.'
            });
        } finally {
            loginBtn.classList.remove('is-loading');
            loginBtn.disabled = false;
        }
    });
}

if (forgotPasswordLink) {
    forgotPasswordLink.addEventListener('click', async function (e) {
        e.preventDefault();

        const { value: username } = await Swal.fire({
            title: 'Lupa Sandi',
            text: 'Masukkan username akunmu.',
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
                console.error('Balasan server:', text);
                throw new Error('Response server tidak valid.');
            }

            if (result.status === 'success' && result.wa_link) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    html: `
                        <p>${result.message}</p>
                        <a href="${result.wa_link}" target="_blank" class="swal2-confirm swal2-styled">
                            Konfirmasi ke Admin
                        </a>
                    `,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: result.status === 'success' ? 'success' : 'error',
                    title: result.status === 'success' ? 'Berhasil' : 'Gagal',
                    text: result.message || 'Terjadi kesalahan.'
                });
            }

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: error.message || 'Tidak bisa terhubung ke server.'
            });
        }
    });
}