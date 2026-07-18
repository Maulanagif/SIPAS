<?php
/**
 * FILE: views/admin/laporan.php
 * FUNGSI: Halaman menu laporan admin - dropdown pilih jenis laporan
 *
 * LAPORAN SAAT INI:
 * 1. Laporan Data Pasien              → laporan_detail.php?jenis=data_pasien
 * 2. Daftar Antrian Per Klaster       → laporan_detail.php?jenis=antrian_klaster
 * 3. Pendaftaran Antrian per Klaster  → laporan_detail.php?jenis=pendaftaran_antrian_klaster
 * 4. Rekapitulasi                     → laporan_detail.php?jenis=rekapitulasi
 *
 * UNTUK MENAMBAH LAPORAN BARU:
 * - Tambah <option value="NAMA_JENIS">Judul</option> di dropdown
 * - Tambah penanganan jenis baru di laporan_detail.php dan cetak_laporan.php
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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
    <link href="../../assets/css/admin/laporan.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header_admin.php'; ?>

        <div class="dashboard-container">
            <!-- Card pilih laporan: dropdown + tombol (menggantikan card grid) -->
            <div class="card report-select-card">
                <h2 class="report-title">Laporan</h2>
                <p class="report-subtitle">Pilih jenis laporan yang ingin Anda lihat.</p>
                <form method="get" action="laporan_detail.php" class="report-dropdown-form">
                    <div class="report-dropdown-wrap">
                        <!-- Dropdown: pilih jenis laporan, value dikirim ke laporan_detail.php?jenis=xxx -->
                        <select name="jenis" id="jenis_laporan" class="report-select" required>
                            <option value="">-- Pilih Jenis Laporan --</option>
                            <option value="data_pasien">Laporan Data Pasien</option>
                            <option value="antrian_klaster">Laporan Daftar Antrian Pasien Per Klaster</option>
                            <option value="pendaftaran_antrian_klaster">Pendaftaran Antrian per Klaster</option>
                            <option value="rekapitulasi">Rekapitulasi</option>
                        </select>
                        <!-- Tombol submit: redirect ke laporan_detail.php dengan parameter jenis -->
                        <button type="submit" class="btn btn-primary report-submit-btn">Lihat Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
</body>
</html>
