<?php
/**
 * FILE: register.php
 * FUNGSI: Halaman registrasi user baru (pasien)
 * 
 * FITUR:
 * - Registrasi dengan email dan password
 * - Validasi format email
 * - Validasi password (minimal 6 karakter)
 * - Konfirmasi password
 * - Cek email sudah digunakan atau belum
 * - Password di-hash sebelum disimpan ke database
 * - Auto-redirect ke login setelah berhasil registrasi
 * 
 * KEAMANAN:
 * - Password di-hash menggunakan password_hash() dengan algoritma default (bcrypt)
 * - Menggunakan prepared statements untuk mencegah SQL injection
 * - Validasi email menggunakan filter_var()
 */

declare(strict_types=1);  // Aktifkan strict type checking
require_once 'config/koneksi.php';  // Include koneksi database

// Variabel untuk menyimpan pesan error dan success
$error = '';
$success = '';

// ============================================
// HANDLE FORM REGISTRASI (POST REQUEST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan bersihkan (trim untuk menghapus spasi di awal/akhir)
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // ============================================
    // VALIDASI INPUT
    // ============================================
    if (empty($email)) {
        $error = 'Email harus diisi!';
    } 
    // Validasi format email menggunakan filter_var()
    // FILTER_VALIDATE_EMAIL memastikan format email valid (contoh: user@domain.com)
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } 
    elseif (empty($password)) {
        $error = 'Password harus diisi!';
    } 
    // Cek apakah password dan konfirmasi password sama
    elseif ($password !== $password_confirm) {
        $error = 'Password dan konfirmasi password tidak sama!';
    } 
    // Validasi panjang password minimal 6 karakter
    elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } 
    // Jika semua validasi berhasil, lanjut proses registrasi
    else {
        try {
            // Cek apakah email sudah digunakan di database
            $stmt = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Jika email sudah ada, tampilkan error
                $error = 'Email sudah digunakan!';
            } else {
                // ============================================
                // REGISTRASI USER BARU
                // ============================================
                // Hash password menggunakan password_hash() dengan algoritma default
                // PASSWORD_DEFAULT menggunakan algoritma bcrypt yang aman
                // Hash ini akan disimpan di database, bukan password plain text
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user baru ke database
                // is_admin = 0 artinya user biasa (bukan admin)
                // Admin dibuat manual di database atau oleh admin yang sudah ada
                $stmt = $koneksi->prepare("INSERT INTO users (email, password_hash, is_admin) VALUES (?, ?, 0)");
                $stmt->execute([$email, $password_hash]);
                
                // Tampilkan pesan sukses
                $success = 'Registrasi berhasil! Silakan login.';
                
                // Redirect ke halaman login setelah 2 detik
                // refresh:2 berarti tunggu 2 detik sebelum redirect
                header("refresh:2;url=login.php");
            }
        } catch (PDOException $e) {
            // Jika terjadi error database, tampilkan pesan error
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | SIPAS</title>
    <link href="assets/css/utilities.css" rel="stylesheet">
    <link href="assets/css/landing.css" rel="stylesheet">
    <link href="assets/css/auth.css" rel="stylesheet">
</head>
<body class="landing-body">
    <!-- Header sama dengan landing page -->
    <header class="landing-header">
        <div class="landing-header-inner">
            <a href="index.php" class="landing-brand" aria-label="Beranda SIPAS">
                <img src="assets/images/logo.png" alt="Logo SIPAS" class="landing-brand-logo">
                <div class="landing-brand-text">
                    <div class="landing-brand-title">SIPAS</div>
                    <div class="landing-brand-sub">Puskesmas Sijunjung</div>
                </div>
            </a>
            <nav class="landing-header-actions" aria-label="Aksi">
                <a href="login.php" class="landing-btn landing-btn-primary">Login</a>
            </nav>
        </div>
    </header>

    <!-- Form Register -->
    <main class="landing-main">
        <div class="landing-hero">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Daftar Akun</h1>
                    <p class="auth-subtitle">Buat akun baru untuk mengakses SIPAS</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="auth-alert auth-alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" method="post" action="">
                    <div class="auth-field">
                        <label class="auth-label">Email</label>
                        <input type="email" name="email" class="auth-input" required>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label">Password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" name="password" id="password" class="auth-input" required>
                            <button type="button" class="auth-toggle-password" onclick="togglePassword('password')" title="Tampilkan/sembunyikan password" aria-label="Tampilkan password">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="auth-label">Konfirmasi Password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" name="password_confirm" id="password_confirm" class="auth-input" required>
                            <button type="button" class="auth-toggle-password" onclick="togglePassword('password_confirm')" title="Tampilkan/sembunyikan password" aria-label="Tampilkan password">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="landing-btn landing-btn-primary auth-btn">Daftar</button>
                    <p class="auth-link">Sudah punya akun? <a href="login.php">Login di sini</a></p>
                </form>
            </div>
        </div>
    </main>
<script>
function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var btn = input && input.nextElementSibling;
    if (!input || !btn || !btn.classList.contains('auth-toggle-password')) return;
    var hide = input.type === 'password';
    input.type = hide ? 'text' : 'password';
    btn.setAttribute('aria-label', hide ? 'Sembunyikan password' : 'Tampilkan password');
    var eye = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    var eyeOff = '<svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
    btn.innerHTML = hide ? eyeOff : eye;
    btn.classList.toggle('active', hide);
}
</script>
</body>
</html>
