<?php
/**
 * FILE: views/admin/riwayat.php
 * FUNGSI: Halaman untuk admin melihat semua riwayat antrian pasien
 * 
 * FITUR:
 * - Menampilkan semua riwayat antrian dengan pencarian nama
 * - Pencarian berdasarkan nama pasien
 * - Menampilkan data lengkap: pasien, antrian, dan klaster/layanan
 * - Urutkan dari yang terbaru ke terlama
 * - Status antrian ditampilkan dengan badge berwarna
 * 
 * AKSES:
 * - Hanya bisa diakses oleh admin (is_admin = 1)
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

$error = '';  // Variabel untuk menyimpan pesan error

// ============================================
// HANDLE PENCARIAN NAMA
// ============================================
$search_nama = isset($_GET['search']) && !empty(trim($_GET['search'])) ? trim($_GET['search']) : '';

// ============================================
// AMBIL DATA RIWAYAT ANTRIAN DENGAN PENCARIAN
// ============================================
// Query mengambil antrian dengan data pasien dan klaster lengkap
// INNER JOIN digunakan agar hanya menampilkan antrian yang sudah punya data pasien dan klaster
$antrian = [];
$where_clause = '';
$params = [];

try {
    // Build WHERE clause berdasarkan pencarian nama
    if (!empty($search_nama)) {
        // Pencarian berdasarkan nama pasien (case-insensitive, partial match)
        // Cari di nama pasien (online) atau nama_manual (manual)
        $where_clause = "WHERE (LOWER(COALESCE(p.nama, a.nama_manual, '')) LIKE LOWER(?))";
        $params[] = '%' . $search_nama . '%';
    } else {
        $where_clause = '';
    }
    
    $query = "
        SELECT 
            COALESCE(p.nik, a.nik_manual) as nik,                    -- NIK pasien (online) atau nik_manual (manual)
            COALESCE(p.nama, a.nama_manual) as nama,                 -- Nama pasien (online) atau nama_manual (manual)
            COALESCE(p.jenis_kelamin, a.jenis_kelamin_manual) as jenis_kelamin,  -- Jenis kelamin (online) atau manual
            COALESCE(p.umur, a.umur_manual) as umur,                 -- Umur (online) atau umur_manual (manual)
            a.status,                                                 -- Status antrian (Menunggu, Dipanggil, Selesai, Batal)
            a.tanggal_kunjungan,                                      -- Tanggal kunjungan
            k.nama_klaster,                                           -- Nama klaster/layanan
            a.sumber                                                  -- Sumber (Online/Manual)
        FROM antrian a
        LEFT JOIN pasien p ON a.pasien_id = p.id  -- LEFT JOIN agar data manual (pasien_id = NULL) tetap muncul
        INNER JOIN klaster k ON a.klaster_id = k.id  -- Gabung dengan tabel klaster
        " . $where_clause . "
        ORDER BY a.created_at DESC  -- Urutkan dari yang terbaru (terakhir mendaftar)
    ";
    
    $stmt = $koneksi->prepare($query);
    $stmt->execute($params);
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil data riwayat: " . $e->getMessage();
}

// ============================================
// FUNGSI HELPER: FORMAT TANGGAL
// ============================================
/**
 * Fungsi untuk memformat tanggal dari format database (Y-m-d) ke format Indonesia (d/m/Y)
 * 
 * @param string|null $tanggal - Tanggal dalam format Y-m-d atau NULL
 * @return string - Tanggal dalam format d/m/Y atau '-' jika kosong
 */
