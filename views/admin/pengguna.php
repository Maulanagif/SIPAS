<?php
/**
 * FILE: views/admin/pengguna.php
 * FUNGSI: Halaman untuk admin melihat daftar semua pengguna terdaftar
 * 
 * FITUR:
 * - Menampilkan semua user biasa (bukan admin) dalam bentuk tabel
 * - Menampilkan data user dan data pasien (jika sudah mengisi)
 * - JOIN dengan tabel pasien untuk mendapatkan data lengkap
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

// Variabel untuk menyimpan error, data users, dan kata kunci pencarian
$error = '';
$users = [];

// Ambil kata kunci pencarian (optional) dari query string
// Pencarian akan digunakan untuk mencari berdasarkan nama lengkap atau email
$search = '';
if (isset($_GET['search'])) {
    // Trim spasi dan gunakan strtolower di query (pakai LIKE) agar tidak case sensitive
    $search = trim($_GET['search']);
}

// ============================================
// AMBIL DATA SEMUA PENGGUNA DARI DATABASE
// ============================================
// Query mengambil data user biasa (bukan admin) dengan data pasien lengkap.
// LEFT JOIN digunakan agar user yang belum isi data pasien tetap muncul di daftar.
// Data pasien diambil lengkap (p.*) agar bisa digunakan untuk modal detail tanpa query tambahan.
// Jika ada kata kunci pencarian, filter berdasarkan nama pasien (p.nama) atau email user (u.email).
try {
    if ($search !== '') {
        // Pencarian dengan LIKE (case-insensitive) pada nama pasien dan email
        $like = '%' . $search . '%';
        $stmt = $koneksi->prepare("
            SELECT 
                u.id,                  -- ID user
                u.email,               -- Email user
                p.*                    -- Semua data pasien (jika ada)
            FROM users u
            LEFT JOIN pasien p ON u.id = p.user_id  -- LEFT JOIN agar user tanpa data pasien tetap muncul
            WHERE (u.is_admin = 0 OR u.is_admin IS NULL)
              AND (
                    (p.nama IS NOT NULL AND p.nama <> '' AND LOWER(p.nama) LIKE LOWER(?))
                 OR LOWER(u.email) LIKE LOWER(?)
              )
            ORDER BY u.id DESC
        ");
        $stmt->execute([$like, $like]);
    } else {
        // Tanpa pencarian → tampilkan semua user biasa
        $stmt = $koneksi->prepare("
            SELECT 
                u.id,                  -- ID user
                u.email,               -- Email user
                p.*                    -- Semua data pasien (jika ada)
            FROM users u
            LEFT JOIN pasien p ON u.id = p.user_id  -- LEFT JOIN agar user tanpa data pasien tetap muncul
            WHERE (u.is_admin = 0 OR u.is_admin IS NULL)  -- Hanya ambil user biasa, bukan admin
            ORDER BY u.id DESC  -- Urutkan dari yang terbaru
        ");
        $stmt->execute();
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil data pengguna: " . $e->getMessage();
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
    <title>Pengguna | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
    <link href="../../assets/css/user/profile_user.css" rel="stylesheet">
    <link href="../../assets/css/admin/pengguna_admin.css" rel="stylesheet">
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

        <!-- Tabel Pengguna (ringkas: No, Nama Lengkap, Email, Detail) -->
        <div class="card">
            <h2 style="margin-top: 0;">Daftar Pengguna</h2>
            <p style="margin-bottom: 20px; color: var(--muted);">
                Total: <strong><?php echo count($users); ?></strong> pengguna
                <?php if ($search !== ''): ?>
                    &mdash; hasil pencarian untuk:
                    <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                <?php endif; ?>
            </p>

            <!-- Form pencarian pengguna (berdasarkan nama/email) -->
            <form method="get" action="">
                <div class="filter-form-inline">
                    <div class="filter-group-inline">
                        <label for="search">Cari Pengguna</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="filter-search-input"
                            placeholder="Cari berdasarkan nama atau email..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <!-- Tombol Cari -->
                    <div style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-filter">Cari</button>
                    </div>
                </div>
            </form>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <p>Belum ada pengguna yang terdaftar.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($users as $user): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <!-- Tombol Lihat akan membuka modal detail (tanpa pindah halaman) -->
                                        <?php if (!empty($user['nama'])): ?>
                                            <strong><?php echo htmlspecialchars($user['nama']); ?></strong>
                                        <?php else: ?>
                                            <span style="color: #6b7280; font-style: italic;">Belum mengisi data pasien</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if (!empty($user['nama'])): ?>
                                            <button type="button" class="btn-detail" style="text-decoration: none; cursor: pointer;"
                                                    onclick="showUserDetail(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                Lihat
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #6b7280; font-style: italic;">Tidak ada data</span>
                                        <?php endif; ?>
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

    <!-- ========== Modal Detail Pengguna ==========
         Ditampilkan saat admin klik tombol "Lihat" di tabel.
         Isi: foto profil (dari pasien.foto_profil atau placeholder) + data rapi per section
         (Identitas, Data Keluarga, Data Pribadi) — layout sama seperti halaman profil user.
         Konten diisi via JavaScript (showUserDetail) dari data baris tabel. -->
    <div id="userDetailModal" class="modal">
        <div class="modal-content modal-detail-pengguna" style="max-width: 720px;">
            <span class="close" onclick="closeUserDetail()">&times;</span>
            <h2>Detail Pengguna</h2>
            <div id="userDetailBody" class="profile-detail-admin-body">
                <!-- Diisi JS: blok foto + profile-view-data (section Identitas, Data Keluarga, Data Pribadi) -->
            </div>
        </div>
    </div>

    <!-- Lightbox foto profil (detail pengguna): tampil saat admin klik foto di modal detail. Tutup: backdrop, ×, atau Escape. -->
    <div class="profile-photo-lightbox" id="userDetailPhotoLightbox" aria-hidden="true">
        <div class="profile-photo-lightbox-backdrop" id="userDetailPhotoLightboxBackdrop"></div>
        <div class="profile-photo-lightbox-content">
            <button type="button" class="profile-photo-lightbox-close" id="userDetailPhotoLightboxClose" aria-label="Tutup">&times;</button>
            <img src="" alt="Foto Profil (besar)" class="profile-photo-lightbox-img" id="userDetailPhotoLightboxImg">
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script>
        /**
         * Helper: format tanggal dari Y-m-d atau datetime ke d/m/Y untuk tampilan.
         */
        function formatTanggalDetail(tanggal) {
            if (!tanggal) return '-';
            const d = new Date(tanggal);
            if (isNaN(d.getTime())) {
                // Jika bukan format datetime, coba asumsi Y-m-d
                const parts = String(tanggal).split('-');
                if (parts.length === 3) {
                    return parts[2] + '/' + parts[1] + '/' + parts[0];
                }
                return tanggal;
            }
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return day + '/' + month + '/' + year;
        }

        /**
         * Helper: escape string untuk dimasukkan ke HTML (mencegah XSS).
         * Nilai null/undefined dikembalikan sebagai '-'.
         */
        function esc(value) {
            if (value === null || value === undefined) return '-';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /**
         * Tampilkan detail pengguna di modal.
         * @param data - Objek gabungan user + pasien (dari json_encode baris tabel).
         * Layout: foto profil di atas (foto_profil atau placeholder), lalu 3 section:
         * Identitas (Email, No.KK, NIK, Nama, Tempat/Tanggal Lahir),
         * Data Keluarga (Nama Kepala Keluarga, Nama Ibu Kandung, Status Keluarga, Alamat),
         * Data Pribadi (Pasien, BPJS jika ada, Jenis Kelamin, Umur, Agama, Pekerjaan, Pendidikan, No.HP).
         * Menggunakan class yang sama dengan halaman profil (profile-view-section, profile-view-grid, dll) agar tampilan konsisten.
         */
        function showUserDetail(data) {
            const modal = document.getElementById('userDetailModal');
            const body = document.getElementById('userDetailBody');
            if (!modal || !body) return;

            /* Jika belum ada data pasien (nama kosong), tampilkan pesan kosong */
            if (!data || !data.nama) {
                body.innerHTML = '<div class="empty-state"><p>Pengguna ini belum mengisi data pasien.</p></div>';
                modal.style.display = 'block';
                return;
            }

            /* Konversi nilai ke teks tampilan */
            const jk = data.jenis_kelamin === 'L' ? 'Laki-laki' : (data.jenis_kelamin === 'P' ? 'Perempuan' : '-');
            const umur = data.umur ? esc(data.umur) + ' tahun' : '-';
            let statusBpjs = '-';
            if (data.is_bpjs !== undefined && data.is_bpjs !== null) {
                if (parseInt(data.is_bpjs) === 1) {
                    statusBpjs = 'BPJS';
                    if (data.jenis_bpjs) statusBpjs += ' (' + esc(data.jenis_bpjs) + ')';
                    if (data.nomor_bpjs) statusBpjs += ' - ' + esc(data.nomor_bpjs);
                } else statusBpjs = 'NON BPJS';
            }
            let statusPasien = '-';
            if (data.is_pasien_baru !== undefined && data.is_pasien_baru !== null) {
                statusPasien = parseInt(data.is_pasien_baru) === 1 ? 'Baru' : 'Lama';
            }
            const tglLahir = formatTanggalDetail(data.tanggal_lahir);

            /* Cek ada foto atau tidak; kalau tidak ada, tampilkan inisial nama */
            const hasPhoto = (data.foto_profil && data.foto_profil.trim() !== '');
            const fotoUrl = hasPhoto ? esc(data.foto_profil) : '';

            /** Ambil inisial dari nama (contoh: "Farhan Maulana" → "FM", "Budi" → "BU") */
            function getInitials(nama) {
                if (!nama || String(nama).trim() === '') return '?';
                const parts = String(nama).trim().split(/\s+/);
                if (parts.length >= 2) {
                    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
                }
                const one = parts[0];
                return one.length >= 2 ? one.substring(0, 2).toUpperCase() : one.charAt(0).toUpperCase();
            }
            const initials = getInitials(data.nama);

            /* Bangun HTML section Identitas (Email, No.KK, NIK, Nama, Tempat/Tanggal Lahir). Garis pembatas antar section diatur via CSS. */
            const sectionIdentitas = `
                <section class="profile-view-section">
                    <h2 class="profile-view-section-title">Identitas</h2>
                    <div class="profile-view-grid">
                        <div class="profile-view-item full"><span class="label">Email</span><span class="value">${esc(data.email)}</span></div>
                        <div class="profile-view-item"><span class="label">No.KK</span><span class="value">${esc(data.no_kk)}</span></div>
                        <div class="profile-view-item"><span class="label">NIK</span><span class="value">${esc(data.nik)}</span></div>
                        <div class="profile-view-item full"><span class="label">Nama</span><span class="value">${esc(data.nama)}</span></div>
                        <div class="profile-view-item"><span class="label">Tempat Lahir</span><span class="value">${esc(data.tempat_lahir)}</span></div>
                        <div class="profile-view-item"><span class="label">Tanggal Lahir</span><span class="value">${tglLahir}</span></div>
                    </div>
                </section>`;

            /* Bangun HTML section Data Keluarga (4 field: 2 kiri, 2 kanan) */
            const sectionKeluarga = `
                <section class="profile-view-section">
                    <h2 class="profile-view-section-title">Data Keluarga</h2>
                    <div class="profile-view-grid">
                        <div class="profile-view-item"><span class="label">Nama Kepala Keluarga</span><span class="value">${esc(data.nama_kepala_keluarga)}</span></div>
                        <div class="profile-view-item"><span class="label">Nama Ibu Kandung</span><span class="value">${esc(data.nama_ibu_kandung)}</span></div>
                        <div class="profile-view-item"><span class="label">Status Keluarga</span><span class="value">${esc(data.status_keluarga)}</span></div>
                        <div class="profile-view-item"><span class="label">Alamat</span><span class="value">${esc(data.alamat)}</span></div>
                    </div>
                </section>`;

            /* Bangun baris section Data Pribadi; jika BPJS dipilih, tambah baris Jenis BPJS & Nomor BPJS */
            let sectionPribadiRows = `
                        <div class="profile-view-item"><span class="label">Pasien</span><span class="value">${statusBpjs} / ${statusPasien}</span></div>`;
            if (data.is_bpjs && parseInt(data.is_bpjs) === 1) {
                sectionPribadiRows += `
                        <div class="profile-view-item"><span class="label">Jenis BPJS</span><span class="value">${esc(data.jenis_bpjs)}</span></div>
                        <div class="profile-view-item"><span class="label">Nomor BPJS</span><span class="value">${esc(data.nomor_bpjs)}</span></div>`;
            }
            sectionPribadiRows += `
                        <div class="profile-view-item"><span class="label">Jenis Kelamin</span><span class="value">${jk}</span></div>
                        <div class="profile-view-item"><span class="label">Umur</span><span class="value">${umur}</span></div>
                        <div class="profile-view-item"><span class="label">Agama</span><span class="value">${esc(data.agama)}</span></div>
                        <div class="profile-view-item"><span class="label">Pekerjaan</span><span class="value">${esc(data.pekerjaan)}</span></div>
                        <div class="profile-view-item"><span class="label">Pendidikan</span><span class="value">${esc(data.pendidikan)}</span></div>
                        <div class="profile-view-item"><span class="label">No.HP</span><span class="value">${esc(data.no_hp)}</span></div>`;

            const sectionPribadi = `
                <section class="profile-view-section">
                    <h2 class="profile-view-section-title">Data Pribadi</h2>
                    <div class="profile-view-grid">${sectionPribadiRows}
                    </div>
                </section>`;

            /* Gabungkan: blok foto atau inisial + 3 section (Identitas, Data Keluarga, Data Pribadi) */
            const photoBlock = hasPhoto
                ? `<div class="profile-detail-admin-photo profile-photo-wrap" id="userDetailPhotoWrap" style="cursor:pointer; display:inline-flex;" data-photo-url="${fotoUrl}" title="Klik untuk perbesar">
                    <img src="${fotoUrl}" alt="Foto Profil" class="profile-detail-admin-img">
                   </div>`
                : `<div class="profile-detail-admin-photo profile-detail-admin-initials" aria-label="Tanpa foto profil">
                    <span class="profile-detail-admin-initials-text">${esc(initials)}</span>
                   </div>`;

            body.innerHTML = `
                ${photoBlock}
                <div class="profile-view-data">${sectionIdentitas}${sectionKeluarga}${sectionPribadi}</div>`;

            modal.style.display = 'block';
            window.addEventListener('click', outsideClickHandler);

            /* Klik foto profil → buka lightbox (hanya jika ada foto) */
            var photoWrap = document.getElementById('userDetailPhotoWrap');
            if (photoWrap) {
                photoWrap.addEventListener('click', function() {
                    openUserDetailPhotoLightbox(photoWrap.getAttribute('data-photo-url'));
                });
            }
        }

        /** Tutup modal detail dan lepas listener klik di luar. */
        function closeUserDetail() {
            const modal = document.getElementById('userDetailModal');
            if (modal) {
                modal.style.display = 'none';
                window.removeEventListener('click', outsideClickHandler);
            }
        }

        /** Saat klik di backdrop (luar konten modal), tutup modal. */
        function outsideClickHandler(event) {
            const modal = document.getElementById('userDetailModal');
            if (event.target === modal) {
                closeUserDetail();
            }
        }

        /** Lightbox foto profil di detail pengguna: buka overlay gambar besar (sama seperti di halaman profil user). */
        function openUserDetailPhotoLightbox(src) {
            if (!src) return;
            var lb = document.getElementById('userDetailPhotoLightbox');
            var lbImg = document.getElementById('userDetailPhotoLightboxImg');
            if (!lb || !lbImg) return;
            lbImg.src = src;
            lb.setAttribute('aria-hidden', 'false');
            lb.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        /** Tutup lightbox foto dan kembalikan scroll. */
        function closeUserDetailPhotoLightbox() {
            var lb = document.getElementById('userDetailPhotoLightbox');
            if (!lb) return;
            lb.classList.remove('is-open');
            lb.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        (function() {
            var backdrop = document.getElementById('userDetailPhotoLightboxBackdrop');
            var closeBtn = document.getElementById('userDetailPhotoLightboxClose');
            if (backdrop) backdrop.addEventListener('click', closeUserDetailPhotoLightbox);
            if (closeBtn) closeBtn.addEventListener('click', closeUserDetailPhotoLightbox);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    var lb = document.getElementById('userDetailPhotoLightbox');
                    if (lb && lb.classList.contains('is-open')) closeUserDetailPhotoLightbox();
                }
            });
        })();
    </script>
</body>
</html>

