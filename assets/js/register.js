/**
 * FILE: assets/js/register.js
 * FUNGSI: JavaScript untuk halaman registrasi - fitur toggle show/hide password
 * 
 * FITUR:
 * - Toggle visibility password (show/hide password) untuk field password
 * - Toggle visibility password (show/hide password) untuk field konfirmasi password
 * - Mengubah ikon mata berdasarkan status (terlihat/tidak terlihat)
 * 
 * CARA KERJA:
 * 1. Saat tombol mata diklik, ubah type input dari 'password' ke 'text' (atau sebaliknya)
 * 2. Ubah ikon SVG sesuai status untuk kedua field (password dan konfirmasi)
 */

// Tunggu sampai DOM (HTML) sudah siap dimuat
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const confirmInput = document.getElementById('password_confirm');
    const toggleConfirmBtn = document.getElementById('togglePasswordConfirm');
    
    // Toggle password visibility
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle type antara 'password' dan 'text'
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Ubah ikon menjadi eye-off (mata tertutup)
                togglePasswordBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                passwordInput.type = 'password';
                // Ubah ikon menjadi eye (mata terbuka)
                togglePasswordBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    }
    
    // Toggle konfirmasi password visibility
    if (toggleConfirmBtn && confirmInput) {
        toggleConfirmBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle type antara 'password' dan 'text'
            if (confirmInput.type === 'password') {
                confirmInput.type = 'text';
                // Ubah ikon menjadi eye-off (mata tertutup)
                toggleConfirmBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                confirmInput.type = 'password';
                // Ubah ikon menjadi eye (mata terbuka)
                toggleConfirmBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    }
});
