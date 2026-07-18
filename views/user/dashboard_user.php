<?php
/**
 * FILE: views/user/dashboard_user.php
 * FUNGSI: Dashboard utama untuk pasien
 * 
 * FITUR:
 * - Menampilkan welcome message dengan nama pasien (bukan email)
 * - Menampilkan daftar antrian aktif (5 teratas)
 * - Quick links ke fitur utama (Daftar Antrian, Lihat Antrian, Riwayat, Profile)
 * 
 * AKSES:
 * - Hanya bisa diakses oleh user biasa (bukan admin)
 * - Harus sudah login
 * - Redirect ke admin jika user adalah admin
 */

session_start();  // Mulai session untuk cek status login
require_once '../../config/koneksi.php';  // Include koneksi database

// ============================================
// CEK AUTENTIKASI
// ============================================
// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// ============================================
// CEK ROLE (JIKA ADMIN, REDIRECT)
// ============================================
// Jika user adalah admin, redirect ke halaman admin
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: ../admin/dashboard_admin.php');
    exit;
}

$user_id = $_SESSION['user_id'];  // Ambil ID user dari session

// ============================================
// AMBIL NAMA PASIEN DARI DATABASE
// ============================================
// Ambil nama pasien untuk ditampilkan di welcome message
// Nama diambil dari tabel pasien, bukan dari session (karena session menyimpan email)
$nama_pasien = 'Pengguna';  // Default jika tidak ada data
try {
    $stmt = $koneksi->prepare("SELECT nama FROM pasien WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $pasien = $stmt->fetch(PDO::FETCH_ASSOC);
    // Jika ada data pasien dan nama tidak kosong, gunakan nama dari database
    if ($pasien && !empty($pasien['nama'])) {
        $nama_pasien = $pasien['nama'];
    }
} catch (PDOException $e) {
    // Jika error, tetap pakai default 'Pengguna'
}

// ============================================
// FUNGSI HELPER: GET STATUS BADGE
// ============================================
/**
 * Fungsi untuk membuat badge HTML berdasarkan status antrian
 * Badge memiliki warna berbeda untuk setiap status
 * 
 * @param string $status - Status antrian (Menunggu, Dipanggil, Selesai, Batal)
 * @return string - HTML badge dengan class sesuai status
 */
function getStatusBadge($status) {
    if ($status == 'Menunggu') {
        return '<span class="badge badge-pending">Menunggu</span>';
    } elseif ($status == 'Dipanggil') {
        return '<span class="badge badge-proses">Dipanggil</span>';
    } elseif ($status == 'Selesai') {
        return '<span class="badge badge-selesai">Selesai</span>';
    } elseif ($status == 'Batal') {
        return '<span class="badge badge-ditolak">Batal</span>';
    } else {
        return '<span class="badge">' . $status . '</span>';
    }
}

// ============================================
// AMBIL DATA ANTRIAN AKTIF USER
// ============================================
// Query untuk mengambil antrian aktif (hari ini dan mendatang)
// Hanya menampilkan 5 antrian teratas untuk preview
// Exclude antrian dengan status "Batal"
$antrian = [];
try {
    $stmt = $koneksi->prepare("
        SELECT 
            a.id_antrian as id,        -- ID antrian
            a.nomor_antrian,           -- Nomor antrian yang diberikan admin
            a.status,                  -- Status antrian
            a.tanggal_kunjungan,       -- Tanggal kunjungan
            k.nama_klaster             -- Nama klaster/layanan
        FROM antrian a
        INNER JOIN pasien p ON a.pasien_id = p.id  -- Gabung dengan tabel pasien
        INNER JOIN klaster k ON a.klaster_id = k.id  -- Gabung dengan tabel klaster
        WHERE p.user_id = ?                        -- Filter berdasarkan user ID
        AND a.tanggal_kunjungan >= CURDATE()       -- Hanya tanggal hari ini atau mendatang
        AND a.status != 'Batal'                     -- Exclude yang dibatalkan
        ORDER BY a.tanggal_kunjungan ASC, a.nomor_antrian ASC  -- Urutkan berdasarkan tanggal dan nomor
        LIMIT 5  -- Hanya ambil 5 teratas untuk preview
    ");
    $stmt->execute([$user_id]);
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Error handling - jika error, array $antrian tetap kosong
}

// ============================================
// AJAX ENDPOINT - Return JSON jika parameter ajax=1
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $antrian,
        'count' => count($antrian)
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/dashboard_user.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="dashboard-container">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>Selamat Datang, <?php echo htmlspecialchars($nama_pasien); ?>!</h2>
                    <p>Informasi dan layanan kunjungan Anda</p>
                </div>
            </div>

            <!-- Antrian Saya (Ringkas) -->
            <div class="section-title">
                <h3>Antrian Saya</h3>
            </div>
            <div class="card">
                <div class="card-header">
                    <h2>Hari Ini & Mendatang</h2>
                </div>

                <?php if (empty($antrian)): ?>
                    <div class="empty-state">
                        <p>Belum ada antrian. <a href="daftar_antrian.php">Daftar antrian pertama Anda</a></p>
                    </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Klaster</th>
                                <th>Nomor Antrian</th>
                                    <th>Tanggal Kunjungan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="antrian-table-body">
                            <?php 
                            $no = 1;
                                foreach ($antrian as $item): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($item['nama_klaster']); ?></strong></td>
                                    <td>
                                            <?php if (!empty($item['nomor_antrian'])): ?>
                                                <span class="number-badge-small"><?php echo htmlspecialchars($item['nomor_antrian']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #6b7280; font-style: italic;">Belum diberikan</span>
                                            <?php endif; ?>
                                    </td>
                                        <td><?php echo date('d/m/Y', strtotime($item['tanggal_kunjungan'])); ?></td>
                                    <td><?php echo getStatusBadge($item['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 15px; text-align: center; border-top: 1px solid #e5e9f2;">
                    <a href="daftar_user.php" class="btn btn-secondary">Lihat Semua Antrian</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
    <script src="../../assets/js/auto_refresh.js"></script>
</body>
</html>
