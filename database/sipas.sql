-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 05:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sipas`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrian`
--

CREATE TABLE `antrian` (
  `id_antrian` int(11) NOT NULL,
  `pasien_id` int(11) DEFAULT NULL,
  `klaster_id` int(11) NOT NULL,
  `keluhan` text DEFAULT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `nomor_antrian` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Menunggu',
  `sumber` varchar(20) DEFAULT 'Online',
  `nama_manual` varchar(255) DEFAULT NULL,
  `umur_manual` int(11) DEFAULT NULL,
  `jenis_kelamin_manual` varchar(1) DEFAULT NULL,
  `nik_manual` varchar(16) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `antrian`
--

INSERT INTO `antrian` (`id_antrian`, `pasien_id`, `klaster_id`, `keluhan`, `tanggal_kunjungan`, `nomor_antrian`, `status`, `sumber`, `nama_manual`, `umur_manual`, `jenis_kelamin_manual`, `nik_manual`, `created_at`, `updated_at`) VALUES
(3, NULL, 3, NULL, '2026-01-15', NULL, 'Selesai', 'Offline', '546545', 22, 'L', '3455645610231563', '2026-01-15 00:31:23', '2026-01-15 00:31:23'),
(4, NULL, 3, NULL, '2026-01-15', NULL, 'Selesai', 'Offline', 'sfdasdasd', 21, 'L', '0231321564564156', '2026-01-15 02:10:49', '2026-01-15 02:10:49'),
(5, NULL, 3, NULL, '2026-01-15', NULL, 'Selesai', 'Offline', 'putra', 23, 'L', '3423423423423423', '2026-01-15 09:44:27', '2026-01-15 09:44:27'),
(6, 1, 2, 'sdasd', '2026-01-16', '41', 'Selesai', 'Online', NULL, NULL, NULL, NULL, '2026-01-15 15:31:37', '2026-01-23 08:24:53'),
(7, 1, 3, 'zxcz', '2026-01-19', '21', 'Selesai', 'Online', NULL, NULL, NULL, NULL, '2026-01-16 09:29:31', '2026-01-16 10:38:56'),
(8, NULL, 2, NULL, '2026-01-16', NULL, 'Selesai', 'Offline', 'sdfsd', 12, 'L', '5432132153213213', '2026-01-16 09:37:10', '2026-01-16 09:37:10'),
(9, NULL, 3, NULL, '2026-01-16', NULL, 'Selesai', 'Offline', 'anto', 24, 'L', '0211230000231321', '2026-01-16 09:54:46', '2026-01-16 09:54:46'),
(10, NULL, 3, NULL, '2026-01-16', '12', 'Selesai', 'Offline', 'adasdasd', 19, 'L', '5643213215645634', '2026-01-16 09:57:14', '2026-01-16 09:57:14'),
(11, NULL, 3, NULL, '2026-01-16', '13', 'Selesai', 'Offline', 'anto', 21, 'L', '6546546534654564', '2026-01-16 16:02:28', '2026-01-16 16:02:28'),
(12, 1, 3, 'Sakit Kepala', '2026-01-26', '1', 'Selesai', 'Online', NULL, NULL, NULL, NULL, '2026-01-25 11:48:14', '2026-01-27 17:07:04'),
(13, NULL, 3, NULL, '2026-01-26', '2', 'Selesai', 'Offline', 'Santo', 23, 'L', '1231231233223213', '2026-01-25 19:06:38', '2026-01-25 19:06:38'),
(14, 1, 3, 'sdsdsdsd', '2026-01-27', NULL, 'Batal', 'Online', NULL, NULL, NULL, NULL, '2026-01-26 06:29:08', '2026-01-27 06:40:57'),
(15, 1, 3, 'sakit kepala', '2026-01-27', NULL, 'Batal', 'Online', NULL, NULL, NULL, NULL, '2026-01-27 06:42:45', '2026-01-27 06:50:50'),
(16, 1, 1, 'qwe', '2026-01-27', '2', 'Selesai', 'Online', NULL, NULL, NULL, NULL, '2026-01-27 06:51:55', '2026-01-27 12:28:47'),
(20, 1, 4, 'asd', '2026-01-28', NULL, 'Batal', 'Online', NULL, NULL, NULL, NULL, '2026-01-27 17:39:13', '2026-01-27 17:39:24'),
(21, 1, 3, 'Sakit Pinggang', '2026-02-02', '1', 'Selesai', 'Online', NULL, NULL, NULL, NULL, '2026-02-01 19:18:00', '2026-02-02 15:30:31'),
(30, 4, 3, 'Sakit Kepala', '2026-03-05', '1', 'Dipanggil', 'Online', NULL, NULL, NULL, NULL, '2026-03-05 04:30:38', '2026-03-05 04:34:39'),
(31, NULL, 3, 'Sakit Perut', '2026-03-05', '2', 'Selesai', 'Offline', 'Ayu', 23, 'P', '1231231321231231', '2026-03-05 04:35:39', '2026-03-05 04:35:39'),
(32, 1, 3, 'asd', '2026-03-06', '1', 'Menunggu', 'Online', NULL, NULL, NULL, NULL, '2026-03-06 03:26:11', '2026-03-06 03:26:11'),
(33, 4, 3, 'asd', '2026-03-06', '2', 'Menunggu', 'Online', NULL, NULL, NULL, NULL, '2026-03-06 03:30:05', '2026-03-06 03:30:05'),
(34, 5, 3, 'asd', '2026-03-06', '3', 'Menunggu', 'Online', NULL, NULL, NULL, NULL, '2026-03-06 03:34:37', '2026-03-06 03:34:37');

-- --------------------------------------------------------

--
-- Table structure for table `klaster`
--

CREATE TABLE `klaster` (
  `id` int(11) NOT NULL,
  `nama_klaster` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klaster`
--

INSERT INTO `klaster` (`id`, `nama_klaster`, `created_at`) VALUES
(1, 'Klaster 1', '2026-01-14 21:12:36'),
(2, 'Klaster 2', '2026-01-14 21:12:36'),
(3, 'Klaster 3', '2026-01-14 21:12:36'),
(4, 'Klaster 4', '2026-01-14 21:12:36'),
(5, 'Lintas Klaster', '2026-01-14 21:12:36');

-- --------------------------------------------------------

--
-- Table structure for table `pasien`
--

CREATE TABLE `pasien` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `no_kk` varchar(50) DEFAULT NULL,
  `nik` varchar(16) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nama_kepala_keluarga` varchar(255) DEFAULT NULL,
  `nama_ibu_kandung` varchar(255) DEFAULT NULL,
  `status_keluarga` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `is_bpjs` tinyint(1) DEFAULT 0,
  `jenis_bpjs` varchar(50) DEFAULT NULL,
  `nomor_bpjs` varchar(50) DEFAULT NULL,
  `is_pasien_baru` tinyint(1) DEFAULT 1,
  `jenis_kelamin` varchar(1) DEFAULT NULL,
  `umur` int(11) DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `pendidikan` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pasien`
--

INSERT INTO `pasien` (`id`, `user_id`, `no_kk`, `nik`, `nama`, `tempat_lahir`, `tanggal_lahir`, `nama_kepala_keluarga`, `nama_ibu_kandung`, `status_keluarga`, `alamat`, `is_bpjs`, `jenis_bpjs`, `nomor_bpjs`, `is_pasien_baru`, `jenis_kelamin`, `umur`, `agama`, `pekerjaan`, `pendidikan`, `no_hp`, `foto_profil`, `created_at`, `updated_at`) VALUES
(1, 2, '5645412131231231', '5423156456342423', 'Farhan Maulana', 'Sijunjung', '2002-06-10', 'eee', 'eee', 'Anak', 'padang', 1, 'PBI', '1231231231231', 1, 'L', 23, 'Islam', 'Mahasiswa', 'S1', '082287786941', 'https://res.cloudinary.com/djxc8fjc2/image/upload/v1769883244/dst09ebhlqlc0tybb0qb.jpg', '2026-01-14 22:11:43', '2026-01-31 18:14:03'),
(3, 4, '1283478124891348', '4124124124128686', 'Adit', 'lubuk basung', '2003-12-19', 'SITAN', 'MARKONAH', 'ANAK', NULL, 0, NULL, NULL, 1, 'L', 22, 'Islam', 'PETANI', 'S1', '085455454454', 'https://res.cloudinary.com/djxc8fjc2/image/upload/v1770072838/qhzdh9j0ups1xvfqyc6s.png', '2026-02-02 22:30:43', '2026-02-06 21:32:28'),
(4, 7, '7773883838382929', '1263127836127831', 'Farhan Maulana', 'Sijunjung', '2002-06-10', 'Embrizol.D', 'Fitri Mainil', 'Anak', 'Padang, Lubeg', 0, NULL, NULL, 1, 'L', 23, 'Islam', 'Mahasiswa', 'SMA', '082287786941', NULL, '2026-03-05 04:29:54', '2026-03-05 04:29:54'),
(5, 6, '1244646756756756', '4568768576745656', 'Fahmi', 'Padang', '2000-03-01', 'asd', 'asd', 'asd', 'asd', 0, NULL, NULL, 1, 'L', 26, 'Islam', 'pns', 'S1', '082242242423', NULL, '2026-03-06 03:34:08', '2026-03-06 03:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `is_admin`, `created_at`) VALUES
(1, 'adminSIPAS@gmail.com', '$2y$10$Iw.EvRZxpP.KE69pYljlQugfWlFX..QiW9J8avorjrMETS/URtbCK', 1, '2026-01-14 21:12:36'),
(2, 'aan@gmail.com', '$2y$10$jB2UxrxPZhf3T.UUPFoAzuje8V.Dx6.xL43UALt.V6jqNSQX3c17.', 0, '2026-01-14 22:10:16'),
(6, 'Fahmi@gmail.com', '$2y$10$E4Sj9/mo3Sv9Wmt3LwmXm.6exXRWnmyLLL7U3tWVaskI.1Ug.NuQi', 0, '2026-02-05 03:21:05'),
(7, 'farhanmaulana1@gmail.com', '$2y$10$N2By0ISCcDT1o8zXilFvX.pyJfQ0IgHQHaBX9D.b67BFrY2fi04.e', 0, '2026-03-05 04:27:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id_antrian`),
  ADD KEY `idx_pasien_id` (`pasien_id`),
  ADD KEY `idx_klaster_id` (`klaster_id`),
  ADD KEY `idx_tanggal_kunjungan` (`tanggal_kunjungan`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sumber` (`sumber`);

--
-- Indexes for table `klaster`
--
ALTER TABLE `klaster`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nama_klaster` (`nama_klaster`(250));

--
-- Indexes for table `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_nama` (`nama`(250));

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_is_admin` (`is_admin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id_antrian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `klaster`
--
ALTER TABLE `klaster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pasien`
--
ALTER TABLE `pasien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
