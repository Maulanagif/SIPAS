<?php
/**
 * FILE: login.php
 * FUNGSI: Halaman login dan autentikasi user (admin dan pasien)
 * 
 * FITUR:
 * - Login dengan email dan password
 * - Logout (via ?logout=1)
 * - Auto-redirect jika sudah login
 * - Redirect berdasarkan role (admin/user) dan status data pasien
 * 
 * KEAMANAN:
 * - Password di-hash menggunakan password_hash()
 * - Password di-verify menggunakan password_verify()
 * - Menggunakan prepared statements untuk mencegah SQL injection
 */

declare(strict_types=1);  // Aktifkan strict type checking untuk keamanan lebih
session_start();  // Mulai session untuk menyimpan data login
require_once 'config/koneksi.php';  // Include koneksi database

// ============================================
// HANDLE LOGOUT
// ============================================
// Jika user mengakses login.php?logout=1, hapus semua session dan redirect ke landing (index)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ============================================
// CEK APAKAH SUDAH LOGIN
// ============================================
// Jika sudah login, redirect ke halaman sesuai role (tidak perlu login lagi)
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: views/admin/dashboard_admin.php');
    } else {
        // Cek apakah user sudah memiliki data di tabel pasien
        try {
            // Gunakan COUNT untuk mengecek apakah ada data, lebih aman
            $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total'] > 0) {
                // Sudah ada data pasien, redirect ke dashboard
                header('Location: views/user/dashboard_user.php');
                exit;
            } else {
                // Belum ada data pasien, redirect ke pendaftaran awal
                header('Location: views/user/pendaftaran_awal.php');
                exit;
            }
        } catch (PDOException $e) {
            // Jika tabel pasien belum ada atau error, redirect ke pendaftaran awal
            header('Location: views/user/pendaftaran_awal.php');
            exit;
        }
    }
    exit;
}

// ============================================
// HANDLE FORM LOGIN (POST REQUEST)
// ============================================
$error = '';  // Variabel untuk menyimpan pesan error

// Cek apakah form login sudah di-submit (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan bersihkan (trim untuk menghapus spasi di awal/akhir)
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validasi: cek apakah email dan password sudah diisi
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        // Cari user di database berdasarkan email
        // Query ini akan mengambil data user termasuk admin (is_admin)
        try {
            $stmt = $koneksi->prepare("SELECT id, email, password_hash, is_admin FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifikasi password menggunakan password_verify()
            // Fungsi ini membandingkan password yang diinput dengan hash di database
            // Pastikan password di database di-hash menggunakan password_hash()
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login berhasil! Simpan data user ke session
                $_SESSION['user_id'] = $user['id'];  // ID user untuk query database
                $_SESSION['username'] = $user['email'];  // Email disimpan sebagai username untuk kompatibilitas
                $_SESSION['is_admin'] = (bool)$user['is_admin'];  // Role user (true = admin, false = user biasa)
                
                // Redirect berdasarkan role user
                if ($user['is_admin']) {
                    // Jika admin, langsung ke dashboard admin
                    header('Location: views/admin/dashboard_admin.php');
                } else {
                    // Jika user biasa, cek apakah sudah isi data pasien
                    try {
                        // Cek apakah user sudah punya data di tabel pasien
                        $stmt_pasien = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE user_id = ?");
                        $stmt_pasien->execute([$user['id']]);
                        $result = $stmt_pasien->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result && $result['total'] > 0) {
                            // Sudah ada data pasien, user bisa langsung ke dashboard
                            header('Location: views/user/dashboard_user.php');
                        } else {
                            // Belum ada data pasien, user harus isi data dulu
                            header('Location: views/user/pendaftaran_awal.php');
                        }
                    } catch (PDOException $e) {
                        // Jika error (misal tabel pasien belum ada), redirect ke pendaftaran awal
                        header('Location: views/user/pendaftaran_awal.php');
                    }
                }
                exit;  // Pastikan script berhenti setelah redirect
            } else {
                // Password salah atau user tidak ditemukan
                // Pesan error sama untuk keamanan (tidak reveal apakah email ada atau tidak)
                $error = 'Email atau password salah!';
            }
        } catch (PDOException $e) {
            // Error dari database, tampilkan pesan error
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
    <title>Login | SIPAS</title>
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
                <a href="register.php" class="landing-btn landing-btn-primary">Daftar Akun</a>
            </nav>
        </div>
    </header>

    <!-- Form Login -->
    <main class="landing-main">
        <div class="landing-hero">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 class="auth-title">Masuk ke SIPAS</h1>
                    <p class="auth-subtitle">Masukkan email dan password Anda</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error">
                        <?php echo htmlspecialchars($error); ?>
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

                    <button type="submit" class="landing-btn landing-btn-primary auth-btn">Masuk</button>
                    <p class="auth-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
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
