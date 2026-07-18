<?php
/**
 * FILE: views/user/includes/header_pa.php
 * FUNGSI: Header component khusus untuk halaman Pendaftaran Awal
 * 
 * FITUR:
 * - Hanya menampilkan logo SIPAS di kiri
 * - Tanpa menu navigasi (karena user belum punya data pasien)
 * - Tanpa tombol logout (sesuai permintaan user)
 * 
 * PERBEDAAN DENGAN header.php:
 * - header.php: untuk halaman user yang sudah punya data pasien (ada menu navigasi)
 * - header_pa.php: untuk halaman pendaftaran awal saja (tanpa menu navigasi)
 * 
 * PENGGUNAAN:
 * - Hanya digunakan di halaman pendaftaran_awal.php
 * - Contoh: <?php include 'includes/header_pa.php'; ?>
 * 
 * STYLING:
 * - Menggunakan styling dari assets/css/user/header.css
 * - Header kanan dikosongkan (tanpa tombol Keluar)
 */
?>
<!-- Header untuk Pendaftaran Awal - Logo + teks diklik → landing -->
<header class="main-header">
    <div class="header-content">
        <div class="header-left">
            <a href="../../landing.php" class="logo-section logo-link" aria-label="Beranda SIPAS">
                <div class="logo-icon">
                    <img src="../../assets/images/logo.png" alt="SIPAS Logo" class="logo-image">
                </div>
                <div class="logo-text">
                    <h1>SIPAS</h1>
                    <p>Puskesmas Sijunjung</p>
                </div>
            </a>
        </div>
        <div class="header-right">
        </div>
    </div>
</header>

