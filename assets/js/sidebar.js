/**
 * FILE: assets/js/sidebar.js
 * FUNGSI: Mengelola sidebar mobile navigation dan active menu item
 * 
 * FITUR:
 * 1. Buka/tutup sidebar mobile (hamburger menu)
 * 2. Auto-close sidebar saat menu item diklik
 * 3. Set active menu item berdasarkan halaman saat ini (desktop & mobile)
 * 
 * CARA KERJA:
 * - Menggunakan class 'active' untuk menampilkan/menyembunyikan sidebar
 * - Menggunakan overlay gelap di belakang sidebar untuk UX yang lebih baik
 * - Membandingkan URL halaman saat ini dengan data-page attribute untuk set active menu
 * 
 * PENGGUNAAN:
 * - Di-include di semua halaman admin dan user
 * - Membutuhkan elemen HTML: #hamburgerBtn, #sidebar, #sidebarOverlay, #sidebarCloseBtn
 */

// Tunggu sampai DOM (HTML) sudah siap dimuat
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // AMBIL ELEMEN-ELEMEN YANG DIPERLUKAN
    // ============================================
    const hamburgerBtn = document.getElementById('hamburgerBtn');      // Tombol hamburger untuk buka sidebar
    const sidebar = document.getElementById('sidebar');                // Container sidebar
    const sidebarOverlay = document.getElementById('sidebarOverlay');  // Overlay gelap di belakang sidebar
    const sidebarCloseBtn = document.getElementById('sidebarCloseBtn'); // Tombol X untuk tutup sidebar
    
    // ============================================
    // FUNGSI BUKA/TUTUP SIDEBAR
    // ============================================
    
    /**
     * Fungsi untuk membuka sidebar mobile
     * Menambahkan class 'active' pada sidebar dan overlay, serta class 'sidebar-open' pada body
     */
    function openSidebar() {
        if (sidebar) sidebar.classList.add('active');              // Tampilkan sidebar
        if (sidebarOverlay) sidebarOverlay.classList.add('active'); // Tampilkan overlay gelap
        document.body.classList.add('sidebar-open');                // Lock scroll body (via CSS)
    }
    
    /**
     * Fungsi untuk menutup sidebar mobile
     * Menghapus class 'active' dari sidebar dan overlay, serta class 'sidebar-open' dari body
     */
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('active');              // Sembunyikan sidebar
        if (sidebarOverlay) sidebarOverlay.classList.remove('active'); // Sembunyikan overlay
        document.body.classList.remove('sidebar-open');                // Unlock scroll body
    }
    
    // ============================================
    // EVENT LISTENERS UNTUK BUKA/TUTUP SIDEBAR
    // ============================================
    
    // Event: Klik tombol hamburger → buka sidebar
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', openSidebar);
    }
    
    // Event: Klik tombol close (X) → tutup sidebar
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener('click', closeSidebar);
    }
    
    // Event: Klik overlay gelap di belakang sidebar → tutup sidebar
    // Ini memberikan UX yang lebih baik, user bisa klik di luar sidebar untuk menutup
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // ============================================
    // AUTO-CLOSE SIDEBAR SAAT MENU ITEM DIKLIK
    // ============================================
    // Support untuk menu user (sidebar-menu-item) dan menu admin (admin-sidebar-menu-item)
    const sidebarMenuItems = document.querySelectorAll('.sidebar-menu-item, .admin-sidebar-menu-item');
    sidebarMenuItems.forEach(item => {
        item.addEventListener('click', () => {
            // Jangan tutup sidebar jika ini adalah link logout
            // Logout biasanya perlu konfirmasi, jadi sidebar tetap terbuka
            if (!item.classList.contains('sidebar-menu-item-logout') && 
                !item.classList.contains('admin-sidebar-menu-item-logout')) {
                // Tutup sidebar setelah 300ms (memberi waktu untuk navigasi)
                setTimeout(closeSidebar, 300);
            }
        });
    });
    
    // ============================================
    // SET ACTIVE MENU ITEM BERDASARKAN HALAMAN
    // ============================================
    // Menandai menu item yang aktif sesuai dengan halaman yang sedang dibuka
    
    // Ambil nama file halaman saat ini dari URL
    // Contoh: /views/admin/dashboard_admin.php → dashboard_admin.php
    const currentPage = window.location.pathname.split('/').pop();
    
    // ============================================
    // SET ACTIVE MENU DESKTOP
    // ============================================
    // Support untuk menu user (header-nav-item) dan menu admin (admin-header-nav-item)
    const headerNavItems = document.querySelectorAll('.header-nav-item, .admin-header-nav-item');
    headerNavItems.forEach(item => {
        // Ambil nama file dari attribute data-page
        const page = item.getAttribute('data-page');
        
        // Jika nama file sama dengan halaman saat ini, tambahkan class 'active'
        if (page && page === currentPage) {
            item.classList.add('active');      // Tambah class active untuk styling (highlight menu)
        } else {
            item.classList.remove('active');   // Hapus class active jika bukan halaman ini
        }
    });
    
    // ============================================
    // SET ACTIVE MENU MOBILE/SIDEBAR
    // ============================================
    // Support untuk menu user (sidebar-menu-item) dan menu admin (admin-sidebar-menu-item)
    const sidebarMenuItemsAll = document.querySelectorAll('.sidebar-menu-item, .admin-sidebar-menu-item');
    sidebarMenuItemsAll.forEach(item => {
        // Ambil nama file dari attribute data-page
        const page = item.getAttribute('data-page');
        
        // Jika nama file sama dengan halaman saat ini, tambahkan class 'active'
        if (page && page === currentPage) {
            item.classList.add('active');      // Tambah class active untuk styling (highlight menu)
        } else {
            item.classList.remove('active');   // Hapus class active jika bukan halaman ini
        }
    });
});

