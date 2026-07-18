<?php
/**
 * FILE: views/user/includes/footer_pa.php
 * FUNGSI: Footer component khusus untuk halaman Pendaftaran Awal
 * 
 * FITUR:
 * - Logo SIPAS dan deskripsi singkat
 * - Informasi kontak puskesmas
 * - Copyright dengan tahun dinamis
 * - Tanpa menu cepat (karena user belum punya akses ke fitur lain)
 * 
 * PERBEDAAN DENGAN footer.php:
 * - footer.php: untuk halaman user yang sudah punya data pasien (ada menu cepat)
 * - footer_pa.php: untuk halaman pendaftaran awal saja (tanpa menu cepat)
 * 
 * PENGGUNAAN:
 * - Hanya digunakan di halaman pendaftaran_awal.php
 * - Contoh: <?php include 'includes/footer_pa.php'; ?>
 * 
 * STYLING:
 * - Menggunakan styling dari assets/css/user/footer.css
 * - Layout lebih sederhana tanpa section menu cepat
 */
?>
<!-- Footer untuk Pendaftaran Awal - Tanpa Menu Cepat -->
<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-grid">
            <!-- Section 1: Tentang -->
            <div class="footer-section">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <img src="../../assets/images/logo.png" alt="SIPAS Logo">
                    </div>
                    <div class="footer-logo-text">
                        <h4>SIPAS</h4>
                        <p>Sistem Informasi Pendaftaran Antrian Pasien</p>
                    </div>
                </div>
                <p>Sistem informasi untuk memudahkan pendaftaran antrian pasien secara online.</p>
            </div>
            
            <!-- Section 2: Kontak -->
            <div class="footer-section">
                <h3>Kontak</h3>
                <p>8X2J+67W, Lalan, Kecamaten Sijunjung, Kabupaten Sijunjung, Sumatera Barat 27562</p>
                <p>Email: info@sipas.com</p>
                <p>Telepon: +62-xxx-xxxx-xxxx</p>
                <p>WhatsApp: +62-823-8683-4040</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> SIPAS. All rights reserved.</p>
        </div>
    </div>
</footer>

