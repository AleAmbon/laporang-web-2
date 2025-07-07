-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 05, 2025 at 08:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lab_ci4`
--

-- --------------------------------------------------------

--
-- Table structure for table `artikel`
--

CREATE TABLE `artikel` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `slug` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_kategori` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artikel`
--

INSERT INTO `artikel` (`id`, `judul`, `isi`, `status`, `slug`, `gambar`, `created_at`, `updated_at`, `id_kategori`) VALUES
(1, 'Masa Depan AI di Indonesia', 'Pembahasan tentang potensi dan tantangan AI di Indonesia.', 'published', 'masa-depan-ai-indonesia', 'ai_indonesia.jpg', '2025-07-06 01:00:34', '2025-07-06 01:00:34', 1),
(2, 'Tren Fashion Terkini 2025', 'Rangkuman tren fashion yang diprediksi populer tahun ini.', 'published', 'tren-fashion-2025', 'fashion_2025.jpg', '2025-07-06 01:00:34', '2025-07-06 01:00:34', 2),
(3, 'Tips Latihan Lari Maraton', 'Panduan lengkap bagi pemula untuk mempersiapkan maraton pertama.', 'published', 'tips-lari-maraton', 'maraton.jpg', '2025-07-06 01:00:34', '2025-07-06 01:00:34', 3),
(4, 'Review Smartphone Terbaru', 'Ulasan lengkap tentang fitur dan performa smartphone terbaru dari Brand X.', 'published', 'review-smartphone-terbaru', 'smartphone.jpg', '2025-07-06 01:00:34', '2025-07-06 01:00:34', 1),
(5, 'Resep Makanan Sehat untuk Keluarga', 'Kumpulan resep masakan sehat yang mudah dibuat di rumah.', 'published', 'resep-makanan-sehat-keluarga', 'makanan_sehat.jpg', '2025-07-06 01:00:34', '2025-07-06 01:00:34', 2);

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `slug_kategori` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `slug_kategori`) VALUES
(1, 'Teknologi', 'teknologi'),
(2, 'Gaya Hidup', 'gaya-hidup'),
(3, 'Olahraga', 'olahraga');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artikel`
--
ALTER TABLE `artikel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_kategori_artikel` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artikel`
--
ALTER TABLE `artikel`
  ADD CONSTRAINT `fk_kategori_artikel` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
