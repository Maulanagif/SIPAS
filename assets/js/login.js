/**
 * FILE: assets/js/login.js
 * FUNGSI: JavaScript untuk halaman login - fitur toggle show/hide password
 * 
 * FITUR:
 * - Toggle visibility password (show/hide password)
 * - Mengubah ikon mata berdasarkan status (terlihat/tidak terlihat)
 * 
 * CARA KERJA:
 * 1. Saat tombol mata diklik, ubah type input dari 'password' ke 'text' (atau sebaliknya)
 * 2. Ubah ikon SVG sesuai status (mata terbuka = password terlihat, mata tertutup = password tersembunyi)
 */

// Tunggu sampai DOM (HTML) sudah siap dimuat
document.addEventListener('DOMContentLoaded', function() {
    // Ambil elemen input password dan tombol toggle
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    
    // Cek apakah kedua elemen ada (untuk mencegah error jika elemen tidak ditemukan)
    if (togglePasswordBtn && passwordInput) {
        // Tambahkan event listener untuk tombol toggle
        togglePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();  // Mencegah default action (jika tombol di dalam form)
            
            // Cek apakah password sedang disembunyikan (type = 'password')
            if (passwordInput.type === 'password') {
                // Ubah ke 'text' agar password terlihat
                passwordInput.type = 'text';
                
                // Ubah ikon menjadi eye-off (mata tertutup) - menunjukkan password sekarang terlihat
                togglePasswordBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                // Ubah kembali ke 'password' agar password tersembunyi
                passwordInput.type = 'password';
                
                // Ubah ikon menjadi eye (mata terbuka) - menunjukkan password sekarang tersembunyi
                togglePasswordBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        });
    }
});

