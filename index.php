<?php
/**
 * FILE: index.php
 * FUNGSI: Entry point utama aplikasi - router sederhana berdasarkan status login
 *
 * ALUR KERJA:
 * 1. Mulai session untuk cek apakah user sudah login
 * 2. Jika sudah login:
 *    - Admin → redirect ke dashboard admin
 *    - User → cek apakah sudah isi data pasien:
 *      * Sudah → redirect ke dashboard user
 *      * Belum → redirect ke form pendaftaran awal
 * 3. Jika belum login → tampilkan landing page (halaman awal)
 */

session_start();  // Mulai session untuk cek status login
require_once 'config/koneksi.php';  // Include koneksi database

// ============================================
// CEK STATUS LOGIN DAN REDIRECT
// ============================================
// Jika user sudah login, redirect ke halaman sesuai role
if (isset($_SESSION['user_id'])) {
    // ============================================
    // CEK ROLE ADMIN
    // ============================================
    // Jika user adalah admin, redirect ke dashboard admin
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        header('Location: views/admin/dashboard_admin.php');
        exit;
    }
    
    // ============================================
    // CEK DATA PASIEN UNTUK USER BIASA
    // ============================================
    // Jika user biasa, cek apakah sudah punya data pasien
    try {
        // Gunakan COUNT untuk mengecek apakah ada data pasien
        $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total'] > 0) {
            // Sudah ada data pasien, redirect ke dashboard user
            header('Location: views/user/dashboard_user.php');
            exit;
        } else {
            // Belum ada data pasien, redirect ke pendaftaran awal
            header('Location: views/user/pendaftaran_awal.php');
            exit;
        }
    } catch (PDOException $e) {
        // Jika error (misalnya tabel belum ada), redirect ke pendaftaran awal
        header('Location: views/user/pendaftaran_awal.php');
        exit;
    }
}

// ============================================
// TAMPILKAN LANDING PAGE
// ============================================
// Jika belum login, tampilkan landing page (halaman awal)
require_once 'views/landing.php';

