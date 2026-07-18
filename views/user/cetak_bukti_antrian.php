<?php
/**
 * FILE: views/user/cetak_bukti_antrian.php
 * FUNGSI: Halaman cetak bukti antrian pasien (ticket) dengan kop Puskesmas.
 *
 * PARAMETER:
 * - id_antrian: ID antrian yang akan dicetak
 *
 * AKSES:
 * - Harus login
 * - Bukan admin
 * - Hanya boleh mencetak antrian milik sendiri
 */

session_start();
require_once '../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    header('Location: ../admin/dashboard_admin.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ============================================
// VALIDASI ID ANTRIAN
// ============================================
// Ambil ID antrian dari parameter GET dan validasi
$id_antrian_raw = $_GET['id_antrian'] ?? '';
$id_antrian = (int)$id_antrian_raw;

if ($id_antrian <= 0) {
    die('Error: ID antrian tidak valid.');
}

// ============================================
// FUNGSI HELPER: FORMAT TANGGAL
// ============================================
/**
 * Fungsi untuk format tanggal menjadi format Indonesia (dd-mm-yyyy)
 * 
 * @param string $tanggal - String tanggal (format Y-m-d)
 * @return string - Tanggal yang sudah diformat (dd-mm-yyyy) atau '-' jika kosong
 */
function formatTanggal($tanggal) {
    if (empty($tanggal)) return '-';
    return date('d-m-Y', strtotime($tanggal));
}

/**
 * Fungsi untuk format tanggal dengan nama hari (Hari, dd-mm-yyyy)
 * 
 * @param string $tanggal - String tanggal (format Y-m-d)
 * @return string - Tanggal dengan nama hari (Hari, dd-mm-yyyy) atau '-' jika kosong
 */
function formatTanggalHari($tanggal) {
    if (empty($tanggal)) return '-';
    $hari = [
        'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'
    ];
    $w = (int)date('w', strtotime($tanggal));
    return ($hari[$w] ?? '-') . ', ' . date('d-m-Y', strtotime($tanggal));
}

// ============================================
// AMBIL DATA ANTRIAN DARI DATABASE
// ============================================
// Ambil data antrian lengkap untuk dicetak
// Validasi bahwa antrian milik user yang login
// ============================================
// AMBIL DATA ANTRIAN DARI DATABASE
// ============================================
// Ambil data antrian lengkap untuk dicetak
// Validasi bahwa antrian milik user yang login
$data = null;
$error = '';

