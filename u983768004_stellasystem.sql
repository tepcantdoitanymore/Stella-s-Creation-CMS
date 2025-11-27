-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 27, 2025 at 02:25 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u983768004_stellasystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_tbl`
--

CREATE TABLE `admin_tbl` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_tbl`
--

INSERT INTO `admin_tbl` (`admin_id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'stella', '$2y$10$GHD7DUAAaJkA3K4fX.SIfe9BmvaWdjPO8N3qU20P1p3Uj7ubHCSDu', 'stellaluna022506@gmail.com', '2025-11-12 11:42:07');

-- --------------------------------------------------------

--
-- Table structure for table `content_tbl`
--

CREATE TABLE `content_tbl` (
  `content_id` int(11) NOT NULL,
  `section` varchar(80) NOT NULL,
  `content_text` longtext DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_tbl`
--

INSERT INTO `content_tbl` (`content_id`, `section`, `content_text`, `image`, `updated_at`) VALUES
(1, 'color_options', '#000000,#ffffff,#fec7dc,#906fac,#4c92d4', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers_tbl`
--

CREATE TABLE `customers_tbl` (
  `customer_id` int(11) NOT NULL,
  `fullname` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `customers_tbl`
--

INSERT INTO `customers_tbl` (`customer_id`, `fullname`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(1, 'tiffany abella', 'bsittwoa@gmail.com', '09955945430', '$2y$10$GDNkaQ/BAGS58KQQtKucIOi3Zjsw5iLW3fqjmyOvyZYXIuAmyTI0q', '2025-11-13 02:50:10'),
(2, 'tiffany', 'lagansetiffany@smcbi.edu.ph', '09955945430', '$2y$10$TCiRMZ6XsKB/EjZJ/rDBqe8tGLBhhWXeXGsGFJNVgbdBKt.XBmIjK', '2025-11-16 05:10:45'),
(3, 'Hi tep', 'aygtoo@gmail.com', '0909aygtoo', '$2y$10$pQzgHKY/fmcRzbMu2U84b.TBxvoQ6FwP2CVLc85rMiJQ.kF5IFjk2', '2025-11-16 13:57:00'),
(4, 'asdfasdf', 'assd@gmail.com', 'asdfasdfasdf', '$2y$10$ivCo9SmCjMuQmEBzgJjv9u1YTiU3BFjUz79CPH2Irj8uBMTkwLrR.', '2025-11-19 10:54:58'),
(5, 'asdkfghasdkfjh', 'asd@gmail.com', 'asdfsadfasdf', '$2y$10$ao8kQmUOvpPKVyUDd84Gh.gLNS10MLHyGSXcobvY4IjNCN2cGkpSS', '2025-11-19 10:55:31'),
(6, 'TEP', 'tep@gmail.com', '0912', '$2y$10$2ZPCb4Ymvb3OPNSmMgn5o.mGCeba37dyV4XTGCXIEB.pPJFNoRTFq', '2025-11-21 01:49:42'),
(7, 'Senpai', 'senpai@gmail.com', '098175600', '$2y$10$6viYdZWCL4H8HWciiPwtX.aOyg3iaunIbf7e9RL5awG2LVRjlbzJG', '2025-11-21 10:38:52'),
(8, 'Romarie Judilla', 'romariejudilla@gmail.com', '09067884485', '$2y$10$Fyx6KPDWvgdafuX3dZyI4e4YM1Hp3PRuSqbmQjo7dlLKrYyYeWdm.', '2025-11-22 03:53:03'),
(9, 'Chay', 'chay@gmail.com', '09309396034', '$2y$10$SgCrehB4gRxz8tRimy3JfuEy8VNAgALXu36fzGpooCUrNeC3gkvo.', '2025-11-22 08:25:24'),
(10, 'John Carl Ray Lelis', 'jcrlelis@gmail.com', '09077679789', '$2y$10$jQjI11BDrvu67XxZfoIESuk.XI7NUI60Mhtscju8YX2GRvjKfivKS', '2025-11-24 09:34:25'),
(11, 'Glen', 'test1@gmail.com', '09191212532', '$2y$10$PGegPHuquzaOx0gb42RfHumIYmFJXOK/RsAhUJnpvzuoquYTYErFa', '2025-11-26 07:44:09'),
(12, 'ROBERTO', 'BERTO@gmail.com', '09514080723', '$2y$10$L0UHartnszEYq0Lwk0o/yuqS4S5VejQl8zurq9Dbd8X0oPBnhdeBW', '2025-11-26 10:53:00'),
(13, 'berto', 'lozano@gmail.com', '09514080723', '$2y$10$W6KwJP//qV4XDG6Ne1xWa.TwGFwgBRB6B4IK0fo3QUSvyqkgJRPe.', '2025-11-26 10:55:02'),
(14, 'Tiffany', 'teptep@gmail.com', '09123456789', '$2y$10$.VIt/SpKiDUGcaRZ3YzfG.tsdTPM.Z70YSWPoZekVbuniv9KQoYse', '2025-11-27 00:38:04'),
(15, 'tep', 'www@gmail.com', '12222222222', '$2y$10$yegcRU7bvhwSu5sfDRXs9urndEIBI2Jq3fLmWtkVjGn4lsal4GGGO', '2025-11-27 01:33:33');

-- --------------------------------------------------------

--
-- Table structure for table `orders_tbl`
--

CREATE TABLE `orders_tbl` (
  `order_id` bigint(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(120) NOT NULL,
  `contact_number` varchar(40) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `front_design` varchar(255) DEFAULT NULL,
  `back_design` varchar(255) DEFAULT NULL,
  `notes` mediumtext DEFAULT NULL,
  `status` enum('Pending','Approved','Completed','Ready','Cancelled','Refunded') NOT NULL DEFAULT 'Pending',
  `order_date` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders_tbl`
--

INSERT INTO `orders_tbl` (`order_id`, `customer_id`, `customer_name`, `contact_number`, `email`, `product_id`, `quantity`, `front_design`, `back_design`, `notes`, `status`, `order_date`) VALUES
(1, NULL, 'RTERER', '09123456789', NULL, 1, 1, '535156aea1ca0c61.png', 'bb65a34dd03d14bd.png', NULL, 'Pending', '2025-11-12 14:20:38'),
(2, NULL, 'TEPTEP', '0123456789', NULL, 1, 1, 'df952535b4d46d89.png', '6cf9ae6bf7d938b7.png', NULL, 'Pending', '2025-11-12 14:20:57'),
(3, NULL, 'TEPTEP', '09123456789', NULL, 5, 1, NULL, NULL, NULL, 'Pending', '2025-11-12 14:22:58'),
(4, NULL, 'TEPTEP', '09123456789', NULL, 5, 1, NULL, NULL, NULL, 'Pending', '2025-11-12 15:38:23'),
(5, 1, 'tiffany abella', '09955945430', NULL, 1, 1, 'da9af7044c1d2ad1.png', 'b43f5025af5db855.png', NULL, '', '2025-11-13 02:52:00'),
(6, 1, 'tiffany abella', '09955945430', NULL, 18, 1, NULL, NULL, NULL, '', '2025-11-13 04:04:36'),
(9, NULL, 'tiffany abella', '09955945430', NULL, 14, 1, NULL, NULL, 'Photo Top: Glitter', 'Pending', '2025-11-13 05:37:15'),
(33, 7, 'Senpai', '', NULL, 2, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-21 10:47:51'),
(34, 7, 'Senpai', '', NULL, 1, 2, '70290d15b9a41316.jpg', 'bb0ecceda43c9f0e.jpg', 'Keychain Color: Black (#000000); Instax Message: text; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-21 10:49:41'),
(32, 7, 'Senpai', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Holo Rainbow; Delivery: To address - smcbi campus (Please include ₱20 delivery fee in your GCash payment.); Mode of payment: GCash', 'Pending', '2025-11-21 10:46:24'),
(13, 2, 'tiffany', '09955945430', NULL, 1, 1, '78f3ffda442ec74c.jpg', '31f27471d44cbd75.jpg', 'Keychain Color: Black (#000000); Instax Message: fhfdjfgjhfdhdfh; Delivery: Meet up at SMCBI Campus; Mode of payment: Cash on Meet-up', 'Pending', '2025-11-17 09:38:14'),
(14, 2, 'tiffany', '', NULL, 1, 1, '1af777a5241f38b3.png', '40e8a5b679066df6.png', 'Keychain Color: Black (#000000); Instax Message: I LOVE YOU!; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-18 02:43:41'),
(15, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Mode of payment: GCash', 'Pending', '2025-11-18 04:28:49'),
(16, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Mode of payment: GCash', 'Pending', '2025-11-18 04:29:17'),
(17, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Mode of payment: GCash', 'Pending', '2025-11-18 04:32:33'),
(18, 2, 'tiffany', '', NULL, 18, 1, NULL, NULL, 'Photo Top: Matte; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-18 10:38:33'),
(19, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-18 10:39:20'),
(20, 2, 'tiffany', '', NULL, 18, 1, NULL, NULL, 'Photo Top: Matte; Delivery: Meet up at Barayong NHS; Mode of payment: GCash', 'Pending', '2025-11-18 11:10:49'),
(21, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Meet up at Barayong NHS; Mode of payment: GCash', 'Pending', '2025-11-19 10:50:20'),
(22, 2, 'tiffany', '', NULL, 15, 1, NULL, NULL, 'Photo Top: Leather; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-19 14:26:55'),
(23, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-19 14:27:16'),
(24, 2, 'tiffany', '', NULL, 15, 10, NULL, NULL, 'Photo Top: Leather; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-19 14:40:29'),
(25, 2, 'tiffany', '', NULL, 12, 1, NULL, NULL, 'Photo Top: Holo Rainbow; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Pending', '2025-11-19 14:45:21'),
(38, 6, 'TEP', '', NULL, 17, 2, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; Note to Seller: HIIIIIIIIIIIIIIIIIIIII', 'Cancelled', '2025-11-22 03:01:08'),
(39, 8, 'Romarie Judilla', '', NULL, 2, 1, NULL, NULL, 'Keychain Color: White (#FFFFFF); Spotify Song: All night – beyoncé; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Cancelled', '2025-11-22 04:03:43'),
(28, 2, 'tiffany', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', '', '2025-11-20 02:32:22'),
(29, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Approved', '2025-11-21 02:31:31'),
(37, 6, 'TEP', '', NULL, 14, 1, NULL, NULL, 'Photo Top: Holo Rainbow; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Cancelled', '2025-11-21 15:33:37'),
(31, 6, 'TEP', '', NULL, 9, 1, 'ebe0e063f5979d85.png', 'b5d0c46927b68a88.png', 'Photo Top: Holo Rainbow; Delivery: Meet up at Barayong NHS; Mode of payment: GCash', 'Completed', '2025-11-21 07:22:44'),
(40, 6, 'TEP', '', NULL, 4, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; Note to Seller: asasa', 'Cancelled', '2025-11-22 04:08:16'),
(41, 6, 'TEP', '', NULL, 12, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Cancelled', '2025-11-22 04:47:57'),
(42, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Meet up at Barayong NHS; Mode of payment: GCash', 'Cancelled', '2025-11-22 04:48:58'),
(43, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash', 'Cancelled', '2025-11-22 05:05:05'),
(44, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 122222222222222', 'Pending', '2025-11-22 05:15:43'),
(45, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Photo Top: Glossy; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Pending', '2025-11-22 06:03:01'),
(46, 6, 'TEP', '', NULL, 15, 1, NULL, NULL, 'Delivery: Meet up at Barayong NHS; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Ready', '2025-11-22 07:20:35'),
(48, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Pickup at Capehan Magsaysay Davao del Sur; Mode of payment: GCash; GCash Ref (last digits): 12345', 'Pending', '2025-11-22 08:18:25'),
(49, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Pending', '2025-11-22 08:19:12'),
(50, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 14:01:29'),
(51, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Meet up at Barayong NHS; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 14:12:18'),
(52, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 14:25:20'),
(53, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Delivery: Meet up at Barayong NHS; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 14:53:06'),
(54, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Pending', '2025-11-23 15:26:12'),
(55, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Pickup at Capehan Magsaysay Davao del Sur; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 15:41:50'),
(56, 6, 'TEP', '', NULL, 16, 1, NULL, NULL, 'Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 12222', 'Pending', '2025-11-23 15:45:43'),
(57, 6, 'TEP', '', NULL, 17, 1, NULL, NULL, 'Photo Top: Glitter; Delivery: Pickup at Capehan Magsaysay Davao del Sur; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Cancelled', '2025-11-23 16:11:05'),
(59, 6, 'TEP', '', NULL, 18, 5, NULL, NULL, 'Delivery: Pickup at Capehan Magsaysay Davao del Sur; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Refunded', '2025-11-24 09:37:49'),
(60, 14, 'Tiffany', '', NULL, 15, 1, NULL, NULL, 'Photo Top: Leather; Delivery: Meet up at SMCBI Campus; Mode of payment: GCash; GCash Ref (last digits): 11111', 'Pending', '2025-11-27 00:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `customer_id`, `token`, `expires_at`, `created_at`) VALUES
(9, 6, '2af2398c98ee75a81829aed56f89f5524008803c814087c81d7a5183b32758d8', '2025-11-21 10:56:52', '2025-11-21 14:56:52'),
(10, 10, 'fbd1d93217e0e7d9108eada232134edc3776687f4fcb05226402fa874357acb0', '2025-11-24 10:39:21', '2025-11-24 09:39:21');

-- --------------------------------------------------------

--
-- Table structure for table `products_tbl`
--

CREATE TABLE `products_tbl` (
  `product_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` mediumtext DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products_tbl`
--

INSERT INTO `products_tbl` (`product_id`, `name`, `description`, `price`, `image`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Acrylic Keychain Instax (B1T1, 5.5×4.2cm)', 'Buy 1 Take 1 acrylic Instax keychain (same pictures). Size: 5.5×4.2 cm.', 79.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(2, 'Photobooth Keychain Spotify (B1T1, 8.3×3.3cm, 6 photos)', 'Buy 1 Take 1 photobooth Spotify keychain. Size: 8.3×3.3 cm. Attach 6 photos.', 85.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(3, 'Photobooth Keychain Small (B1T1, 5.5×4.2cm, 6 photos)', 'Buy 1 Take 1 photobooth keychain (small). Size: 5.5×4.2 cm. Attach 6 photos.', 79.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(4, 'Photobooth Keychain Big (B1T1, 8.3×3.3cm, 6 photos)', 'Buy 1 Take 1 photobooth keychain (big). Size: 8.3×3.3 cm. Attach 4 photos.', 85.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(5, 'Instax Mini Wallet Size (18 pcs, 4.7×6.9cm)', '18 pieces per set. Size: 4.7×6.9 cm.', 125.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(6, 'Instax Small (10 pcs, 5.44×8.83cm)', '10 pieces per set. Size: 5.44×8.83 cm.', 79.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(7, 'Instax Square (8 pcs, 7.1×8.6cm)', '8 pieces per set. Size: 7.1×8.6 cm.', 99.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(8, 'Instax Wide (5 pcs, 10.8×8.6cm)', '5 pieces per set. Size: 10.8×8.6 cm.', 130.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(9, 'Photocard – Holographic Rainbow (per pc)', 'Type of photo: Holographic Rainbow. Price is per piece.', 12.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(10, 'Photocard – Leather (per pc)', 'Type of photo: Leather. Price is per piece.', 8.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(11, 'Photocard – Glossy (per pc)', 'Type of photo: Glossy. Price is per piece.', 6.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(12, 'Photocard – Glitter (per pc)', 'Type of photo: Glitter. Price is per piece.', 6.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(13, 'Photocard – Matte (per pc)', 'Type of photo: Matte. Price is per piece.', 6.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(14, 'Ref Magnet – Holographic Rainbow (per pc)', 'Type of photo: Holographic Rainbow. Email us for preferred designs.', 18.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(15, 'Ref Magnet – Leather (per pc)', 'Type of photo: Leather. Email us for preferred designs.', 15.00, NULL, NULL, 1, '2025-11-12 13:35:16', NULL),
(16, 'Ref Magnet – Glossy (per pc)', 'Type of photo: Glossy. Email us for preferred designs.', 12.00, NULL, NULL, 0, '2025-11-12 13:35:16', '2025-11-24 08:01:13'),
(17, 'Ref Magnet – Glitter (per pc)', 'Type of photo: Glitter. Email us for preferred designs.', 15.00, NULL, NULL, 1, '2025-11-12 13:35:16', '2025-11-22 02:40:49'),
(18, 'Ref Magnet – Matte (per pc)', 'Type of photo: Matte. Email us for preferred designs.', 12.00, NULL, NULL, 1, '2025-11-12 13:35:16', '2025-11-21 15:47:18');

-- --------------------------------------------------------

--
-- Table structure for table `uploads_tbl`
--

CREATE TABLE `uploads_tbl` (
  `upload_id` int(11) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `role` enum('front','back','gallery') NOT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `uploads_tbl`
--

INSERT INTO `uploads_tbl` (`upload_id`, `order_id`, `role`, `filename`, `uploaded_at`) VALUES
(1, 1, 'front', '535156aea1ca0c61.png', '2025-11-12 14:20:38'),
(2, 1, 'back', 'bb65a34dd03d14bd.png', '2025-11-12 14:20:38'),
(3, 2, 'front', 'df952535b4d46d89.png', '2025-11-12 14:20:57'),
(4, 2, 'back', '6cf9ae6bf7d938b7.png', '2025-11-12 14:20:57'),
(5, 3, 'gallery', 'd52ff61bf9b56edc.png', '2025-11-12 14:22:58'),
(6, 3, 'gallery', 'cadd64996ffbe74e.png', '2025-11-12 14:22:58'),
(7, 3, 'gallery', 'ed41e34dae837915.png', '2025-11-12 14:22:58'),
(8, 3, 'gallery', '7be0b8f18630d13d.png', '2025-11-12 14:22:58'),
(9, 3, 'gallery', '183f7ae1f5db215f.png', '2025-11-12 14:22:58'),
(10, 3, 'gallery', 'bf490891e8819271.png', '2025-11-12 14:22:58'),
(11, 3, 'gallery', '55a2a84031bda786.png', '2025-11-12 14:22:58'),
(12, 3, 'gallery', 'b1aa5e3af982ed00.png', '2025-11-12 14:22:58'),
(13, 3, 'gallery', '1c85e656eb6de93b.png', '2025-11-12 14:22:58'),
(14, 3, 'gallery', '1656049d80af143d.png', '2025-11-12 14:22:58'),
(15, 3, 'gallery', 'b9478ccce1b91347.png', '2025-11-12 14:22:58'),
(16, 3, 'gallery', '79e8fbc0dbcf2f83.png', '2025-11-12 14:22:58'),
(17, 3, 'gallery', '82cadb0251607816.png', '2025-11-12 14:22:58'),
(18, 3, 'gallery', '597f9dbfceed1d17.png', '2025-11-12 14:22:58'),
(19, 3, 'gallery', 'ea79643a9325702c.png', '2025-11-12 14:22:58'),
(20, 3, 'gallery', 'ee8c60d6d2e28700.png', '2025-11-12 14:22:58'),
(21, 3, 'gallery', '4adc9608ad409df6.png', '2025-11-12 14:22:58'),
(22, 3, 'gallery', '4c46b3c3de545702.png', '2025-11-12 14:22:58'),
(23, 4, 'gallery', 'cac1951b90a08550.png', '2025-11-12 15:38:24'),
(24, 4, 'gallery', '1f590a047722e346.png', '2025-11-12 15:38:24'),
(25, 4, 'gallery', '568e9534e739adeb.png', '2025-11-12 15:38:24'),
(26, 4, 'gallery', '3ffaabae1fdf4c61.png', '2025-11-12 15:38:24'),
(27, 4, 'gallery', 'c5f0648c8b5afcef.png', '2025-11-12 15:38:24'),
(28, 4, 'gallery', '07d11d4ac00a737f.png', '2025-11-12 15:38:24'),
(29, 4, 'gallery', '889c4ebc0c944e79.png', '2025-11-12 15:38:24'),
(30, 4, 'gallery', '7ec92618732ec221.png', '2025-11-12 15:38:24'),
(31, 4, 'gallery', 'b36bc00ae7c0c4ce.png', '2025-11-12 15:38:24'),
(32, 4, 'gallery', 'f6ad40ee8ea288dc.png', '2025-11-12 15:38:24'),
(33, 4, 'gallery', '0bfe2890ddb7b13c.png', '2025-11-12 15:38:24'),
(34, 4, 'gallery', '0ff713450549b3ea.png', '2025-11-12 15:38:24'),
(35, 4, 'gallery', '8965b81aa3ece768.png', '2025-11-12 15:38:24'),
(36, 4, 'gallery', 'c4b725df1f375555.png', '2025-11-12 15:38:24'),
(37, 4, 'gallery', '0e78c808a37efd5b.png', '2025-11-12 15:38:24'),
(38, 4, 'gallery', '7cffcfe944e53606.png', '2025-11-12 15:38:24'),
(39, 4, 'gallery', '756c94b2b7488320.png', '2025-11-12 15:38:24'),
(40, 4, 'gallery', 'f128cd9b17bbdcb3.png', '2025-11-12 15:38:24'),
(41, 5, 'front', 'da9af7044c1d2ad1.png', '2025-11-13 02:52:00'),
(42, 5, 'back', 'b43f5025af5db855.png', '2025-11-13 02:52:00'),
(93, 34, 'back', 'bb0ecceda43c9f0e.jpg', '2025-11-21 10:49:41'),
(44, 9, 'gallery', '2fb175272e44b411.jpg', '2025-11-13 05:37:15'),
(92, 34, 'front', '70290d15b9a41316.jpg', '2025-11-21 10:49:41'),
(91, 33, 'gallery', '279844f8459142d9.png', '2025-11-21 10:47:51'),
(90, 33, '', 'ebac39866c009fb9.jpg', '2025-11-21 10:47:51'),
(89, 32, '', 'f7cfdf818e1c7fd5.jpg', '2025-11-21 10:46:24'),
(88, 32, 'gallery', '51e0453370672723.jpg', '2025-11-21 10:46:24'),
(50, 13, 'front', '78f3ffda442ec74c.jpg', '2025-11-17 09:38:14'),
(51, 13, 'back', '31f27471d44cbd75.jpg', '2025-11-17 09:38:14'),
(52, 14, 'front', '1af777a5241f38b3.png', '2025-11-18 02:43:41'),
(53, 14, 'back', '40e8a5b679066df6.png', '2025-11-18 02:43:41'),
(54, 14, '', '42a99c25c6a068b5.png', '2025-11-18 02:43:41'),
(55, 15, 'gallery', '386f5802493b9fc0.png', '2025-11-18 04:28:49'),
(56, 15, '', '2245737310c0c2a3.png', '2025-11-18 04:28:49'),
(57, 16, 'gallery', '1072bbaeba5c20ae.png', '2025-11-18 04:29:17'),
(58, 16, '', 'bbfcb0b556553d94.png', '2025-11-18 04:29:17'),
(59, 17, 'gallery', 'c9b8d905724110eb.png', '2025-11-18 04:32:33'),
(60, 17, '', '1c452c49baffd7f2.png', '2025-11-18 04:32:33'),
(61, 18, 'gallery', '22d4995c44ccfddb.png', '2025-11-18 10:38:33'),
(62, 18, '', '41a8c7a15eb2b64d.png', '2025-11-18 10:38:33'),
(63, 19, 'gallery', 'da0caa161be58bf3.png', '2025-11-18 10:39:20'),
(64, 19, '', '480783a56e5d8944.png', '2025-11-18 10:39:20'),
(65, 20, 'gallery', '762061de0b8f809d.png', '2025-11-18 11:10:50'),
(66, 20, '', '7a39fb278e789fdf.png', '2025-11-18 11:10:50'),
(67, 21, 'gallery', 'aba75645dcb932a5.jpg', '2025-11-19 10:50:20'),
(68, 21, '', '0b2dff7746a2d025.jpg', '2025-11-19 10:50:20'),
(69, 22, 'gallery', '1d080ee944434f74.png', '2025-11-19 14:26:55'),
(70, 22, '', '7506d24255ac96d4.png', '2025-11-19 14:26:55'),
(71, 23, 'gallery', 'e5e82a3334f47678.png', '2025-11-19 14:27:16'),
(72, 23, '', '53e26e6e01a20b02.png', '2025-11-19 14:27:16'),
(73, 24, 'gallery', '815ee2e53199a5d3.png', '2025-11-19 14:40:29'),
(74, 24, '', '0c0441a8fecba06d.png', '2025-11-19 14:40:29'),
(102, 38, 'gallery', '5bdd03f2b68c7a30.png', '2025-11-22 03:01:08'),
(94, 34, '', '3f05bc6c2f5fa50d.jpg', '2025-11-21 10:49:41'),
(101, 38, '', '318546d432d7a562.png', '2025-11-22 03:01:08'),
(79, 28, 'gallery', 'fecac270a716c316.png', '2025-11-20 02:32:22'),
(80, 28, '', 'e1d489df7e605b0a.png', '2025-11-20 02:32:22'),
(81, 29, '', 'dc99180e0a809130.png', '2025-11-21 02:31:31'),
(82, 29, 'gallery', 'a918b28023f40cbe.jpg', '2025-11-21 02:31:31'),
(100, 37, '', 'd353fc6bb03a9ecc.png', '2025-11-21 15:33:37'),
(99, 37, 'gallery', 'a7c36fa933c07f64.png', '2025-11-21 15:33:37'),
(85, 31, 'front', 'ebe0e063f5979d85.png', '2025-11-21 07:22:44'),
(86, 31, 'back', 'b5d0c46927b68a88.png', '2025-11-21 07:22:44'),
(87, 31, '', 'c55e06c4c028af5a.png', '2025-11-21 07:22:44'),
(104, 39, 'gallery', 'a5b5c403f1adf3bd.jpeg', '2025-11-22 04:03:43'),
(103, 39, 'gallery', 'e6e085010aa88cf4.jpeg', '2025-11-22 04:03:43'),
(105, 39, 'gallery', '6dc0d710b4a81471.jpeg', '2025-11-22 04:03:44'),
(106, 39, '', '2fbea8ce8b10bb2a.jpeg', '2025-11-22 04:03:44'),
(107, 40, '', '4f37fe32e18cb3ad.png', '2025-11-22 04:08:16'),
(108, 40, 'gallery', '7f130e71eac9f536.png', '2025-11-22 04:08:16'),
(109, 40, 'gallery', '3f161d6e1749f17f.png', '2025-11-22 04:08:16'),
(110, 40, 'gallery', '37998088248be0c1.png', '2025-11-22 04:08:16'),
(111, 40, 'gallery', 'be98245889690c3c.png', '2025-11-22 04:08:16'),
(112, 41, '', 'a0c60bb48f99f1de.png', '2025-11-22 04:47:57'),
(113, 41, 'gallery', 'aec89eafde85effb.png', '2025-11-22 04:47:57'),
(114, 42, '', '8a2d0015a8837bdc.png', '2025-11-22 04:48:58'),
(115, 42, 'gallery', 'b03b5b8d9fd1db0b.png', '2025-11-22 04:48:58'),
(116, 43, '', 'c9a34456d1fb6a9c.png', '2025-11-22 05:05:05'),
(117, 43, 'gallery', '23ba7504c985878d.png', '2025-11-22 05:05:05'),
(118, 44, 'gallery', 'b72571a643ee1765.png', '2025-11-22 05:15:43'),
(119, 45, 'gallery', 'f1a29af3a160ca8f.png', '2025-11-22 06:03:01'),
(120, 46, 'gallery', '591fa8d2aae29bfa.png', '2025-11-22 07:20:35'),
(122, 48, 'gallery', '8b0484ceb9e9d871.jpg', '2025-11-22 08:18:25'),
(123, 49, 'gallery', 'dfa89e76ba06e758.jpg', '2025-11-22 08:19:12'),
(124, 50, 'gallery', '4619a43c09408592.png', '2025-11-23 14:01:29'),
(125, 51, 'gallery', '05d808f66391735c.png', '2025-11-23 14:12:18'),
(126, 52, 'gallery', '8097f3726015d640.png', '2025-11-23 14:25:20'),
(127, 53, 'gallery', 'de26a0913eca0361.png', '2025-11-23 14:53:06'),
(128, 54, 'gallery', '437b3984813556b1.png', '2025-11-23 15:26:12'),
(129, 55, 'gallery', '2e242de7f706e774.png', '2025-11-23 15:41:50'),
(130, 57, 'gallery', '72a4a86737df972d.png', '2025-11-23 16:11:05'),
(132, 59, 'gallery', 'd8de11c0fcbfbe1c.jpg', '2025-11-24 09:37:49'),
(133, 60, 'gallery', '9562db648f329207.png', '2025-11-27 00:38:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `content_tbl`
--
ALTER TABLE `content_tbl`
  ADD PRIMARY KEY (`content_id`),
  ADD UNIQUE KEY `section` (`section`);

--
-- Indexes for table `customers_tbl`
--
ALTER TABLE `customers_tbl`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders_tbl`
--
ALTER TABLE `orders_tbl`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_date` (`order_date`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `fk_password_resets_customer` (`customer_id`);

--
-- Indexes for table `products_tbl`
--
ALTER TABLE `products_tbl`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_products_active` (`is_active`);

--
-- Indexes for table `uploads_tbl`
--
ALTER TABLE `uploads_tbl`
  ADD PRIMARY KEY (`upload_id`),
  ADD KEY `idx_uploads_order` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_tbl`
--
ALTER TABLE `admin_tbl`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `content_tbl`
--
ALTER TABLE `content_tbl`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers_tbl`
--
ALTER TABLE `customers_tbl`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `orders_tbl`
--
ALTER TABLE `orders_tbl`
  MODIFY `order_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `products_tbl`
--
ALTER TABLE `products_tbl`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `uploads_tbl`
--
ALTER TABLE `uploads_tbl`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