function formatTanggal($tanggal) {
    // Jika tanggal kosong atau NULL, return '-'
    if (empty($tanggal) || $tanggal == null) {
        return '-';
    }
    // Format tanggal dari Y-m-d ke d/m/Y (contoh: 2024-01-15 → 15/01/2024)
    return date('d/m/Y', strtotime($tanggal));
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Antrian | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
    <link href="../../assets/css/admin/riwayat_admin.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header_admin.php'; ?>
        
        <div class="dashboard-container">

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tabel Riwayat -->
            <div class="card">
                <h2 style="margin-top: 0;">Daftar Riwayat Antrian</h2>
                
                <!-- Search Section -->
                <form method="get" action="" style="margin-bottom: 20px;">
                    <div class="filter-form-inline">
                        <div class="filter-group-inline">
                            <label>Cari Nama Pasien</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_nama); ?>" 
                                   placeholder="Masukkan nama pasien..." 
                                   class="filter-search-input"
                                   autocomplete="off">
                        </div>
                        <!-- Tombol Cari -->
                        <div style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                            <button type="submit" class="btn-filter">Cari</button>
                        </div>
                    </div>
                </form>
                
                <?php if (empty($antrian)): ?>
                    <div class="empty-state">
                        <p>Tidak ada riwayat antrian.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Tanggal Kunjungan</th>
                                    <th>Nama Pasien</th>
                                    <th class="text-center">Umur</th>
                                    <th class="text-center">Jenis Kelamin</th>
                                    <th>NIK</th>
                                    <th>Layanan Yang Dituju</th>
                                    <th class="text-center">Sumber</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($antrian as $item): 
                                ?>
                                    <tr>
                                        <td data-label="No" class="text-center"><?php echo $no++; ?></td>
                                        <td data-label="Tanggal Kunjungan" class="text-center"><?php echo formatTanggal($item['tanggal_kunjungan']); ?></td>
                                        <td data-label="Nama Pasien"><strong><?php echo htmlspecialchars($item['nama']); ?></strong></td>
                                        <td data-label="Umur" class="text-center"><?php echo !empty($item['umur']) ? htmlspecialchars($item['umur']) . ' tahun' : '-'; ?></td>
                                        <td data-label="Jenis Kelamin" class="text-center">
                                            <?php 
                                            $jk = $item['jenis_kelamin'] ?? '';
                                            if ($jk == 'L' || strtoupper($jk) == 'LAKI-LAKI') {
                                                echo 'Laki-laki';
                                            } elseif ($jk == 'P' || strtoupper($jk) == 'PEREMPUAN') {
                                                echo 'Perempuan';
                                            } else {
                                                echo !empty($jk) ? htmlspecialchars($jk) : '-';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="NIK"><?php echo !empty($item['nik']) ? htmlspecialchars($item['nik']) : '-'; ?></td>
                                        <td data-label="Layanan Yang Dituju"><?php echo htmlspecialchars($item['nama_klaster']); ?></td>
                                        <td data-label="Sumber" class="text-center" style="vertical-align: middle;">
                                            <?php 
                                            // Ambil nilai sumber dari database
                                            $sumber = '';
                                            if (isset($item['sumber']) && $item['sumber'] !== null && $item['sumber'] !== '') {
                                                $sumber = trim($item['sumber']);
                                            } else {
                                                // Fallback: tentukan berdasarkan pasien_id
                                                // Jika pasien_id NULL, berarti Offline
                                                $sumber = 'Online'; // Default, karena query sudah menggunakan COALESCE
                                            }
                                            
                                            // Normalisasi nilai (case-insensitive)
                                            $sumber_lower = strtolower($sumber);
                                            
                                            // Tampilkan badge sesuai sumber
                                            if ($sumber_lower == 'offline' || $sumber_lower == 'manual') {
                                                echo '<span class="badge-sumber badge-offline" style="background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Offline</span>';
                                            } else {
                                                echo '<span class="badge-sumber badge-online" style="background-color: #cfe2ff; color: #084298; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Online</span>';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="Status" class="text-center">
                                            <?php 
                                            $status = $item['status'] ?? '';
                                            $statusLower = strtolower($status);
                                            $statusClass = 'status-badge ';
                                            
                                            if ($statusLower == 'menunggu') {
                                                $statusClass .= 'status-menunggu';
                                            } elseif ($statusLower == 'dipanggil' || $statusLower == 'sedang_dilayani') {
                                                $statusClass .= 'status-dipanggil';
                                            } elseif ($statusLower == 'selesai') {
                                                $statusClass .= 'status-selesai';
                                            } elseif ($statusLower == 'batal') {
                                                $statusClass .= 'status-batal';
                                            } else {
                                                $statusClass .= 'status-menunggu';
                                            }
                                            
                                            $statusText = ucfirst($status);
                                            if ($statusLower == 'sedang_dilayani') {
                                                $statusText = 'Dipanggil';
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 20px; color: #666; font-size: 14px;">
                        <p>
                            Total: <strong><?php echo count($antrian); ?></strong> antrian
                            <?php if (!empty($search_nama)): ?>
                                <span style="color: var(--primary);">| Pencarian: "<strong><?php echo htmlspecialchars($search_nama); ?></strong>"</span>
                            <?php else: ?>
                                <span style="color: var(--muted);">| Menampilkan semua riwayat</span>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
</body>
</html>

