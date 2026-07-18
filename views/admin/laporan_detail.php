<?php
/**
 * FILE: views/admin/laporan_detail.php
 * FUNGSI: Halaman detail laporan untuk admin. Satu halaman menampilkan isi sesuai parameter ?jenis=...
 *
 * JENIS LAPORAN YANG DIDUKUNG:
 * - data_pasien                   → Tabel: No | Nama Pasien | NIK | ... | Status Pasien
 * - antrian_klaster               → Filter: Tanggal + Klaster. Tabel: No | Nomor Antrian | Nama Pasien | Keluhan
 * - pendaftaran_antrian_klaster   → Filter: Periode (tanggal dari–sampai) + Klaster. Tabel: No | Tanggal Kunjungan | Nomor Antrian | Nama Pasien | Keluhan | Sumber Pendaftaran | Status Antrian
 *
 * UNTUK MENAMBAH JENIS LAPORAN BARU:
 * 1. Di bawah: tambah nilai baru di in_array($jenis, ['data_pasien', 'antrian_klaster', 'pendaftaran_antrian_klaster', 'JENIS_BARU'])
 * 2. Di try: tambah elseif ($jenis === 'JENIS_BARU') { ... query & isi $data_laporan }
 * 3. Di HTML: tambah elseif untuk judul, filter (jika perlu), dan tabel sesuai kolom laporan baru
 * 4. Di cetak_laporan.php: tambah penanganan jenis yang sama
 */

session_start();
require_once '../../config/koneksi.php';

// ============================================
// CEK AUTENTIKASI DAN AUTHORIZATION
// ============================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../login.php');
    exit;
}

$jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : 'data_pasien';
if (!in_array($jenis, ['data_pasien', 'antrian_klaster', 'pendaftaran_antrian_klaster', 'rekapitulasi'])) {
    $jenis = 'data_pasien';
}

$error = '';
$data_laporan = [];
$klaster_list = [];
$tanggal_filter = date('Y-m-d');
$klaster_filter = 'semua';
$tanggal_awal = date('Y-m-01');   // untuk pendaftaran_antrian_klaster: awal periode (default: awal bulan ini)
$tanggal_akhir = date('Y-m-d');   // untuk pendaftaran_antrian_klaster: akhir periode (default: hari ini)
$bulan_filter = '';               // untuk pendaftaran_antrian_klaster: YYYY-MM jika filter per bulan

function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == null) return '-';
    return date('d/m/Y', strtotime($tanggal));
}

