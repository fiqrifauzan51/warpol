-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 02, 2026 at 06:08 AM
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
-- Database: `warpol_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `satuan` varchar(20) DEFAULT 'pcs',
  `stok_sistem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `nama_barang`, `satuan`, `stok_sistem`, `created_at`) VALUES
(1, 'Kopi Arabica Premium', 'gram', 150, '2026-04-30 04:02:01'),
(2, 'Kopi Robusta', 'gram', 120, '2026-04-30 04:02:01'),
(3, 'Susu Full Cream', 'liter', 80, '2026-04-30 04:02:01'),
(4, 'Gula Pasir', 'kg', 200, '2026-04-30 04:02:01'),
(5, 'Cup Small', 'pcs', 600, '2026-04-30 04:02:01'),
(6, 'Cup Medium', 'pcs', 500, '2026-04-30 04:02:01'),
(7, 'Cup Large', 'pcs', 300, '2026-04-30 04:02:01'),
(8, 'Sirup Vanilla', 'botol', 30, '2026-04-30 04:02:01'),
(9, 'Sirup Hazelnut', 'botol', 26, '2026-04-30 04:02:01'),
(10, 'Sirup Caramel', 'botol', 20, '2026-04-30 04:02:01'),
(11, 'Whipped Cream', 'kaleng', 39, '2026-04-30 04:02:01'),
(12, 'Cokelat Bubuk', 'gram', 60, '2026-04-30 04:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `stok_opname`
--

CREATE TABLE `stok_opname` (
  `id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `stok_sistem` int(11) NOT NULL,
  `stok_fisik` int(11) NOT NULL,
  `selisih` int(11) GENERATED ALWAYS AS (`stok_fisik` - `stok_sistem`) STORED,
  `keterangan` text DEFAULT NULL,
  `status` enum('selesai','pending') DEFAULT 'selesai',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stok_opname`
--

INSERT INTO `stok_opname` (`id`, `barang_id`, `user_id`, `stok_sistem`, `stok_fisik`, `keterangan`, `status`, `created_at`) VALUES
(12, 7, 2, 300, 300, '', 'selesai', '2026-05-02 03:21:58'),
(13, 9, 2, 25, 26, 'datang tanpa jadwal 1', 'selesai', '2026-05-02 04:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas') DEFAULT 'petugas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$L9T9DZnuH65xlrqstq.fsuoRyrg4whOZbh.5dER8yoWWd1SjKmwG2', 'admin', '2026-04-30 04:02:01'),
(2, 'Fiqri Petugas', 'fiqri', '$2y$10$wxlM/go7sSfVSFRwi6TH9eq.88744FdewibPNBkS.i1PevJMN.Eka', 'petugas', '2026-04-30 04:02:01'),
(3, 'Budi Petugas', 'budi', '$2y$10$Xmh6p44eZ8rbK5vEIT3JquuoH7WJqBkvOkM/Kmayk3WYLt1oXzo2a', 'petugas', '2026-04-30 04:02:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stok_opname`
--
ALTER TABLE `stok_opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD CONSTRAINT `stok_opname_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`),
  ADD CONSTRAINT `stok_opname_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
