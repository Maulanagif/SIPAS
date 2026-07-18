<?php
// ============================================
// HEADER COMPONENT - UNTUK SEMUA HALAMAN USER
// ============================================
// 
// CARA MENGUBAH TATA LETAK HEADER:
// ============================================
// 
// 1. STRUKTUR HTML (File ini: views/user/includes/header.php)
//    - Baris 8-27: Bagian Logo (header-left)
//    - Baris 29-58: Bagian Navigasi Menu (header-nav)
//    - Baris 60-74: Bagian User Info & Logout (header-right)
//    - Baris 78-142: Sidebar untuk Mobile/Tablet
// 
//    Untuk mengubah urutan atau menambah/menghapus elemen:
//    - Pindahkan div dengan class sesuai kebutuhan
//    - Tambah/hapus menu item di dalam <nav class="header-nav-menu">
// 
// 2. STYLING CSS (File: assets/css/header.css)
//    - .main-header, .header-content, .header-left, .header-nav, .header-nav-item, .header-right
// 
//    Untuk mengubah tata letak:
//    - Ubah "display: flex" menjadi "display: grid" untuk grid layout
//    - Ubah "justify-content" untuk mengatur posisi horizontal
//    - Ubah "align-items" untuk mengatur posisi vertikal
//    - Ubah "flex-direction" untuk mengubah arah (row/column)
//    - Ubah "order" untuk mengubah urutan elemen
// 
// 3. RESPONSIVE DESIGN (File: assets/css/common_user.css)
//    - Baris 650-680: Media query untuk mobile/tablet (max-width: 768px)
//    - Di sini bisa mengubah bagaimana header tampil di layar kecil
// 
// CONTOH MENGUBAH TATA LETAK:
// ============================================
// 
// Contoh 1: Mengubah urutan (Logo kanan, Menu kiri)
//   - Di header.php: Pindahkan <div class="header-left"> setelah <nav class="header-nav">
//   - Di CSS: Ubah "justify-content: space-between" menjadi "flex-direction: row-reverse"
// 
// Contoh 2: Menu vertikal di desktop
//   - Di CSS: Ubah .header-nav-menu dari "display: flex" menjadi "display: flex; flex-direction: column"
// 
// Contoh 3: Mengubah warna background header
//   - Di CSS: Ubah .main-header background dari "white" ke warna lain
// 
// Pastikan session sudah dimulai sebelum include file ini

// Ambil foto_profil dan nama pasien untuk tampil di sidebar (jika belum di-set oleh halaman)
if ((!isset($foto_profil) || !isset($nama_pasien_sidebar)) && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../config/koneksi.php';
    $stmt_foto = $koneksi->prepare("SELECT foto_profil, nama FROM pasien WHERE user_id = ?");
    $stmt_foto->execute([$_SESSION['user_id']]);
    $row_foto = $stmt_foto->fetch(PDO::FETCH_ASSOC);
    if (!isset($foto_profil)) $foto_profil = $row_foto['foto_profil'] ?? null;
    $nama_pasien_sidebar = !empty($row_foto['nama']) ? $row_foto['nama'] : ($_SESSION['username'] ?? '');
} else {
    $foto_profil = $foto_profil ?? null;
    if (!isset($nama_pasien_sidebar)) $nama_pasien_sidebar = $_SESSION['username'] ?? '';
}
?>
<!-- Header Horizontal dengan Logo di Kiri dan Menu di Kanan -->
<header class="main-header">
    <div class="header-content">
        <!-- Logo Section (Kiri) -->
        <div class="header-left">
            <!-- Hamburger Menu Button (Hanya tampil di mobile/tablet) -->
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="logo-section">
                <div class="logo-icon">
                    <img src="../../assets/images/logo.png" alt="SIPAS Logo" class="logo-image">
                </div>
                <div class="logo-text">
                    <h1>SIPAS</h1>
                    <p>Puskesmas Sijunjung</p>
                </div>
            </div>
        </div>
        
        <!-- Navigasi Menu (Tengah/Kanan) -->
        <nav class="header-nav">
            <div class="header-nav-menu">
                <a href="dashboard_user.php" class="header-nav-item" data-page="dashboard_user.php">
                    <span>Dashboard</span>
                </a>
                <a href="daftar_user.php" class="header-nav-item" data-page="daftar_user.php">
                    <span>Daftar</span>
                </a>
                <a href="riwayat_user.php" class="header-nav-item" data-page="riwayat_user.php">
                    <span>Riwayat</span>
                </a>
                <a href="profile_user.php" class="header-nav-item" data-page="profile_user.php">
                    <span>Profil</span>
                </a>
            </div>
        </nav>
        
        <!-- User Info & Logout (Kanan) -->
        <div class="header-right">
            <a href="../../login.php?logout=1" class="header-nav-item header-nav-item-logout">
                <span>Keluar</span>
            </a>
        </div>
    </div>
</header>

<!-- Sidebar untuk Mobile/Tablet -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <img src="../../assets/images/logo.png" alt="SIPAS Logo">
            </div>
            <div class="sidebar-logo-text">
                <h2>SIPAS</h2>
                <p>Sistem Informasi Pendaftaran Antrian Pasien</p>
            </div>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <!-- Sidebar Menu -->
    <nav class="sidebar-menu">
        <a href="dashboard_user.php" class="sidebar-menu-item" data-page="dashboard_user.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M2 10L10 2L18 10M4 10V18H8V14H12V18H16V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="daftar_user.php" class="sidebar-menu-item" data-page="daftar_user.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 2H16C16.5523 2 17 2.44772 17 3V17C17 17.5523 16.5523 18 16 18H4C3.44772 18 3 17.5523 3 17V3C3 2.44772 3.44772 2 4 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 6H14M6 10H14M6 14H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Daftar</span>
        </a>
        <a href="riwayat_user.php" class="sidebar-menu-item" data-page="riwayat_user.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 6V10L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Riwayat</span>
        </a>
        <a href="profile_user.php" class="sidebar-menu-item" data-page="profile_user.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" fill="currentColor"/>
                <path d="M10 12C5.58172 12 2 15.5817 2 20H18C18 15.5817 14.4183 12 10 12Z" fill="currentColor"/>
            </svg>
            <span>Profil</span>
        </a>
        <a href="../../login.php?logout=1" class="sidebar-menu-item sidebar-menu-item-logout">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M7 16H4C3.44772 16 3 15.5523 3 15V5C3 4.44772 3.44772 4 4 4H7M13 12L17 8M17 8L13 4M17 8H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Keluar</span>
        </a>
    </nav>
    
    <!-- Sidebar User Info (paling bawah) - tampilkan nama lengkap -->
    <div class="sidebar-user-info">
        <div class="sidebar-user-avatar">
            <?php if (!empty($foto_profil)): ?>
                <img src="<?php echo htmlspecialchars($foto_profil); ?>" alt="Foto" class="sidebar-user-avatar-img">
            <?php else: ?>
                <?php
                $initial = strlen($nama_pasien_sidebar) >= 2 ? strtoupper(substr($nama_pasien_sidebar, 0, 2)) : strtoupper(substr($nama_pasien_sidebar, 0, 1));
                ?>
                <span><?php echo htmlspecialchars($initial); ?></span>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-name"><?php echo htmlspecialchars($nama_pasien_sidebar); ?></div>
    </div>
</aside>