try {
    // --- LAPORAN 1: Data Pasien (semua dari tabel pasien) ---
    if ($jenis === 'data_pasien') {
        $stmt = $koneksi->prepare("
            SELECT 
                id, nama, nik, jenis_kelamin, tanggal_lahir, umur, no_hp,
                is_pasien_baru, is_bpjs
            FROM pasien
            ORDER BY nama ASC
        ");
        $stmt->execute();
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($jenis === 'antrian_klaster') {
        // --- LAPORAN 2: Daftar Antrian Per Klaster (filter tanggal + klaster, kolom: nomor_antrian, nama, keluhan) ---
        // Ambil daftar klaster untuk dropdown filter
        $stmtK = $koneksi->prepare("SELECT id, nama_klaster FROM klaster ORDER BY nama_klaster ASC");
        $stmtK->execute();
        $klaster_list = $stmtK->fetchAll(PDO::FETCH_ASSOC);

        $tanggal_filter = isset($_GET['tanggal']) && trim($_GET['tanggal']) !== '' ? trim($_GET['tanggal']) : date('Y-m-d');
        $klaster_filter = isset($_GET['klaster_id']) ? trim($_GET['klaster_id']) : 'semua';
        if ($klaster_filter !== 'semua' && !ctype_digit($klaster_filter)) $klaster_filter = 'semua';

        $params = [$tanggal_filter];
        $where_klaster = '';
        if ($klaster_filter !== 'semua') {
            $where_klaster = ' AND a.klaster_id = ?';
            $params[] = (int)$klaster_filter;
        }

        $sql = "
            SELECT 
                a.nomor_antrian,
                COALESCE(p.nama, a.nama_manual) AS nama,
                a.keluhan
            FROM antrian a
            LEFT JOIN pasien p ON a.pasien_id = p.id
            INNER JOIN klaster k ON a.klaster_id = k.id
            WHERE a.tanggal_kunjungan = ?" . $where_klaster . "
            ORDER BY k.nama_klaster ASC, a.nomor_antrian ASC, a.created_at ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute($params);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($jenis === 'pendaftaran_antrian_klaster') {
        // --- LAPORAN 3: Pendaftaran Antrian per Klaster (filter periode tanggal dari–sampai + klaster, 7 kolom) ---
        $stmtK = $koneksi->prepare("SELECT id, nama_klaster FROM klaster ORDER BY nama_klaster ASC");
        $stmtK->execute();
        $klaster_list = $stmtK->fetchAll(PDO::FETCH_ASSOC);

        // Filter periode: bisa pakai bulan (YYYY-MM) atau tanggal_awal/tanggal_akhir
        $bulan_filter = isset($_GET['bulan']) && preg_match('/^\d{4}-\d{2}$/', trim($_GET['bulan'])) ? trim($_GET['bulan']) : '';
        if ($bulan_filter !== '') {
            $tanggal_awal = $bulan_filter . '-01';
            $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
        } else {
            $tanggal_awal = isset($_GET['tanggal_awal']) && trim($_GET['tanggal_awal']) !== '' ? trim($_GET['tanggal_awal']) : date('Y-m-01');
            $tanggal_akhir = isset($_GET['tanggal_akhir']) && trim($_GET['tanggal_akhir']) !== '' ? trim($_GET['tanggal_akhir']) : date('Y-m-d');
        }
        $klaster_filter = isset($_GET['klaster_id']) ? trim($_GET['klaster_id']) : 'semua';
        if ($klaster_filter !== 'semua' && !ctype_digit($klaster_filter)) $klaster_filter = 'semua';

        $params = [$tanggal_awal, $tanggal_akhir];
        $where_klaster = '';
        if ($klaster_filter !== 'semua') {
            $where_klaster = ' AND a.klaster_id = ?';
            $params[] = (int)$klaster_filter;
        }

        $sql = "
            SELECT 
                a.tanggal_kunjungan,
                a.nomor_antrian,
                COALESCE(p.nama, a.nama_manual) AS nama,
                a.keluhan,
                COALESCE(a.sumber, 'Online') AS sumber_pendaftaran,  /* Offline=manual, Online=daftar via sistem */
                a.status
            FROM antrian a
            LEFT JOIN pasien p ON a.pasien_id = p.id
            INNER JOIN klaster k ON a.klaster_id = k.id
            WHERE a.tanggal_kunjungan BETWEEN ? AND ?" . $where_klaster . "
            ORDER BY a.tanggal_kunjungan ASC, k.nama_klaster ASC, a.nomor_antrian ASC, a.created_at ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute($params);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($jenis === 'rekapitulasi') {
        // --- LAPORAN 4: Rekapitulasi per klaster (jumlah Antri, Selesai, Batal). Filter: periode bulan ---
        $stmtK = $koneksi->prepare("SELECT id, nama_klaster FROM klaster ORDER BY nama_klaster ASC");
        $stmtK->execute();
        $klaster_list = $stmtK->fetchAll(PDO::FETCH_ASSOC);

        $bulan_filter = isset($_GET['bulan']) && preg_match('/^\d{4}-\d{2}$/', trim($_GET['bulan'])) ? trim($_GET['bulan']) : '';
        if ($bulan_filter !== '') {
            $tanggal_awal = $bulan_filter . '-01';
            $tanggal_akhir = date('Y-m-t', strtotime($tanggal_awal));
        } else {
            $tanggal_awal = date('Y-m-01');
            $tanggal_akhir = date('Y-m-d');
        }

        /* Hanya status Selesai dan Batal yang masuk laporan; Menunggu dan Dipanggil tidak dihitung. Antri = total (selesai + batal). */
        $sql = "
            SELECT
                k.nama_klaster AS klaster,
                COUNT(a.klaster_id) AS antri,
                SUM(CASE WHEN LOWER(a.status) = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN LOWER(a.status) = 'batal' THEN 1 ELSE 0 END) AS batal
            FROM klaster k
            LEFT JOIN antrian a ON a.klaster_id = k.id
                AND a.tanggal_kunjungan BETWEEN ? AND ?
                AND LOWER(a.status) IN ('selesai','batal')
            GROUP BY k.id, k.nama_klaster
            ORDER BY k.nama_klaster ASC
        ";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$tanggal_awal, $tanggal_akhir]);
        $data_laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Error mengambil data laporan: " . $e->getMessage();
}

$page_title = $jenis === 'data_pasien' ? 'Laporan Data Pasien' : ($jenis === 'antrian_klaster' ? 'Laporan Daftar Antrian Pasien Per Klaster' : ($jenis === 'rekapitulasi' ? 'REKAPITULASI PENDAFTARAN ANTRIAN SELURUH KLASTER' : 'Pendaftaran Antrian per Klaster'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | SIPAS</title>
    <link href="../../assets/css/utilities.css" rel="stylesheet">
    <link href="../../assets/css/admin/header_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/dashboard_admin.css" rel="stylesheet">
    <link href="../../assets/css/admin/table_common.css" rel="stylesheet">
    <link href="../../assets/css/admin/laporan.css" rel="stylesheet">
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

            <div class="card" id="laporanContent">
                <h2 style="margin-top: 0;"><?php echo htmlspecialchars($page_title); ?></h2>

                <?php if ($jenis === 'antrian_klaster'): ?>
                <div class="laporan-filter-bar">
                    <form method="get" action="" class="laporan-filter-form">
                        <input type="hidden" name="jenis" value="antrian_klaster">
                        <div class="filter-form-inline filter-form-laporan">
                            <div class="filter-group-inline">
                                <label>Tanggal</label>
                                <input type="date" name="tanggal" value="<?php echo htmlspecialchars($tanggal_filter); ?>" class="filter-search-input" onchange="this.form.submit()">
                            </div>
                            <div class="filter-group-inline">
                                <label>Klaster</label>
                                <select name="klaster_id" class="filter-select" onchange="this.form.submit()">
                                    <option value="semua"<?php echo $klaster_filter === 'semua' ? ' selected' : ''; ?>>Semua</option>
                                    <?php foreach ($klaster_list as $kl): ?>
                                        <option value="<?php echo (int)$kl['id']; ?>"<?php echo $klaster_filter === (string)$kl['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($kl['nama_klaster']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="laporan-cetak-wrap"><button type="button" class="btn btn-primary" onclick="cetakLaporan()">Cetak Laporan</button></div>
                </div>
                <?php endif; ?>
                <?php if ($jenis === 'pendaftaran_antrian_klaster'): ?>
                <div class="laporan-filter-bar">
                    <form method="get" action="" class="laporan-filter-form">
                        <input type="hidden" name="jenis" value="pendaftaran_antrian_klaster">
                        <div class="filter-form-inline filter-form-laporan">
                            <div class="filter-group-inline">
                                <label>Periode Bulan</label>
                                <input type="month" name="bulan" value="<?php echo htmlspecialchars($bulan_filter !== '' ? $bulan_filter : date('Y-m')); ?>" class="filter-search-input" onchange="this.form.submit()">
                            </div>
                            <div class="filter-group-inline">
                                <label>Klaster</label>
                                <select name="klaster_id" class="filter-select" onchange="this.form.submit()">
                                    <option value="semua"<?php echo $klaster_filter === 'semua' ? ' selected' : ''; ?>>Semua</option>
                                    <?php foreach ($klaster_list as $kl): ?>
                                        <option value="<?php echo (int)$kl['id']; ?>"<?php echo $klaster_filter === (string)$kl['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($kl['nama_klaster']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="laporan-cetak-wrap"><button type="button" class="btn btn-primary" onclick="cetakLaporan()">Cetak Laporan</button></div>
                </div>
                <?php endif; ?>
                <?php if ($jenis === 'rekapitulasi'): ?>
                <div class="laporan-filter-bar">
                    <form method="get" action="" class="laporan-filter-form">
                        <input type="hidden" name="jenis" value="rekapitulasi">
                        <div class="filter-form-inline filter-form-laporan">
                            <div class="filter-group-inline">
                                <label>Periode Bulan</label>
                                <input type="month" name="bulan" value="<?php echo htmlspecialchars($bulan_filter !== '' ? $bulan_filter : date('Y-m')); ?>" class="filter-search-input" onchange="this.form.submit()">
                            </div>
                        </div>
                    </form>
                    <div class="laporan-cetak-wrap"><button type="button" class="btn btn-primary" onclick="cetakLaporan()">Cetak Laporan</button></div>
                </div>
                <?php endif; ?>

                <?php if ($jenis === 'data_pasien'): ?>
                <div class="laporan-cetak-only" style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-primary" onclick="cetakLaporan()">Cetak Laporan</button>
                </div>
                <?php endif; ?>

                <div class="screen-content">
                    <?php if (empty($data_laporan)): ?>
                        <div class="empty-state">
                            <p><?php
                                if ($jenis === 'data_pasien') echo 'Tidak ada data pasien.';
                                elseif ($jenis === 'antrian_klaster') echo 'Tidak ada data antrian untuk filter yang dipilih.';
                                elseif ($jenis === 'rekapitulasi') echo 'Tidak ada data rekapitulasi untuk periode yang dipilih.';
                                else echo 'Tidak ada data pendaftaran antrian untuk periode yang dipilih.';
                            ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <?php if ($jenis === 'data_pasien'): ?>
                            <!-- TABEL LAPORAN 1: Data Pasien (8 kolom). NIK & No HP rata tengah. -->
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Nama Pasien</th>
                                        <th class="text-center">NIK</th>  <!-- Rata tengah -->
                                        <th class="text-center">Jenis Kelamin</th>
                                        <th class="text-center">Tgl Lahir / Umur</th>
                                        <th class="text-center">No HP</th>
                                        <th class="text-center">Status Pasien</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($data_laporan as $item):
                                        $tgl_lahir = $item['tanggal_lahir'] ?? '';
                                        $umur = $item['umur'] ?? '';
                                        $tgl_umur = '';
                                        if (!empty($tgl_lahir)) {
                                            $tgl_umur = formatTanggal($tgl_lahir);
                                            if ($umur !== '' && $umur !== null) $tgl_umur .= ' / ' . (int)$umur . ' th';
                                        } elseif ($umur !== '' && $umur !== null) {
                                            $tgl_umur = (int)$umur . ' tahun';
                                        } else {
                                            $tgl_umur = '-';
                                        }
                                        $jk = $item['jenis_kelamin'] ?? '';
                                        if ($jk == 'L' || strtoupper($jk) == 'LAKI-LAKI') $jk_text = 'Laki-laki';
                                        elseif ($jk == 'P' || strtoupper($jk) == 'PEREMPUAN') $jk_text = 'Perempuan';
                                        else $jk_text = !empty($jk) ? htmlspecialchars($jk) : '-';
                                        $status_pasien = !empty($item['is_pasien_baru']) ? 'Pasien Baru' : 'Pasien Lama';
                                    ?>
                                        <tr>
                                            <td class="text-center" data-label="No"><?php echo $no++; ?></td>
                                            <td data-label="Nama Pasien"><strong><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></strong></td>
                                            <td class="text-center" data-label="NIK"><?php echo htmlspecialchars($item['nik'] ?? '-'); ?></td>
                                            <td class="text-center" data-label="Jenis Kelamin"><?php echo $jk_text; ?></td>
                                            <td class="text-center" data-label="Tgl Lahir/Umur"><?php echo $tgl_umur; ?></td>
                                            <td class="text-center" data-label="No HP"><?php echo htmlspecialchars($item['no_hp'] ?? '-'); ?></td>
                                            <td class="text-center" data-label="Status Pasien"><?php echo $status_pasien; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php elseif ($jenis === 'antrian_klaster'): ?>
                            <!-- TABEL LAPORAN 2: Daftar Antrian Per Klaster (4 kolom: No, Nomor Antrian, Nama Pasien, Keluhan) -->
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Nomor Antrian</th>
                                        <th>Nama Pasien</th>
                                        <th>Keluhan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($data_laporan as $item): ?>
                                        <tr>
                                            <td class="text-center" data-label="No"><?php echo $no++; ?></td>
                                            <td class="text-center" data-label="Nomor Antrian"><?php echo !empty($item['nomor_antrian']) ? htmlspecialchars($item['nomor_antrian']) : '-'; ?></td>
                                            <td data-label="Nama Pasien"><strong><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></strong></td>
                                            <td data-label="Keluhan"><?php echo htmlspecialchars($item['keluhan'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php elseif ($jenis === 'pendaftaran_antrian_klaster'): ?>
                            <!-- TABEL LAPORAN 3: Pendaftaran Antrian per Klaster. Kolom Sumber Pendaftaran = Offline/Online. -->
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Tanggal Kunjungan</th>
                                        <th class="text-center">Nomor Antrian</th>
                                        <th>Nama Pasien</th>
                                        <th>Keluhan</th>
                                        <th class="text-center">Sumber Pendaftaran</th>
                                        <th class="text-center">Status Antrian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    foreach ($data_laporan as $item):
                                        /* Sumber pendaftaran: Offline (manual) atau Online (via sistem) */
                                        $sumber_pendaftaran = !empty($item['sumber_pendaftaran']) ? ucfirst(strtolower(trim($item['sumber_pendaftaran']))) : 'Online';
                                        $status_antrian = $item['status'] ?? '-';
                                        if (strtolower($status_antrian) === 'sedang_dilayani') $status_antrian = 'Dipanggil';
                                        else $status_antrian = ucfirst($status_antrian);
                                    ?>
                                        <tr>
                                            <td class="text-center" data-label="No"><?php echo $no++; ?></td>
                                            <td class="text-center" data-label="Tanggal Kunjungan"><?php echo formatTanggal($item['tanggal_kunjungan'] ?? ''); ?></td>
                                            <td class="text-center" data-label="Nomor Antrian"><?php echo !empty($item['nomor_antrian']) ? htmlspecialchars($item['nomor_antrian']) : '-'; ?></td>
                                            <td data-label="Nama Pasien"><strong><?php echo htmlspecialchars($item['nama'] ?? '-'); ?></strong></td>
                                            <td data-label="Keluhan"><?php echo htmlspecialchars($item['keluhan'] ?? '-'); ?></td>
                                            <td class="text-center" data-label="Sumber Pendaftaran"><?php echo htmlspecialchars($sumber_pendaftaran); ?></td>
                                            <td class="text-center" data-label="Status Antrian"><?php echo htmlspecialchars($status_antrian); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php elseif ($jenis === 'rekapitulasi'): ?>
                            <!-- TABEL REKAPITULASI: satu baris header agar rapi, tidak tumpang tindih -->
                            <table class="data-table table-rekapitulasi">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th>Klaster</th>
                                        <th class="text-center"> Antri</th>
                                        <th class="text-center">Selesai</th>
                                        <th class="text-center">Batal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($data_laporan as $row): ?>
                                        <tr>
                                            <td class="text-center" data-label="No"><?php echo $no++; ?></td>
                                            <td data-label="Klaster"><?php echo htmlspecialchars($row['klaster'] ?? '-'); ?></td>
                                            <td class="text-center" data-label="Antri"><?php echo (int)($row['antri'] ?? 0); ?></td>
                                            <td class="text-center" data-label="Selesai"><?php echo (int)($row['selesai'] ?? 0); ?></td>
                                            <td class="text-center" data-label="Batal"><?php echo (int)($row['batal'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <?php if ($jenis !== 'data_pasien'): ?>
                        <div style="margin-top: 20px; color: #666; font-size: 14px;">
                            <p>Total: <strong><?php echo count($data_laporan); ?></strong> <?php echo $jenis === 'rekapitulasi' ? 'klaster.' : 'antrian.'; ?>
                            <?php if ($jenis === 'antrian_klaster'): ?>
                                <span style="color: var(--primary);"> | Tanggal: <?php echo formatTanggal($tanggal_filter); ?></span>
                            <?php elseif ($jenis === 'pendaftaran_antrian_klaster' || $jenis === 'rekapitulasi'): ?>
                                <?php
                                    $nama_bulan = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
                                    if ($bulan_filter !== '') {
                                        $p = explode('-', $bulan_filter);
                                        $periode_show = ($nama_bulan[$p[1]] ?? $p[1]) . ' ' . $p[0];
                                    } else {
                                        $periode_show = ($nama_bulan[date('m', strtotime($tanggal_awal))] ?? date('m', strtotime($tanggal_awal))) . ' ' . date('Y', strtotime($tanggal_awal));
                                    }
                                ?>
                                <span style="color: var(--primary);"> | Periode: <?php echo htmlspecialchars($periode_show); ?></span>
                            <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/sidebar.js"></script>
    <script>
        function cetakLaporan() {
            var url = 'cetak_laporan.php?jenis=<?php echo urlencode($jenis); ?>';
            <?php if ($jenis === 'antrian_klaster'): ?>
            url += '&tanggal=<?php echo urlencode($tanggal_filter); ?>&klaster_id=<?php echo urlencode($klaster_filter); ?>';
            <?php elseif ($jenis === 'pendaftaran_antrian_klaster'): ?>
            url += '&tanggal_awal=<?php echo urlencode($tanggal_awal); ?>&tanggal_akhir=<?php echo urlencode($tanggal_akhir); ?>&klaster_id=<?php echo urlencode($klaster_filter); ?>';
            url += '&bulan=<?php echo urlencode($bulan_filter !== '' ? $bulan_filter : date('Y-m', strtotime($tanggal_awal))); ?>';
            <?php elseif ($jenis === 'rekapitulasi'): ?>
            url += '&bulan=<?php echo urlencode($bulan_filter !== '' ? $bulan_filter : date('Y-m', strtotime($tanggal_awal))); ?>';
            <?php endif; ?>
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
