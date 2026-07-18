<?php
/**
 * FILE: views/user/daftar_antrian.php
 * FUNGSI: Form untuk user mendaftar antrian baru
 * 
 * FITUR:
 * - Form pilih klaster/layanan yang dituju
 * - Pilih tanggal kunjungan (hanya hari yang tersedia)
 * - Pilih waktu kunjungan (08:00-15:00 WIB, sesuai jam operasional)
 * - Input keluhan pasien
 * - Validasi jam operasional dan tanggal yang valid
 * - Auto-disable tanggal yang sudah lewat
 * 
 * AKSES:
 * - User harus sudah punya data pasien (redirect ke pendaftaran_awal jika belum)
 * - Harus sudah login
 * - Bukan admin
 * 
 * CATATAN:
 * - Jam operasional pendaftaran: 08:00-15:00 WIB
 * - Hari Sabtu dan Minggu: jam pelayanan tutup (tidak bisa daftar)
 */

session_start();  // Mulai session untuk cek status login
require_once '../../config/koneksi.php';  // Include koneksi database (timezone sudah di-set di koneksi.php)

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
$error = '';   // Variabel untuk menyimpan pesan error
$success = ''; // Variabel untuk menyimpan pesan success

// ============================================
// CEK APAKAH USER SUDAH PUNYA DATA PASIEN
// ============================================
// User harus sudah isi data pasien dulu sebelum bisa daftar antrian
try {
    $stmt = $koneksi->prepare("SELECT * FROM pasien WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $pasien_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pasien_data) {
        // Belum ada data pasien, redirect ke pendaftaran awal
        header('Location: pendaftaran_awal.php');
        exit;
    }
} catch (PDOException $e) {
    // Jika error, log error dan redirect ke pendaftaran awal
    error_log("Error checking pasien data: " . $e->getMessage());
    header('Location: pendaftaran_awal.php');
    exit;
}

// Simpan data pasien untuk keperluan form
$user_data = $pasien_data;

// ============================================
// AMBIL DAFTAR KLASTER/LAYANAN YANG TERSEDIA
// ============================================
// Klaster adalah layanan yang tersedia (contoh: Poli Umum, Poli Gigi, dll)
$klaster_list = [];
try {
    $stmt = $koneksi->prepare("SELECT * FROM klaster ORDER BY nama_klaster ASC");
    $stmt->execute();
    $klaster_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Jika tidak ada data klaster, tampilkan pesan error
    // User tidak bisa daftar antrian jika tidak ada klaster
    if (empty($klaster_list)) {
        $error = "Data klaster belum tersedia. Silakan hubungi administrator untuk mengisi data klaster.";
    }
} catch (PDOException $e) {
    $error = "Error mengambil data klaster: " . $e->getMessage();
}

// ============================================
// FUNGSI HELPER: GET AVAILABLE DATES
// ============================================
/**
 * Fungsi untuk menentukan tanggal yang tersedia untuk pendaftaran
 * 
 * LOGIKA:
 * - Jika belum lewat jam 15:00 hari ini, hari ini bisa dipilih
 * - Besok dan hari-hari berikutnya bisa dipilih (kecuali Sabtu dan Minggu)
 * - Maksimal 2 tanggal yang ditampilkan
 * - Hari Sabtu dan Minggu: jam pelayanan tutup (tidak bisa daftar)
 * 
 * @return array - Array tanggal yang tersedia (format: Y-m-d)
 */
function getAvailableDates() {
    $currentHour = (int)date('H');
    $currentMinute = (int)date('i');
    $currentDayOfWeek = (int)date('N'); // 1=Monday, 5=Friday, 6=Saturday, 7=Sunday
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $dates = [];
    
    // Jam tutup pendaftaran pasien (sesuai jadwal baru):
    // Pendaftaran Pasien: 08.00 - 15.00 WIB (diasumsikan sama untuk hari kerja yang buka)
    $closingHour = 15;
    $closingMinute = 0; // Tutup tepat jam 15:00
    
    // Cek hari ini
    $todayDayOfWeek = (int)date('N', strtotime($today));
    if ($todayDayOfWeek != 6 && $todayDayOfWeek != 7) { // Bukan Sabtu dan Minggu
        // Jika belum lewat jam tutup hari ini (sebelum 15:00), tambahkan hari ini
        // Kondisi: jam < 15:00 (tidak termasuk jam 15:00)
        // Jika sudah jam 15:00 atau lebih (misal 15:00, 15:01, 16:00, 19:13, dll), tidak tambahkan hari ini
        if ($currentHour < $closingHour) {
            $dates[] = $today;
        }
        // Jika sudah >= 15:00, tidak tambahkan hari ini
    }
    
    // Cek besok dan hari-hari berikutnya (skip Sabtu dan Minggu karena jam pelayanan tutup)
    $nextDate = $tomorrow;
    $maxDays = 7; // Maksimal cek 7 hari ke depan
    $daysChecked = 0;
    
    while (count($dates) < 2 && $daysChecked < $maxDays) {
        $nextDayOfWeek = (int)date('N', strtotime($nextDate));
        
        // Jika bukan Sabtu (6) dan bukan Minggu (7), tambahkan ke daftar
        if ($nextDayOfWeek != 6 && $nextDayOfWeek != 7) {
            $dates[] = $nextDate;
        }
        
        // Lanjut ke hari berikutnya
        $nextDate = date('Y-m-d', strtotime($nextDate . ' +1 day'));
        $daysChecked++;
    }
    
    return $dates;
}

