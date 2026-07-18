/**
 * FILE: assets/js/daftar_antrian.js
 * FUNGSI: JavaScript untuk halaman pendaftaran antrian baru
 * 
 * FITUR:
 * - Toggle field BPJS (tampilkan/sembunyikan field jenis BPJS dan nomor BPJS)
 * - Auto calculate umur dari tanggal lahir
 * 
 * PENGGUNAAN:
 * - Di-include di halaman daftar_antrian.php
 * - Auto execute saat DOM ready
 */

// Menunggu DOM siap sebelum menjalankan script
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Toggle field BPJS berdasarkan pilihan radio button
     * Menampilkan/sembunyikan field jenis BPJS dan nomor BPJS
     */
    const bpjsRadio = document.getElementById('is_bpjs');           // Radio button BPJS
    const nonBpjsRadio = document.getElementById('non_bpjs');       // Radio button NON BPJS
    const jenisBpjsGroup = document.getElementById('jenis_bpjs_group'); // Container field jenis BPJS
    const nomorBpjsGroup = document.getElementById('nomor_bpjs_group');  // Container field nomor BPJS
    
    /**
     * Fungsi untuk toggle field BPJS berdasarkan pilihan radio button
     * Jika BPJS dipilih, tampilkan field jenis BPJS dan nomor BPJS
     * Jika NON BPJS dipilih, sembunyikan kedua field tersebut
     */
    function toggleBpjsFields() {
        if (bpjsRadio && bpjsRadio.checked) {
            if (jenisBpjsGroup) jenisBpjsGroup.style.display = 'block';
            if (nomorBpjsGroup) nomorBpjsGroup.style.display = 'block';
        } else {
            if (jenisBpjsGroup) jenisBpjsGroup.style.display = 'none';
            if (nomorBpjsGroup) nomorBpjsGroup.style.display = 'none';
        }
    }
    
    // Tambahkan event listener untuk toggle field BPJS saat radio button berubah
    if (bpjsRadio) bpjsRadio.addEventListener('change', toggleBpjsFields);
    if (nonBpjsRadio) nonBpjsRadio.addEventListener('change', toggleBpjsFields);

    /**
     * Auto calculate umur dari tanggal lahir
     * Menghitung umur secara otomatis saat user memilih tanggal lahir
     * Hanya menghitung jika field umur masih kosong (tidak overwrite input manual)
     * 
     * CARA KERJA:
     * - Mendengarkan perubahan pada input tanggal lahir
     * - Menghitung selisih tahun antara tanggal lahir dan hari ini
     * - Mempertimbangkan bulan dan hari untuk akurasi (jika belum ulang tahun tahun ini, kurangi 1)
     * - Mengisi field umur dengan hasil perhitungan
     */
    const tanggalLahirInput = document.querySelector('input[name="tanggal_lahir"]');
    if (tanggalLahirInput) {
        tanggalLahirInput.addEventListener('change', function() {
            const umurInput = document.querySelector('input[name="umur"]');
            if (this.value && umurInput && !umurInput.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                const age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    umurInput.value = age - 1;
                } else {
                    umurInput.value = age;
                }
            }
        });
    }

});

