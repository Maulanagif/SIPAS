<?php
/**
 * FILE: views/admin/includes/header_admin.php
 * FUNGSI: Header dan navigasi untuk semua halaman admin
 * 
 * KOMPONEN:
 * 1. Header desktop dengan logo, menu navigasi, dan tombol logout
 * 2. Sidebar mobile dengan menu navigasi dan user info (muncul saat klik hamburger)
 * 3. Overlay gelap untuk sidebar mobile
 * 
 * MENU NAVIGASI:
 * - Dashboard (dashboard_admin.php)
 * - Kelola Antrian (kelola_antrian.php)
 * - Kunjungan Offline (kunjungan_offline.php)
 * - Pengguna (pengguna.php)
 * - Riwayat (riwayat.php)
 * - Laporan (laporan.php)
 * 
 * PENGGUNAAN:
 * - Di-include di semua halaman admin dengan: <?php include 'includes/header_admin.php'; ?>
 * - Pastikan session sudah dimulai sebelum include file ini
 * - Memerlukan file sidebar.js untuk fungsi mobile navigation
 */
?>
<header class="admin-header">
    <div class="admin-header-content">
        <!-- Logo Section -->
        <div class="admin-header-left">
            <!-- Hamburger Menu Button (Hanya tampil di mobile/tablet) -->
            <button class="admin-hamburger-btn" id="hamburgerBtn" aria-label="Toggle Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="admin-logo-section">
                <div class="admin-logo-icon">
                    <img src="../../assets/images/logo.png" alt="SIPAS Logo" class="admin-logo-image">
                </div>
                <div class="admin-logo-text">
                    <h1>SIPAS</h1>
                    <p>Admin </p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="admin-header-nav">
            <div class="admin-header-nav-menu">
                <a href="dashboard_admin.php" class="admin-header-nav-item" data-page="dashboard_admin.php">
                    <span>Dashboard</span>
                </a>
                <?php
                // Pastikan link ke kelola_antrian selalu menggunakan parameter tanggal hari ini
                // Ini memastikan ketika klik menu, akan menampilkan data hari ini, bukan tanggal yang difilter sebelumnya
                $today_for_link = date('Y-m-d');
                ?>
                <a href="kelola_antrian.php?tanggal=<?php echo urlencode($today_for_link); ?>" class="admin-header-nav-item" data-page="kelola_antrian.php">
                    <span>Kelola Antrian</span>
                </a>
                <a href="pengguna.php" class="admin-header-nav-item" data-page="pengguna.php">
                    <span>Pengguna</span>
                </a>
                <a href="riwayat.php" class="admin-header-nav-item" data-page="riwayat.php">
                    <span>Riwayat</span>
                </a>
                <a href="laporan.php" class="admin-header-nav-item" data-page="laporan.php">
                    <span>Laporan</span>
                </a>
            </div>
        </nav>
        
        <!-- Logout Button -->
        <div class="admin-header-right">
            <a href="../../login.php?logout=1" class="admin-logout-btn">Keluar</a>
        </div>
    </div>
</header>

<!-- Sidebar untuk Mobile/Tablet -->
<div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
<aside class="admin-sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div class="admin-sidebar-header">
        <div class="admin-sidebar-logo">
            <div class="admin-sidebar-logo-icon">
                <img src="../../assets/images/logo.png" alt="SIPAS Logo">
            </div>
            <div class="admin-sidebar-logo-text">
                <h2>SIPAS</h2>
                <p>Admin Panel</p>
            </div>
        </div>
        <button class="admin-sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <!-- Sidebar Menu -->
    <nav class="admin-sidebar-menu">
        <a href="dashboard_admin.php" class="admin-sidebar-menu-item" data-page="dashboard_admin.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M2 10L10 2L18 10M4 10V18H8V14H12V18H16V10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Dashboard</span>
        </a>
        <a href="kelola_antrian.php?tanggal=<?php echo urlencode($today_for_link ?? date('Y-m-d')); ?>" class="admin-sidebar-menu-item" data-page="kelola_antrian.php">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 2H16C16.5523 2 17 2.44772 17 3V17C17 17.5523 16.5523 18 16 18H4C3.44772 18 3 17.5523 3 17V3C3 2.44772 3.44772 2 4 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 6H14M6 10H14M6 14H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Kelola Antrian</span>
        </a>
        <a href="pengguna.php" class="admin-sidebar-menu-item" data-page="pengguna.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10Z" fill="currentColor"/>
                <path d="M10 12C5.58172 12 2 15.5817 2 20H18C18 15.5817 14.4183 12 10 12Z" fill="currentColor"/>
            </svg>
            <span>Pengguna</span>
        </a>
        <a href="riwayat.php" class="admin-sidebar-menu-item" data-page="riwayat.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
  <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 6V10L13 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Riwayat</span>
        </a>
        <a href="laporan.php" class="admin-sidebar-menu-item" data-page="laporan.php">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 2H16C16.5523 2 17 2.44772 17 3V17C17 17.5523 16.5523 18 16 18H4C3.44772 18 3 17.5523 3 17V3C3 2.44772 3.44772 2 4 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 6H14M6 10H14M6 14H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Laporan</span>
        </a>
        <a href="../../login.php?logout=1" class="admin-sidebar-menu-item admin-sidebar-menu-item-logout">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M7 16H4C3.44772 16 3 15.5523 3 15V5C3 4.44772 3.44772 4 4 4H7M13 12L17 8M17 8L13 4M17 8H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>Keluar</span>
        </a>
    </nav>
    
    <!-- Sidebar User Info (paling bawah) -->
    <div class="admin-sidebar-user-info">
        <div class="admin-sidebar-user-avatar">
            <span><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
        </div>
        <div class="admin-sidebar-user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
    </div>
</aside>

