<?php
/**
 * FILE: views/user/includes/footer.php
 * FUNGSI: Footer component untuk semua halaman user
 * 
 * FITUR:
 * - Logo SIPAS dan deskripsi singkat
 * - Menu cepat (Dashboard, Daftar Antrian, Riwayat, Profil)
 * - Informasi kontak puskesmas
 * - Copyright dengan tahun dinamis
 * 
 * PENGGUNAAN:
 * - Di-include di bagian bawah body setiap halaman user
 * - Contoh: <?php include 'includes/footer.php'; ?>
 * 
 * STYLING:
 * - File CSS: assets/css/user/footer.css
 * - Responsive design untuk mobile dan desktop
 */
?>
<!-- Footer -->
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
            
            <!-- Section 2: Menu Cepat -->
            <div class="footer-section">
                <h3>Menu Cepat</h3>
                <a href="dashboard_user.php">Dashboard</a>
                <a href="daftar_user.php">Daftar Antrian</a>
                <a href="riwayat_user.php">Riwayat Kunjungan</a>
                <a href="profile_user.php">Profil Saya</a>
            </div>
            
            <!-- Section 3: Kontak -->
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

