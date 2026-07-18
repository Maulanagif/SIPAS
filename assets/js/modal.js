/**
 * FILE: assets/js/modal.js
 * FUNGSI: Menampilkan dan mengelola modal detail antrian/riwayat (tiket antrian).
 *
 * DIGUNAKAN DI:
 * - views/user/riwayat_antrian.php (modal detail saat user klik baris antrian)
 * - views/user/daftar_antrian.php (modal detail saat user klik baris antrian)
 *
 * FITUR:
 * - formatDate: format tanggal ke dd/mm/yyyy (Indonesia)
 * - formatDateTime: format tanggal + waktu ke dd/mm/yyyy HH:mm (Indonesia)
 * - getStatusBadge: badge HTML untuk status antrian (Menunggu, Diproses, Selesai, Batal)
 * - escapeHtml / safeText: escape string untuk mencegah XSS di konten modal
 * - showDetail(data): isi modal dengan data antrian (Klaster, Hari/Tanggal kunjungan, Keluhan, Nomor antrian, Nama, NIK, Status Antrian) + tombol Cetak dan Batalkan
 * - closeModal(): sembunyikan modal; tutup juga saat klik backdrop (luar konten)
 */

/**
 * Format tanggal menjadi format Indonesia (dd/mm/yyyy).
 * Dipakai untuk tanggal kunjungan dan tanggal pendaftaran di tampilan modal.
 *
 * @param {string} dateString - String tanggal (format ISO atau Y-m-d)
 * @returns {string} - Tanggal yang sudah diformat (contoh: 01/02/2026)
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Format tanggal dan waktu menjadi format Indonesia (dd/mm/yyyy HH:mm).
 * Dipakai untuk waktu pendaftaran di modal detail antrian.
 *
 * @param {string} dateString - String tanggal dan waktu (format ISO/datetime)
 * @returns {string} - Tanggal dan waktu yang sudah diformat (contoh: 01/02/2026 14:30)
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Membuat badge status berdasarkan status antrian (untuk tampilan di tabel atau modal).
 * Pemetaan: menunggu → Menunggu (badge-pending), dipanggil/sedang_dilayani → Diproses (badge-proses),
 * selesai → Selesai (badge-selesai), batal → Batal (badge-ditolak).
 *
 * @param {string} status - Status antrian (lowercase atau asli dari database)
 * @returns {string} - HTML span dengan class badge + kelas warna (badge-pending, badge-proses, dll), atau teks status asli jika tidak dikenali
 */
function getStatusBadge(status) {
    if (status == 'menunggu') {
        return '<span class="badge badge-pending">Menunggu</span>';
    } else if (status == 'dipanggil' || status == 'sedang_dilayani') {
        return '<span class="badge badge-proses">Diproses</span>';
    } else if (status == 'selesai') {
        return '<span class="badge badge-selesai">Selesai</span>';
    } else if (status == 'batal') {
        return '<span class="badge badge-ditolak">Batal</span>';
    }
    return status;
}

