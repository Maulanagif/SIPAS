<?php
/**
 * FILE: config/koneksi.php
 * FUNGSI: Konfigurasi koneksi database menggunakan PDO (PHP Data Objects)
 * 
 * CARA KERJA:
 * - File ini membuat koneksi ke database MySQL menggunakan PDO
 * - Variabel $koneksi dapat digunakan di semua file yang require file ini
 * - Menggunakan prepared statements untuk keamanan (mencegah SQL injection)
 * 
 * PENTING:
 * - Ganti nilai $host, $dbname, $username, $password sesuai dengan konfigurasi database Anda
 * - Pastikan database sudah dibuat sebelum menggunakan aplikasi ini
 * - ATTR_ERRMODE di-set ke EXCEPTION agar error langsung ditampilkan untuk debugging
 */

// Set timezone ke Asia/Jakarta (WIB) untuk memastikan waktu server sesuai
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi database
$host = 'localhost';      // Host database (biasanya localhost untuk XAMPP)
$dbname = 'sipas';        // Nama database
$username = 'root';       // Username database (default XAMPP: root)
$password = '';           // Password database (default XAMPP: kosong)

try {
    // Membuat koneksi PDO ke database MySQL
    // Format: mysql:host=HOST;dbname=NAMA_DATABASE
    $koneksi = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Set error mode ke EXCEPTION agar semua error database langsung ditangkap
    // Ini membantu debugging dan mencegah error tersembunyi
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan eksekusi script
    // Catatan: Di production, sebaiknya jangan tampilkan pesan error detail ke user
    die("Koneksi gagal: " . $e->getMessage());
}
