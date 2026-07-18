<?php
/**
 * FILE: views/user/profile_user.php
 * FUNGSI: Halaman profil user - untuk mengelola data profil dan ganti password
 *
 * FITUR:
 * - Tampilan profil: foto atau inisial nama user (bukan username); tanpa ikon edit di view.
 * - Tombol "Informasi Akun" membuka form edit profil (foto/inisial + ikon ganti foto, field data pasien, Simpan/Batal).
 * - Tombol "Tentang" menampilkan jam operasional dan tutorial pendaftaran antrian dalam card; card putih di atas Tentang dihilangkan.
 * - Form ganti password; validasi NIK; auto-calculate umur; toggle field BPJS.
 *
 * PERUBAHAN / YANG DIBUAT (ringkasan):
 * - Inisial dari nama user (2 kata → FM, 1 kata → 2 huruf depan); tampil jika tidak ada foto_profil.
 * - Tampilan view: foto/inisial → nama (bold) → garis pemisah → tombol Informasi Akun & Informasi SIPAS (vertikal, persegi panjang, berikon).
 * - Ikon edit foto hanya di Informasi Akun; di tampilan utama profil tidak ada ikon edit.
 * - Section Informasi SIPAS: dibungkus card (deskripsi SIPAS, jam operasional + tutorial); saat ditampilkan, card putih wrapper dihilangkan via JS/CSS.
 *
 * AKSES: User harus punya data pasien, login, bukan admin.
 * CATATAN: Password di-hash dengan password_hash() sebelum disimpan.
 */
session_start();
require_once '../../config/koneksi.php';
require_once '../../config/cloudinary.php';

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
$error = '';   // Variabel untuk menyimpan pesan error
$success = ''; // Variabel untuk menyimpan pesan success

// ============================================
// CEK APAKAH USER SUDAH PUNYA DATA PASIEN
// ============================================
// User harus sudah isi data pasien dulu sebelum bisa akses profil
try {
    // Gunakan COUNT untuk mengecek apakah ada data, lebih aman
    $stmt = $koneksi->prepare("SELECT COUNT(*) as total FROM pasien WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || $result['total'] == 0) {
        // Belum ada data pasien, redirect ke pendaftaran awal
        header('Location: pendaftaran_awal.php');
        exit;
    }
} catch (PDOException $e) {
    // Jika tabel pasien belum ada, redirect ke pendaftaran awal
    header('Location: pendaftaran_awal.php');
    exit;
}

// ============================================
// AMBIL DATA USER DARI DATABASE
// ============================================
// Ambil data dari tabel users (untuk email) dan pasien (untuk informasi profil)
$user = null;
$pasien = null;
try {
    // Ambil data dari tabel users (untuk email)
    $stmt = $koneksi->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: ../../login.php');
        exit;
    }
    
    // Ambil data dari tabel pasien (untuk informasi profil)
    $stmt = $koneksi->prepare("SELECT * FROM pasien WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $pasien = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Gabungkan data user dan pasien
    if ($pasien) {
        $user = array_merge($user, $pasien);
    }
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
    $user = null;
}

