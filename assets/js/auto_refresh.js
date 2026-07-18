/**
 * FILE: assets/js/auto_refresh.js
 * FUNGSI: Auto refresh data antrian menggunakan AJAX setiap 3 detik
 * 
 * FITUR:
 * - Auto refresh tabel antrian di dashboard user setiap 3 detik
 * - Auto refresh tabel antrian di halaman daftar user setiap 3 detik
 * - Update status antrian secara real-time tanpa reload halaman
 * 
 * CARA KERJA:
 * 1. Menggunakan setInterval untuk refresh setiap 3 detik
 * 2. Menggunakan fetch API untuk mengambil data via AJAX
 * 3. Update DOM dengan data terbaru tanpa reload halaman
 * 
 * PENGGUNAAN:
 * - Di-include di halaman dashboard_user.php dan daftar_user.php
 * - Membutuhkan endpoint AJAX di PHP: ?ajax=1
 */

(function() {
    'use strict';
    
    /**
     * Fungsi helper untuk format tanggal menjadi format Indonesia (dd/mm/yyyy)
     * 
     * @param {string} tanggal - String tanggal (format Y-m-d atau datetime)
     * @returns {string} - Tanggal yang sudah diformat (dd/mm/yyyy) atau '-' jika kosong
     */
    function formatTanggal(tanggal) {
        if (!tanggal) return '-';
        const date = new Date(tanggal);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return day + '/' + month + '/' + year;
    }
    
    /**
     * Fungsi helper untuk format waktu menjadi format HH:mm
     * 
     * @param {string} waktu - String waktu (format HH:mm atau datetime)
     * @returns {string} - Waktu yang sudah diformat (HH:mm) atau '-' jika kosong
     */
    function formatWaktu(waktu) {
        if (!waktu) return '-';
        // Jika sudah format HH:mm, langsung return
        if (typeof waktu === 'string' && /^\d{2}:\d{2}$/.test(waktu)) {
            return waktu;
        }
        // Jika datetime, extract jam dan menit
        const date = new Date(waktu);
        if (isNaN(date.getTime())) {
            return waktu; // Return as is jika tidak bisa di-parse
        }
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return hours + ':' + minutes;
    }
    
    /**
     * Fungsi helper untuk membuat badge status HTML berdasarkan status antrian
     * 
     * @param {string} status - Status antrian (menunggu, dipanggil, selesai, batal, dll)
     * @returns {string} - HTML badge dengan class dan teks sesuai status
     */
    function getStatusBadge(status) {
        const statusLower = status.toLowerCase();
        let badgeClass = 'badge';
        let badgeText = status;
        
        if (statusLower === 'menunggu') {
            badgeClass = 'badge badge-pending';
            badgeText = 'Menunggu';
        } else if (statusLower === 'dipanggil' || statusLower === 'sedang_dilayani') {
            badgeClass = 'badge badge-proses';
            badgeText = 'Dipanggil';
        } else if (statusLower === 'selesai') {
            badgeClass = 'badge badge-selesai';
            badgeText = 'Selesai';
        } else if (statusLower === 'batal') {
            badgeClass = 'badge badge-ditolak';
            badgeText = 'Batal';
        }
        
        return '<span class="' + badgeClass + '">' + badgeText + '</span>';
    }
    
    /**
     * Inisialisasi auto refresh untuk halaman dashboard user (beranda)
     * Refresh tabel antrian setiap 3 detik menggunakan AJAX
     * 
     * CARA KERJA:
     * - Mengambil data dari dashboard_user.php?ajax=1
     * - Update tabel #antrian-table-body dengan data terbaru
     * - Tampilkan/sembunyikan empty state sesuai kondisi
     */
    function initBerandaUserRefresh() {
        const tableBody = document.querySelector('#antrian-table-body');
        if (!tableBody) return;
        
        const emptyState = tableBody.closest('.card').querySelector('.empty-state');
        
        function refreshData() {
            fetch('dashboard_user.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        if (data.data.length === 0) {
                            if (emptyState) emptyState.style.display = 'block';
                            tableBody.innerHTML = '';
                        } else {
                            if (emptyState) emptyState.style.display = 'none';
                            let html = '';
                            data.data.forEach((item, index) => {
                                html += '<tr>';
                                html += '<td>' + (index + 1) + '</td>';
                                html += '<td><strong>' + escapeHtml(item.nama_klaster) + '</strong></td>';
                                html += '<td>';
                                if (item.nomor_antrian) {
                                    html += '<span class="number-badge-small">' + escapeHtml(item.nomor_antrian) + '</span>';
                                } else {
                                    html += '<span style="color: #6b7280; font-style: italic;">Belum diberikan</span>';
                                }
                                html += '</td>';
                                html += '<td>' + formatTanggal(item.tanggal_kunjungan) + '</td>';
                                html += '<td>' + getStatusBadge(item.status) + '</td>';
                                html += '</tr>';
                            });
                            tableBody.innerHTML = html;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                });
        }
        
        // Refresh setiap 3 detik
        setInterval(refreshData, 3000);
        // Refresh sekali saat halaman dimuat
        refreshData();
    }
    
    /**
     * Inisialisasi auto refresh untuk halaman daftar antrian user
     * Refresh tabel antrian setiap 3 detik menggunakan AJAX
     * 
     * CARA KERJA:
     * - Mengambil data dari daftar_user.php?ajax=1
     * - Update tabel #daftar-antrian-table-body dengan data terbaru
     * - Tampilkan/sembunyikan empty state sesuai kondisi
     * - Tampilkan tombol Batalkan untuk antrian yang bisa dibatalkan
     */
    function initDaftarUserRefresh() {
        const tableBody = document.querySelector('#daftar-antrian-table-body');
        if (!tableBody) return;
        
        const emptyState = tableBody.closest('.card').querySelector('.empty-state');
        
        function refreshData() {
            fetch('daftar_user.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        if (data.data.length === 0) {
                            if (emptyState) emptyState.style.display = 'block';
                            tableBody.innerHTML = '';
                        } else {
                            if (emptyState) emptyState.style.display = 'none';
                            let html = '';
                            data.data.forEach((item, index) => {
                                html += '<tr>';
                                html += '<td>' + (index + 1) + '</td>';
                                html += '<td><strong>' + escapeHtml(item.nama_klaster) + '</strong></td>';
                                html += '<td>';
                                if (item.nomor_antrian) {
                                    html += '<span class="number-badge-small">' + escapeHtml(item.nomor_antrian) + '</span>';
                                } else {
                                    html += '<span style="color: #6b7280; font-style: italic;">Belum diberikan</span>';
                                }
                                html += '</td>';
                                html += '<td>' + escapeHtml(item.keluhan || '-') + '</td>';
                                html += '<td>' + formatTanggal(item.tanggal_kunjungan) + '</td>';
                                html += '<td>' + getStatusBadge(item.status) + '</td>';
                                
                                // Kolom Aksi: tampilkan tombol Batalkan jika status Menunggu atau Dipanggil
                                html += '<td>';
                                const statusLower = (item.status || '').toLowerCase();
                                if (statusLower === 'menunggu' || statusLower === 'dipanggil' || statusLower === 'sedang_dilayani') {
                                    html += '<form method="post" action="batalkan_antrian.php" onsubmit="return confirm(\'Batalkan antrian ini?\');" style="display:inline;">';
                                    html += '<input type="hidden" name="id_antrian" value="' + escapeHtml(String(item.id)) + '">';
                                    html += '<button type="submit" class="btn btn-danger btn-sm">Batalkan</button>';
                                    html += '</form>';
                                } else {
                                    html += '<span style="color:#6b7280; font-style: italic;">-</span>';
                                }
                                html += '</td>';

                                // Kolom Detail
                                html += '<td><button class="btn-detail" onclick="showDetail(' + escapeHtml(JSON.stringify(item)) + ')">Detail</button></td>';
                                html += '</tr>';
                            });
                            tableBody.innerHTML = html;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                });
        }
        
        setInterval(refreshData, 3000);
        refreshData();
    }
    
    /**
     * Inisialisasi auto refresh untuk halaman dashboard admin
     * Refresh tabel antrian setiap 3 detik menggunakan AJAX
     * 
     * CARA KERJA:
     * - Mengambil data dari dashboard_admin.php?ajax=1
     * - Update tabel #antrian-admin-table-body dengan data terbaru
     * - Update total antrian di #total-antrian
     * - Tampilkan/sembunyikan empty state sesuai kondisi
     */
    function initBerandaAdminRefresh() {
        const tableBody = document.querySelector('#antrian-admin-table-body');
        const totalInfo = document.getElementById('total-antrian');
        if (!tableBody) return;
        
        const emptyState = tableBody.closest('.card').querySelector('.empty-state');
        
        function refreshData() {
            fetch('dashboard_admin.php?ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        if (data.data.length === 0) {
                            if (emptyState) emptyState.style.display = 'block';
                            tableBody.innerHTML = '';
                            if (totalInfo) {
                                totalInfo.innerHTML = 'Total: <strong>0</strong> antrian';
                            }
                        } else {
                            if (emptyState) emptyState.style.display = 'none';
                            let html = '';
                            data.data.forEach((item, index) => {
                                html += '<tr>';
                                html += '<td class="text-center">' + (index + 1) + '</td>';
                                html += '<td><strong>' + escapeHtml(item.nama_pasien) + '</strong></td>';
                                html += '<td>' + escapeHtml(item.nik || '-') + '</td>';
                                html += '<td>' + escapeHtml(item.nama_klaster) + '</td>';
                                html += '<td>';
                                if (item.waktu_daftar) {
                                    const date = new Date(item.waktu_daftar);
                                    const day = String(date.getDate()).padStart(2, '0');
                                    const month = String(date.getMonth() + 1).padStart(2, '0');
                                    const year = date.getFullYear();
                                    const hours = String(date.getHours()).padStart(2, '0');
                                    const minutes = String(date.getMinutes()).padStart(2, '0');
                                    html += day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ' WIB';
                                } else {
                                    html += '-';
                                }
                                html += '</td>';
                                html += '<td class="text-center">';
                                if (item.nomor_antrian) {
                                    html += '<strong>' + escapeHtml(item.nomor_antrian) + '</strong>';
                                } else {
                                    html += '<span class="text-muted">-</span>';
                                }
                                html += '</td>';
                                html += '<td class="text-center">' + getStatusBadge(item.status) + '</td>';
                                html += '</tr>';
                            });
                            tableBody.innerHTML = html;
                            
                            if (totalInfo) {
                                let totalText = 'Total: <strong>' + data.total + '</strong> antrian';
                                if (data.count < data.total) {
                                    totalText += ' (Menampilkan ' + data.count + ' teratas)';
                                }
                                totalInfo.innerHTML = totalText;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                });
        }
        
        setInterval(refreshData, 3000);
        refreshData();
    }
    
    // Auto refresh untuk kelola antrian admin
    function initKelolaAntrianRefresh() {
        const tableBody = document.querySelector('#kelola-antrian-table-body');
        if (!tableBody) return;
        
        const emptyState = tableBody.closest('.card').querySelector('.empty-state');
        let isFormFocused = false;
        
        // Deteksi jika user sedang mengisi form
        tableBody.addEventListener('focusin', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                isFormFocused = true;
            }
        });
        
        tableBody.addEventListener('focusout', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                // Delay sedikit untuk memastikan user tidak sedang pindah ke field lain
                setTimeout(() => {
                    isFormFocused = false;
                }, 500);
            }
        });
        
        function refreshData() {
            // Jangan refresh jika user sedang mengisi form
            if (isFormFocused) {
                return;
            }
            
            // Simpan nilai input yang sedang diisi sebelum refresh
            const currentInputs = {};
            const currentSelects = {};
            tableBody.querySelectorAll('input[type="number"]').forEach(input => {
                if (input.value) {
                    currentInputs[input.id] = input.value;
                }
            });
            tableBody.querySelectorAll('select').forEach(select => {
                currentSelects[select.id] = select.value;
            });
            
            // Ambil tanggal dari URL atau input filter
            const urlParams = new URLSearchParams(window.location.search);
            const tanggal = urlParams.get('tanggal') || document.querySelector('input[name="tanggal"]')?.value || new Date().toISOString().split('T')[0];
            
            fetch('kelola_antrian.php?ajax=1&tanggal=' + encodeURIComponent(tanggal) + '&_t=' + Date.now())
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        if (data.data.length === 0) {
                            if (emptyState) emptyState.style.display = 'block';
                            tableBody.innerHTML = '';
                        } else {
                            if (emptyState) emptyState.style.display = 'none';
                            let html = '';
                            data.data.forEach((item, index) => {
                                const isSelesai = (item.status === 'Selesai' || item.status === 'selesai');
                                html += '<tr>';
                                html += '<td data-label="No">' + (index + 1) + '</td>';
                                html += '<td data-label="Nama"><strong>' + escapeHtml(item.nama) + '</strong></td>';
                                html += '<td data-label="Klaster">' + escapeHtml(item.nama_klaster) + '</td>';
                                html += '<td data-label="Waktu Daftar">';
                                if (item.waktu_daftar) {
                                    // waktu_daftar adalah datetime, format ke H:i
                                    const date = new Date(item.waktu_daftar);
                                    if (!isNaN(date.getTime())) {
                                        const hours = String(date.getHours()).padStart(2, '0');
                                        const minutes = String(date.getMinutes()).padStart(2, '0');
                                        html += hours + ':' + minutes + ' WIB';
                                    } else {
                                        html += '-';
                                    }
                                } else {
                                    html += '-';
                                }
                                html += '</td>';
                                html += '<td data-label="Nomor Antrian">';
                                html += '<input type="hidden" name="antrian_id[antrian_' + item.id + ']" value="' + item.id + '">';
                                // Gunakan nilai yang sudah diisi jika ada, jika tidak gunakan nilai dari server
                                const nomorValue = currentInputs['nomor_antrian_' + item.id] || item.nomor_antrian || '';
                                html += '<input type="number" id="nomor_antrian_' + item.id + '" name="nomor_antrian[antrian_' + item.id + ']" class="input-nomor ' + (isSelesai ? 'disabled-field' : '') + '" value="' + escapeHtml(nomorValue) + '" min="1" placeholder="-" ' + (isSelesai ? 'disabled' : '') + '>';
                                html += '</td>';
                                html += '<td data-label="Sumber" style="text-align: center; vertical-align: middle;">';
                                // Ambil nilai sumber dari item
                                let sumber = '';
                                if (item.sumber && item.sumber !== null && item.sumber !== '') {
                                    sumber = String(item.sumber).trim();
                                } else {
                                    // Fallback: tentukan berdasarkan pasien_id dan nama_manual
                                    if (!item.pasien_id || item.nama_manual) {
                                        sumber = 'Offline';
                                    } else {
                                        sumber = 'Online';
                                    }
                                }
                                // Normalisasi nilai (case-insensitive)
                                const sumberLower = sumber.toLowerCase();
                                // Tampilkan badge sesuai sumber
                                if (sumberLower === 'offline' || sumberLower === 'manual') {
                                    html += '<span class="badge-sumber badge-offline" style="background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Offline</span>';
                                } else {
                                    html += '<span class="badge-sumber badge-online" style="background-color: #cfe2ff; color: #084298; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; display: inline-block; white-space: nowrap; min-width: 60px; text-align: center;">Online</span>';
                                }
                                html += '</td>';
                                html += '<td data-label="Status">';
                                // Gunakan nilai yang sudah dipilih jika ada, jika tidak gunakan nilai dari server
                                const statusValue = currentSelects['status_' + item.id] || item.status || 'Menunggu';
                                html += '<select id="status_' + item.id + '" name="status[antrian_' + item.id + ']" class="status-select ' + (isSelesai ? 'disabled-field' : '') + '" data-antrian-id="' + item.id + '" ' + (isSelesai ? 'disabled' : '') + '>';
                                html += '<option value="Menunggu"' + (statusValue === 'Menunggu' || statusValue === 'menunggu' ? ' selected' : '') + '>Menunggu</option>';
                                html += '<option value="Dipanggil"' + (statusValue === 'Dipanggil' || statusValue === 'dipanggil' || statusValue === 'sedang_dilayani' ? ' selected' : '') + '>Dipanggil</option>';
                                html += '<option value="Selesai"' + (statusValue === 'Selesai' || statusValue === 'selesai' ? ' selected' : '') + '>Selesai</option>';
                                html += '<option value="Batal"' + (statusValue === 'Batal' || statusValue === 'batal' ? ' selected' : '') + '>Batal</option>';
                                html += '</select>';
                                html += '</td>';
                                html += '</tr>';
                            });
                            tableBody.innerHTML = html;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                });
        }
        
        setInterval(refreshData, 3000);
        refreshData();
    }
    
    // Helper function untuk escape HTML
    function escapeHtml(text) {
        if (text == null) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    // Initialize saat DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Deteksi halaman dan inisialisasi sesuai
        const path = window.location.pathname;
        
        if (path.includes('dashboard_user.php')) {
            initBerandaUserRefresh();
        } else if (path.includes('daftar_user.php')) {
            initDaftarUserRefresh();
        } else if (path.includes('dashboard_admin.php')) {
            initBerandaAdminRefresh();
        } else if (path.includes('kelola_antrian.php')) {
            initKelolaAntrianRefresh();
        }
    });
})();