// Fungsi untuk mendapatkan jam operasional berdasarkan hari (jam pendaftaran kunjungan)
function getOperatingHours($date) {
    $dayOfWeek = date('N', strtotime($date)); // 1=Monday, 7=Sunday
    
    // Jadwal baru (diasumsikan untuk pendaftaran pasien):
    // Senin-Kamis (1-4): 08:00-15:00
    // Jum'at (5): 08:00-15:00
    // Sabtu (6): Jam pelayanan tutup
    // Minggu (7): Jam pelayanan tutup
    
    if ($dayOfWeek == 5) { // Jum'at
        return [
            'start' => '08:00',
            'end' => '15:00',
            'available' => true
        ];
    } elseif ($dayOfWeek == 6) { // Sabtu - Jam pelayanan tutup
        return [
            'start' => '08:00',
            'end' => '12:00',
            'available' => false
        ];
    } elseif ($dayOfWeek >= 1 && $dayOfWeek <= 4) { // Senin-Kamis
        return [
            'start' => '08:00',
            'end' => '15:00',
            'available' => true
        ];
    } else { // Minggu - Jam pelayanan tutup
        return [
            'start' => '08:00',
            'end' => '12:00',
            'available' => false
        ];
    }
}

// Ambil tanggal yang tersedia
$available_dates = getAvailableDates();

