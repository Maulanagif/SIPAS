<?php
/**
 * FILE: views/admin/cetak_laporan.php
 * FUNGSI: Halaman cetak laporan (tanpa sidebar/navbar). Auto print saat dimuat.
 *
 * ============================================================================
 * DAFTAR JENIS LAPORAN (parameter ?jenis=...) - CARI LOKASI EDIT DI FILE:
 * ============================================================================
 *
 * 1. LAPORAN DATA PASIEN (jenis=data_pasien)
 *    - Judul kop: "LAPORAN DATA PASIEN"
 *    - Cari: "LAPORAN 1: DATA PASIEN" atau "TABEL: LAPORAN DATA PASIEN"
 *    - Kolom: No | Nama Pasien | NIK | Jenis Kelamin | Tgl Lahir/Umur | No HP | Status Pasien
 *
 * 2. LAPORAN DAFTAR ANTRIAN PER KLASTER (jenis=antrian_klaster)
 *    - Judul kop: "DAFTAR ANTRIAN PASIEN PER KLASTER"
 *    - Cari: "LAPORAN 2: ANTRIAN KLASTER" atau "TABEL: LAPORAN DAFTAR ANTRIAN"
 *    - Info: Klaster + Tanggal kunjungan
 *    - Kolom: No | Nomor Antrian | Nama Pasien | Keluhan
 *
 * 3. LAPORAN PENDAFTARAN ANTRIAN PER KLASTER (jenis=pendaftaran_antrian_klaster)
 *    - Judul kop: "PENDAFTARAN ANTRIAN PER KLASTER"
 *    - Cari: "LAPORAN 3: PENDAFTARAN ANTRIAN" atau "TABEL: PENDAFTARAN ANTRIAN"
 *    - Info: Periode + Klaster
 *    - Kolom: No | Tanggal Kunjungan | Nomor Antrian | Nama | Keluhan | Sumber Pendaftaran | Status Antrian
 *
 * 4. LAPORAN REKAPITULASI (jenis=rekapitulasi)
 *    - Judul kop: "REKAPITULASI PENDAFTARAN ANTRIAN SELURUH KLASTER"
 *    - Cari: "LAPORAN 4: REKAPITULASI" atau "TABEL: REKAPITULASI"
 *    - Info: Periode (Bulan Tahun)
 *    - Kolom: No | Klaster | Antri | Selesai | Batal
 *
 * ============================================================================
 * LOKASI EDIT PENTING:
 * ============================================================================
 * - judul_kop           → Teks judul di atas tabel (di-set per jenis laporan)
 * - info_klaster, info_tanggal_kunjungan → Teks di bawah judul (antrian_klaster)
 * - info_periode_pendaftaran, info_klaster_pendaftaran → (pendaftaran_antrian_klaster)
 * - info_periode_rekapitulasi            → (rekapitulasi)
 * - formatHariTanggalCetak               → Format "Hari, dd/mm/yyyy"
 * - signature-block                      → Blok tanda tangan (Sijunjung, tanggal | Admin)
 */

session_start();
require_once '../../config/koneksi.php';

// ============================================
// CEK AUTENTIKASI DAN AUTHORIZATION
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../login.php');
    exit;
}

$jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : 'data_pasien';
if (!in_array($jenis, ['data_pasien', 'antrian_klaster', 'pendaftaran_antrian_klaster', 'rekapitulasi'])) {
    $jenis = 'data_pasien';
}

function formatTanggalCetak($tanggal) {
    if (empty($tanggal)) return '-';
    return date('d/m/Y', strtotime($tanggal));
}

function formatHariTanggalCetak($tanggal) {
    if (empty($tanggal)) return '-';
    $hari_id = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $n = (int) date('w', strtotime($tanggal)); // 0 = Minggu, 6 = Sabtu
    return $hari_id[$n] . ', ' . date('d/m/Y', strtotime($tanggal));
}

$data_laporan = [];
$error = '';
$judul_kop = 'LAPORAN DATA PASIEN';
$info_filter = '';
// Untuk cetak antrian_klaster: baris di bawah judul (urutan: Klaster dulu, lalu Tanggal kunjungan)
$info_klaster = '';
$info_tanggal_kunjungan = '';
$info_periode_pendaftaran = '';
$info_periode_rekapitulasi = ''; // Untuk rekapitulasi: "Periode: Bulan Tahun"

