<?php
/**
 * FILE: views/user/daftar_user.php
 * FUNGSI: Halaman daftar antrian pasien - menampilkan antrian aktif (hari ini & mendatang)
 * 
 * FITUR:
 * - Menampilkan semua antrian aktif user (hari ini dan mendatang)
 * - Menampilkan detail setiap antrian (tanggal, klaster, nomor antrian, status)
 * - Tombol untuk membatalkan antrian (jika status masih Menunggu atau Dipanggil)
 * - Modal detail untuk melihat informasi lengkap setiap antrian
 * - Badge status dengan warna berbeda untuk setiap status
 * - Auto refresh setiap 3 detik untuk update status terbaru
 * - AJAX endpoint untuk auto refresh tanpa reload halaman
 * 
 * AKSES:
 * - User harus sudah punya data pasien (redirect ke pendaftaran_awal jika belum)
 * - Harus sudah login
 * - Bukan admin (redirect ke admin jika admin)
 * 
 * CATATAN:
 * - Hanya menampilkan antrian dengan tanggal >= hari ini
 * - Exclude antrian dengan status "Batal"
 * - Urutkan berdasarkan tanggal kunjungan dan nomor antrian
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
// PESAN HASIL PEMBATALAN ANTRIAN
// ============================================
// Pesan sukses atau error dari proses pembatalan antrian
$cancel_success = isset($_GET['success_cancel']) && $_GET['success_cancel'] == '1';
$cancel_error = trim($_GET['error_cancel'] ?? '');

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
// Exclude antrian dengan status "Batal"
$antrian = [];
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
        WHERE p.user_id = ? AND a.tanggal_kunjungan >= CURDATE() AND a.status != 'Batal'
        ORDER BY a.tanggal_kunjungan DESC, a.nomor_antrian ASC
    ");
    $stmt->execute([$user_id]);
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
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
    <title>Daftar Antrian | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/daftar_user.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="dashboard-container">
            

            <?php if ($cancel_success): ?>
                <div class="alert alert-success">Antrian berhasil dibatalkan.</div>
            <?php endif; ?>

            <?php if (!empty($cancel_error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($cancel_error); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <!-- Tabel Daftar Antrian -->
            <div class="card">
                <div class="card-header">
                    <h2>Antrian Saya (Hari Ini & Mendatang)</h2>
                    <a href="daftar_antrian.php" class="btn btn-primary">+ Daftar Antrian Baru</a>
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
                                    <th>Keluhan</th>
                                    <th>Tanggal Kunjungan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody id="daftar-antrian-table-body">
                                <?php 
                                $no = 1;
                                foreach ($antrian as $item): 
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['nama_klaster']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['nomor_antrian'])): ?>
                                                <span class="number-badge-small"><?php echo htmlspecialchars($item['nomor_antrian']); ?></span>
                                            <?php else: ?>
                                                <span style="color: #6b7280; font-style: italic;">Belum diberikan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['keluhan'] ?: '-'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($item['tanggal_kunjungan'])); ?></td>
                                        <td><?php echo getStatusBadge($item['status']); ?></td>
                                        <td>
                                            <?php 
                                            $status = $item['status'] ?? '';
                                            if (in_array($status, ['Menunggu', 'Dipanggil'], true)): ?>
                                                <form method="post" action="batalkan_antrian.php" onsubmit="return confirm('Batalkan antrian ini?');" style="display:inline;">
                                                    <input type="hidden" name="id_antrian" value="<?php echo (int)$item['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Batalkan</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#6b7280; font-style: italic;">-</span>
                                            <?php endif; ?>
                                        </td>
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
            <h2>Detail Antrian</h2>
            <div id="modalBody"></div>
        </div>
    </div>

    <script src="../../assets/js/modal.js"></script>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
    <script src="../../assets/js/auto_refresh.js"></script>
</body>
</html>