/**
 * Escape HTML sederhana untuk mencegah XSS saat menyisipkan data ke innerHTML modal.
 * Mengubah & < > " ' menjadi entity HTML (&amp; &lt; &gt; &quot; &#039;).
 *
 * @param {string} str - String yang akan di-escape (akan dikonversi ke string)
 * @returns {string} - String yang sudah aman untuk dimasukkan ke atribut atau teks HTML
 */
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')    // & menjadi &amp;
        .replace(/</g, '&lt;')     // < menjadi &lt;
        .replace(/>/g, '&gt;')     // > menjadi &gt;
        .replace(/"/g, '&quot;')   // " menjadi &quot;
        .replace(/'/g, '&#039;');  // ' menjadi &#039;
}

/**
 * Helper: teks aman untuk ditampilkan di modal (escape HTML + fallback jika kosong).
 * Dipakai untuk semua field data antrian (nama, NIK, keluhan, dll) agar null/undefined/kosong tampil sebagai '-' dan nilai lain di-escape.
 *
 * @param {*} value - Nilai yang akan dicek dan di-escape (bisa string, number, null, undefined)
 * @param {string} fallback - Nilai default jika value kosong/null/undefined (default: '-')
 * @returns {string} - Teks yang sudah di-escape atau fallback
 */
function safeText(value, fallback = '-') {
    if (value === null || value === undefined) return fallback;
    const text = String(value).trim();
    return text.length ? escapeHtml(text) : fallback;
}

/**
 * Menampilkan modal detail antrian/riwayat (tiket antrian).
 * Mengisi #modalBody dengan HTML tiket: Klaster, Hari/Tanggal kunjungan, Keluhan, Nomor antrian, Nama, NIK, Status Antrian.
 *
 * @param {Object} data - Data antrian (nama_klaster, tanggal_kunjungan, keluhan, nomor_antrian, nama, nik, status, id, dll)
 */
function showDetail(data) {
    const modal = document.getElementById('detailModal');
    const modalBody = document.getElementById('modalBody');

    if (!modal || !modalBody) {
        console.error('Modal element tidak ditemukan');
        return;
    }

    /* Format Hari/Tanggal kunjungan (misal: Senin, 08/02/2026) */
    let hariTanggalText = '-';
    if (data.tanggal_kunjungan) {
        const d = new Date(data.tanggal_kunjungan);
        hariTanggalText = d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    const statusLower = String(data.status || '').toLowerCase();
    const nomorAntrianText = safeText(data.nomor_antrian, '-');
    const statusBadgeHtml = getStatusBadge(statusLower);

    /* Link cetak bukti antrian (hanya jika id antrian ada) */
    const printHref = data.id ? `cetak_bukti_antrian.php?id_antrian=${encodeURIComponent(String(data.id))}` : '';

    /* Tombol Batalkan hanya ditampilkan jika status Menunggu/Dipanggil/Sedang dilayani dan id antrian ada */
    const canCancel = (statusLower === 'menunggu' || statusLower === 'dipanggil' || statusLower === 'sedang_dilayani') && data.id;
    const cancelHtml = canCancel
        ? `
            <form method="post" action="batalkan_antrian.php" onsubmit="return confirm('Batalkan antrian ini?');" class="queue-ticket-cancel-form">
                <input type="hidden" name="id_antrian" value="${escapeHtml(String(data.id))}">
                <button type="submit" class="btn btn-danger queue-ticket-cancel-btn">Batalkan</button>
            </form>
          `
        : '';

    modalBody.innerHTML = `
        <div class="queue-ticket">
            <div class="queue-ticket-top">
                <div class="queue-ticket-row">
                    <div class="queue-ticket-left">
                        <span class="queue-ticket-icon">🏥</span>
                        <span class="queue-ticket-label">Klaster</span>
                    </div>
                    <div class="queue-ticket-right">${safeText(data.nama_klaster)}</div>
                </div>
                <div class="queue-ticket-row">
                    <div class="queue-ticket-left">
                        <span class="queue-ticket-icon">📅</span>
                        <span class="queue-ticket-label">Hari/Tanggal Kunjungan</span>
                    </div>
                    <div class="queue-ticket-right">${hariTanggalText}</div>
                </div>
                <div class="queue-ticket-row">
                    <div class="queue-ticket-left">
                        <span class="queue-ticket-icon">📝</span>
                        <span class="queue-ticket-label">Keluhan</span>
                    </div>
                    <div class="queue-ticket-right">${safeText(data.keluhan)}</div>
                </div>
            </div>

            <div class="queue-ticket-divider"></div>

            <div class="queue-ticket-center">
                <div class="queue-ticket-center-label">Nomor Antrean</div>
                <div class="queue-ticket-number">${nomorAntrianText}</div>
            </div>

            <div class="queue-ticket-bottom">
                <div class="queue-ticket-bottom-grid">
                    <div class="queue-ticket-field">
                        <div class="queue-ticket-field-label">Nama</div>
                        <div class="queue-ticket-field-value">${safeText(data.nama)}</div>
                    </div>
                    <div class="queue-ticket-field">
                        <div class="queue-ticket-field-label">NIK</div>
                        <div class="queue-ticket-field-value">${safeText(data.nik)}</div>
                    </div>
                    <div class="queue-ticket-field">
                        <div class="queue-ticket-field-label">Status Antrian</div>
                        <div class="queue-ticket-field-value">${statusBadgeHtml}</div>
                    </div>
                </div>
            </div>

            <div class="queue-ticket-actions">
                ${printHref ? `<a class="btn btn-secondary queue-ticket-print-btn" href="${printHref}" target="_blank" rel="noopener">Cetak</a>` : ''}
                ${cancelHtml}
            </div>
        </div>
    `;
    
    modal.style.display = 'block';
}

/**
 * Menutup modal detail antrian (sembunyikan overlay).
 * Dipanggil dari tombol tutup atau saat user klik di backdrop (luar konten modal).
 */
function closeModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/* Saat halaman selesai load: tutup modal jika user klik di luar konten modal (backdrop) */
window.addEventListener('DOMContentLoaded', function() {
    window.onclick = function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    };
});

