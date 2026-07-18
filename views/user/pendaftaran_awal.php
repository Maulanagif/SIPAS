<?php
/**
 * FILE: views/user/pendaftaran_awal.php
 * FUNGSI: Form untuk user mengisi data pasien pertama kali
 * 
 * FITUR:
 * - Form lengkap data pasien (2 kolom layout)
 * - Validasi NIK harus unik
 * - Auto-calculate umur dari tanggal lahir
 * - Toggle field BPJS (muncul jika pilih BPJS)
 * - Validasi NIK dan Nama wajib diisi
 * 
 * AKSES:
 * - Hanya bisa diakses oleh user yang belum punya data pasien
 * - Jika sudah punya data pasien, redirect ke dashboard
 * - Harus sudah login
 * 
 * CATATAN:
 * - Form ini hanya bisa diisi sekali setelah user registrasi
 * - Setelah berhasil, redirect ke dashboard_user.php
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
$error = '';   // Variabel untuk menyimpan pesan error
$success = ''; // Variabel untuk menyimpan pesan success

// ============================================
// CEK APAKAH USER SUDAH PUNYA DATA PASIEN
// ============================================
// Jika sudah punya data pasien, tidak perlu isi lagi (redirect ke dashboard)
try {
    // Gunakan COUNT untuk mengecek apakah ada data, lebih efisien
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['total'] > 0) {
        // Sudah ada data pasien, redirect ke dashboard
        header('Location: dashboard_user.php');
        exit;
    }
} catch (PDOException $e) {
    // Tabel pasien mungkin belum ada, lanjutkan proses
}

// ============================================
// PROSES PENDAFTARAN AWAL PASIEN (POST REQUEST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ============================================
    // AMBIL DATA DARI FORM
    // ============================================
    // Data Pokok Pasien - trim() untuk menghapus spasi di awal/akhir
    $no_kk = trim($_POST['no_kk'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $nama_kepala_keluarga = trim($_POST['nama_kepala_keluarga'] ?? '');
    $nama_ibu_kandung = trim($_POST['nama_ibu_kandung'] ?? '');
    $status_keluarga = trim($_POST['status_keluarga'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    
    // Data BPJS
    // is_bpjs = 1 jika radio "BPJS" dipilih, 0 jika "NON BPJS"
    $is_bpjs = isset($_POST['is_bpjs']) && $_POST['is_bpjs'] == '1' ? 1 : 0;
    $jenis_bpjs = $_POST['jenis_bpjs'] ?? null;  // PBI, NON PBI, MANDIRI
    $nomor_bpjs = trim($_POST['nomor_bpjs'] ?? '');
    
    // Data Pasien
    $is_pasien_baru = isset($_POST['is_pasien_baru']) ? 1 : 0;  // 1 = BARU, 0 = LAMA
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? null;  // L atau P
    $umur = !empty($_POST['umur']) ? intval($_POST['umur']) : null;  // Convert ke integer
    $agama = trim($_POST['agama'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    
    // ============================================
    // VALIDASI INPUT
    // ============================================
    if (empty($nik)) {
        $error = 'NIK harus diisi!';
    } elseif (empty($nama)) {
        $error = 'Nama harus diisi!';
    } else {
        try {
            // ============================================
            // CEK NIK UNIK
            // ============================================
            // Cek apakah NIK sudah digunakan oleh user lain
            // NIK harus unik di seluruh sistem (tidak boleh duplikat)
            $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE nik = ? AND user_id != ?");
            $stmt->execute([$nik, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total'] > 0) {
                $error = 'NIK sudah terdaftar!';
            } else {
                // ============================================
                // AUTO-CALCULATE UMUR DARI TANGGAL LAHIR
                // ============================================
                // Jika umur tidak diisi tapi tanggal lahir ada, hitung umur otomatis
                if (empty($umur) && !empty($tanggal_lahir)) {
                    $birthDate = new DateTime($tanggal_lahir);
                    $today = new DateTime();
                    $umur = $today->diff($birthDate)->y;  // Hitung selisih tahun
                }
                
                // ============================================
                // INSERT DATA PASIEN KE DATABASE
                // ============================================
                // Insert semua data pasien ke tabel pasien
                // Field kosong di-set ke NULL (bukan empty string)
                $stmt = $koneksi->prepare("
                    INSERT INTO pasien (
                        user_id, no_kk, nik, nama, tempat_lahir, tanggal_lahir,
                        nama_kepala_keluarga, nama_ibu_kandung, status_keluarga, alamat,
                        is_bpjs, jenis_bpjs, nomor_bpjs, is_pasien_baru,
                        jenis_kelamin, umur, agama, pekerjaan, pendidikan, no_hp
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");
                $stmt->execute([
                    $user_id,                                    // user_id (wajib)
                    !empty($no_kk) ? $no_kk : null,             // no_kk (opsional)
                    $nik,                                        // nik (wajib)
                    $nama,                                       // nama (wajib)
                    !empty($tempat_lahir) ? $tempat_lahir : null,
                    !empty($tanggal_lahir) ? $tanggal_lahir : null,
                    !empty($nama_kepala_keluarga) ? $nama_kepala_keluarga : null,
                    !empty($nama_ibu_kandung) ? $nama_ibu_kandung : null,
                    !empty($status_keluarga) ? $status_keluarga : null,
                    !empty($alamat) ? $alamat : null,
                    $is_bpjs,                                    // is_bpjs (0 atau 1)
                    !empty($jenis_bpjs) ? $jenis_bpjs : null,
                    !empty($nomor_bpjs) ? $nomor_bpjs : null,
                    $is_pasien_baru,                             // is_pasien_baru (0 atau 1)
                    !empty($jenis_kelamin) ? $jenis_kelamin : null,
                    $umur,                                       // umur (bisa NULL)
                    !empty($agama) ? $agama : null,
                    !empty($pekerjaan) ? $pekerjaan : null,
                    !empty($pendidikan) ? $pendidikan : null,
                    !empty($no_hp) ? $no_hp : null
                ]);
                
                // Tampilkan pesan sukses
                $success = 'Pendaftaran awal berhasil! Anda akan diarahkan ke dashboard.';
                
                // Redirect ke dashboard setelah 2 detik
                header("refresh:2;url=dashboard_user.php");
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
    <title>Pendaftaran Awal | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/pendaftaran_awal.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include 'includes/header_pa.php'; ?>

        <div class="dashboard-container">
            <div class="page-header">
                <h1>Pendaftaran Awal</h1>
                <p>Lengkapi data diri Anda terlebih dahulu untuk mengakses layanan SIPAS</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Form Data Pokok Pasien -->
            <div class="card">
                <div class="card-header">
                    <h2>Form Data Pokok Pasien</h2>
                </div>
                <form method="post" action="" class="data-pasien-form" id="formPasien">
                    <div class="form-row">
                        <!-- Kolom Kiri -->
                        <div class="form-col">
                            <div class="form-group">
                                <label>NO.KK</label>
                                <input type="text" name="no_kk" class="form-input" 
                                       maxlength="16" pattern="[0-9]{1,16}" onkeypress="return hanyaAngka(event)">
                            </div>

                            <div class="form-group">
                                <label>NIK *</label>
                                <input type="text" name="nik" class="form-input" required
                                       maxlength="16" pattern="[0-9]{1,16}" onkeypress="return hanyaAngka(event)">
                            </div>

                            <div class="form-group">
                                <label>NAMA *</label>
                                <input type="text" name="nama" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label>TEMPAT/TGL LAHIR</label>
                                <div class="form-row-inline">
                                    <input type="text" name="tempat_lahir" class="form-input" 
                                           placeholder="Tempat lahir">
                                    <input type="date" name="tanggal_lahir" class="form-input" id="tanggal_lahir_pasien">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>NAMA KEPALA KELUARGA</label>
                                <input type="text" name="nama_kepala_keluarga" class="form-input">
                            </div>

                            <div class="form-group">
                                <label>NAMA IBU KANDUNG</label>
                                <input type="text" name="nama_ibu_kandung" class="form-input">
                            </div>

                            <div class="form-group">
                                <label>STATUS KELUARGA</label>
                                <input type="text" name="status_keluarga" class="form-input" 
                                       placeholder="Contoh: Kepala Keluarga, Istri, Anak">
                            </div>

                            <div class="form-group">
                                <label>ALAMAT</label>
                                <textarea name="alamat" class="form-input" rows="3"
                                          placeholder="Alamat lengkap"></textarea>
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div class="form-col">
                            <div class="form-group">
                                <label>PASIEN</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="radio" name="is_bpjs" value="1" id="is_bpjs">
                                        <span>BPJS</span>
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="radio" name="is_bpjs" value="0" id="non_bpjs" checked>
                                        <span>NON BPJS</span>
                                    </label>
                                </div>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="radio" name="is_pasien_baru" value="0" checked>
                                        <span>LAMA</span>
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="radio" name="is_pasien_baru" value="1">
                                        <span>BARU</span>
                                    </label>
                                </div>
                                <div class="form-group" id="jenis_bpjs_group" style="display: none;">
                                    <label>Jenis BPJS</label>
                                    <select name="jenis_bpjs" class="form-input">
                                        <option value="">-- Pilih --</option>
                                        <option value="PBI">PBI</option>
                                        <option value="NON PBI">NON PBI</option>
                                        <option value="MANDIRI">MANDIRI</option>
                                    </select>
                                </div>
                                <div class="form-group" id="nomor_bpjs_group" style="display: none;">
                                    <label>NOMOR BPJS</label>
                                    <input type="text" name="nomor_bpjs" class="form-input" 
                                           maxlength="13" pattern="[0-9]{1,13}" onkeypress="return hanyaAngka(event)">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>L/P UMUR</label>
                                <div class="form-row-inline">
                                    <select name="jenis_kelamin" class="form-input">
                                        <option value="">-- Pilih --</option>
                                        <option value="L">L (Laki-laki)</option>
                                        <option value="P">P (Perempuan)</option>
                                    </select>
                                    <input type="number" name="umur" class="form-input" id="umur_pasien"
                                           placeholder="Umur" min="0" max="150">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>AGAMA</label>
                                <select name="agama" class="form-input">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam">Islam</option>
                                    <option value="Kristen">Kristen</option>
                                    <option value="Katolik">Katolik</option>
                                    <option value="Hindu">Hindu</option>
                                    <option value="Buddha">Buddha</option>
                                    <option value="Konghucu">Konghucu</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>PEKERJAAN</label>
                                <input type="text" name="pekerjaan" class="form-input" 
                                       placeholder="Contoh: PNS, Swasta, Wiraswasta">
                            </div>

                            <div class="form-group">
                                <label>PENDIDIKAN</label>
                                <select name="pendidikan" class="form-input">
                                    <option value="">-- Pilih --</option>
                                    <option value="Tidak Sekolah">Tidak Sekolah</option>
                                    <option value="SD">SD</option>
                                    <option value="SMP">SMP</option>
                                    <option value="SMA">SMA</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                    <option value="S3">S3</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>NO.HP</label>
                                <input type="text" name="no_hp" class="form-input" 
                                       maxlength="12" pattern="[0-9]{1,12}" onkeypress="return hanyaAngka(event)"
                                       placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/common.js"></script>
    <script>
        // Toggle BPJS fields dan Auto calculate umur
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle BPJS fields
            const isBpjs = document.getElementById('is_bpjs');
            const nonBpjs = document.getElementById('non_bpjs');
            const jenisBpjsGroup = document.getElementById('jenis_bpjs_group');
            const nomorBpjsGroup = document.getElementById('nomor_bpjs_group');
            
            function toggleBpjsFields() {
                if (isBpjs && isBpjs.checked) {
                    if (jenisBpjsGroup) jenisBpjsGroup.style.display = 'block';
                    if (nomorBpjsGroup) nomorBpjsGroup.style.display = 'block';
                } else {
                    if (jenisBpjsGroup) jenisBpjsGroup.style.display = 'none';
                    if (nomorBpjsGroup) nomorBpjsGroup.style.display = 'none';
                }
            }
            
            if (isBpjs) isBpjs.addEventListener('change', toggleBpjsFields);
            if (nonBpjs) nonBpjs.addEventListener('change', toggleBpjsFields);
            toggleBpjsFields(); // Initial call

            // Auto calculate umur from tanggal lahir
            const tanggalLahirInput = document.getElementById('tanggal_lahir_pasien');
            const umurInput = document.getElementById('umur_pasien');
            
            if (tanggalLahirInput && umurInput) {
                tanggalLahirInput.addEventListener('change', function() {
                    if (this.value) {
                        const birthDate = new Date(this.value);
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        
                        umurInput.value = age;
                    } else {
                        umurInput.value = '';
                    }
                });
            }
        });
    </script>
    <?php include 'includes/footer_pa.php'; ?>
</body>
</html>

