<?php
/**
 * FILE: views/admin/kelola_antrian.php
 * FUNGSI: Halaman untuk admin mengelola antrian pasien
 * 
 * FITUR UTAMA:
 * 1. Filter antrian berdasarkan tanggal kunjungan
 * 2. Berikan nomor antrian secara manual kepada pasien
 * 3. Update status antrian (Menunggu, Dipanggil, Selesai, Batal)
 * 4. Proteksi: Antrian dengan status "Selesai" tidak bisa diubah lagi
 * 
 * AKSES:
 * - Hanya bisa diakses oleh admin (is_admin = 1)
 * - Redirect ke login jika belum login atau bukan admin
 * 
 * CATATAN PENTING:
 * - Menggunakan array key dengan prefix "antrian_" untuk memastikan mapping yang benar
 * - Menggunakan database transaction untuk memastikan konsistensi data
 * - Validasi nomor antrian unik per klaster dan tanggal
 * - Form tambah antrian offline: layout grid (4 kolom, 3 kolom, keluhan, tombol)
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

// Variabel untuk menyimpan error, success message, dan filter tanggal
$error = '';
$success = '';

// Ambil tanggal hari ini dari database menggunakan CURDATE() (lebih akurat)
$tanggal_hari_ini_db = date('Y-m-d'); // Default
try {
    $stmt_date = $koneksi->query("SELECT CURDATE() as tanggal_hari_ini");
    $result_date = $stmt_date->fetch(PDO::FETCH_ASSOC);
    $tanggal_hari_ini_db = $result_date['tanggal_hari_ini'] ?? date('Y-m-d');
} catch (PDOException $e) {
    // Fallback ke PHP date jika query gagal
    $tanggal_hari_ini_db = date('Y-m-d');
}

// Ambil tanggal filter dari GET parameter
// Jika tidak ada parameter tanggal di URL, atau parameter kosong, gunakan tanggal hari ini dari database
// Format: Y-m-d (contoh: 2024-01-15)
if (isset($_GET['tanggal']) && !empty(trim($_GET['tanggal']))) {
    // Jika ada parameter tanggal di URL
    $tanggal_from_url = trim($_GET['tanggal']);
    
    // Jika parameter 'filter_submitted' ada, berarti user memang sengaja filter tanggal tertentu
    // Jika tidak ada 'filter_submitted', dan tanggal berbeda dari hari ini, berarti user akses via menu navigasi
    // Dalam hal ini, redirect ke tanggal hari ini agar selalu menampilkan data hari ini saat kembali ke halaman
    // Exception: Jika ada parameter 'success' (dari POST redirect), biarkan untuk menampilkan pesan sukses
    if (!isset($_GET['filter_submitted']) && !isset($_GET['success']) && $tanggal_from_url !== $tanggal_hari_ini_db) {
        // Redirect ke tanggal hari ini (akses via menu navigasi, bukan form filter)
        header('Location: kelola_antrian.php?tanggal=' . urlencode($tanggal_hari_ini_db));
        exit;
    }
    
    $tanggal_filter = $tanggal_from_url;
} else {
    // Jika tidak ada parameter tanggal atau parameter kosong, gunakan tanggal hari ini dari database
    $tanggal_filter = $tanggal_hari_ini_db;
}

// ============================================
// AMBIL DAFTAR KLASTER/LAYANAN (untuk form tambah antrian offline)
// ============================================
$klaster_list = [];
try {
    $stmt = $koneksi->prepare("SELECT * FROM klaster ORDER BY nama_klaster ASC");
    $stmt->execute();
    $klaster_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Error akan ditangani di form
}

// ============================================
// PROSES TAMBAH ANTRIAN OFFLINE (POST REQUEST)
// ============================================
// Handler untuk form tambah antrian offline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_offline'])) {
    // Ambil data dari form tambah antrian offline
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? '';
    $nama = trim($_POST['nama'] ?? '');
    $umur = !empty($_POST['umur']) ? intval($_POST['umur']) : null;
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? null;
    $nik = trim($_POST['nik'] ?? '');
    $klaster_id = $_POST['klaster_id'] ?? '';
    $nomor_antrian = !empty($_POST['nomor_antrian']) ? trim($_POST['nomor_antrian']) : null;
    $keluhan = trim($_POST['keluhan'] ?? '');
    $status = 'Selesai'; // Status otomatis "Selesai" untuk antrian offline
    
    // Validasi
    if (empty($tanggal_kunjungan)) {
        $error = 'Tanggal kunjungan harus diisi!';
    } else {
        $ts = strtotime($tanggal_kunjungan);
        $hari = $ts ? (int)date('N', $ts) : 0; // 1=Senin .. 7=Minggu
        if ($hari < 1 || $hari > 5) {
            $error = 'Tanggal kunjungan hanya boleh hari kerja (Senin–Jumat).';
        }
    }
    if (empty($error) && !empty($tanggal_kunjungan)) {
        if (empty($nama)) {
            $error = 'Nama pasien harus diisi!';
        } elseif (empty($umur) || $umur <= 0) {
        $error = 'Umur harus diisi dan valid!';
    } elseif (empty($jenis_kelamin)) {
        $error = 'Jenis kelamin harus dipilih!';
    } elseif (empty($klaster_id)) {
        $error = 'Layanan yang dituju harus dipilih!';
    } else {
        try {
            // Generate nomor antrian otomatis berdasarkan tanggal saja (gabungan semua klaster per hari)
            $stmt_max = $koneksi->prepare("
                SELECT MAX(CAST(nomor_antrian AS UNSIGNED)) AS max_no
                FROM antrian
                WHERE tanggal_kunjungan = ?
            ");
            $stmt_max->execute([$tanggal_kunjungan]);
            $row_max = $stmt_max->fetch(PDO::FETCH_ASSOC);
            $next_nomor = ($row_max && $row_max['max_no'] !== null) ? ((int)$row_max['max_no'] + 1) : 1;

            // Insert data antrian offline dengan nomor antrian otomatis
            $stmt = $koneksi->prepare("
                INSERT INTO antrian (
                    pasien_id, 
                    klaster_id, 
                    tanggal_kunjungan, 
                    nomor_antrian,
                    status,
                    sumber,
                    nama_manual,
                    umur_manual,
                    jenis_kelamin_manual,
                    nik_manual,
                    keluhan
                ) VALUES (
                    NULL, 
                    ?, 
                    ?, 
                    ?,
                    ?,
                    'Offline',
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");
            $stmt->execute([
                $klaster_id,
                $tanggal_kunjungan,
                $next_nomor,
                $status,
                $nama,
                $umur,
                $jenis_kelamin,
                !empty($nik) ? $nik : null,
                !empty($keluhan) ? $keluhan : null
            ]);
            
            if (empty($error)) {
                $success = 'Antrian offline berhasil ditambahkan!';
                
                // Redirect untuk mempertahankan tanggal filter
                header('Location: kelola_antrian.php?tanggal=' . urlencode($tanggal_kunjungan) . '&success=1');
                exit;
            }
            
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    }
}

// ============================================
// TAMPILKAN PESAN SUCCESS DARI GET PARAMETER
// ============================================
// Setelah redirect dari POST, tampilkan pesan success
// Ini untuk memberikan feedback bahwa update berhasil
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'Data antrian berhasil diperbarui!';
}

// ============================================
// PROSES UPDATE NOMOR ANTRIAN DAN STATUS (POST REQUEST)
// ============================================
// Handler untuk form update nomor antrian dan status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['tambah_offline'])) {
    // Ambil data dari form POST
    // Data dikirim dalam bentuk array dengan key yang sama untuk memastikan mapping yang benar
    // Format: name="antrian_id[antrian_123]", name="nomor_antrian[antrian_123]", dll
    $antrian_ids = $_POST['antrian_id'] ?? [];
    $nomor_antrians = $_POST['nomor_antrian'] ?? [];
    $statuses = $_POST['status'] ?? [];
    
    // Validasi: cek apakah ada data antrian yang dikirim
    if (empty($antrian_ids)) {
        $error = 'Tidak ada data antrian yang dikirim!';
    } else {
        try {
            // Mulai database transaction untuk memastikan konsistensi data
            // Jika ada error, semua perubahan akan di-rollback
            $koneksi->beginTransaction();
            
            // ============================================
            // LOOP SETIAP ANTRIAN YANG AKAN DIUPDATE
            // ============================================
            // Loop berdasarkan key dari form (bukan berdasarkan ID)
            // Key format: "antrian_123" untuk memastikan mapping yang benar
            foreach ($antrian_ids as $antrian_id_key => $antrian_id_value) {
                // Convert ID antrian ke integer
                // $antrian_id_value adalah ID antrian yang sebenarnya dari hidden input
                $antrian_id = intval($antrian_id_value);
                
                // Skip jika ID tidak valid
                if (empty($antrian_id) || $antrian_id <= 0) {
                    continue;
                }
                
                // Ambil data nomor antrian dan status berdasarkan key yang sama
                // Menggunakan key ($antrian_id_key) untuk memastikan data sesuai dengan antrian yang benar
                // Trim untuk menghapus spasi di awal/akhir
                $nomor_antrian = trim($nomor_antrians[$antrian_id_key] ?? '');
                $status = trim($statuses[$antrian_id_key] ?? '');
                
                // ============================================
                // CEK STATUS SEBELUMNYA DARI DATABASE
                // ============================================
                // Ambil status saat ini dari database untuk validasi
                $stmt = $koneksi->prepare("SELECT status FROM antrian WHERE id_antrian = ?");
                $stmt->execute([$antrian_id]);
                $current_status = $stmt->fetchColumn();
                
                // ============================================
                // PROTEKSI: JIKA STATUS SUDAH "SELESAI", SKIP UPDATE
                // ============================================
                // Antrian yang sudah "Selesai" tidak boleh diubah lagi
                // Ini mencegah perubahan data setelah pasien sudah selesai dilayani
                if ($current_status == 'Selesai' || $current_status == 'selesai') {
                    continue;  // Skip update untuk antrian ini
                }
                
                // ============================================
                // UPDATE NOMOR ANTRIAN
                // ============================================
                // Update nomor antrian jika diisi (tidak kosong)
                if (!empty($nomor_antrian)) {
                    // Validasi: nomor antrian harus berupa angka
                    if (!is_numeric($nomor_antrian)) {
                        throw new Exception("Nomor antrian harus berupa angka!");
                    }
                    
                    // Ambil data antrian untuk validasi unik nomor antrian
                    // Perlu klaster_id dan tanggal_kunjungan untuk cek duplikasi
                    $stmt = $koneksi->prepare("SELECT klaster_id, tanggal_kunjungan FROM antrian WHERE id_antrian = ?");
                    $stmt->execute([$antrian_id]);
                    $antrian_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($antrian_data) {
                        // ============================================
                        // VALIDASI NOMOR ANTRIAN UNIK
                        // ============================================
                        // Cek apakah nomor antrian sudah digunakan untuk klaster yang sama
                        // pada tanggal yang sama (tidak boleh duplikat)
                        // Status 'Batal' tidak dihitung (bisa pakai nomor yang sama)
                        $stmt = $koneksi->prepare("
                            SELECT id_antrian FROM antrian 
                            WHERE klaster_id = ? 
                            AND tanggal_kunjungan = ? 
                            AND nomor_antrian = ? 
                            AND id_antrian != ? 
                            AND status != 'Batal'
                        ");
                        $stmt->execute([
                            $antrian_data['klaster_id'], 
                            $antrian_data['tanggal_kunjungan'], 
                            $nomor_antrian, 
                            $antrian_id
                        ]);
                        $existing = $stmt->fetch();
                        
                        // Jika nomor antrian sudah digunakan, throw error
                        if ($existing) {
                            throw new Exception("Nomor antrian {$nomor_antrian} sudah digunakan untuk klaster ini pada tanggal yang sama!");
                        }
                        
                        // Update nomor antrian ke database
                        $stmt = $koneksi->prepare("UPDATE antrian SET nomor_antrian = ? WHERE id_antrian = ?");
                        $stmt->execute([$nomor_antrian, $antrian_id]);
                    }
                } else {
                    // ============================================
                    // HAPUS NOMOR ANTRIAN (JIKA DIKOSONGKAN)
                    // ============================================
                    // Jika nomor antrian dikosongkan oleh admin, set ke NULL
                    $stmt = $koneksi->prepare("UPDATE antrian SET nomor_antrian = NULL WHERE id_antrian = ?");
                    $stmt->execute([$antrian_id]);
                }
                
                // ============================================
                // UPDATE STATUS ANTRIAN ATAU HAPUS JIKA BATAL
                // ============================================
                if (!empty($status)) {
                    $valid_statuses = ['Menunggu', 'Dipanggil', 'Selesai', 'Batal'];
                    if (in_array($status, $valid_statuses)) {
                        if ($status === 'Batal') {
                            // Hapus dari database agar hilang dari daftar antrian
                            $stmt = $koneksi->prepare("DELETE FROM antrian WHERE id_antrian = ?");
                            $stmt->execute([$antrian_id]);
                        } else {
                            $stmt = $koneksi->prepare("UPDATE antrian SET status = ? WHERE id_antrian = ?");
                            $stmt->execute([$status, $antrian_id]);
                        }
                    }
                }
            }
            
            // ============================================
            // COMMIT TRANSACTION
            // ============================================
            // Jika semua update berhasil, commit perubahan ke database
            $koneksi->commit();
            $success = 'Data antrian berhasil diperbarui!';
            
            // Redirect untuk mempertahankan tanggal filter setelah POST
            // Juga menambahkan parameter success=1 untuk menampilkan pesan sukses
            $tanggal_param = !empty($_POST['tanggal_filter']) ? $_POST['tanggal_filter'] : $tanggal_filter;
            header('Location: kelola_antrian.php?tanggal=' . urlencode($tanggal_param) . '&success=1');
            exit;
            
        } catch (Exception $e) {
            // ============================================
            // ROLLBACK TRANSACTION JIKA ERROR
            // ============================================
            // Jika terjadi error, rollback semua perubahan
            // Ini memastikan konsistensi data (semua berhasil atau semua gagal)
            $koneksi->rollBack();
            $error = $e->getMessage();
        }
    }
}

// ============================================
// AMBIL DATA ANTRIAN BERDASARKAN FILTER TANGGAL
// ============================================

// Query untuk mengambil data antrian berdasarkan tanggal filter
// Hanya menampilkan antrian dengan status bukan "Batal"
// Urutkan berdasarkan waktu pendaftaran (yang pertama mendaftar muncul pertama)
$antrian = [];
try {
    $stmt = $koneksi->prepare("
        SELECT 
            a.id_antrian as id,        -- ID antrian (alias untuk menghindari konflik dengan pasien.id)
            a.pasien_id,               -- ID pasien yang mendaftar (NULL untuk manual)
            a.keluhan,                 -- Keluhan pasien
            a.klaster_id,              -- ID klaster/layanan
            p.id as pasien_table_id,   -- ID pasien (alias untuk menghindari konflik dengan id_antrian)
            p.user_id,                 -- ID user
            p.no_kk,                   -- Nomor KK
            COALESCE(p.nik, a.nik_manual) as nik,                     -- NIK pasien (online) atau nik_manual (manual)
            COALESCE(p.nama, a.nama_manual) as nama,                    -- Nama pasien (online) atau nama_manual (manual)
            p.tempat_lahir,            -- Tempat lahir
            p.tanggal_lahir,           -- Tanggal lahir
            p.nama_kepala_keluarga,    -- Nama kepala keluarga
            p.nama_ibu_kandung,        -- Nama ibu kandung
            p.status_keluarga,         -- Status dalam keluarga
            p.alamat,                  -- Alamat
            p.is_bpjs,                 -- Apakah pasien BPJS
            p.jenis_bpjs,              -- Jenis BPJS
            p.nomor_bpjs,              -- Nomor BPJS
            p.is_pasien_baru,          -- Pasien baru atau lama
            COALESCE(p.jenis_kelamin, a.jenis_kelamin_manual) as jenis_kelamin,           -- Jenis kelamin (online) atau manual
            COALESCE(p.umur, a.umur_manual) as umur,                    -- Umur (online) atau umur_manual (manual)
            p.agama,                   -- Agama
            p.pekerjaan,               -- Pekerjaan
            p.pendidikan,              -- Pendidikan
            p.no_hp,                   -- Nomor HP
            a.nomor_antrian,           -- Nomor antrian yang diberikan admin
            a.status,                  -- Status antrian
            a.tanggal_kunjungan,       -- Tanggal kunjungan
            a.created_at as waktu_daftar,  -- Waktu pendaftaran
            k.nama_klaster,            -- Nama klaster/layanan
            a.sumber                  -- Sumber (Online/Offline) - langsung dari tabel
        FROM antrian a
        LEFT JOIN pasien p ON a.pasien_id = p.id  -- LEFT JOIN agar data manual (pasien_id = NULL) tetap muncul
        INNER JOIN klaster k ON a.klaster_id = k.id  -- Gabung dengan tabel klaster
        WHERE a.tanggal_kunjungan = ? AND a.status != 'Batal'  -- Filter tanggal dan exclude yang dibatalkan
        ORDER BY a.created_at ASC  -- Urutkan dari yang pertama mendaftar
    ");
    $stmt->execute([$tanggal_filter]);
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Pastikan kolom sumber ada di hasil query
    // Jika kolom sumber tidak ada di database, akan menggunakan default 'Online'
    if (!empty($antrian) && isset($_GET['debug'])) {
        echo "<!-- DEBUG: Sample antrian data: " . htmlspecialchars(print_r($antrian[0], true)) . " -->";
        echo "<!-- DEBUG: Kolom yang ada: " . htmlspecialchars(implode(', ', array_keys($antrian[0]))) . " -->";
    }
} catch (PDOException $e) {
    $error = "Error mengambil data antrian: " . $e->getMessage();
}

// ============================================
// AJAX ENDPOINT - Return JSON jika parameter ajax=1
// ============================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    // Cegah browser menyimpan cache agar data terbaru selalu diambil setelah hapus/edit di database
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode([
        'success' => true,
        'data' => $antrian,
        'count' => count($antrian),
        'tanggal' => $tanggal_filter
    ]);
    exit;
}
// Cegah cache halaman penuh: refresh selalu ambil data terbaru dari database
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Kelola Antrian | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
    <link href="../../assets/css/admin/kelola_antrian.css" rel="stylesheet">
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

        <?php if ($success): ?>
            <div id="alert-success-kelola" class="alert alert-success alert-dismissible" style="position: relative; padding-right: 40px;">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="alert-close-btn" onclick="dismissSuccessAlert()" aria-label="Tutup pesan">&times;</button>
            </div>
        <?php endif; ?>

        <!-- ========== FORM TAMBAH ANTRIAN OFFLINE ==========
             Form untuk menambah antrian pasien yang datang langsung (tidak daftar online).
             Layout: 4 kolom (baris 1), 3 kolom (baris 2), keluhan full width, tombol Tambah. -->
        <div class="card" style="margin-bottom: 20px;">
            <h2 style="margin-top: 0; margin-bottom: 16px;">Tambah Antrian Offline</h2>
            <form method="post" action="" style="margin-bottom: 0;" id="form-tambah-offline" onsubmit="return validasiTanggalHariKerja(this);">
                <input type="hidden" name="tambah_offline" value="1">
                <div class="form-tambah-offline">
                    <!-- Baris 1: Tanggal, Nama, Umur, Jenis Kelamin (4 field sama lebar) -->
                    <div class="form-row">
                        <div class="form-group form-group-standard">
                            <label>Tanggal Kunjungan *</label>
                            <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan_offline" class="form-input" 
                                   value="<?php echo htmlspecialchars($tanggal_filter); ?>" required title="Hanya hari kerja (Senin–Jumat)">
                        </div>
                        <div class="form-group form-group-standard">
                            <label>Nama Pasien *</label>
                            <input type="text" name="nama" class="form-input" required 
                                   placeholder="Masukkan nama pasien">
                        </div>
                        <div class="form-group form-group-compact">
                            <label>Umur *</label>
                            <input type="number" name="umur" class="form-input" required 
                                   min="0" max="150" placeholder="Umur">
                        </div>
                        <div class="form-group form-group-compact">
                            <label>Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="L">L (Laki-laki)</option>
                                <option value="P">P (Perempuan)</option>
                            </select>
                        </div>
                    </div>
                    <!-- Baris 2: NIK, Layanan, Nomor Antrian (3 field sama lebar) -->
                    <div class="form-row">
                        <div class="form-group form-group-standard">
                            <label>NIK</label>
                            <input type="text" name="nik" id="nik_offline" class="form-input" 
                                   maxlength="16" pattern="[0-9]{1,16}" placeholder="NIK" 
                                   inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="form-group form-group-standard">
                            <label>Layanan Yang Dituju *</label>
                            <select name="klaster_id" class="form-select" required>
                                <option value="">-- Pilih Klaster --</option>
                                <?php if (!empty($klaster_list)): ?>
                                    <?php foreach ($klaster_list as $klaster): ?>
                                        <option value="<?php echo $klaster['id']; ?>">
                                            <?php echo htmlspecialchars($klaster['nama_klaster']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group form-group-compact">
                            <label>Nomor Antrian</label>
                            <input type="text" class="form-input" value="Otomatis oleh sistem" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label>Keluhan</label>
                            <textarea name="keluhan" class="form-input" rows="3" 
                                      placeholder="Masukkan keluhan pasien"></textarea>
                        </div>
                    </div>
                    <!-- Baris 4: Tombol Tambah -->
                    <div class="form-row">
                        <div class="form-group form-group-button">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-submit-form">Tambah</button>
                        </div>
                    </div>
                </div>
                <small style="color: var(--muted); font-size: 12px; display: block; margin-top: 10px; margin-bottom: 0;">
                    * Status otomatis "Selesai" dan nomor antrian otomatis oleh sistem untuk antrian offline
                </small>
            </form>
        </div>

        <!-- Tabel Antrian -->
        <div class="card">
            <h2 style="margin-top: 0;">Daftar Antrian - <?php echo date('d/m/Y', strtotime($tanggal_filter)); ?></h2>
            
            <!-- Filter Section (selalu di atas) -->
            <form method="get" action="">
                <div class="filter-form-inline">
                    <div class="filter-group-inline">
                        <label>Filter Tanggal</label>
                        <input type="date" name="tanggal" class="filter-input-date" value="<?php echo htmlspecialchars($tanggal_filter); ?>" required>
                    </div>
                    <!-- Parameter untuk menandai bahwa form filter di-submit (untuk membedakan akses via menu vs form) -->
                    <input type="hidden" name="filter_submitted" value="1">
                    <div style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                    <button type="submit" class="btn-filter">Filter</button>
                    </div>
                </div>
            </form>
            
            <?php if (empty($antrian)): ?>
                <div class="empty-state">
                    <p>Tidak ada antrian pada tanggal tersebut.</p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <input type="hidden" name="tanggal_filter" value="<?php echo htmlspecialchars($tanggal_filter); ?>">
                    <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Klaster</th>
                                        <th>Waktu Daftar</th>
                                        <th>Nomor Antrian</th>
                                        <th>Sumber</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="kelola-antrian-table-body">
                                    <?php 
                                    $no = 1;
                                    foreach ($antrian as $index => $item): 
                                    ?>
                                        <tr>
                                            <td data-label="No"><?php echo $no++; ?></td>
                                            <td data-label="Nama"><strong><?php echo htmlspecialchars($item['nama']); ?></strong></td>
                                            <td data-label="Klaster"><?php echo htmlspecialchars($item['nama_klaster']); ?></td>
                                            <td data-label="Waktu Daftar">
                                                <?php 
                                                if ($item['waktu_daftar']) {
                                                    echo date('H:i', strtotime($item['waktu_daftar'])) . ' WIB';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Nomor Antrian">
                                                <input 
                                                    type="hidden" 
                                                    name="antrian_id[antrian_<?php echo $item['id']; ?>]" 
                                                    value="<?php echo $item['id']; ?>"
                                                >
                                                <?php 
                                                $isSelesai = ($item['status'] == 'Selesai' || $item['status'] == 'selesai');
                                                ?>
                                                <input 
                                                    type="number" 
                                                    id="nomor_antrian_<?php echo $item['id']; ?>"
                                                    name="nomor_antrian[antrian_<?php echo $item['id']; ?>]" 
                                                    class="input-nomor <?php echo $isSelesai ? 'disabled-field' : ''; ?>"
                                                    value="<?php echo htmlspecialchars($item['nomor_antrian'] ?? ''); ?>"
                                                    min="1"
                                                    placeholder="-"
                                                    <?php echo $isSelesai ? 'disabled' : ''; ?>
                                                >
                                            </td>
                                            <td data-label="Sumber" style="text-align: center; vertical-align: middle;">
                                                <?php 
                                                // Ambil nilai sumber langsung dari database
                                                $sumber = '';
                                                
                                                // Cek apakah kolom sumber ada di hasil query
                                                if (isset($item['sumber']) && $item['sumber'] !== null && $item['sumber'] !== '') {
                                                    $sumber = trim($item['sumber']);
                                                } else {
                                                    // Fallback: tentukan berdasarkan pasien_id dan nama_manual
                                                    // Jika pasien_id NULL atau ada nama_manual, berarti Offline
                                                    if (empty($item['pasien_id']) || !empty($item['nama_manual'])) {
                                                        $sumber = 'Offline';
                                                    } else {
                                                        $sumber = 'Online';
                                                    }
                                                }
                                                
                                                // Normalisasi nilai (case-insensitive)
                                                $sumber_lower = strtolower($sumber);
                                                
                                                // Tampilkan badge sesuai sumber - PASTIKAN SELALU DITAMPILKAN
                                                if ($sumber_lower == 'offline' || $sumber_lower == 'manual') {
                                                    echo '<span class="badge-sumber badge-offline" style="background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Offline</span>';
                                                } else {
                                                    echo '<span class="badge-sumber badge-online" style="background-color: #cfe2ff; color: #084298; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Online</span>';
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Status">
                                                <select 
                                                    id="status_<?php echo $item['id']; ?>"
                                                    name="status[antrian_<?php echo $item['id']; ?>]" 
                                                    class="status-select <?php echo $isSelesai ? 'disabled-field' : ''; ?>"
                                                    data-antrian-id="<?php echo $item['id']; ?>"
                                                    <?php echo $isSelesai ? 'disabled' : ''; ?>
                                                >
                                                    <option value="Menunggu" <?php echo ($item['status'] == 'Menunggu' || $item['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                                    <option value="Dipanggil" <?php echo ($item['status'] == 'Dipanggil' || $item['status'] == 'dipanggil' || $item['status'] == 'sedang_dilayani') ? 'selected' : ''; ?>>Dipanggil</option>
                                                    <option value="Selesai" <?php echo ($item['status'] == 'Selesai' || $item['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="Batal" <?php echo ($item['status'] == 'Batal' || $item['status'] == 'batal') ? 'selected' : ''; ?>>Batal</option>
                                                </select>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                    <div class="form-actions" style="margin-top: 20px; text-align: center; padding-top: 20px; border-top: 1px solid var(--border);">
                        <button type="submit" class="btn-submit" style="background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.3s;">Simpan Perubahan</button>
                    </div>
                    
                    <!-- Informasi -->
                    <div class="info-notice" style="margin-top: 20px; padding: 15px; background: #f0f7ff; border-left: 4px solid var(--primary); border-radius: 6px;">
                        <p style="margin: 0 0 8px 0; color: var(--text); font-size: 14px; line-height: 1.6;">
                            <strong>ℹ️ Informasi:</strong> Ketika status antrian diubah menjadi <strong>"Selesai"</strong>, nomor antrian dan status tidak dapat diubah lagi. Hal ini untuk menjaga integritas data antrian yang sudah selesai dilayani.
                        </p>
                        <p style="margin: 0; color: var(--text); font-size: 14px; line-height: 1.6;">
                            <strong>⚠️ Perhatian:</strong> Jika status diubah menjadi <strong>"Batal"</strong>, antrian tersebut akan <strong>terhapus</strong>,Antrian yang dibatalkan tidak akan muncul lagi di halaman kelola antrian.
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script>
        // NIK hanya angka: blokir huruf di input tambah antrian offline
        (function() {
            var nikInput = document.getElementById('nik_offline');
            if (nikInput) {
                nikInput.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                });
                nikInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    var text = (e.clipboardData || window.clipboardData).getData('text');
                    var digits = text.replace(/\D/g, '');
                    var start = this.selectionStart, end = this.selectionEnd;
                    var val = this.value;
                    this.value = val.substring(0, start) + digits + val.substring(end);
                    this.setSelectionRange(start + digits.length, start + digits.length);
                });
            }
        })();
        // Validasi form tambah antrian offline: tanggal kunjungan hanya boleh hari kerja (Senin–Jumat)
        function validasiTanggalHariKerja(form) {
            if (!form || !form.querySelector('input[name="tambah_offline"][value="1"]')) return true;
            var input = document.getElementById('tanggal_kunjungan_offline') || form.querySelector('input[name="tanggal_kunjungan"]');
            if (!input || !input.value) return true;
            var d = new Date(input.value + 'T12:00:00');
            var hari = d.getDay(); // 0=Minggu, 1=Senin, ..., 6=Sabtu
            if (hari === 0 || hari === 6) {
                alert('Tanggal kunjungan hanya boleh hari kerja (Senin–Jumat). Silakan pilih tanggal lain.');
                input.focus();
                return false;
            }
            return true;
        }
        // Hapus parameter success dari URL agar setelah refresh pesan tidak muncul lagi
        (function() {
            var url = new URL(window.location.href);
            if (url.searchParams.get('success') === '1') {
                url.searchParams.delete('success');
                var cleanUrl = url.pathname + (url.search || '') + url.hash;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        })();
        function dismissSuccessAlert() {
            var el = document.getElementById('alert-success-kelola');
            if (el) el.style.display = 'none';
            var url = new URL(window.location.href);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname + (url.search || '') + url.hash);
        }
    </script>
    <script src="../../assets/js/auto_refresh.js"></script>
</body>
</html>

