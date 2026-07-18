<?php
/**
 * FILE: views/user/batalkan_antrian.php
 * FUNGSI: Proses pembatalan antrian oleh pasien
 * 
 * FITUR:
 * - Membatalkan antrian yang masih bisa dibatalkan (status Menunggu atau Dipanggil)
 * - Validasi bahwa antrian milik user yang login
 * - Validasi bahwa status antrian masih bisa dibatalkan
 * - Update status antrian menjadi "Batal" di database
 * - Redirect kembali ke halaman daftar antrian dengan pesan sukses/error
 * 
 * AKSES:
 * - Hanya bisa diakses via POST request (form submission)
 * - User harus sudah login
 * - Antrian harus milik user yang login
 * 
 * CATATAN:
 * - Hanya antrian dengan status "Menunggu" atau "Dipanggil"/"sedang_dilayani" yang bisa dibatalkan
 * - Antrian yang sudah "Selesai" atau sudah "Batal" tidak bisa dibatalkan lagi
 * - Setelah dibatalkan, antrian tidak akan muncul di daftar antrian aktif
 */
session_start();
require_once '../../config/koneksi.php';

// ============================================
// CEK AUTENTIKASI
// ============================================
// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ============================================
// VALIDASI REQUEST METHOD
// ============================================
// Hanya terima request POST (form submission)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: daftar_user.php');
    exit;
}

// ============================================
// VALIDASI ID ANTRIAN
// ============================================
// Ambil dan validasi ID antrian dari POST request
$id_antrian = isset($_POST['id_antrian']) ? (int)$_POST['id_antrian'] : 0;

if ($id_antrian <= 0) {
    header('Location: daftar_user.php?error_cancel=' . urlencode('Data antrian tidak valid.'));
    exit;
}

// ============================================
// PROSES PEMBATALAN ANTRIAN
// ============================================
try {
    // Cek dulu antrian milik user ini dan statusnya masih boleh dibatalkan
    $stmt = $koneksi->prepare("
        SELECT a.id_antrian, a.status, p.user_id
        FROM antrian a
        INNER JOIN pasien p ON a.pasien_id = p.id
        WHERE a.id_antrian = ?
        LIMIT 1
    ");
    $stmt->execute([$id_antrian]);
    $antrian = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$antrian || (int)$antrian['user_id'] !== (int)$user_id) {
        // Antrian tidak ditemukan atau bukan milik user yang login
        header('Location: daftar_user.php?error_cancel=' . urlencode('Antrian tidak ditemukan.'));
        exit;
    }

    // ============================================
    // VALIDASI STATUS ANTRIAN
    // ============================================
    // Hanya boleh batal jika status Menunggu atau Dipanggil/sedang_dilayani
    $status = strtolower($antrian['status'] ?? '');

    if (!in_array($status, ['menunggu', 'dipanggil', 'sedang_dilayani'], true)) {
        header('Location: daftar_user.php?error_cancel=' . urlencode('Antrian tidak dapat dibatalkan.'));
        exit;
    }

    // ============================================
    // UPDATE STATUS ANTRIAN MENJADI BATAL
    // ============================================
    // Update status antrian menjadi "Batal" di database
    $stmtUpdate = $koneksi->prepare("
        UPDATE antrian 
        SET status = 'Batal'
        WHERE id_antrian = ?
    ");
    $stmtUpdate->execute([$id_antrian]);

    // Redirect kembali ke halaman daftar antrian dengan pesan sukses
    header('Location: daftar_user.php?success_cancel=1');
    exit;

} catch (PDOException $e) {
    $msg = 'Gagal membatalkan antrian: ' . $e->getMessage();
    header('Location: daftar_user.php?error_cancel=' . urlencode($msg));
    exit;
}


