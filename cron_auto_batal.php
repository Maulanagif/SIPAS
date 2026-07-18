<?php
/**
 * FILE: cron_auto_batal.php
 * FUNGSI: Auto-batal antrian yang status Menunggu atau Dipanggil saat jam pelayanan habis
 * 
 * LOGIKA:
 * - Antrian yang dipanggil (status Dipanggil) atau menunggu (status Menunggu) tetapi tidak diubah
 *   ke Selesai akan otomatis dibatalkan (dihapus) saat jam pelayanan habis.
 * - Jam pelayanan habis: 15:00 WIB (sesuai pendaftaran antrian 08:00-15:00)
 * - Hanya memproses antrian dengan tanggal_kunjungan = hari ini
 * 
 * PENJADWALAN:
 * - Jalankan via Windows Task Scheduler atau cron setiap hari jam 15:01 WIB
 * - Contoh Windows Task Scheduler: php "C:\xampp\htdocs\SIPAS\cron_auto_batal.php"
 * - Contoh cron Linux: 1 15 * * 1-5 php /path/to/SIPAS/cron_auto_batal.php
 * 
 * AKSES:
 * - Bisa dipanggil via CLI: php cron_auto_batal.php
 * - Bisa dipanggil via HTTP (opsional, untuk server tanpa cron)
 */

// Atur timezone ke WIB agar jam sesuai dengan waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/koneksi.php';

// Konfigurasi jam pelayanan habis: 15:00 WIB (sesuai jam tutup pendaftaran antrian)
$jam_tutup = 15;
$menit_tutup = 0;

// Hitung waktu saat ini dan waktu tutup dalam menit (untuk perbandingan)
$sekarang = (int)date('H') * 60 + (int)date('i');
$waktu_tutup = $jam_tutup * 60 + $menit_tutup;

// Cek: script hanya berjalan jika sudah lewat jam tutup (misal 15:01 atau lebih)
if ($sekarang < $waktu_tutup) {
    $msg = "Belum jam pelayanan habis. Jalankan setelah {$jam_tutup}:00 WIB.";
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit;
}

// Ambil tanggal hari ini dalam format Y-m-d untuk filter antrian
$tanggal_hari_ini = date('Y-m-d');

try {
    // Query: ambil semua antrian yang masih Menunggu/Dipanggil untuk tanggal hari ini
    $stmt = $koneksi->prepare("
        SELECT id_antrian, COALESCE(nama_manual, 'Pasien') as nama, status, nomor_antrian
        FROM antrian
        WHERE tanggal_kunjungan = ?
        AND LOWER(TRIM(status)) IN ('menunggu', 'dipanggil', 'sedang_dilayani')
        ORDER BY id_antrian ASC
    ");
    $stmt->execute([$tanggal_hari_ini]);
    $antrian_batal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $jumlah = count($antrian_batal);
    
    if ($jumlah > 0) {
        // Hapus antrian dari database (sama seperti saat admin memilih status Batal di kelola_antrian)
        $stmt_del = $koneksi->prepare("
            DELETE FROM antrian
            WHERE tanggal_kunjungan = ?
            AND LOWER(TRIM(status)) IN ('menunggu', 'dipanggil', 'sedang_dilayani')
        ");
        $stmt_del->execute([$tanggal_hari_ini]);
        $terhapus = $stmt_del->rowCount();
        
        $msg = "Auto-batal: {$terhapus} antrian (Menunggu/Dipanggil) untuk {$tanggal_hari_ini} telah dibatalkan otomatis karena jam pelayanan habis.";
    } else {
        $msg = "Tidak ada antrian Menunggu/Dipanggil yang perlu dibatalkan untuk {$tanggal_hari_ini}.";
    }
    
    // Output: format berbeda untuk CLI vs HTTP (browser/API)
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $msg, 'terhapus' => $jumlah]);
    }
} catch (PDOException $e) {
    // Tangani error database
    $msg = 'Error: ' . $e->getMessage();
    if (php_sapi_name() === 'cli') {
        echo $msg . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit(1);
}
