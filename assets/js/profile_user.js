/**
 * FILE: assets/js/profile_user.js
 * FUNGSI: Halaman profile user — tampilan (foto/inisial + nama + tombol), Informasi Akun, Informasi SIPAS
 *
 * FITUR:
 * - Toggle 3 panel: view (Profil), Informasi Akun (form edit), Informasi SIPAS (deskripsi + jam operasional + tutorial).
 * - Saat menampilkan Informasi SIPAS: tambah class .profile-card-hide-bg pada .profile-card agar card putih di atas section dihilangkan.
 * - Klik gambar foto → lightbox; ikon pensil (hanya di Informasi Akun) → ganti foto (upload).
 * - Toggle BPJS, auto-calculate umur dari tanggal lahir.
 *
 * PERUBAHAN / YANG DIBUAT (ringkasan):
 * - initViewEditToggle: tombol Informasi Akun & Informasi SIPAS; saat showTentang() → profileCard.classList.add('profile-card-hide-bg'); saat showView/showInformasiAkun → remove.
 * - initPhotoUpload: hanya bind ke form/edit (profilePhotoEditBtnEdit, formUploadFotoEdit); tidak ada ikon edit di view.
 *
 * PENGGUNAAN: Di-include di profile_user.php setelah common.js
 */

(function() {
    'use strict';

    /**
     * Inisialisasi semua fitur saat halaman siap.
     * Memanggil: toggle view/edit, lightbox foto, upload foto, toggle BPJS, hitung umur.
     */
    function init() {
        initViewEditToggle();
        initPhotoLightbox();
        initPhotoUpload();
        initBpjsToggle();
        initUmurFromTanggalLahir();
    }

    /**
     * Toggle antara mode tampilan (view), Informasi Akun (form edit), dan Informasi SIPAS.
     * - Klik "Informasi Akun" → tampilkan form Informasi Akun.
     * - Klik "Informasi SIPAS" → tampilkan section Informasi SIPAS.
     * - Klik "Batal" (di form) atau "Tutup" (di Informasi SIPAS) → kembali ke view (foto + nama + tombol).
     */
    function initViewEditToggle() {
        var viewBody = document.getElementById('profileViewBody');
        var editBody = document.getElementById('profileEditBody');
        var tentangBody = document.getElementById('profileTentangBody');
        var btnInformasiAkun = document.getElementById('btnInformasiAkun');
        var btnTentang = document.getElementById('btnTentang');
        var btnBatal = document.getElementById('btnBatalEdit');
        var btnTutupTentang = document.getElementById('btnTutupTentang');

        if (!viewBody || !editBody) return;

        /* Saat Informasi SIPAS ditampilkan: hapus card putih di atas section (CSS .profile-card-hide-bg) */
        var profileCard = document.querySelector('.profile-card');

        function showView() {
            viewBody.style.display = 'block';
            editBody.style.display = 'none';
            if (tentangBody) tentangBody.style.display = 'none';
            if (profileCard) profileCard.classList.remove('profile-card-hide-bg');
        }

        function showInformasiAkun() {
            viewBody.style.display = 'none';
            editBody.style.display = 'block';
            if (tentangBody) tentangBody.style.display = 'none';
            if (profileCard) profileCard.classList.remove('profile-card-hide-bg');
        }

        function showTentang() {
            viewBody.style.display = 'none';
            editBody.style.display = 'none';
            if (tentangBody) tentangBody.style.display = 'block';
            if (profileCard) profileCard.classList.add('profile-card-hide-bg'); /* hapus card putih di atas Informasi SIPAS */
        }

        if (btnInformasiAkun) btnInformasiAkun.addEventListener('click', showInformasiAkun);
        if (btnTentang) btnTentang.addEventListener('click', showTentang);
        if (btnBatal) btnBatal.addEventListener('click', showView);
        if (btnTutupTentang) btnTutupTentang.addEventListener('click', showView);
    }

    /**
     * Lightbox foto profil: klik area foto (bukan ikon pensil) → tampil gambar besar di overlay.
     * - openLightbox(src): tampilkan gambar di lightbox, kunci scroll body.
     * - closeLightbox(): tutup overlay, kembalikan scroll.
     * - Klik backdrop / tombol tutup / tombol Escape → tutup lightbox.
     */
    function initPhotoLightbox() {
        var lightbox = document.getElementById('profilePhotoLightbox');
        var lightboxImg = document.getElementById('profilePhotoLightboxImg');
        var backdrop = document.getElementById('profilePhotoLightboxBackdrop');
        var closeBtn = document.getElementById('profilePhotoLightboxClose');

        if (!lightbox || !lightboxImg) return;

        function openLightbox(src) {
            if (!src) return;
            lightboxImg.src = src;
            lightbox.setAttribute('aria-hidden', 'false');
            lightbox.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        /* Klik pada wrap foto: jika bukan klik ikon pensil, buka lightbox dengan URL gambar (data-full-src atau src) */
        function onPhotoWrapClick(e) {
            if (e.target.closest('.profile-photo-edit-btn')) return;
            var wrap = e.currentTarget;
            var img = wrap.querySelector('.profile-photo-img');
            if (!img) return;
            var fullSrc = img.getAttribute('data-full-src') || img.src;
            if (fullSrc) openLightbox(fullSrc);
        }

        var wrapView = document.getElementById('profilePhotoWrap');
        var wrapEdit = document.getElementById('profilePhotoWrapEdit');
        if (wrapView) wrapView.addEventListener('click', onPhotoWrapClick);
        if (wrapEdit) wrapEdit.addEventListener('click', onPhotoWrapClick);

        if (backdrop) backdrop.addEventListener('click', closeLightbox);
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
        });
    }

    /**
     * Upload foto: hanya di halaman Informasi Akun (ikon pensil hanya ada di sana).
     * Klik ikon pensil → trigger input file; pilih file → form upload otomatis di-submit.
     * Di tampilan profil (view) tidak ada ikon edit, jadi hanya bind ke profilePhotoEditBtnEdit + formUploadFotoEdit.
     */
    function initPhotoUpload() {
        var formEdit = document.getElementById('formUploadFotoEdit');
        var inputEdit = document.getElementById('foto_profil_input_edit');
        var btnEdit = document.getElementById('profilePhotoEditBtnEdit');

        if (btnEdit && inputEdit && formEdit) {
            btnEdit.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); inputEdit.click(); });
            inputEdit.addEventListener('change', function() {
                if (this.files && this.files.length > 0) formEdit.submit();
            });
        }
    }

    /**
     * Toggle field BPJS: saat radio "BPJS" dipilih → tampilkan grup Jenis BPJS & Nomor BPJS.
     * Saat "NON BPJS" dipilih → sembunyikan grup tersebut.
     * toggle() dipanggil sekali di awal agar tampilan sesuai nilai yang sudah tercentang (dari PHP).
     */
    function initBpjsToggle() {
        var isBpjs = document.getElementById('is_bpjs_profile');
        var nonBpjs = document.getElementById('non_bpjs_profile');
        var group = document.getElementById('jenis_bpjs_group_profile');
        if (!group) return;

        function toggle() {
            if (isBpjs && isBpjs.checked) {
                group.style.display = 'grid';
            } else {
                group.style.display = 'none';
            }
        }

        if (isBpjs) isBpjs.addEventListener('change', toggle);
        if (nonBpjs) nonBpjs.addEventListener('change', toggle);
        toggle();
    }

    /**
     * Auto-calculate umur: saat user mengubah tanggal lahir di form edit,
     * hitung umur (selisih tahun dari hari ini) dan isikan ke field Umur.
     */
    function initUmurFromTanggalLahir() {
        var tanggalInput = document.getElementById('tanggal_lahir_profile');
        var umurInput = document.getElementById('umur_profile');
        if (!tanggalInput || !umurInput) return;

        tanggalInput.addEventListener('change', function() {
            if (!this.value) {
                umurInput.value = '';
                return;
            }
            var birth = new Date(this.value);
            var today = new Date();
            var age = today.getFullYear() - birth.getFullYear();
            var m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
            umurInput.value = age >= 0 ? age : '';
        });
    }

    /* Jalankan init saat DOM ready (atau langsung jika sudah ready) */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
