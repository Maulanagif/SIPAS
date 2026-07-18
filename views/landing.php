<?php
/**
 * FILE: views/landing.php
 * FUNGSI: Halaman awal (landing) SIPAS – pengenalan sistem sebelum login/daftar
 * 
 * AKSES: Ditampilkan via index.php saat user belum login.
 * LINK: Masuk → login.php, Daftar → register.php
 */
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAS – Sistem Informasi Antrian dan Pendaftaran Pasien | Puskesmas Sijunjung</title>
    <link href="assets/css/utilities.css" rel="stylesheet">
    <link href="assets/css/landing.css" rel="stylesheet">
</head>
<body class="landing-body">
    <!-- Header -->
    <header class="landing-header">
        <div class="landing-header-inner">
            <a href="index.php" class="landing-brand" aria-label="Beranda SIPAS">
                <img src="assets/images/logo.png" alt="Logo SIPAS" class="landing-brand-logo">
                <div class="landing-brand-text">
                    <div class="landing-brand-title">SIPAS</div>
                    <div class="landing-brand-sub">Puskesmas Sijunjung</div>
                </div>
            </a>
            <nav class="landing-header-actions" aria-label="Aksi">
                <a href="login.php" class="landing-btn landing-btn-primary">Login</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="landing-main">
        <div class="landing-hero">
            <div class="landing-hero-card">
                <h1 class="landing-hero-title">Sistem Informasi Pendaftaran Antrian Pasien</h1>
                <p class="landing-hero-sub">
                    Kelola antrian dan pendaftaran pasien Puskesmas Sijunjung secara online
                </p>
                <a href="register.php" class="landing-btn landing-btn-cta">Daftar Akun Pasien</a>
            </div>
        </div>
    </main>

</body>
</html>