try {
    // Query untuk mengambil data antrian (Klaster, Hari/Tanggal, Keluhan, Nomor antrian, Nama, NIK, Status)
    $stmt = $koneksi->prepare("
        SELECT
            a.id_antrian,
            a.nomor_antrian,
            a.keluhan,
            a.tanggal_kunjungan,
            a.status,
            a.created_at as waktu_daftar,
            k.nama_klaster,
            COALESCE(p.nama, a.nama_manual) as nama,
            COALESCE(p.nik, a.nik_manual) as nik
        FROM antrian a
        INNER JOIN pasien p ON a.pasien_id = p.id
        INNER JOIN klaster k ON a.klaster_id = k.id
        WHERE a.id_antrian = ? AND p.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$id_antrian, $user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validasi bahwa antrian ditemukan dan milik user yang login
    if (!$data) {
        $error = 'Data antrian tidak ditemukan atau tidak memiliki akses.';
    }
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}

if ($error || !$data) {
    die($error ?: 'Data antrian tidak ditemukan.');
}

$nomor = $data['nomor_antrian'] ?? null;
$nomorText = !empty($nomor) ? $nomor : '-';

$keluhan = trim((string)($data['keluhan'] ?? ''));
$keluhanText = $keluhan !== '' ? $keluhan : '-';

$tanggalHari = formatTanggalHari($data['tanggal_kunjungan'] ?? '');

$status = $data['status'] ?? '';
$statusText = $status ? ucfirst(strtolower($status)) : '-';
if (strtolower($status) === 'sedang_dilayani') $statusText = 'Dipanggil';

$waktuCetak = date('d-m-Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bukti Antrian | SIPAS</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #fff;
        }
        .no-print { display: block; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }

        .page {
            max-width: 21cm;
            margin: 0 auto;
            padding: 1cm;
        }

        /* Kop */
        .kop {
            border: 1px solid #111827;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .kop-header {
            display: grid;
            grid-template-columns: 70px 1fr 70px;
            gap: 10px;
            align-items: center;
        }
        .kop-logo {
            display: grid;
            place-items: center;
        }
        .kop-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .kop-center {
            text-align: center;
            line-height: 1.2;
        }
        .kop-title {
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .kop-subtitle {
            font-size: 12px;
            margin-top: 3px;
        }
        .kop-line {
            border-top: 2px solid #111827;
            margin-top: 10px;
        }

        .doc-title {
            text-align: center;
            margin: 14px 0 12px 0;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* Ticket */
        .ticket {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }
        .ticket-top {
            padding: 14px 14px 6px 14px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 7px 0;
            font-size: 13px;
        }
        .left { color: #374151; font-weight: 700; }
        .right { text-align: right; font-weight: 600; max-width: 60%; word-break: break-word; }
        .divider { height: 1px; background: #e5e7eb; }
        .center {
            padding: 16px 14px 10px 14px;
            text-align: center;
        }
        .center-label {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .number {
            font-size: 56px;
            font-weight: 900;
            color: #2563eb;
            line-height: 1;
        }
        .ticket-bottom {
            padding: 12px 14px 14px 14px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0;
            font-size: 13px;
        }
        .field-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .field-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .field-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 0;
        }
        .field-value {
            font-weight: 600;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        .meta {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e5e7eb;
            color: #6b7280;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .actions {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            border: 1px solid #e5e7eb;
            color: #111827;
            background: #fff;
        }
        .btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
</head>
<body>
    <div class="page">
        <div class="actions no-print">
            <a class="btn" href="daftar_user.php">Kembali</a>
            <a class="btn btn-primary" href="#" onclick="window.print(); return false;">Cetak</a>
        </div>

        <div class="kop">
            <div class="kop-header">
                <div class="kop-logo">
                    <img src="../../assets/images/logosjj.png" alt="Logo" />
                </div>
                <div class="kop-center">
                    <div class="kop-title">Puskesmas Sijunjung</div>
                    <div class="kop-subtitle">Jl. Puskesmas No. 85 Telp (0754) 20053 Sijunjung 27553</div>
                </div>
                <div class="kop-logo">
                    <img src="../../assets/images/logo.png" alt="Logo Puskesmas" />
                </div>
            </div>
            <div class="kop-line"></div>
        </div>

        <div class="doc-title">
            <h2>Bukti Antrian</h2>
        </div>

        <div class="ticket">
            <div class="ticket-top">
                <div class="row">
                    <div class="left">Klaster</div>
                    <div class="right"><?php echo htmlspecialchars($data['nama_klaster'] ?? '-'); ?></div>
                </div>
                <div class="row">
                    <div class="left">Hari/Tanggal Kunjungan</div>
                    <div class="right"><?php echo htmlspecialchars($tanggalHari); ?></div>
                </div>
                <div class="row">
                    <div class="left">Keluhan</div>
                    <div class="right"><?php echo htmlspecialchars($keluhanText); ?></div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="center">
                <div class="center-label">Nomor Antrean</div>
                <div class="number"><?php echo htmlspecialchars($nomorText); ?></div>
            </div>

            <div class="ticket-bottom">
                <div class="grid">
                    <div class="field-row">
                        <div class="field-label">Nama</div>
                        <div class="field-value"><?php echo htmlspecialchars($data['nama'] ?? '-'); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">NIK</div>
                        <div class="field-value"><?php echo htmlspecialchars($data['nik'] ?? '-'); ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field-label">Status Antrian</div>
                        <div class="field-value"><?php echo htmlspecialchars($statusText); ?></div>
                    </div>
                </div>

                <div class="meta">
                    <span><strong>Waktu Pendaftaran:</strong> <?php echo !empty($data['waktu_daftar']) ? date('d-m-Y H:i', strtotime($data['waktu_daftar'])) : '-'; ?></span>
                    <span><strong>Dicetak:</strong> <?php echo htmlspecialchars($waktuCetak); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto print saat halaman dibuka
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>

