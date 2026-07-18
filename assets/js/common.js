/**
 * FILE: assets/js/common.js
 * FUNGSI: File JavaScript yang berisi fungsi-fungsi umum yang digunakan di berbagai halaman
 * 
 * CARA PENGGUNAAN:
 * - File ini di-include di halaman yang membutuhkan fungsi hanyaAngka()
 * - Contoh: <script src="../../assets/js/common.js"></script>
 * - Dipanggil dengan: onkeypress="return hanyaAngka(event)" pada input field
 */

/**
 * Fungsi untuk hanya menerima input angka (0-9)
 * Mencegah user memasukkan karakter selain angka di input field
 * 
 * CARA KERJA:
 * - Mendeteksi keyCode dari event keyboard
 * - Jika keyCode di antara 48-57 (angka 0-9), return true (diterima)
 * - Jika keyCode di bawah 32, return true (untuk tombol kontrol seperti Backspace, Tab, dll)
 * - Jika keyCode selain itu, return false (ditolak, karakter tidak akan muncul)
 * 
 * PENGGUNAAN:
 * - Digunakan di input field yang hanya menerima angka:
 *   * NIK (16 digit)
 *   * NO.KK (16 digit)
 *   * NO.HP (15 digit)
 *   * NOMOR BPJS (13 digit)
 * 
 * CONTOH:
 * <input type="text" name="nik" onkeypress="return hanyaAngka(event)">
 * 
 * @param {Event} event - Event keyboard dari user input
 * @returns {boolean} - true jika karakter diterima (angka atau kontrol), false jika ditolak
 */
function hanyaAngka(event) {
    // Mendapatkan keyCode dari event
    // event.which untuk browser modern, event.keyCode untuk browser lama (fallback)
    var charCode = (event.which) ? event.which : event.keyCode;
    
    // KeyCode 0-31 adalah tombol kontrol (Backspace, Tab, Enter, dll) - harus diizinkan
    // KeyCode 48-57 adalah angka 0-9 - harus diizinkan
    // Jika keyCode > 31 DAN (keyCode < 48 ATAU keyCode > 57), berarti bukan angka maupun kontrol
    // Jadi tolak dengan return false
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;  // Tolak karakter (tidak akan muncul di input)
    }
    
    return true;  // Terima karakter (akan muncul di input)
}
