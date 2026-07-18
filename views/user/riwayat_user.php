<?php
/**
 * FILE: views/user/riwayat_user.php
 * FUNGSI: Halaman riwayat kunjungan pasien - menampilkan semua riwayat kunjungan
 * 
 * FITUR:
 * - Menampilkan semua riwayat kunjungan pasien (dari masa lalu hingga sekarang)
 * - Menampilkan detail lengkap setiap kunjungan (tanggal, klaster, status, dll)
 * - Modal detail untuk melihat informasi lengkap setiap antrian
 * - Badge status dengan warna berbeda untuk setiap status
 * - Urutkan berdasarkan tanggal kunjungan (terbaru di atas)
 * 
 * AKSES:
 * - User harus sudah punya data pasien (redirect ke pendaftaran_awal jika belum)
 * - Harus sudah login
 * - Bukan admin (redirect ke admin jika admin)
 * 
 * CATATAN:
 * - Menampilkan semua riwayat, tidak hanya yang aktif
 * - Termasuk antrian yang sudah selesai, batal, atau sudah lewat
 */
session_start();
require_once '../../config/koneksi.php';

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

$user_id = $_SESSION['user_id'];

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
// AMBIL DATA RIWAYAT KUNJUNGAN USER
// ============================================
// Query untuk mengambil semua riwayat kunjungan user
// Urutkan berdasarkan tanggal kunjungan (terbaru di atas)
$riwayat = [];
$error_message = '';

try {
    $stmt = $koneksi->prepare("
        SELECT 
            a.id_antrian as id,
            a.keluhan,
            a.nomor_antrian,
            a.status,
            a.tanggal_kunjungan,
            a.created_at as waktu_daftar,
            k.nama_klaster,
            p.nama,
            p.nik,
            COALESCE(p.umur, a.umur_manual) as umur,
            COALESCE(p.jenis_kelamin, a.jenis_kelamin_manual) as jenis_kelamin
        FROM antrian a
        INNER JOIN pasien p ON a.pasien_id = p.id
        INNER JOIN klaster k ON a.klaster_id = k.id
        WHERE p.user_id = ?
        ORDER BY a.tanggal_kunjungan DESC, a.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Kunjungan | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/riwayat_user.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="dashboard-container">
           

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2>Riwayat Kunjungan Saya</h2>
                </div>

                <?php if (empty($riwayat)): ?>
                    <div class="empty-state">
                        <p>Belum ada riwayat kunjungan. <a href="daftar_antrian.php">Daftar antrian pertama Anda</a></p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Klaster</th>
                                    <th class="text-center">Nomor Antrian</th>
                                    <th>Keluhan</th>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Status</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($riwayat as $item): 
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_klaster']); ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($item['nomor_antrian'])): ?>
                                                <span class="number-badge-small"><?php echo htmlspecialchars($item['nomor_antrian']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #6b7280; font-style: italic;">Belum diberikan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['keluhan'] ?: '-'); ?></td>
                                        <td class="text-center"><?php echo date('d/m/Y', strtotime($item['tanggal_kunjungan'])); ?></td>
                                        <td class="text-center"><?php echo getStatusBadge($item['status']); ?></td>
                                        <td>
                                            <button class="btn-detail" onclick="showDetail(<?php echo htmlspecialchars(json_encode($item)); ?>)">Detail</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Detail Kunjungan</h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <script src="../../assets/js/modal.js"></script>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
