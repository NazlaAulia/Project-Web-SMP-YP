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

        const { value: formValues } = await Swal.fire({
            title: 'Lupa Sandi',
            html: `
                <select id="fp_role" class="swal2-input">
                    <option value="">Pilih jenis akun</option>
                    <option value="siswa">Siswa</option>
                    <option value="guru">Guru</option>
                </select>
                <input id="fp_identifier" class="swal2-input" placeholder="Username siswa / Email guru">
            `,
            confirmButtonText: 'Kirim',
            cancelButtonText: 'Batal',
            showCancelButton: true,
            preConfirm: () => {
                const role = document.getElementById('fp_role').value;
                const identifier = document.getElementById('fp_identifier').value.trim();

                if (!role || !identifier) {
                    Swal.showValidationMessage('Jenis akun dan data akun wajib diisi.');
                    return false;
                }

                if (role === 'guru' && !identifier.includes('@')) {
                    Swal.showValidationMessage('Guru wajib memakai email.');
                    return false;
                }

                return {
                    role: role,
                    identifier: identifier
                };
            }
        });

        if (!formValues) return;

        try {
            const response = await fetch('forgot_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formValues)
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
                return;
            }

            Swal.fire({
                icon: result.status === 'success' ? 'success' : 'error',
                title: result.status === 'success' ? 'Berhasil' : 'Gagal',
                text: result.message || 'Terjadi kesalahan.'
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

const resetParams = new URLSearchParams(window.location.search);
const resetToken = resetParams.get('token');

if (resetToken) {
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
                    token: resetToken,
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
                window.location.href = 'login.html';
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