// Proses pendaftaran antrian baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Data Antrian (data pasien sudah ada di tabel pasien)
    $klaster_id = $_POST['klaster_id'] ?? '';
    $keluhan = trim($_POST['keluhan'] ?? '');
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? '';
    
    // Validasi
    if (empty($klaster_id)) {
        $error = 'Pilih klaster terlebih dahulu!';
    } elseif (empty($tanggal_kunjungan)) {
        $error = 'Pilih tanggal kunjungan!';
    } elseif (strtotime($tanggal_kunjungan) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal kunjungan tidak boleh di masa lalu!';
    } else {
        // Validasi hari (Sabtu dan Minggu tutup)
        $dayOfWeek = date('N', strtotime($tanggal_kunjungan)); // 1=Monday, 7=Sunday
        
        if ($dayOfWeek == 6) { // Sabtu: Jam pelayanan tutup
            $error = 'Jam pelayanan puskesmas tutup pada hari Sabtu!';
        } elseif ($dayOfWeek == 7) { // Minggu: Jam pelayanan tutup
            $error = 'Jam pelayanan puskesmas tutup pada hari Minggu!';
        }
    }
    
    // Jika tidak ada error, lanjutkan proses
    if (empty($error)) {
        try {
            // Ambil data pasien untuk mendapatkan pasien_id (menggunakan kolom 'id')
            $stmt_pasien = $koneksi->prepare("SELECT id FROM pasien WHERE user_id = ?");
            $stmt_pasien->execute([$user_id]);
            $pasien_data = $stmt_pasien->fetch(PDO::FETCH_ASSOC);
            
            if (!$pasien_data) {
                $error = 'Data pasien tidak ditemukan. Silakan lengkapi data pasien terlebih dahulu.';
            } else {
                $pasien_id = $pasien_data['id'];
                
            // Cek apakah user sudah punya antrian di tanggal yang sama
            $stmt = $koneksi->prepare("
                SELECT COUNT(*) as total 
                    FROM antrian a
                    INNER JOIN pasien p ON a.pasien_id = p.id
                    WHERE p.user_id = ? AND a.tanggal_kunjungan = ? AND a.status != 'Batal'
            ");
            $stmt->execute([$user_id, $tanggal_kunjungan]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($existing > 0) {
                $error = 'Anda sudah memiliki antrian pada tanggal tersebut!';
            } else {
                // Generate nomor antrian otomatis berdasarkan tanggal saja (gabungan semua klaster per hari)
                $stmt_max = $koneksi->prepare("
                    SELECT MAX(CAST(nomor_antrian AS UNSIGNED)) AS max_no
                    FROM antrian
                    WHERE tanggal_kunjungan = ?
                ");
                $stmt_max->execute([$tanggal_kunjungan]);
                $row_max = $stmt_max->fetch(PDO::FETCH_ASSOC);
                $next_nomor = ($row_max && $row_max['max_no'] !== null) ? ((int)$row_max['max_no'] + 1) : 1;

                // Insert antrian baru (hanya data antrian, data pasien sudah ada di tabel pasien)
                // Sumber otomatis "Online" karena pendaftaran melalui sistem
                $stmt = $koneksi->prepare("
                    INSERT INTO antrian (
                        pasien_id, klaster_id, keluhan, tanggal_kunjungan, 
                        nomor_antrian, status, sumber
                    ) VALUES (
                        ?, ?, ?, ?, ?, 'Menunggu', 'Online'
                    )
                ");
                $stmt->execute([
                    $pasien_id,
                    $klaster_id,
                    !empty($keluhan) ? $keluhan : null,
                    $tanggal_kunjungan,
                    $next_nomor
                ]);
                
                $success = 'Antrian berhasil didaftarkan! Nomor antrian telah diberikan secara otomatis. Silakan cek status antrian Anda di halaman "Daftar Antrian".';
                // Redirect setelah 2 detik
                header("refresh:2;url=daftar_user.php");
                }
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Antrian Baru | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/daftar_antrian.css" rel="stylesheet">
</head>
<body>
        <div class="dashboard-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="dashboard-container">
            <!-- Page header dihapus: judul utama cukup diwakili oleh judul card di bawah -->

            <!-- Tampilkan pesan error/success -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Form Pendaftaran Antrian -->
            <div class="card">
                <div class="card-header">
                    <h2>Form Pendaftaran Antrian</h2>
                    <p style="margin: 10px 0 0 0; color: var(--muted); font-size: 14px;">
                        Data pasien : <strong><?php echo htmlspecialchars($pasien_data['nama'] ?? '-'); ?></strong>
                    </p>
                </div>
                <form method="post" action="" class="data-pasien-form" id="formAntrian">
                    <div class="form-row">
                        <!-- Kolom Kiri -->
                        <div class="form-col">
                            <div class="form-group">
                                <label>KELUHAN</label>
                                <textarea name="keluhan" class="form-input" rows="5" 
                                          placeholder="Jelaskan keluhan atau gejala yang Anda alami"></textarea>
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div class="form-col">
                            <div class="form-group">
                                <label>LAYANAN YANG DITUJU *</label>
                                <select name="klaster_id" id="klaster_select" class="form-input" required>
                                    <option value="">-- Pilih Klaster --</option>
                                    <?php if (empty($klaster_list)): ?>
                                        <option value="" disabled>Data klaster belum tersedia</option>
                                    <?php else: ?>
                                        <?php foreach ($klaster_list as $klaster): ?>
                                            <option value="<?php echo $klaster['id']; ?>">
                                                <?php echo htmlspecialchars($klaster['nama_klaster']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (empty($klaster_list)): ?>
                                    <small class="text-muted" style="color: #ef4444; display: block; margin-top: 5px;">
                                        Data klaster belum tersedia. Silakan hubungi administrator.
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>TANGGAL KUNJUNGAN *</label>
                                <select name="tanggal_kunjungan" id="tanggal_kunjungan" class="form-input" required>
                                    <option value="">-- Pilih Tanggal --</option>
                                    <?php 
                                    foreach ($available_dates as $date): 
                                        $dayOfWeek = (int)date('N', strtotime($date)); // 1=Monday, 7=Sunday
                                        // Skip hari Sabtu (6) dan Minggu (7) karena jam pelayanan tutup
                                        if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                                            continue;
                                        }
                                        $dateFormatted = date('d/m/Y', strtotime($date));
                                        $dayName = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'][date('w', strtotime($date))];
                                    ?>
                                        <option value="<?php echo $date; ?>" data-date="<?php echo $date; ?>">
                                            <?php echo $dayName . ', ' . $dateFormatted; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Daftar Antrian</button>
                        <a href="daftar_user.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/common.js"></script>
    <script src="../../assets/js/daftar_antrian.js"></script>
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