try {
    // ========== LAPORAN 1: DATA PASIEN (jenis=data_pasien) ==========
    if ($jenis === 'data_pasien') {
        $stmt = $koneksi->prepare("
            SELECT id, nama, nik, jenis_kelamin, tanggal_lahir, umur, no_hp, is_pasien_baru, is_bpjs
            FROM pasien
            ORDER BY nama ASC
        ");
        $stmt->execute();
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ========== LAPORAN 3: PENDAFTARAN ANTRIAN PER KLASTER (jenis=pendaftaran_antrian_klaster) ==========
    } elseif ($jenis === 'pendaftaran_antrian_klaster') {
        $judul_kop = 'PENDAFTARAN ANTRIAN PER KLASTER';
        $bulan_cetak = isset($_GET['bulan']) && preg_match('/^\d{4}-\d{2}$/', trim($_GET['bulan'])) ? trim($_GET['bulan']) : '';
        if ($bulan_cetak !== '') {
            $tanggal_awal = $bulan_cetak . '-01';
            $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
        } else {
            $tanggal_awal = isset($_GET['tanggal_awal']) && trim($_GET['tanggal_awal']) !== '' ? trim($_GET['tanggal_awal']) : date('Y-m-01');
            $tanggal_akhir = isset($_GET['tanggal_akhir']) && trim($_GET['tanggal_akhir']) !== '' ? trim($_GET['tanggal_akhir']) : date('Y-m-d');
        }
        $klaster_id = isset($_GET['klaster_id']) ? trim($_GET['klaster_id']) : 'semua';
        if ($klaster_id !== 'semua' && !ctype_digit($klaster_id)) $klaster_id = 'semua';

        $nama_bulan_id = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        if ($bulan_cetak !== '') {
            $parts = explode('-', $bulan_cetak);
            $info_periode_pendaftaran = ($nama_bulan_id[(int)$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
        } else {
            $awal_ts = strtotime($tanggal_awal);
            $info_periode_pendaftaran = ($nama_bulan_id[(int)date('n', $awal_ts)] ?? date('m', $awal_ts)) . ' ' . date('Y', $awal_ts);
        }
        if ($klaster_id !== 'semua') {
            $stmtK = $koneksi->prepare("SELECT nama_klaster FROM klaster WHERE id = ?");
            $stmtK->execute([(int)$klaster_id]);
            $rowK = $stmtK->fetch(PDO::FETCH_ASSOC);
            $info_klaster_pendaftaran = $rowK ? $rowK['nama_klaster'] : '-';
        } else {
            $info_klaster_pendaftaran = 'Semua';
        }

        $params = [$tanggal_awal, $tanggal_akhir];
        $where_klaster = '';
        if ($klaster_id !== 'semua') {
            $where_klaster = ' AND a.klaster_id = ?';
            $params[] = (int)$klaster_id;
        }
        $sql = "
            /* sumber_pendaftaran: Offline=antrian manual, Online=daftar via sistem */
            SELECT a.tanggal_kunjungan, a.nomor_antrian, COALESCE(p.nama, a.nama_manual) AS nama, a.keluhan, COALESCE(a.sumber, 'Online') AS sumber_pendaftaran, a.status
            FROM antrian a
            LEFT JOIN pasien p ON a.pasien_id = p.id
            INNER JOIN klaster k ON a.klaster_id = k.id
            WHERE a.tanggal_kunjungan BETWEEN ? AND ?" . $where_klaster . "
            ORDER BY a.tanggal_kunjungan ASC, k.nama_klaster ASC, a.nomor_antrian ASC, a.created_at ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute($params);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ========== LAPORAN 4: REKAPITULASI (jenis=rekapitulasi) ==========
    } elseif ($jenis === 'rekapitulasi') {
        $judul_kop = 'REKAPITULASI PENDAFTARAN ANTRIAN SELURUH KLASTER';
        $bulan_cetak = isset($_GET['bulan']) && preg_match('/^\d{4}-\d{2}$/', trim($_GET['bulan'])) ? trim($_GET['bulan']) : '';
        if ($bulan_cetak !== '') {
            $tanggal_awal = $bulan_cetak . '-01';
            $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
        } else {
            $tanggal_awal = date('Y-m-01');
            $tanggal_akhir = date('Y-m-d');
        }
        $nama_bulan_id = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        if ($bulan_cetak !== '') {
            $parts = explode('-', $bulan_cetak);
            $info_periode_rekapitulasi = ($nama_bulan_id[(int)$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
        } else {
            $awal_ts = strtotime($tanggal_awal);
            $info_periode_rekapitulasi = ($nama_bulan_id[(int)date('n', $awal_ts)] ?? date('m', $awal_ts)) . ' ' . date('Y', $awal_ts);
        }
        /* Hanya status Selesai dan Batal yang masuk laporan; Menunggu dan Dipanggil tidak dihitung. Antri = total (selesai + batal). */
        $sql = "
            SELECT
                k.nama_klaster AS klaster,
                COUNT(a.klaster_id) AS antri,
                SUM(CASE WHEN LOWER(a.status) = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN LOWER(a.status) = 'batal' THEN 1 ELSE 0 END) AS batal
            FROM klaster k
            LEFT JOIN antrian a ON a.klaster_id = k.id
                AND a.tanggal_kunjungan BETWEEN ? AND ?
                AND LOWER(a.status) IN ('selesai','batal')
            GROUP BY k.id, k.nama_klaster
            ORDER BY k.nama_klaster ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ========== LAPORAN 2: DAFTAR ANTRIAN PER KLASTER (jenis=antrian_klaster) ==========
    } else {
        $judul_kop = 'DAFTAR ANTRIAN PASIEN PER KLASTER';
        $tanggal_antrian = isset($_GET['tanggal']) && trim($_GET['tanggal']) !== '' ? trim($_GET['tanggal']) : date('Y-m-d');
        $klaster_id = isset($_GET['klaster_id']) ? trim($_GET['klaster_id']) : 'semua';
        if ($klaster_id !== 'semua' && !ctype_digit($klaster_id)) $klaster_id = 'semua';

        $params = [$tanggal_antrian];
        $where_klaster = '';
        if ($klaster_id !== 'semua') {
            $where_klaster = ' AND a.klaster_id = ?';
            $params[] = (int)$klaster_id;
        }
        if ($klaster_id !== 'semua') {
            $stmtK = $koneksi->prepare("SELECT nama_klaster FROM klaster WHERE id = ?");
            $stmtK->execute([(int)$klaster_id]);
            $rowK = $stmtK->fetch(PDO::FETCH_ASSOC);
            $info_klaster = 'Klaster: ' . ($rowK ? $rowK['nama_klaster'] : '-');
        } else {
            $info_klaster = 'Klaster: Semua';
        }
        $info_tanggal_kunjungan = 'Tanggal : ' . formatTanggalCetak($tanggal_antrian);

        $sql = "
            SELECT a.nomor_antrian, COALESCE(p.nama, a.nama_manual) AS nama, a.keluhan
            FROM antrian a
            LEFT JOIN pasien p ON a.pasien_id = p.id
            INNER JOIN klaster k ON a.klaster_id = k.id
            WHERE a.tanggal_kunjungan = ?" . $where_klaster . "
            ORDER BY k.nama_klaster ASC, a.nomor_antrian ASC, a.created_at ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute($params);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Error mengambil data laporan: " . $e->getMessage();
}

// Tanggal cetak untuk ditampilkan (format: Sijunjung, 03 Februari 2026)
$nama_bulan_id = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$tgl_cetak = date('d') . ' ' . $nama_bulan_id[(int)date('n')] . ' ' . date('Y');
$tanggal_display = $tgl_cetak; // untuk info di bawah judul
$tanggal_ttd = 'Sijunjung, ' . $tgl_cetak; // untuk blok tanda tangan
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak <?php
    if ($jenis === 'antrian_klaster') echo 'Laporan Daftar Antrian Per Klaster';
    elseif ($jenis === 'pendaftaran_antrian_klaster') echo 'Pendaftaran Antrian per Klaster';
    elseif ($jenis === 'rekapitulasi') echo 'REKAPITULASI PENDAFTARAN ANTRIAN SELURUH KLASTER';
    else echo 'Laporan Data Pasien';
?> | SIPAS</title>
    <style>
        /* ============================================
           STYLING UNTUK CETAK A4 FORMAL
           ============================================
           File: cetak_laporan.php
           Deskripsi: Styles khusus untuk halaman cetak laporan antrian
           Format: A4 Portrait dengan kop surat formal
           ============================================ */
        
        /* ============================================
           1. RESET & BASE STYLES
           ============================================
           Reset semua margin, padding, dan box-sizing
           ============================================ */
        
        /* Universal Reset - Reset semua elemen */
        * {
            margin: 0;                         /* Hapus margin default semua elemen */
            padding: 0;                        /* Hapus padding default semua elemen */
            box-sizing: border-box;            /* Padding dan border termasuk dalam width/height */
        }
        
        /* ============================================
           2. BODY STYLES
           ============================================
           Style dasar untuk body (font, warna, layout)
           ============================================ */
        
        body {
            /* FONT FAMILY - Font untuk seluruh dokumen
               Times New Roman = font formal standar untuk dokumen resmi */
            font-family: 'Times New Roman', serif;
            
            /* FONT SIZE - Ukuran font dasar (11pt = standar dokumen formal)
               Ubah untuk lebih besar/kecil: font-size: 12pt; atau 10pt; */
            font-size: 11pt;
            
            /* LINE HEIGHT - Jarak antar baris (1.6 = 160% dari font size)
               Ubah untuk lebih rapat/lebar: line-height: 1.5; atau 1.8; */
            line-height: 1.6;
            
            /* COLOR - Warna teks hitam (standar dokumen formal) */
            color: #000;
            
            /* BACKGROUND - Background putih (standar dokumen formal) */
            background: white;
            
            /* PADDING & MARGIN - Hapus semua padding dan margin */
            padding: 0;
            margin: 0;
        }
        
        /* ============================================
           3. CONTAINER
           ============================================
           Container utama untuk konten dokumen
           ============================================ */
        
        .container {
            /* MAX WIDTH - Lebar maksimal 21cm (lebar kertas A4)
               Menggunakan cm agar sesuai dengan ukuran kertas A4 */
            max-width: 21cm;
            
            /* MARGIN AUTO - Center container di tengah halaman */
            margin: 0 auto;
            
            /* PADDING - Jarak dalam container (1cm = standar margin dokumen)
               Ubah untuk lebih besar/kecil: padding: 1.5cm; atau 0.8cm; */
            padding: 1cm;
        }
        
        /* ============================================
           4. KOP SURAT (HEADER)
           ============================================
           Bagian kop surat dengan logo, nama instansi, dan alamat
           ============================================ */
        
        /* Container Kop Surat */
        .kop-laporan {
            margin-bottom: 0;
        }
        
        /* Header Kop Surat - Layout grid untuk logo kiri, konten tengah, logo kanan */
        .kop-header {
            /* DISPLAY GRID - Menggunakan CSS Grid untuk layout 3 kolom
               Kolom 1: Logo kiri, Kolom 2: Konten tengah (fleksibel), Kolom 3: Logo kanan */
            display: grid;
            
            /* GRID TEMPLATE COLUMNS - 3 kolom: 120px (logo kiri) | 1fr (konten tengah) | 120px (logo kanan)
               Ubah lebar logo: 100px 1fr 100px; (logo lebih kecil)
               Atau 150px 1fr 150px; (logo lebih besar) */
            grid-template-columns: 120px 1fr 120px;
            
            /* GRID TEMPLATE ROWS - Tinggi tetap 110px untuk header
               Ubah untuk lebih tinggi/rendah: grid-template-rows: 120px; atau 100px; */
            grid-template-rows: 110px;
            
            /* ALIGN ITEMS CENTER - Vertikal center semua elemen dalam grid */
            align-items: center;
            
            /* JUSTIFY ITEMS CENTER - Horizontal center semua elemen dalam grid */
            justify-items: center;
            
            /* GAP - Jarak antar kolom (antara logo kiri, konten tengah, logo kanan)
               Ubah untuk lebih jauh/dekat: gap: 20px; atau 10px; */
            gap: 15px;
            
            /* MARGIN BOTTOM - Jarak bawah header ke garis (kecil = garis dekat gambar) */
            margin-bottom: 4px;
            
            /* MIN HEIGHT - Tinggi minimal header (memastikan header tidak terlalu kecil) */
            min-height: 110px;
        }
        
        /* Container Logo (kiri dan kanan) */
        .kop-logo {
            /* WIDTH & HEIGHT - Lebar dan tinggi penuh container grid (120px x 110px) */
            width: 100%;
            height: 100%;
            
            /* DISPLAY GRID - Menggunakan grid untuk center logo di tengah container */
            display: grid;
            
            /* PLACE ITEMS CENTER - Center logo secara vertikal dan horizontal */
            place-items: center;
            
            /* POSITION RELATIVE - Untuk positioning elemen di dalam jika perlu */
            position: relative;
        }
        
        /* Gambar Logo */
        .kop-logo img {
            /* WIDTH & HEIGHT - Ukuran logo kiri (logosjj.png) 115px x 115px
               Logo kiri memiliki dimensi landscape sehingga perlu lebih besar */
            width: 115px;
            height: 115px;
            
            /* OBJECT FIT CONTAIN - Logo tidak terdistorsi, proporsional
               Logo akan di-fit ke dalam area 115x115px tanpa terpotong */
            object-fit: contain;
            
            /* OBJECT POSITION - Posisi logo di tengah center
               Ubah untuk offset: object-position: center top; */
            object-position: center center;
            
            /* DISPLAY BLOCK - Menghapus space di bawah gambar */
            display: block;
            
            /* MARGIN & PADDING - Hapus margin dan padding */
            margin: 0;
            padding: 0;
            
            /* VERTICAL ALIGN - Alignment vertikal di tengah */
            vertical-align: middle;
        }
        
        /* Logo Kanan - Ukuran lebih kecil karena aspect ratio portrait
           Logo kanan (logo.png) memiliki dimensi portrait
           sehingga ukuran lebih kecil untuk visual yang seimbang */
        .kop-logo:last-child img {
            /* WIDTH & HEIGHT - Ukuran logo kanan (logo.png) 90px x 90px
               Mengkompensasi perbedaan aspect ratio agar visual seimbang */
            width: 90px;
            height: 90px;
        }
        
        /* Konten Tengah (Nama Instansi & Alamat) */
        .kop-content {
            /* FLEX - Mengambil sisa ruang yang tersedia (kolom tengah fleksibel) */
            flex: 1;
            
            /* DISPLAY FLEX - Menggunakan flexbox untuk layout vertikal */
            display: flex;
            
            /* FLEX DIRECTION COLUMN - Susun nama dan alamat secara vertikal */
            flex-direction: column;
            
            /* ALIGN ITEMS CENTER - Center konten secara horizontal */
            align-items: center;
            
            /* TEXT ALIGN CENTER - Teks rata tengah */
            text-align: center;
            
            /* PADDING - Jarak kiri-kanan dari logo (0 atas-bawah, 20px kiri-kanan)
               Ubah untuk lebih jauh/dekat: padding: 0 25px; atau 0 15px; */
            padding: 0 20px;
        }
        
        /* Nama Instansi */
        .kop-nama {
            /* FONT FAMILY - Font Times New Roman (formal) */
            font-family: 'Times New Roman', serif;
            
            /* FONT SIZE - Ukuran font 16pt (besar dan menonjol)
               Ubah untuk lebih besar/kecil: font-size: 18pt; atau 14pt; */
            font-size: 16pt;
            
            /* FONT WEIGHT - Tebal (bold) untuk emphasis */
            font-weight: bold;
            
            /* MARGIN - Margin bawah 5px dari alamat
               Format: margin: atas kanan bawah kiri */
            margin: 0 0 5px 0;
            
            /* TEXT TRANSFORM - Ubah semua huruf menjadi kapital */
            text-transform: uppercase;
            
            /* LETTER SPACING - Jarak antar huruf (0.5px untuk spacing yang rapi)
               Ubah untuk lebih lebar/rapat: letter-spacing: 1px; atau 0px; */
            letter-spacing: 0.5px;
        }
        
        /* Alamat Instansi */
        .kop-alamat {
            /* FONT FAMILY - Font Times New Roman (formal) */
            font-family: 'Times New Roman', serif;
            
            /* FONT SIZE - Ukuran font 10pt (lebih kecil dari nama)
               Ubah untuk lebih besar/kecil: font-size: 11pt; atau 9pt; */
            font-size: 10pt;
            
            /* MARGIN - Hapus margin default */
            margin: 0;
            
            /* LINE HEIGHT - Jarak antar baris (1.4 = 140% dari font size)
               Ubah untuk lebih rapat/lebar: line-height: 1.5; atau 1.3; */
            line-height: 1.4;
        }
        
        /* ---- JARAK GAMPANG DIUBAH: ubah angka di bawah ini saja ---- */
        /* Garis: margin atas kecil = garis dekat gambar. Judul: margin-top = jarak garis ke judul */
        .kop-garis { border-bottom: 2px solid #000; margin: 2px 0 0 0; width: 100%; }
        
        .kop-judul {
            text-align: center;
            margin-top: 12px;
            margin-bottom: 0;
        }
        .kop-judul h2 {
            font-family: 'Times New Roman', serif;
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* margin: [atas] 0 [bawah] 0 → ubah 4px = jarak judul-ke-info, 10px = jarak info-ke-tabel */
        .info-periode {
            text-align: center;
            margin: 4px 0 10px 0;
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            font-weight: normal;
        }
        
        /* ============================================
           6. TABEL LAPORAN
           ============================================
           Styles untuk tabel data laporan
           ============================================ */
        
        /* Container Tabel */
        .table-container {
            /* WIDTH - Lebar penuh container */
            width: 100%;
            
            /* MARGIN AUTO - Center tabel di tengah */
            margin: 0 auto;
            
            /* OVERFLOW X AUTO - Scroll horizontal jika tabel terlalu lebar */
            overflow-x: auto;
        }
        
        /* Tabel */
        table {
            /* WIDTH - Lebar penuh container */
            width: 100%;
            
            /* BORDER COLLAPSE - Gabungkan border sel yang berdekatan
               Membuat border terlihat lebih rapi dan tidak double */
            border-collapse: collapse;
            
            /* MARGIN - Hapus margin default */
            margin: 0;
            
            /* FONT SIZE - Ukuran font 10pt untuk teks dalam tabel
               Ubah untuk lebih besar/kecil: font-size: 11pt; atau 9pt; */
            font-size: 10pt;
        }
        
        /* Header dan Cell Tabel */
        table th,
        table td {
            /* BORDER - Border hitam 1px di semua sisi
               Ubah ketebalan: border: 2px solid #000; */
            border: 1px solid #000;
            
            /* PADDING - Jarak dalam cell (8px atas-bawah, 10px kiri-kanan)
               Ubah untuk lebih besar/kecil: padding: 10px 12px; atau 6px 8px; */
            padding: 8px 10px;
            
            /* TEXT ALIGN - Teks rata kiri secara default */
            text-align: left;
            
            /* VERTICAL ALIGN - Alignment vertikal di atas
               Berguna untuk cell dengan multiple lines */
            vertical-align: top;
        }
        
        /* Header Tabel */
        table th {
            /* BACKGROUND COLOR - Background biru untuk header
               rgb(157, 174, 248) = biru muda
               Ubah warna: background-color: #667eea; (biru) atau #2563eb; (biru gelap) */
            background-color: rgb(157, 174, 248);
            
            /* COLOR - Warna teks putih (kontras dengan background biru) */
            color: white;
            
            /* FONT WEIGHT - Tebal (bold) */
            font-weight: bold;
            
            /* TEXT ALIGN - Teks rata tengah di header */
            text-align: center;
            
            /* FONT SIZE - Ukuran font 10pt (sama dengan body tabel) */
            font-size: 10pt;
        }
        
        /* Cell Tabel (Body) */
        table td {
            /* TEXT ALIGN - Teks rata kiri di body tabel */
            text-align: left;
            
            /* FONT SIZE - Ukuran font 10pt */
            font-size: 10pt;
        }
        
        /* Kolom No (Kolom Pertama) - Center alignment */
        table th:first-child,
        table td:first-child {
            /* TEXT ALIGN - Teks rata tengah untuk kolom nomor */
            text-align: center;
            
            /* WIDTH - Lebar tetap 40px untuk kolom nomor
               Ubah untuk lebih lebar/sempit: width: 50px; atau 30px; */
            width: 40px;
        }
        
        /* Status Badge - Badge untuk status antrian (menunggu, selesai, dll) */
        .status-badge {
            /* DISPLAY INLINE BLOCK - Tampil inline tapi bisa diatur lebar/tinggi */
            display: inline-block;
            
            /* PADDING - Jarak dalam badge (2px atas-bawah, 6px kiri-kanan)
               Ubah untuk lebih besar/kecil: padding: 4px 8px; */
            padding: 2px 6px;
            
            /* BORDER - Border hitam 1px */
            border: 1px solid #000;
            
            /* BORDER RADIUS - Sudut melengkung 3px
               Ubah untuk lebih bulat/tajam: border-radius: 5px; atau 0; */
            border-radius: 3px;
            
            /* FONT SIZE - Ukuran font 9pt (lebih kecil dari teks tabel)
               Ubah untuk lebih besar/kecil: font-size: 10pt; atau 8pt; */
            font-size: 9pt;
        }
        
        /* Zebra Striping - Warna alternatif untuk baris genap (mudah dibaca) */
        table tbody tr:nth-child(even) {
            /* BACKGROUND COLOR - Background abu-abu sangat terang untuk baris genap
               Membantu readability dan membedakan baris
               Ubah warna: background-color: #f5f5f5; atau #fafafa; */
            background-color: #f9f9f9;
        }
        
        /* Blok Tanda Tangan - blok di kanan, isi rata kiri agar Sijunjung & Admin sejajar */
        .signature-block {
            margin-top: 40px;
            margin-left: auto;
            width: fit-content;
            max-width: 280px;
            text-align: left;
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
        }
        .signature-date {
            margin-bottom: 0.20em; /* Jarak dekat antara tanggal dan Admin */
        }
        .signature-role {
            margin-bottom: 24px; /* Jarak ke garis tanda tangan (tidak terlalu jauh) */
        }
        .signature-underline {
            margin-top: 0;
            letter-spacing: 2px;
        }
        
        /* ============================================
           7. PRINT SETTINGS (MEDIA QUERY)
           ============================================
           Pengaturan khusus untuk saat dokumen dicetak
           ============================================ */
        
        @media print {
            /* @PAGE - Pengaturan halaman untuk print
               Mengatur ukuran kertas dan margin saat dicetak */
            @page {
                /* SIZE - Ukuran kertas A4 Portrait (21cm x 29.7cm)
                   Ubah ke landscape: size: A4 landscape;
                   Atau ukuran lain: size: Letter portrait; */
                size: A4 portrait;
                
                /* MARGIN - Margin halaman 1.5cm di semua sisi
                   Standar margin untuk dokumen formal
                   Ubah untuk lebih besar/kecil: margin: 2cm; atau 1cm; */
                margin: 1.5cm;
            }
            
            /* Body saat Print */
            body {
                /* MARGIN & PADDING - Hapus semua margin dan padding
                   Mengandalkan margin dari @page */
                margin: 0;
                padding: 0;
            }
            
            /* Container saat Print */
            .container {
                /* MAX WIDTH - Lebar maksimal 100% (menggunakan lebar halaman penuh) */
                max-width: 100%;
                
                /* PADDING & MARGIN - Hapus padding dan margin
                   Mengandalkan margin dari @page */
                padding: 0;
                margin: 0;
            }

            /* Tabel saat print - hilangkan scroll horizontal,
               biarkan tabel menyesuaikan lebar halaman */
            .table-container {
                overflow-x: visible !important;
            }
            
            /* No Print - Sembunyikan elemen dengan class .no-print saat print
               Contoh: tombol kembali, menu navigasi, dll */
            .no-print {
                display: none !important;
            }
            
            /* Pastikan Kop Header Alignment Tetap Presisi Saat Print */
            .kop-header {
                /* DISPLAY GRID - Tetap menggunakan grid layout
                   !important untuk override style lain yang mungkin conflict */
                display: grid !important;
                
                /* GRID TEMPLATE COLUMNS - Tetap 3 kolom: 120px | 1fr | 120px */
                grid-template-columns: 120px 1fr 120px !important;
                
                /* GRID TEMPLATE ROWS - Tinggi tetap 110px */
                grid-template-rows: 110px !important;
                
                /* MIN HEIGHT - Tinggi minimal 110px */
                min-height: 110px !important;
            }
            
            .kop-logo {
                /* WIDTH & HEIGHT - Lebar dan tinggi penuh container */
                width: 100% !important;
                height: 100% !important;
                
                /* DISPLAY GRID - Tetap menggunakan grid untuk centering */
                display: grid !important;
                
                /* PLACE ITEMS CENTER - Center logo di tengah */
                place-items: center !important;
            }
            
            .kop-logo img {
                /* WIDTH & HEIGHT - Ukuran logo kiri (logosjj.png) 115px x 115px */
                width: 115px !important;
                height: 115px !important;
                
                /* OBJECT FIT CONTAIN - Logo tetap proporsional */
                object-fit: contain !important;
                
                /* OBJECT POSITION - Posisi logo di tengah */
                object-position: center center !important;
            }
            
            /* Logo Kanan - Ukuran lebih kecil saat print untuk visual yang seimbang
               Logo kanan (logo.png) memiliki dimensi portrait */
            .kop-logo:last-child img {
                width: 90px !important;
                height: 90px !important;
            }
            
            .kop-laporan { margin-bottom: 0 !important; }
            .kop-judul { margin-bottom: 0 !important; }
            .info-periode { margin: 4px 0 10px 0 !important; }
            
            /* Tabel Print Settings */
            table {
                /* PAGE BREAK INSIDE AUTO - Boleh page break di dalam tabel
                   Jika tabel panjang, boleh dipotong di tengah */
                page-break-inside: auto;
            }
            
            table tr {
                /* PAGE BREAK INSIDE AVOID - Hindari memotong baris tabel di tengah
                   Baris tabel tidak boleh dipotong antar halaman */
                page-break-inside: avoid;
                
                /* PAGE BREAK AFTER AUTO - Boleh page break setelah baris */
                page-break-after: auto;
            }
            
            table thead {
                /* DISPLAY TABLE HEADER GROUP - Header tabel ditampilkan di setiap halaman
                   Jika tabel panjang dan dipotong ke halaman baru, header akan muncul lagi */
                display: table-header-group;
            }
            
            table tfoot {
                /* DISPLAY TABLE FOOTER GROUP - Footer tabel ditampilkan di setiap halaman
                   Jika ada footer tabel, akan muncul di setiap halaman */
                display: table-footer-group;
            }
        }
        
        /* ============================================
           8. SCREEN ONLY (Hanya Tampil di Layar)
           ============================================
           Styles untuk elemen yang hanya tampil di layar, tidak saat print
           ============================================ */
        
        /* Tombol Kembali - Hanya tampil di layar, tidak saat print */
        .btn-kembali {
            /* POSITION FIXED - Posisi fixed di layar (tetap di tempat saat scroll) */
            position: fixed;
            
            /* TOP & RIGHT - Posisi di pojok kanan atas
               Ubah posisi: top: 30px; right: 30px; (lebih jauh dari pinggir) */
            top: 20px;
            right: 20px;
            
            /* PADDING - Jarak dalam tombol (10px atas-bawah, 20px kiri-kanan)
               Ubah untuk lebih besar/kecil: padding: 12px 24px; */
            padding: 10px 20px;
            
            /* BACKGROUND - Background biru
               Menggunakan warna primary (#2563eb)
               Ubah warna: background: #1e4fc4; (biru gelap) atau #667eea; (biru ungu) */
            background: #2563eb;
            
            /* COLOR - Warna teks putih (kontras dengan background biru) */
            color: white;
            
            /* BORDER - Hapus border default */
            border: none;
            
            /* BORDER RADIUS - Sudut melengkung 8px
               Ubah untuk lebih bulat/tajam: border-radius: 10px; atau 0; */
            border-radius: 8px;
            
            /* CURSOR POINTER - Cursor menjadi pointer saat hover */
            cursor: pointer;
            
            /* FONT SIZE - Ukuran font 14px
               Ubah untuk lebih besar/kecil: font-size: 16px; atau 12px; */
            font-size: 14px;
            
            /* FONT WEIGHT - Tebal 600 (semi-bold) */
            font-weight: 600;
            
            /* TEXT DECORATION - Hapus underline (jika digunakan sebagai link) */
            text-decoration: none;
            
            /* DISPLAY INLINE BLOCK - Tampil inline tapi bisa diatur lebar/tinggi */
            display: inline-block;
            
            /* Z-INDEX - Layer di atas konten lain (1000 = sangat tinggi)
               Memastikan tombol selalu terlihat di atas konten */
            z-index: 1000;
        }
        
        /* Hover State untuk Tombol Kembali */
        .btn-kembali:hover {
            /* BACKGROUND - Background biru lebih gelap saat hover
               Memberikan efek visual saat mouse berada di atas tombol
               #1e4fc4 = biru lebih gelap dari #2563eb */
            background: #1e4fc4;
        }
    </style>
</head>
<body>
    <!-- Tombol Kembali (hanya tampil di screen, tidak di print) -->
    <a href="laporan.php" class="btn-kembali no-print">Kembali</a>
    
    <div class="container">
        <!-- Kop Surat -->
        <div class="kop-laporan">
            <div class="kop-header">
                <div class="kop-logo">
                    <img src="../../assets/images/logosjj.png" alt="Logo">
                </div>
                <div class="kop-content">
                    <div class="kop-nama">Puskesmas Sijunjung</div>
                    <div class="kop-alamat">Jl. Puskesmas No. 85 Telp (0754) 20053 Sijunjung 27553</div>
                </div>
                <div class="kop-logo">
                    <img src="../../assets/images/logo.png" alt="Logo Puskesmas">
                </div>
            </div>
            <div class="kop-garis"></div>
            <div class="kop-judul">
                <h2><?php echo htmlspecialchars($judul_kop); ?></h2>
            </div>
        </div>
        
        <!-- Tanggal / Info filter (di atas tabel) -->
        <div class="info-periode">
            <?php if ($jenis === 'antrian_klaster'): ?>
                <div><?php echo htmlspecialchars($info_klaster); ?></div>
                <div><?php echo htmlspecialchars($info_tanggal_kunjungan); ?></div>
            <?php elseif ($jenis === 'pendaftaran_antrian_klaster'): ?>
            <!-- LAPORAN 3: Pendaftaran Antrian - tampilkan Periode + Klaster -->
                <div>Periode: <?php echo htmlspecialchars($info_periode_pendaftaran); ?></div>
                <div>Klaster: <?php echo htmlspecialchars($info_klaster_pendaftaran ?? 'Semua'); ?></div>
            <?php elseif ($jenis === 'rekapitulasi'): ?>
                <div>Periode: <?php echo htmlspecialchars($info_periode_rekapitulasi); ?></div>
            <?php else: ?>
            <!-- LAPORAN 1: Data Pasien - tampilkan Tanggal -->
                Tanggal: <?php echo htmlspecialchars($tanggal_display); ?>
            <?php endif; ?>
        </div>
        
        <!-- ============================================================ -->
        <!-- TABEL CETAK: 4 jenis laporan (urutannya: antrian_klaster, pendaftaran, rekapitulasi, data_pasien) -->
        <!-- Cari: "TABEL: LAPORAN 1/2/3/4" untuk mengubah header/kolom tiap laporan -->
        <!-- ============================================================ -->
        <div class="table-container">
        <?php if (!empty($error)): ?>
            <p style="text-align: center; margin: 20px 0; color: #842029;"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (empty($data_laporan)): ?>
            <!-- Pesan kosong (beda tiap jenis laporan) -->
            <p style="text-align: center; margin: 20px 0;"><?php
                if ($jenis === 'data_pasien') echo 'Tidak ada data pasien.';
                elseif ($jenis === 'pendaftaran_antrian_klaster') echo 'Tidak ada data pendaftaran antrian untuk periode yang dipilih.';
                elseif ($jenis === 'rekapitulasi') echo 'Tidak ada data rekapitulasi untuk periode yang dipilih.';
                else echo 'Tidak ada data antrian untuk filter yang dipilih.';
            ?></p>
        <?php else: ?>

        <?php if ($jenis === 'antrian_klaster'): ?>
        <!-- ============================================================ -->
        <!-- TABEL: LAPORAN DAFTAR ANTRIAN PASIEN PER KLASTER               -->
        <!-- Kolom: No | Nomor Antrian | Nama Pasien | Keluhan             -->
        <!-- Ubah header/kolom tabel di bawah ini.                          -->
        <!-- ============================================================ -->
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Keluhan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data_laporan as $item): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td style="text-align: center;"><?php echo !empty($item['nomor_antrian']) ? htmlspecialchars($item['nomor_antrian']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['keluhan'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'pendaftaran_antrian_klaster'): ?>
        <!-- ============================================================ -->
        <!-- TABEL LAPORAN 3: PENDAFTARAN ANTRIAN PER KLASTER (7 kolom)     -->
        <!-- Kolom: No | Tanggal Kunjungan | Nomor Antrian | Nama | Keluhan | Sumber Pendaftaran | Status Antrian -->
        <!-- Ubah header/kolom tabel di bawah ini jika perlu.               -->
        <!-- ============================================================ -->
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Nomor Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Keluhan</th>
                        <th>Sumber Pendaftaran</th>
                        <th>Status Antrian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($data_laporan as $item):
                        $sumber_pendaftaran = !empty($item['sumber_pendaftaran']) ? ucfirst(strtolower(trim($item['sumber_pendaftaran']))) : 'Online';
                        $status_antrian = $item['status'] ?? '-';
                        if (strtolower($status_antrian) === 'sedang_dilayani') $status_antrian = 'Dipanggil';
                        else $status_antrian = ucfirst($status_antrian);
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td style="text-align: center;"><?php echo formatTanggalCetak($item['tanggal_kunjungan'] ?? ''); ?></td>
                            <td style="text-align: center;"><?php echo !empty($item['nomor_antrian']) ? htmlspecialchars($item['nomor_antrian']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['keluhan'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($sumber_pendaftaran); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($status_antrian); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'rekapitulasi'): ?>
        <!-- ============================================================ -->
        <!-- TABEL LAPORAN 4: REKAPITULASI                                 -->
        <!-- Kolom: No | Klaster | Antri | Selesai | Batal                 -->
        <!-- Ubah header/kolom tabel di bawah ini jika perlu.               -->
        <!-- ============================================================ -->
            <table>
                <thead>
                    <tr>
                        <th style="text-align: center; width: 40px;">No</th>
                        <th style="min-width: 100px;">Klaster</th>
                        <th style="text-align: center; width: 65px;">Antri</th>
                        <th style="text-align: center; width: 65px;">Selesai</th>
                        <th style="text-align: center; width: 65px;">Batal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data_laporan as $row): ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['klaster'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo (int)($row['antri'] ?? 0); ?></td>
                            <td style="text-align: center;"><?php echo (int)($row['selesai'] ?? 0); ?></td>
                            <td style="text-align: center;"><?php echo (int)($row['batal'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
        <!-- ============================================================ -->
        <!-- TABEL LAPORAN 1: DATA PASIEN (7 kolom)                        -->
        <!-- Kolom: No | Nama Pasien | NIK | Jenis Kelamin | Tgl Lahir/Umur | No HP | Status Pasien -->
        <!-- Ubah header/kolom tabel di bawah ini jika perlu.               -->
        <!-- ============================================================ -->
            <table>
                <thead>
                    <tr>
                        <!-- Laporan Data Pasien: NIK dan No HP rata tengah -->
                        <th style="text-align: center;">No</th>
                        <th>Nama Pasien</th>
                        <th style="text-align: center;">NIK</th>
                        <th style="text-align: center;">Jenis Kelamin</th>
                        <th style="text-align: center;">Tgl Lahir / Umur</th>
                        <th style="text-align: center;">No HP</th>
                        <th style="text-align: center;">Status Pasien</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($data_laporan as $item):
                        $tgl_lahir = $item['tanggal_lahir'] ?? '';
                        $umur = $item['umur'] ?? '';
                        $tgl_umur = '';
                        if (!empty($tgl_lahir)) {
                            $tgl_umur = formatTanggalCetak($tgl_lahir);
                            if ($umur !== '' && $umur !== null) $tgl_umur .= ' / ' . (int)$umur . ' th';
                        } elseif ($umur !== '' && $umur !== null) {
                            $tgl_umur = (int)$umur . ' tahun';
                        } else {
                            $tgl_umur = '-';
                        }
                        $jk = $item['jenis_kelamin'] ?? '';
                        if ($jk == 'L' || strtoupper($jk) == 'LAKI-LAKI') $jk_text = 'Laki-laki';
                        elseif ($jk == 'P' || strtoupper($jk) == 'PEREMPUAN') $jk_text = 'Perempuan';
                        else $jk_text = !empty($jk) ? htmlspecialchars($jk) : '-';
                        $status_pasien = !empty($item['is_pasien_baru']) ? 'Pasien Baru' : 'Pasien Lama';
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($item['nik'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo $jk_text; ?></td>
                            <td style="text-align: center;"><?php echo $tgl_umur; ?></td>
                            <td style="text-align: center;"><?php echo htmlspecialchars($item['no_hp'] ?? '-'); ?></td>
                            <td style="text-align: center;"><?php echo $status_pasien; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php endif; ?>
    </div>

        <!-- Blok Tanda Tangan (sama untuk semua jenis laporan): Sijunjung, [tanggal] | Admin, | _______________ -->
        <div class="signature-block">
            <div class="signature-date"><?php echo htmlspecialchars($tanggal_ttd); ?></div>
            <div class="signature-role">Admin,</div>
            <div class="signature-underline">_______________</div>
        </div>
    </div>
    
    <script>
        // Auto print dialog saat halaman dimuat
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); // Delay 500ms agar konten ter-render dulu
        };
    </script>
</body>
</html>

