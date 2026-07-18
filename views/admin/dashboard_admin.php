<?php
/**
 * FILE: views/admin/dashboard_admin.php
 * FUNGSI: Halaman dashboard untuk admin
 * 
 * FITUR:
 * - Menampilkan menu navigasi ke fitur admin lainnya
 * - Menampilkan welcome message dengan nama admin
 * - Menu card untuk akses cepat ke fitur utama
 * 
 * AKSES:
 * - Hanya bisa diakses oleh user dengan role admin (is_admin = 1)
 * - Redirect ke login jika belum login atau bukan admin
 */

session_start();  // Mulai session untuk cek status login
require_once '../../config/koneksi.php';  // Include koneksi database

// ============================================
// CEK AUTENTIKASI DAN AUTHORIZATION
// ============================================
// Cek apakah user sudah login DAN apakah user adalah admin
// Jika tidak login atau bukan admin, redirect ke halaman login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../login.php');
    exit;
}

// ============================================
// AMBIL DATA ANTRIAN HARI INI
// ============================================
// Pastikan menggunakan tanggal hari ini dengan benar
// Gunakan CURDATE() dari MySQL untuk mendapatkan tanggal server database yang akurat
$antrian_hari_ini = [];
$total_antrian_hari_ini = 0;

// Ambil tanggal hari ini dari database (lebih akurat daripada PHP date())
try {
    $stmt_date = $koneksi->query("SELECT CURDATE() as tanggal_hari_ini");
    $result_date = $stmt_date->fetch(PDO::FETCH_ASSOC);
    $tanggal_hari_ini = $result_date['tanggal_hari_ini'] ?? date('Y-m-d');
} catch (PDOException $e) {
    // Fallback ke PHP date jika query gagal
    $tanggal_hari_ini = date('Y-m-d');
}

// Fungsi untuk format tanggal Indonesia dengan hari
function formatTanggalLengkap($tanggal) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'];
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    // Pastikan menggunakan timestamp yang benar dari tanggal
    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        // Jika gagal, gunakan waktu saat ini
        $timestamp = time();
    }
    
    $hari_nama = $hari[date('w', $timestamp)];
    $tanggal_angka = date('d', $timestamp);
    $bulan_nama = $bulan[(int)date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    
    return $hari_nama . ', ' . $tanggal_angka . ' ' . $bulan_nama . ' ' . $tahun;
}

try {
    // Query untuk mengambil antrian hari ini dengan data pasien dan klaster
    // Menggunakan CURDATE() langsung dari MySQL untuk memastikan tanggal akurat
    $stmt = $koneksi->prepare("
        SELECT 
            a.id_antrian as id,
            a.nomor_antrian,
            a.status,
            a.tanggal_kunjungan,
            a.created_at as waktu_daftar,
            COALESCE(p.nama, a.nama_manual) as nama_pasien,
            COALESCE(p.nik, a.nik_manual) as nik,
            k.nama_klaster
        FROM antrian a
        LEFT JOIN pasien p ON a.pasien_id = p.id
        INNER JOIN klaster k ON a.klaster_id = k.id
        WHERE a.tanggal_kunjungan = CURDATE()
        AND a.status != 'Batal'
        ORDER BY 
            CASE a.status 
                WHEN 'Menunggu' THEN 1
                WHEN 'Dipanggil' THEN 2
                WHEN 'Selesai' THEN 3
                ELSE 4
            END,
            a.nomor_antrian ASC,
            a.created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $antrian_hari_ini = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung total antrian hari ini menggunakan CURDATE()
    $stmt_count = $koneksi->prepare("
        SELECT COUNT(*) as total 
        FROM antrian 
        WHERE tanggal_kunjungan = CURDATE() AND status != 'Batal'
    ");
    $stmt_count->execute();
    $result_count = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_antrian_hari_ini = $result_count ? $result_count['total'] : 0;
} catch (PDOException $e) {
    error_log("Error mengambil antrian hari ini: " . $e->getMessage());
}

// ============================================
// AJAX ENDPOINT - Return JSON jika parameter ajax=1
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $antrian_hari_ini,
        'count' => count($antrian_hari_ini),
        'total' => $total_antrian_hari_ini
    ]);
    exit;
}

