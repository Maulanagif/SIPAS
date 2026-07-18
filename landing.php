<?php
/**
 * FILE: landing.php
 * FUNGSI: Halaman landing (beranda) SIPAS – selalu tampil tanpa redirect
 *
 * Digunakan untuk link "kembali ke beranda" dari Pendaftaran Awal, dll.
 * Berbeda dengan index.php: index cek login dan redirect; landing selalu
 * menampilkan halaman awal sehingga logo di Pendaftaran Awal bisa mengarah ke sini.
 *
 * Aset (CSS, gambar) di views/landing.php memakai path relatif assets/;
 * karena dokumen dilayani dari root, path resolve ke assets/ dengan benar.
 */
require_once __DIR__ . '/views/landing.php';
