-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 06:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

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
(1, 'Kopi Arabica Premium', 'gram', 172, '2026-04-30 04:02:01'),
(2, 'Kopi Robusta', 'gram', 120, '2026-04-30 04:02:01'),
(3, 'Susu Full Cream', 'liter', 112, '2026-04-30 04:02:01'),
(4, 'Gula Pasir', 'kg', 200, '2026-04-30 04:02:01'),
(5, 'Cup Small', 'pcs', 600, '2026-04-30 04:02:01'),
(6, 'Cup Medium', 'pcs', 501, '2026-04-30 04:02:01'),
(7, 'Cup Large', 'pcs', 300, '2026-04-30 04:02:01'),
(8, 'Sirup Vanilla', 'botol', 32, '2026-04-30 04:02:01'),
(9, 'Sirup Hazelnut', 'botol', 7, '2026-04-30 04:02:01'),
(10, 'Sirup Caramel', 'botol', 20, '2026-04-30 04:02:01'),
(11, 'Whipped Cream', 'kaleng', 42, '2026-04-30 04:02:01'),
(12, 'Cokelat Bubuk', 'gram', 60, '2026-04-30 04:02:01'),
(14, 'beans robusta', 'kg', 34, '2026-06-27 05:05:54');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `no_po` varchar(30) DEFAULT NULL,
  `barang_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `qty` int(11) DEFAULT NULL,
  `status` enum('pending','received','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `pengirim` varchar(100) DEFAULT NULL,
  `penerima` varchar(100) DEFAULT NULL,
  `ttd_pengirim` text DEFAULT NULL,
  `ttd_penerima` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `no_po`, `barang_id`, `user_id`, `qty`, `status`, `notes`, `pengirim`, `penerima`, `ttd_pengirim`, `ttd_penerima`, `approved_at`, `approved_by`, `created_at`) VALUES
(2, 'PO-20260627-7923', NULL, 2, NULL, 'received', '', 'tata', 'Fiqri Petugas', NULL, NULL, '2026-06-27 05:06:13', 1, '2026-06-27 05:04:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `barang_id`, `qty`, `notes`) VALUES
(1, 2, 11, 3, ''),
(2, 2, 9, 4, ''),
(3, 2, 8, 2, ''),
(4, 2, 3, 32, ''),
(5, 2, 6, 1, ''),
(6, 2, 1, 22, '');

-- --------------------------------------------------------

--
-- Table structure for table `po_items`
--

CREATE TABLE `po_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `satuan` varchar(20) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `nomor_po` varchar(30) NOT NULL,
  `petugas_id` int(11) NOT NULL,
  `pengirim` varchar(100) NOT NULL,
  `penerima` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL,
  `total_item` int(11) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(13, 9, 2, 25, 26, 'datang tanpa jadwal 1', 'selesai', '2026-05-02 04:01:39'),
(14, 9, 2, 26, 3, '', 'selesai', '2026-06-27 04:56:22');

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
(1, 'Administrator', 'admin', '$2y$10$LCYh2jinaPaqQkhs/fg4A.MQ6OSvG/H.bwBjlSqNKEP41yvYBbdEG', 'admin', '2026-04-30 04:02:01'),
(2, 'Fiqri Petugas', 'fiqri', '$2y$10$nbv5Lu4IVmZSXy.eMt/n7e7N8XLdPxKADC9Ok29X45E9OdaIWNCYW', 'petugas', '2026-04-30 04:02:01'),
(3, 'Budi Petugas', 'budi', '$2y$10$U0rkaZBiAkg/WIfkoVB.RebNiaYecUcP3lZoVfbtTYX8CyR08KA5u', 'petugas', '2026-04-30 04:02:01'),
(4, 'asep', 'asef', 'petugas123', 'petugas', '2026-05-08 10:21:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_time` (`attempted_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `po_items`
--
ALTER TABLE `po_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_po` (`nomor_po`),
  ADD KEY `petugas_id` (`petugas_id`),
  ADD KEY `approved_by` (`approved_by`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `po_items`
--
ALTER TABLE `po_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_opname`
--
ALTER TABLE `stok_opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);

--
-- Constraints for table `po_items`
--
ALTER TABLE `po_items`
  ADD CONSTRAINT `po_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `po_items_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

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