// ============================================
// PROSES UPDATE PROFILE (POST REQUEST)
// ============================================
// Handle form submission untuk update profil pasien
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Ambil data dari form
    $no_kk = trim($_POST['no_kk'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $nama_kepala_keluarga = trim($_POST['nama_kepala_keluarga'] ?? '');
    $nama_ibu_kandung = trim($_POST['nama_ibu_kandung'] ?? '');
    $status_keluarga = trim($_POST['status_keluarga'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $is_bpjs = isset($_POST['is_bpjs']) && $_POST['is_bpjs'] == '1' ? 1 : 0;
    $jenis_bpjs = $_POST['jenis_bpjs'] ?? null;
    $nomor_bpjs = trim($_POST['nomor_bpjs'] ?? '');
    $is_pasien_baru = isset($_POST['is_pasien_baru']) ? 1 : 0;
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? null;
    $umur = !empty($_POST['umur']) ? intval($_POST['umur']) : null;
    $agama = trim($_POST['agama'] ?? '');
    $pekerjaan = trim($_POST['pekerjaan'] ?? '');
    $pendidikan = trim($_POST['pendidikan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    
    // Validasi
    if (empty($nik)) {
        $error = 'NIK harus diisi!';
    } elseif (empty($nama)) {
        $error = 'Nama harus diisi!';
    } else {
        // Update ke tabel pasien
    try {
            // Hitung umur dari tanggal lahir jika tidak diisi
            if (empty($umur) && !empty($tanggal_lahir)) {
                $birthDate = new DateTime($tanggal_lahir);
                $today = new DateTime();
                $umur = $today->diff($birthDate)->y;
            }
            
        $tanggal_lahir_db = !empty($tanggal_lahir) ? $tanggal_lahir : null;
        
        $stmt = $koneksi->prepare("
                UPDATE pasien 
                SET no_kk = ?, nik = ?, nama = ?, tempat_lahir = ?, tanggal_lahir = ?,
                    nama_kepala_keluarga = ?, nama_ibu_kandung = ?, status_keluarga = ?,
                    alamat = ?, is_bpjs = ?, jenis_bpjs = ?, nomor_bpjs = ?,
                    is_pasien_baru = ?, jenis_kelamin = ?, umur = ?,
                    agama = ?, pekerjaan = ?, pendidikan = ?, no_hp = ?
                WHERE user_id = ?
        ");
        $stmt->execute([
            !empty($no_kk) ? $no_kk : null,
                $nik,
                $nama,
            !empty($tempat_lahir) ? $tempat_lahir : null,
            $tanggal_lahir_db,
                !empty($nama_kepala_keluarga) ? $nama_kepala_keluarga : null,
                !empty($nama_ibu_kandung) ? $nama_ibu_kandung : null,
                !empty($status_keluarga) ? $status_keluarga : null,
            !empty($alamat) ? $alamat : null,
                $is_bpjs,
                !empty($jenis_bpjs) ? $jenis_bpjs : null,
                !empty($nomor_bpjs) ? $nomor_bpjs : null,
                $is_pasien_baru,
                !empty($jenis_kelamin) ? $jenis_kelamin : null,
                $umur,
                !empty($agama) ? $agama : null,
                !empty($pekerjaan) ? $pekerjaan : null,
                !empty($pendidikan) ? $pendidikan : null,
            !empty($no_hp) ? $no_hp : null,
            $user_id
        ]);
            
            $success = 'Profile berhasil diperbarui!';
            
            // Reload data setelah update
            $stmt = $koneksi->prepare("SELECT id, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $koneksi->prepare("SELECT * FROM pasien WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pasien = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pasien) {
                $user = array_merge($user, $pasien);
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
        }
    }


// ============================================
// PROSES UPLOAD FOTO PROFIL (POST upload_foto)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto']) && isset($_FILES['foto_profil'])) {
    $file = $_FILES['foto_profil'];
    if ($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0 && $file['size'] <= 5 * 1024 * 1024) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed, true)) {
            $url = cloudinary_upload_foto($file['tmp_name']);
            if ($url) {
                $stmt = $koneksi->prepare("UPDATE pasien SET foto_profil = ? WHERE user_id = ?");
                $stmt->execute([$url, $user_id]);
                $success = 'Foto profil berhasil diunggah!';
                $stmt = $koneksi->prepare("SELECT * FROM pasien WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $pasien = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($pasien) {
                    $user = $user ? array_merge($user, $pasien) : $pasien;
                }
            } else {
                $error = !empty($GLOBALS['cloudinary_last_error'])
                    ? 'Gagal mengunggah foto: ' . htmlspecialchars($GLOBALS['cloudinary_last_error'])
                    : 'Gagal mengunggah foto. Periksa konfigurasi Cloudinary.';
            }
        } else {
            $error = 'Format file harus JPG, PNG, GIF, atau WebP.';
        }
    } else {
        $error = 'Pilih file gambar (maks. 5MB) atau terjadi error upload.';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/user/header.css" rel="stylesheet">
    <link href="../../assets/css/user/footer.css" rel="stylesheet">
    <link href="../../assets/css/user/profile_user.css" rel="stylesheet">
</head>
<body class="profile-page">
    <div class="dashboard-wrapper">
        <?php include 'includes/header.php'; ?>

        <div class="dashboard-container">
            <!-- Tampilkan pesan error/success -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($user): ?>
                <?php
                /* URL foto: pakai foto_profil dari DB jika ada; jika tidak, tampilkan inisial nama (bukan username). */
                $foto_url = !empty($user['foto_profil']) ? $user['foto_profil'] : '';
                $has_foto = !empty($user['foto_profil']);
                /* Inisial dari nama user: 2 kata → huruf depan kata pertama + kata terakhir; 1 kata → 2 huruf depan atau 1 huruf */
                $nama_trim = trim($user['nama'] ?? '');
                if ($nama_trim === '') {
                    $profile_initial = '?';
                } else {
                    $parts = array_filter(explode(' ', $nama_trim));
                    if (count($parts) >= 2) {
                        $profile_initial = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                    } else {
                        $one = $parts[0];
                        $profile_initial = strlen($one) >= 2 ? strtoupper(substr($one, 0, 2)) : strtoupper(substr($one, 0, 1));
                    }
                }
                ?>
                <div class="profile-page-content">
                    <div class="profile-card">

                        <!-- ========== MODE TAMPILAN (View) — foto, nama, tombol Informasi Akun & Tentang ========== -->
                        <div class="profile-view-body" id="profileViewBody">
                            <h1 class="profile-card-title">Profil</h1>

                            <!-- Area foto tampilan: foto atau inisial nama saja; tanpa ikon edit (edit/ganti foto hanya di Informasi Akun). -->
                            <div class="profile-photo-section">
                                <div class="profile-photo-wrap" id="profilePhotoWrap">
                                    <?php if ($has_foto): ?>
                                        <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto Profil" class="profile-photo-img" id="profilePhotoImg" data-full-src="<?php echo htmlspecialchars($user['foto_profil']); ?>">
                                    <?php else: ?>
                                        <span class="profile-photo-initials" id="profilePhotoInitialsView" aria-label="Tanpa foto profil"><?php echo htmlspecialchars($profile_initial); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Nama di bawah foto (bold, ukuran nyaman) -->
                            <div class="profile-view-name"><?php echo htmlspecialchars($user['nama'] ?? '-'); ?></div>

                            <!-- Garis pemisah (jarak dari nama, tampil rapi) -->
                            <div class="profile-view-divider-line" aria-hidden="true"></div>

                            <!-- Tombol Informasi Akun (atas) dan Informasi SIPAS (bawah): full width, sama besar, berikon -->
                            <div class="profile-view-actions profile-view-actions-buttons">
                                <button type="button" class="btn btn-primary profile-view-btn-block" id="btnInformasiAkun">
                                    <svg class="profile-view-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                                    <span>Informasi Akun</span>
                                </button>
                                <button type="button" class="btn btn-secondary profile-view-btn-block" id="btnTentang">
                                    <svg class="profile-view-btn-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8"/><path d="M8 11h8"/></svg>
                                    <span>Informasi SIPAS</span>
                                </button>
                            </div>
                        </div>

                        <!-- ========== INFORMASI AKUN — disembunyikan default; tampil setelah klik "Informasi Akun" ==========
                             Isi persis seperti form edit profil: judul "Informasi Akun", foto + ganti foto, form update profil,
                             tombol Batal (kembali ke view) dan Simpan. -->
                        <div class="profile-edit-body" id="profileEditBody" style="display:none;">
                            <h1 class="profile-card-title">Informasi Akun</h1>

                            <!-- Foto atau inisial di Informasi Akun; ikon pensil untuk ganti foto (upload). -->
                            <div class="profile-photo-section profile-photo-section-edit">
                                <div class="profile-photo-wrap" id="profilePhotoWrapEdit">
                                    <?php if ($has_foto): ?>
                                        <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="Foto Profil" class="profile-photo-img" id="profilePhotoImgEdit" data-full-src="<?php echo htmlspecialchars($user['foto_profil']); ?>">
                                    <?php else: ?>
                                        <span class="profile-photo-initials" id="profilePhotoInitialsEdit" aria-label="Tanpa foto profil"><?php echo htmlspecialchars($profile_initial); ?></span>
                                    <?php endif; ?>
                                    <button type="button" class="profile-photo-edit-btn" id="profilePhotoEditBtnEdit" title="Ganti foto">
                                        <svg class="icon-pencil" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </div>
                                <form id="formUploadFotoEdit" method="post" action="" enctype="multipart/form-data" style="display:none;">
                                    <input type="hidden" name="upload_foto" value="1">
                                    <input type="file" name="foto_profil" id="foto_profil_input_edit" accept="image/jpeg,image/png,image/gif,image/webp">
                                </form>
                            </div>

                        <form method="post" action="" class="profile-form" id="formProfile">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="profile-form-row">
                                <div class="form-group">
                                    <label for="no_kk">No.KK</label>
                                    <input type="text" name="no_kk" id="no_kk" class="form-input" maxlength="16" pattern="[0-9]{1,16}" onkeypress="return hanyaAngka(event)" value="<?php echo htmlspecialchars($user['no_kk'] ?? ''); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label for="nik">NIK *</label>
                                    <input type="text" name="nik" id="nik" class="form-input" required maxlength="16" pattern="[0-9]{1,16}" value="<?php echo htmlspecialchars($user['nik'] ?? ''); ?>" readonly>
                                </div>
                            </div>

                            <div class="profile-form-row">
                                <div class="form-group">
                                    <label for="nama">Nama *</label>
                                    <input type="text" name="nama" id="nama" class="form-input" required value="<?php echo htmlspecialchars($user['nama'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email_profile">Email</label>
                                    <input type="text" name="email" id="email_profile" class="form-input" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                                </div>
                            </div>

                            <div class="profile-form-row">
                                <div class="form-group">
                                    <label for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-input" value="<?php echo htmlspecialchars($user['tempat_lahir'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir_profile" class="form-input" value="<?php echo htmlspecialchars($user['tanggal_lahir'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="profile-form-row">
                                <div class="form-group">
                                    <label for="nama_kepala_keluarga">Nama Kepala Keluarga</label>
                                    <input type="text" name="nama_kepala_keluarga" id="nama_kepala_keluarga" class="form-input" value="<?php echo htmlspecialchars($user['nama_kepala_keluarga'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="nama_ibu_kandung">Nama Ibu Kandung</label>
                                    <input type="text" name="nama_ibu_kandung" id="nama_ibu_kandung" class="form-input" value="<?php echo htmlspecialchars($user['nama_ibu_kandung'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="status_keluarga">Status Keluarga</label>
                                <input type="text" name="status_keluarga" id="status_keluarga" class="form-input" placeholder="Contoh: Kepala Keluarga, Istri, Anak" value="<?php echo htmlspecialchars($user['status_keluarga'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="alamat">Alamat</label>
                                <textarea name="alamat" id="alamat" class="form-input" rows="3" placeholder="Alamat lengkap"><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Pasien</label>
                                <div class="checkbox-group-inline">
                                    <label class="checkbox-label"><input type="radio" name="is_bpjs" value="1" id="is_bpjs_profile" <?php echo (isset($user['is_bpjs']) && $user['is_bpjs']) ? 'checked' : ''; ?>><span>BPJS</span></label>
                                    <label class="checkbox-label"><input type="radio" name="is_bpjs" value="0" id="non_bpjs_profile" <?php echo empty($user['is_bpjs']) ? 'checked' : ''; ?>><span>NON BPJS</span></label>
                                    <label class="checkbox-label"><input type="radio" name="is_pasien_baru" value="0" <?php echo empty($user['is_pasien_baru']) ? 'checked' : ''; ?>><span>LAMA</span></label>
                                    <label class="checkbox-label"><input type="radio" name="is_pasien_baru" value="1" <?php echo !empty($user['is_pasien_baru']) ? 'checked' : ''; ?>><span>BARU</span></label>
                                </div>
                            </div>

                            <div class="profile-form-row" id="jenis_bpjs_group_profile" style="display:<?php echo !empty($user['is_bpjs']) ? 'grid' : 'none'; ?>;">
                                <div class="form-group">
                                    <label for="jenis_bpjs">Jenis BPJS</label>
                                    <select name="jenis_bpjs" id="jenis_bpjs" class="form-input">
                                        <option value="">-- Pilih --</option>
                                        <option value="PBI" <?php echo (isset($user['jenis_bpjs']) && $user['jenis_bpjs'] === 'PBI') ? 'selected' : ''; ?>>PBI</option>
                                        <option value="NON PBI" <?php echo (isset($user['jenis_bpjs']) && $user['jenis_bpjs'] === 'NON PBI') ? 'selected' : ''; ?>>NON PBI</option>
                                        <option value="MANDIRI" <?php echo (isset($user['jenis_bpjs']) && $user['jenis_bpjs'] === 'MANDIRI') ? 'selected' : ''; ?>>MANDIRI</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="nomor_bpjs">Nomor BPJS</label>
                                    <input type="text" name="nomor_bpjs" id="nomor_bpjs" class="form-input" maxlength="13" pattern="[0-9]{1,13}" onkeypress="return hanyaAngka(event)" value="<?php echo htmlspecialchars($user['nomor_bpjs'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="profile-form-row">
                                <div class="form-group">
                                    <label for="jenis_kelamin">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" id="jenis_kelamin" class="form-input">
                                        <option value="">-- Pilih --</option>
                                        <option value="L" <?php echo (isset($user['jenis_kelamin']) && $user['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>L (Laki-laki)</option>
                                        <option value="P" <?php echo (isset($user['jenis_kelamin']) && $user['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>P (Perempuan)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="umur_profile">Umur</label>
                                    <input type="number" name="umur" id="umur_profile" class="form-input" placeholder="Umur" min="0" max="150" value="<?php echo htmlspecialchars($user['umur'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="agama">Agama</label>
                                <select name="agama" id="agama" class="form-input">
                                    <option value="">-- Pilih --</option>
                                    <option value="Islam" <?php echo (isset($user['agama']) && $user['agama'] === 'Islam') ? 'selected' : ''; ?>>Islam</option>
                                    <option value="Kristen" <?php echo (isset($user['agama']) && $user['agama'] === 'Kristen') ? 'selected' : ''; ?>>Kristen</option>
                                    <option value="Katolik" <?php echo (isset($user['agama']) && $user['agama'] === 'Katolik') ? 'selected' : ''; ?>>Katolik</option>
                                    <option value="Hindu" <?php echo (isset($user['agama']) && $user['agama'] === 'Hindu') ? 'selected' : ''; ?>>Hindu</option>
                                    <option value="Buddha" <?php echo (isset($user['agama']) && $user['agama'] === 'Buddha') ? 'selected' : ''; ?>>Buddha</option>
                                    <option value="Konghucu" <?php echo (isset($user['agama']) && $user['agama'] === 'Konghucu') ? 'selected' : ''; ?>>Konghucu</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pekerjaan">Pekerjaan</label>
                                <input type="text" name="pekerjaan" id="pekerjaan" class="form-input" placeholder="Contoh: PNS, Swasta" value="<?php echo htmlspecialchars($user['pekerjaan'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="pendidikan">Pendidikan</label>
                                <select name="pendidikan" id="pendidikan" class="form-input">
                                    <option value="">-- Pilih --</option>
                                    <option value="Tidak Sekolah" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'Tidak Sekolah') ? 'selected' : ''; ?>>Tidak Sekolah</option>
                                    <option value="SD" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'SD') ? 'selected' : ''; ?>>SD</option>
                                    <option value="SMP" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'SMP') ? 'selected' : ''; ?>>SMP</option>
                                    <option value="SMA" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'SMA') ? 'selected' : ''; ?>>SMA</option>
                                    <option value="D3" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'D3') ? 'selected' : ''; ?>>D3</option>
                                    <option value="S1" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'S1') ? 'selected' : ''; ?>>S1</option>
                                    <option value="S2" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'S2') ? 'selected' : ''; ?>>S2</option>
                                    <option value="S3" <?php echo (isset($user['pendidikan']) && $user['pendidikan'] === 'S3') ? 'selected' : ''; ?>>S3</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="no_hp">No.HP</label>
                                <input type="text" name="no_hp" id="no_hp" class="form-input" maxlength="12" pattern="[0-9]{1,12}" onkeypress="return hanyaAngka(event)" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($user['no_hp'] ?? ''); ?>">
                            </div>

                            <div class="profile-form-actions">
                                <button type="button" class="btn btn-secondary" id="btnBatalEdit">Batal</button>
                                <button type="submit" class="btn btn-primary btn-save">Simpan</button>
                            </div>
                        </form>
                        </div>
                    </div>

                        <!-- ========== INFORMASI SIPAS — deskripsi SIPAS, jam operasional + tutorial; tampil setelah klik "Informasi SIPAS". -->
                        <div class="profile-tentang-body" id="profileTentangBody" style="display:none;">
                            <h1 class="profile-card-title">Informasi SIPAS</h1>
                            <div class="profile-tentang-card">
                                <div class="profile-tentang-card-inner">
                                    <section class="profile-tentang-section">
                                        <p class="profile-tentang-label">SIPAS (Sistem Informasi Pendaftaran Antrian Pasien) adalah layanan daring untuk mendaftar antrian Puskesmas dengan mudah. Pasien dapat memilih tanggal, klaster layanan, dan melihat status antrian dari rumah.</p>
                                    </section>
                                    <!-- Section: Jam Operasional Puskesmas -->
                                    <section class="profile-tentang-section">
                                        <h2 class="profile-tentang-section-title">Jam Operasional</h2>
                                        <div class="profile-tentang-divider"></div>
                                        <p class="profile-tentang-label">Jam Pelayanan Puskesmas</p>
                                        <ul class="profile-tentang-list">
                                            <li>Senin s/d Kamis: 07.30 – 16.00 WIB (Istirahat 12.00 – 13.00 WIB)</li>
                                            <li>Jum'at: 07.30 – 16.30 WIB (Istirahat 12.00 – 13.30 WIB)</li>
                                        </ul>
                                        <p class="profile-tentang-label">Pendaftaran Antrian</p>
                                        <ul class="profile-tentang-list">
                                            <li>Setiap hari pelayanan: 08.00 – 15.00 WIB</li>
                                            <li>Kecuali hari libur / tanggal merah</li>
                                        </ul>
                                    </section>
                                    <!-- Section: Informasi klaster pelayanan (diambil dari papan informasi Puskesmas) -->
                                    <section class="profile-tentang-section">
                                        <h2 class="profile-tentang-section-title">Klaster Pelayanan Puskesmas</h2>
                                        <div class="profile-tentang-divider"></div>
                                        <p class="profile-tentang-label"><strong>Klaster 1 – Manajemen</strong></p>
                                        <ul class="profile-tentang-list">
                                            <li>Manajemen Puskesmas</li>
                                        </ul>
                                        <p class="profile-tentang-label"><strong>Klaster 2 – Ibu dan Anak</strong></p>
                                        <ul class="profile-tentang-list">
                                            <li>Ibu hamil, bersalin dan nifas</li>
                                            <li>Balita dan anak pra sekolah</li>
                                            <li>Anak usia sekolah dan remaja</li>
                                            <li>Gizi</li>
                                        </ul>
                                        <p class="profile-tentang-label"><strong>Klaster 3 – Usia Dewasa dan Lansia</strong></p>
                                        <ul class="profile-tentang-list">
                                            <li>Usia dewasa 18 – ≤ 59 tahun</li>
                                            <li>Usia lansia ≥ 60 tahun</li>
                                        </ul>
                                        <p class="profile-tentang-label"><strong>Klaster 4 – Penanggulangan Penyakit Menular</strong></p>
                                        <ul class="profile-tentang-list">
                                            <li>Penanggulangan penyakit menular</li>
                                            <li>Kesehatan lingkungan</li>
                                            <li>Surveilans</li>
                                            <li>Imunisasi</li>
                                            <li>TB / HIV</li>
                                        </ul>
                                        <p class="profile-tentang-label"><strong>Lintas Klaster</strong></p>
                                        <ul class="profile-tentang-list">
                                            <li>UGD 24 jam</li>
                                            <li>Rawat inap</li>
                                            <li>Laboratorium</li>
                                            <li>Kefarmasian</li>
                                            <li>Gizi</li>
                                            <li>Imunisasi</li>
                                            <li>Kesehatan gigi dan mulut</li>
                                            <li>Kesehatan jiwa</li>
                                            <li>Persalinan 24 jam</li>
                                        </ul>
                                    </section>
                                    <!-- Section: Tutorial langkah pendaftaran antrian -->
                                    <section class="profile-tentang-section">
                                        <h2 class="profile-tentang-section-title">Tutorial Pendaftaran Antrian</h2>
                                        <div class="profile-tentang-divider"></div>
                                        <ol class="profile-tentang-list profile-tentang-list-ol">
                                            <li>Pastikan Anda sudah <strong>login</strong> dan memiliki <strong>data pasien lengkap</strong> (isi di menu Profil → Informasi Akun jika belum).</li>
                                            <li>Buka menu <strong>Daftar</strong> di navigasi, lalu pilih <strong>Daftar Antrian</strong>.</li>
                                            <li>Pilih <strong>tanggal kunjungan</strong> (hanya hari kerja, Senin–Jum'at).</li>
                                            <li>Pilih <strong>klaster</strong> sesuai layanan yang dibutuhkan (Manajemen, Ibu & Anak, Dewasa & Lansia, Penanggulangan Penyakit Menular, atau Lintas Klaster).</li>
                                            <li>Isi <strong>keluhan</strong> singkat, lalu klik <strong>Daftar Antrian</strong>.</li>
                                            <li>Setelah berhasil, nomor antrian dan status dapat dilihat di <strong>Daftar dan Dashboard</strong>. Cetak bukti jika perlu.</li>
                                            <li>Datang ke Puskesmas sesuai <strong>tanggal dan waktu</strong> yang dipilih, bawa kartu identitas (KTP/Kartu Keluarga).</li>
                                        </ol>
                                    </section>
                                    <div class="profile-tentang-actions">
                                        <button type="button" class="btn btn-secondary" id="btnTutupTentang">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>

                <!-- Lightbox foto profil: overlay fullscreen; tampil saat user klik area foto (bukan ikon pensil). Tutup: backdrop, tombol ×, atau Escape. -->
                <div class="profile-photo-lightbox" id="profilePhotoLightbox" aria-hidden="true">
                    <div class="profile-photo-lightbox-backdrop" id="profilePhotoLightboxBackdrop"></div>
                    <div class="profile-photo-lightbox-content">
                        <button type="button" class="profile-photo-lightbox-close" id="profilePhotoLightboxClose" aria-label="Tutup">&times;</button>
                        <img src="" alt="Foto Profil (besar)" class="profile-photo-lightbox-img" id="profilePhotoLightboxImg">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/common.js"></script>
    <script src="../../assets/js/profile_user.js"></script>
    
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
