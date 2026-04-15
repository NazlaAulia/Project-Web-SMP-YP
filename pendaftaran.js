// Tunggu sampai semua elemen halaman selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Efek Input Fokus (Geser sedikit saat diklik)
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

    // 2. Animasi Tombol saat Form Dikirim
    const form = document.querySelector('form');
    const btn = document.querySelector('.btn-submit');

    if (form) {
        form.onsubmit = function() {
            btn.innerHTML = "Sedang Memproses... ⏳";
            btn.style.opacity = "0.8";
            btn.style.pointerEvents = "none"; // Biar gak diklik dua kali
        };
    }
});