// ============================================
// FUNGSI HELPER: FORMAT STATUS BADGE
// ============================================
function getStatusBadge($status) {
    switch($status) {
        case 'Menunggu':
            return '<span class="status-badge status-badge-menunggu">Menunggu</span>';
        case 'Dipanggil':
            return '<span class="status-badge status-badge-dipanggil">Dipanggil</span>';
        case 'Selesai':
            return '<span class="status-badge status-badge-selesai">Selesai</span>';
        case 'Batal':
            return '<span class="status-badge status-badge-batal">Batal</span>';
        default:
            return '<span class="status-badge status-badge-default">' . htmlspecialchars($status) . '</span>';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header_admin.php'; ?>
        
    <div class="dashboard-container">
        <!-- Antrian Hari Ini -->
        <div class="card">
            <div class="card-header-section">
                <div class="card-header-info">
                    <h2 class="card-header-title">Antrian Hari Ini</h2>
                    <p class="card-header-date">
                        <span id="tanggal-display"><?php echo formatTanggalLengkap($tanggal_hari_ini ?? date('Y-m-d')); ?></span>
                    </p>
                    <p class="card-header-time">
                        <span id="jam-display"><?php echo date('H:i:s'); ?></span> WIB
                    </p>
                </div>
                <div class="card-header-actions">
                    <a href="kelola_antrian.php?tanggal=<?php echo urlencode($tanggal_hari_ini ?? date('Y-m-d')); ?>" class="btn primary">
                        Kelola Antrian
                    </a>
                </div>
            </div>
            
            <div id="antrian-container">
                <?php if ($total_antrian_hari_ini == 0): ?>
                    <div class="empty-state">
                        <p>Tidak ada antrian untuk hari ini.</p>
                    </div>
                <?php else: ?>
                    <p id="total-antrian" class="card-content-info">
                        Total: <strong><?php echo $total_antrian_hari_ini; ?></strong> antrian 
                        <?php if (count($antrian_hari_ini) < $total_antrian_hari_ini): ?>
                            (Menampilkan <?php echo count($antrian_hari_ini); ?> teratas)
                        <?php endif; ?>
                    </p>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Nama Pasien</th>
                                    <th>NIK</th>
                                    <th>Klaster</th>
                                    <th>Waktu Daftar</th>
                                    <th class="text-center">Nomor Antrian</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="antrian-admin-table-body">
                                <?php 
                                $no = 1;
                                foreach ($antrian_hari_ini as $item): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['nama_pasien']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['nik'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($item['nama_klaster']); ?></td>
                                        <td>
                                            <?php 
                                            if ($item['waktu_daftar']) {
                                                // Format: dd/mm/YYYY HH:ii WIB (contoh: 08/01/2024 14:30 WIB)
                                                echo date('d/m/Y H:i', strtotime($item['waktu_daftar'])) . ' WIB';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                            if ($item['nomor_antrian']) {
                                                echo '<strong>' . htmlspecialchars($item['nomor_antrian']) . '</strong>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center"><?php echo getStatusBadge($item['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($antrian_hari_ini) < $total_antrian_hari_ini): ?>
                        <div class="card-footer-actions">
                            <a href="kelola_antrian.php?tanggal=<?php echo urlencode($tanggal_hari_ini ?? date('Y-m-d')); ?>" class="btn btn-secondary">
                                Lihat Semua Antrian
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script>
        // Real-time clock untuk jam yang terus berjalan dan tanggal
        function updateClock() {
            const now = new Date();
            
            // Format jam: HH:MM:SS
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = hours + ':' + minutes + ':' + seconds;
            
            // Update jam display
            const jamDisplay = document.getElementById('jam-display');
            if (jamDisplay) {
                jamDisplay.textContent = timeString;
            }
            
            // Update tanggal menggunakan waktu lokal browser
            const tanggalDisplay = document.getElementById('tanggal-display');
            if (tanggalDisplay) {
                const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'];
                const bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                
                const hari_nama = hari[now.getDay()];
                const tanggal_angka = String(now.getDate()).padStart(2, '0');
                const bulan_nama = bulan[now.getMonth()];
                const tahun = now.getFullYear();
                
                tanggalDisplay.textContent = hari_nama + ', ' + tanggal_angka + ' ' + bulan_nama + ' ' + tahun;
            }
        }
        
        // Update jam setiap detik
        setInterval(updateClock, 1000);
        
        // Panggil sekali saat halaman dimuat untuk update tanggal dan jam
        updateClock();
    </script>
</body>
</html>


