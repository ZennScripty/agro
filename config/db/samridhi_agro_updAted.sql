-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 02, 2026 at 03:57 PM
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
-- Database: `samridhi_agro`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `old_data`, `new_data`, `created_at`) VALUES
(2, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 16:30:28'),
(3, 1, 'create', 'staff', 'Created new staff: Mohan Jaiswal (mohan_jaiswal)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 17:28:22'),
(4, 1, 'update', 'staff', 'Updated staff: Mohan Jaiswaaa (mohan_jaiswal)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 17:44:06'),
(5, 1, 'create', 'agent', 'Created new agent: Sohan Jaiswal (AG20264450)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 17:58:06'),
(6, 1, 'create', 'agent', 'Created new agent: radhey Jaiswal (AG20269242)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 17:59:28'),
(7, 1, 'create', 'agent', 'Created new agent: radhey Jaiswal (AG20266259)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:00:21'),
(8, 1, 'update', 'agent', 'Updated agent: bachha yadav (AG20266259)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:04:20'),
(9, 1, 'update', 'agent', 'Updated agent: bachha yadav (AG20266259)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:06:07'),
(10, 1, 'create', 'shop', 'Created new shop: yadav agro (SH20260929)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:14:39'),
(11, 1, 'create', 'shop', 'Created new shop: sona agro (SH20263274)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:16:53'),
(12, 1, 'update', 'shop', 'Updated shop: sona agro (SH20262430)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:23:03'),
(13, 1, 'update', 'staff', 'Added permission \"View Shops\" to staff: Mohan Jaiswaaa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:31:37'),
(14, 1, 'create', 'category', 'Created new category: tool', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:35:36'),
(15, 1, 'create', 'product', 'Created new product: kitanashak (SKU: PRD-2026-10765)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:48:25'),
(16, 1, 'update', 'product', 'Updated product: kitanashaka (SKU: PRD-2026-10765)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 18:55:20'),
(17, 1, 'create', 'order', 'Created order #ORD-2024-0001 for shop: Test Shop', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-09 19:01:31'),
(18, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2024-0002', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-09 19:01:31'),
(19, 1, 'update', 'order', 'Updated order status from confirmed to processing for order #ORD-2024-0003', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-09 19:01:31'),
(20, 1, 'update', 'order', 'Updated order status from processing to shipped for order #ORD-2024-0004', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-08 19:01:31'),
(21, 1, 'update', 'order', 'Updated order status from shipped to delivered for order #ORD-2024-0005', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-06 19:01:31'),
(22, 1, 'update', 'order', 'Cancelled order #ORD-2024-0007', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-05 19:01:31'),
(23, 1, 'update', 'order', 'Order #ORD-2024-0008 marked as returned', '127.0.0.1', 'Mozilla/5.0', NULL, NULL, '2026-08-02 19:01:31'),
(24, 1, 'update', 'profile', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 19:10:10'),
(25, 1, 'update', 'profile', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 19:14:31'),
(26, 1, 'update', 'profile', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-11 19:16:10'),
(27, 1, 'update', 'agent', 'Agent approved: radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 13:16:03'),
(28, 1, 'update', 'agent', 'Updated agent: radhey Jaiswal (AG20269242)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 13:17:34'),
(29, 4, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 13:20:55'),
(30, 1, 'create', 'agent', 'Created new agent: Ravi Kumar (AG20261001)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-28 15:47:09'),
(31, 1, 'update', 'agent', 'Agent approved: Ravi Kumar', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-29 15:47:09'),
(32, 1, 'create', 'agent', 'Created new agent: Priya Singh (AG20261002)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-23 15:47:09'),
(33, 1, 'update', 'agent', 'Agent approved: Priya Singh', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-24 15:47:09'),
(34, 1, 'create', 'agent', 'Created new agent: Sneha Reddy (AG20261004)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-13 15:47:09'),
(35, 1, 'update', 'agent', 'Agent approved: Sneha Reddy', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-14 15:47:09'),
(36, 1, 'create', 'agent', 'Created new agent: Vikram Singh (AG20261005)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-02 15:47:09'),
(37, 1, 'update', 'agent', 'Agent approved: Vikram Singh', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-03 15:47:09'),
(38, 1, 'create', 'shop', 'Created new shop: Rajesh Agro Store (SH20261001)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-07 15:47:09'),
(39, 1, 'update', 'shop', 'Shop approved: Rajesh Agro Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-08 15:47:09'),
(40, 1, 'create', 'shop', 'Created new shop: Green Fields Mart (SH20261002)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-04 15:47:09'),
(41, 1, 'update', 'shop', 'Shop approved: Green Fields Mart', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-05 15:47:09'),
(42, 1, 'create', 'shop', 'Created new shop: Organic World Store (SH20261003)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-31 15:47:09'),
(43, 1, 'update', 'shop', 'Shop approved: Organic World Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-01 15:47:09'),
(44, 1, 'create', 'shop', 'Created new shop: Agri Hub Store (SH20261005)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-25 15:47:09'),
(45, 1, 'update', 'shop', 'Shop approved: Agri Hub Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-26 15:47:09'),
(46, 1, 'create', 'shop', 'Created new shop: Nature\'s Basket (SH20261006)', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-21 15:47:09'),
(47, 1, 'update', 'shop', 'Shop approved: Nature\'s Basket', '::1', 'Mozilla/5.0', NULL, NULL, '2026-07-22 15:47:09'),
(48, 1, 'create', 'order', 'Created order #ORD-2026-0001 for shop: Rajesh Agro Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-09 15:47:09'),
(49, 1, 'create', 'order', 'Created order #ORD-2026-0002 for shop: Green Fields Mart', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-08 15:47:09'),
(50, 1, 'create', 'order', 'Created order #ORD-2026-0003 for shop: Organic World Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-07 15:47:09'),
(51, 1, 'create', 'order', 'Created order #ORD-2026-0004 for shop: Agri Hub Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-05 15:47:09'),
(52, 1, 'create', 'order', 'Created order #ORD-2026-0005 for shop: Nature\'s Basket', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-10 15:47:09'),
(53, 1, 'create', 'order', 'Created order #ORD-2026-0006 for shop: Nature\'s Basket', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-06 15:47:09'),
(54, 1, 'create', 'order', 'Created order #ORD-2026-0007 for shop: Rajesh Agro Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-11 15:47:09'),
(55, 1, 'update', 'payment', 'Collected payment of ₹3640.00 from Rajesh Agro Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-10 15:47:09'),
(56, 1, 'update', 'payment', 'Collected payment of ₹6180.00 from Green Fields Mart', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-09 15:47:09'),
(57, 1, 'update', 'payment', 'Submitted payment of ₹7950.00 to admin for Agri Hub Store', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-08 15:47:09'),
(58, 1, 'update', 'payment', 'Payment confirmed by admin for Nature\'s Basket - ₹3000.00', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-11 15:47:09'),
(59, 1, 'update', 'payment', 'Payment confirmed by admin for Nature\'s Basket - ₹9720.00', '::1', 'Mozilla/5.0', NULL, NULL, '2026-08-10 15:47:09'),
(60, 4, 'logout', 'auth', 'Agent logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 16:26:33'),
(61, 1, 'update', 'shop', 'Shop approved: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 16:27:16'),
(62, 1, 'update', 'shop', 'Shop approved: yadav agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 16:30:01'),
(63, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 16:36:51'),
(64, 7, 'create', 'order', 'Placed order #ORD-2026-01993', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 16:43:10'),
(65, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-01993', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:11:30'),
(66, 1, 'update', 'order', 'Updated order status from confirmed to processing for order #ORD-2026-01993', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:12:14'),
(67, 7, 'create', 'order', 'Placed order #ORD-2026-02873', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:26:24'),
(68, 1, 'update', 'shop', 'Updated shop: sona agro (SH20262430)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:27:59'),
(69, 1, 'update', 'agent', 'Agent approved: bachha yadav', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:29:30'),
(70, 4, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-12 17:30:18'),
(71, 7, 'update', 'profile', 'Updated shop profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:36:46'),
(72, 7, 'create', 'payment', 'Made payment of ₹650 for order #ORD-2026-02873 (Installment 1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 17:56:57'),
(73, 7, 'create', 'payment', 'Made payment of ₹320 for order #ORD-2026-02873 (Installment 2)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:01:21'),
(74, 7, 'create', 'order', 'Placed order #ORD-2026-13500', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:01:38'),
(75, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-13500', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:02:11'),
(76, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-02873', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:02:14'),
(77, 7, 'create', 'order', 'Placed order #ORD-2026-47014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:05:31'),
(78, 7, 'create', 'payment', 'Made payment of ₹200 for order #ORD-2026-47014 (Installment 1)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:14:16'),
(79, 7, 'create', 'payment', 'Made payment of ₹80 for order #ORD-2026-47014 (Installment 2)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:14:40'),
(80, 4, 'update', 'payment', 'Collected payment of ₹580 from sona agro', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-12 18:30:51'),
(81, 7, 'logout', 'auth', 'Shop logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:39:53'),
(82, 4, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:43:46'),
(83, 4, 'logout', 'auth', 'Agent logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-12 18:44:15'),
(84, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-12 18:44:57'),
(85, 4, 'update', 'payment', 'Submitted payment of ₹580.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 18:47:39'),
(86, 1, 'update', 'shop', 'Shop approved: Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 19:00:23'),
(87, 1, 'update', 'shop', 'Shop approved: Farm Fresh Market', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 19:00:28'),
(88, 1, 'update', 'shop', 'Updated shop: Village Mart (SH20261007)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 19:00:40'),
(89, 1, 'update', 'shop', 'Updated shop: yadav agro (SH20260929)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-12 19:00:52'),
(90, 4, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 12:40:39'),
(91, 4, 'update', 'payment', 'Collected payment of ₹340 from sona agro (Receiver: jai)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 13:41:37'),
(92, 4, 'update', 'payment', 'Submitted payment of ₹1310.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 13:43:22'),
(93, 4, 'logout', 'auth', 'Agent logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 13:53:24'),
(94, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:04:05'),
(95, 2, 'update', 'profile', 'Updated staff profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:11:58'),
(96, 1, 'update', 'staff', 'Added permission \"Update Settings\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:30'),
(97, 1, 'update', 'staff', 'Added permission \"Approve Agents\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:36'),
(98, 1, 'update', 'staff', 'Added permission \"Create Agents\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:39'),
(99, 1, 'update', 'staff', 'Added permission \"Delete Agents\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:42'),
(100, 1, 'update', 'staff', 'Added permission \"Edit Agents\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:47'),
(101, 1, 'update', 'staff', 'Added permission \"View Agents\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:53'),
(102, 1, 'update', 'staff', 'Added permission \"Create Categories\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:15:58'),
(103, 1, 'update', 'staff', 'Added permission \"Delete Categories\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:02'),
(104, 1, 'update', 'staff', 'Added permission \"Edit Categories\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:06'),
(105, 1, 'update', 'staff', 'Added permission \"View Categories\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:10'),
(106, 1, 'update', 'staff', 'Added permission \"View Dashboard\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:14'),
(107, 1, 'update', 'staff', 'Added permission \"Update Inventory\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:19'),
(108, 1, 'update', 'staff', 'Added permission \"View Inventory\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:24'),
(109, 1, 'update', 'staff', 'Added permission \"Approve Orders\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:29'),
(110, 1, 'update', 'staff', 'Added permission \"Cancel Orders\" to staff: Mohan Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:16:35'),
(111, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:26:00'),
(112, 1, 'update', 'staff', 'Granted 26 permissions for staff: Mohan Jaiswal (Update Orders, View Orders, Confirm Payments, View Payments, Create Products and 21 more)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:39:12'),
(113, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:42:34'),
(114, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 15:44:18'),
(115, 2, 'logout', 'auth', 'Staff logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 16:11:38'),
(116, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 16:20:49'),
(117, 2, 'logout', 'auth', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 16:20:49'),
(118, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 16:21:20'),
(119, 1, 'update', 'inventory', 'Adjusted stock for kitanashaka: +5 (New: 4)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:35:22'),
(120, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (View Dashboard)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:50:05'),
(121, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:51:21'),
(122, 1, 'update', 'staff', 'Revokeed 5 permissions for staff: Mohan Jaiswal (Approve Shops, Create Shops, Delete Shops, Edit Shops, View Shops)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:53:05'),
(123, 1, 'update', 'staff', 'Granted 6 permissions for staff: Mohan Jaiswal (View Dashboard, Approve Shops, Create Shops, Delete Shops, Edit Shops and 1 more)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:55:59'),
(124, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (Edit Shops, View Shops)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 17:57:43'),
(125, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (Approve Shops)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:01:11'),
(126, 2, 'unauthorized_access', 'security', 'Attempted to access shop-edit.php without shop.edit permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:23:23'),
(127, 2, 'unauthorized_access', 'security', 'Attempted to access shop-edit.php without shop.edit permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:23:34'),
(128, 1, 'update', 'staff', 'Granted 3 permissions for staff: Mohan Jaiswal (Approve Shops, Edit Shops, View Shops)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:26:56'),
(129, 1, 'update', 'staff', 'Revokeed 5 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:28:14'),
(130, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:28:33'),
(131, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:29:12'),
(132, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:29:14'),
(133, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:30:52'),
(134, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 18:31:29'),
(135, 1, 'update', 'staff', 'Granted 5 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:03:13'),
(136, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (View Dashboard)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:03:50'),
(137, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:03:56'),
(138, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:08:58'),
(139, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:09:12'),
(140, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Dashboard)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-13 19:09:48'),
(141, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 11:49:32'),
(142, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 11:54:35'),
(143, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 12:01:47'),
(144, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.19', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 12:02:18'),
(145, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.19', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 12:03:15'),
(146, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 12:04:58'),
(147, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-47014 (Installment 3) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 12:49:14'),
(148, 7, 'create', 'payment', 'Made payment of ₹10 for order #ORD-2026-47014 (Installment 4) to Admin', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 12:57:14'),
(149, 7, 'create', 'payment', 'Made payment of ₹30 for order #ORD-2026-47014 (Installment 5) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:03:54'),
(150, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-47014 (Installment 6) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:12:51'),
(151, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-47014 (Installment 7) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:22:31'),
(152, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-47014 (Installment 8) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:22:59'),
(153, 7, 'create', 'payment', 'Made payment of ₹180 for order #ORD-2026-47014 (Installment 9) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:32:17'),
(154, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-47014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 13:41:13'),
(155, 7, 'create', 'order', 'Placed order #ORD-2026-46045', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:43:47'),
(156, 1, 'update', 'order', 'Updated order status from pending to shipped for order #ORD-2026-46045', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 13:44:36'),
(157, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-46045 (Installment 1) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 13:45:12'),
(158, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-46045 (Installment 2) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-18 14:01:11'),
(159, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 14:09:40'),
(160, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-46045 (Installment 3) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 14:09:56'),
(161, 7, 'create', 'payment', 'Made payment of ₹70 for order #ORD-2026-46045 (Installment 4) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 14:14:01'),
(162, 7, 'create', 'order', 'Placed order #ORD-2026-21725', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 14:16:25'),
(163, 7, 'create', 'payment', 'Made payment of ₹500 for order #ORD-2026-21725 (Installment 1) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 14:18:27'),
(164, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 15:41:16'),
(165, 7, 'create', 'payment', 'Made payment of ₹60 for order #ORD-2026-21725 (Installment 2) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 15:46:38'),
(166, 7, 'create', 'payment', 'Made payment of ₹490 for order #ORD-2026-21725 (Installment 3) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 15:47:36'),
(167, 7, 'create', 'payment', 'Made payment of ₹60 for order #ORD-2026-46045 (Installment 5) to Admin', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 16:04:17'),
(168, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-21725', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 16:30:28'),
(169, 1, 'update', 'order', 'Updated order status from confirmed to processing for order #ORD-2026-21725', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 16:30:48'),
(170, 1, 'update', 'order', 'Updated order status from processing to shipped for order #ORD-2026-21725', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 16:31:01'),
(171, 1, 'update', 'order', 'Updated order status from shipped to delivered for order #ORD-2026-21725', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 16:31:11'),
(172, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 16:43:59'),
(173, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 16:44:08'),
(174, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 16:58:39'),
(175, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 16:58:46'),
(176, 7, 'create', 'order', 'Placed order #ORD-2026-76316', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-18 18:54:55'),
(177, 1, 'create', 'product', 'Created new product: kitanashak (SKU: PRD-2026-82979)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-18 19:00:49'),
(178, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 11:24:41'),
(179, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 11:31:35'),
(180, 7, 'update', 'profile', 'Updated shop profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 11:58:29'),
(181, 7, 'update', 'profile', 'Updated shop profile', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-19 11:59:14'),
(182, 7, 'update', 'profile', 'Updated shop profile', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-19 12:07:43'),
(183, 7, 'update', 'profile', 'Updated shop profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 12:08:13'),
(184, 7, 'update', 'profile', 'Updated shop profile', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-19 12:13:35'),
(185, 7, 'update', 'profile', 'Updated shop profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 12:24:31'),
(186, 7, 'update', 'profile', 'Updated shop profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 12:24:55'),
(187, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 12:26:43'),
(188, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 12:26:46'),
(189, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 12:29:11'),
(190, 4, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 12:29:28'),
(191, 4, 'update', 'profile', 'Updated agent profile', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 12:32:54'),
(192, 4, 'update', 'profile', 'Updated agent profile', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 12:33:14'),
(193, 1, 'update', 'profile', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 12:45:59'),
(194, 1, 'update', 'profile', 'Updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 12:46:15'),
(195, 4, 'logout', 'auth', 'Agent logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 13:25:44'),
(196, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 13:26:21'),
(197, 7, 'logout', 'auth', 'Shop logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 13:38:12'),
(198, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 13:39:16'),
(199, 7, 'create', 'payment', 'Made payment of ₹5 for order #ORD-2026-46045 (Installment 6) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 14:00:05'),
(200, 7, 'create', 'order', 'Placed order #ORD-2026-94016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 14:00:38'),
(201, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-76316 (Installment 1) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 16:37:31'),
(202, 7, 'create', 'payment', 'Made payment of ₹60 for order #ORD-2026-76316 (Installment 2) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 16:37:57'),
(203, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-19 16:38:29'),
(204, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 16:42:54'),
(205, 2, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 16:44:28'),
(206, 1, 'update', 'staff', 'Revokeed 5 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 16:45:32'),
(207, 2, 'unauthorized_access', 'security', 'Attempted to access agents.php without agent.view permission', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 16:45:40'),
(208, 7, 'create', 'order', 'Placed order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 17:05:50'),
(209, 7, 'create', 'order', 'Placed order #ORD-2026-17811', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 17:12:41'),
(210, 7, 'create', 'order', 'Placed order #ORD-2026-54604', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 17:59:08'),
(211, 7, 'create', 'payment', 'Made payment of ₹110 for order #ORD-2026-54604 (Installment 1) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:03:39'),
(212, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-54604 (Installment 2) to Admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:05:11'),
(213, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:08:55'),
(214, 1, 'update', 'order', 'Updated order status from pending to delivered for order #ORD-2026-54604', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:09:07'),
(215, 1, 'update', 'order', 'Cancelled order #ORD-2026-17811 for shop: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:09:24'),
(216, 1, 'update', 'order', 'Cancelled order #ORD-2026-72078 for shop: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:09:34'),
(217, 7, 'create', 'payment', 'Made payment of ₹160 for order #ORD-2026-54604 (Installment 3) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:10:43'),
(218, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-94016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:18:53'),
(219, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-17811', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:21:48'),
(220, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:21:53'),
(221, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:23:51'),
(222, 1, 'update', 'order', 'Updated order status from confirmed to pending for order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:23:57'),
(223, 7, 'update', 'order', 'Cancelled order #ORD-2026-17811 (Stock restored)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:25:52'),
(224, 7, 'update', 'order', 'Cancelled order #ORD-2026-72078 (Stock restored)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:26:24'),
(225, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-17811', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:26:39'),
(226, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:26:43'),
(227, 7, 'update', 'order', 'Cancelled order #ORD-2026-17811', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:32:36'),
(228, 7, 'update', 'order', 'Cancelled order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:32:56'),
(229, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-17811', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:33:26');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `old_data`, `new_data`, `created_at`) VALUES
(230, 1, 'update', 'order', 'Updated order status from cancelled to pending for order #ORD-2026-72078', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:33:31'),
(231, 2, 'update', 'profile', 'Updated staff profile', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 18:38:26'),
(232, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 18:45:59'),
(233, 7, 'logout', 'auth', 'Shop logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:49:29'),
(234, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:51:13'),
(235, 5, 'logout', 'auth', 'Agent logged out', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-19 18:54:18'),
(236, 4, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-19 18:55:12'),
(237, 4, 'update', 'payment', 'Submitted payment of ₹320.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-19 18:59:10'),
(238, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-17811 (Installment 1) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 20:58:59'),
(239, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-17811 (Installment 2) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-19 20:59:33'),
(240, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 16:33:38'),
(241, 1, 'confirm', 'payment', 'Confirmed payment of ₹320.00 from sona agro submitted by radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 16:38:35'),
(242, 1, 'update', 'order', 'Updated order status from delivered to pending for order #ORD-2026-54604', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:20:15'),
(243, 1, 'create', 'product', 'Created new product: drops (SKU: PRD-2026-52510)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:23:09'),
(244, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-21 17:25:20'),
(245, 7, 'create', 'order', 'Placed order #ORD-2026-87548', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-21 17:26:23'),
(246, 1, 'update', 'order', 'Updated order status from pending to confirmed for order #ORD-2026-87548', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:26:50'),
(247, 7, 'create', 'payment', 'Made payment of ₹100 for order #ORD-2026-87548 (Installment 1) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0', NULL, NULL, '2026-08-21 17:27:52'),
(248, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:31:03'),
(249, 5, 'logout', 'auth', 'Agent logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:31:53'),
(250, 4, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-21 17:32:05'),
(251, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-87548 (Installment 2) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:32:06'),
(252, 7, 'create', 'payment', 'Made payment of ₹10 for order #ORD-2026-87548 (Installment 3) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:35:05'),
(253, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-87548 (Installment 4) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:37:51'),
(254, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-87548 (Installment 5) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:50:08'),
(255, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:50:34'),
(256, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:51:53'),
(257, 5, 'logout', 'auth', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:51:53'),
(258, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:53:18'),
(259, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-87548 (Installment 6) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:53:31'),
(260, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:53:54'),
(261, 7, 'create', 'payment', 'Made payment of ₹220 for order #ORD-2026-87548 (Installment 7) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 11:54:07'),
(262, 7, 'create', 'payment', 'Made payment of ₹10 for order #ORD-2026-87548 (Installment 8) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:54:37'),
(263, 7, 'create', 'payment', 'Made payment of ₹11 for order #ORD-2026-87548 (Installment 9) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:55:17'),
(264, 7, 'create', 'payment', 'Made payment of ₹19 for order #ORD-2026-87548 (Installment 10) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 11:55:40'),
(265, 7, 'create', 'payment', 'Made payment of ₹63 for order #ORD-2026-87548 (Installment 11) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:04:09'),
(266, 7, 'create', 'payment', 'Made payment of ₹63 for order #ORD-2026-87548 (Installment 12) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 12:04:34'),
(267, 7, 'create', 'payment', 'Made payment of ₹11 for order #ORD-2026-87548 (Installment 13) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:09:00'),
(268, 7, 'create', 'payment', 'Made payment of ₹3 for order #ORD-2026-87548 (Installment 14) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:09:24'),
(269, 7, 'create', 'payment', 'Made payment of ₹2 for order #ORD-2026-87548 (Installment 15) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:10:17'),
(270, 7, 'create', 'payment', 'Made payment of ₹4 for order #ORD-2026-87548 (Installment 16) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 12:12:06'),
(271, 7, 'create', 'payment', 'Made payment of ₹4 for order #ORD-2026-87548 (Installment 17) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 12:12:22'),
(272, 7, 'create', 'payment', 'Made payment of ₹10 for order #ORD-2026-87548 (Installment 18) to radhey Jaiswal', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-22 12:13:39'),
(273, 7, 'create', 'payment', 'Made payment of ₹14 for order #ORD-2026-87548 (Installment 19) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:14:03'),
(274, 7, 'create', 'payment', 'Made payment of ₹10 for order #ORD-2026-87548 (Installment 20) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:14:21'),
(275, 7, 'create', 'payment', 'Made payment of ₹40 for order #ORD-2026-87548 (Installment 21) to radhey Jaiswal', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-22 12:14:36'),
(276, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:19:40'),
(277, 5, 'check_in', 'attendance', 'Checked in at Unknown location', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:32:06'),
(278, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:32:39'),
(279, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:35:59'),
(280, 5, 'check_out', 'attendance', 'Checked out at Unknown location', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:37:06'),
(281, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 12:38:34'),
(282, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 13:42:35'),
(283, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 13:43:16'),
(284, 1, 'delete', 'weekly_holiday', 'Deleted weekly holiday ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 13:53:33'),
(285, 1, 'create', 'weekly_holiday', 'Created weekly holiday: Sunday', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 13:53:52'),
(286, 1, 'create', 'holiday', 'Created holiday: Raksha Bandhan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 14:02:27'),
(287, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 14:03:28'),
(288, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 14:03:42'),
(289, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:05:51'),
(290, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:08:44'),
(291, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:08:53'),
(292, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:09:18'),
(293, 1, 'update', 'holiday', 'Updated holiday: Raksha Bandhan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:09:37'),
(294, 5, 'check_in', 'attendance', 'Checked in at Unknown location', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:14:47'),
(295, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:15:06'),
(296, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:15:48'),
(297, 5, 'check_out', 'attendance', 'Checked out at Unknown location', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 15:30:48'),
(298, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:23:14'),
(299, 5, 'check_in', 'attendance', 'Checked in at null', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:40:42'),
(300, 5, 'check_out', 'attendance', 'Checked out at null', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:40:54'),
(301, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 16:42:11'),
(302, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:49:38'),
(303, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:55:43'),
(304, 5, 'check_in', 'attendance', 'Checked in at null', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:56:01'),
(305, 5, 'check_out', 'attendance', 'Checked out at null', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 16:56:07'),
(306, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 17:02:08'),
(307, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:07:16'),
(308, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:07:39'),
(309, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 17:08:50'),
(310, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:09:08'),
(311, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:12:25'),
(312, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:15:50'),
(313, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:18:38'),
(314, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:40:47'),
(315, 5, 'check_out', 'attendance', 'Checked out at null', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 17:45:29'),
(316, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 17:59:50'),
(317, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 18:08:17'),
(318, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 18:09:56'),
(319, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 18:10:44'),
(320, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-24 18:12:20'),
(321, 5, 'check_in', 'attendance', 'Checked in at Unknown location', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:36:09'),
(322, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:37:45'),
(323, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:39:18'),
(324, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:41:47'),
(325, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:50:25'),
(326, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Linux; Android 13; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 18:50:32'),
(327, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-24 18:54:58'),
(328, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-24 19:19:41'),
(329, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 12:39:31'),
(330, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 12:40:03'),
(331, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 12:53:12'),
(332, 1, 'logout', 'auth', 'Agent logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 12:54:34'),
(333, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 12:55:06'),
(334, 5, 'logout', 'auth', 'Agent logged out', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 12:57:20'),
(335, 2, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 12:58:05'),
(336, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:00:13'),
(337, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:13:34'),
(338, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:13:40'),
(339, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:16:44'),
(340, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:21:22'),
(341, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:22:04'),
(342, 2, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:22:33'),
(343, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:23:05'),
(344, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:23:38'),
(345, 2, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:23:51'),
(346, 2, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:43:11'),
(347, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:47:53'),
(348, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:50:13'),
(349, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:51:02'),
(350, 2, 'update', 'profile', 'Updated staff profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:52:01'),
(351, 2, 'update', 'profile', 'Updated staff profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:52:20'),
(352, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:52:54'),
(353, 2, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:55:21'),
(354, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:57:11'),
(355, 2, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 13:57:37'),
(356, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 13:59:30'),
(357, 2, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 14:01:01'),
(358, 2, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:01:34'),
(359, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:02:44'),
(360, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:05:38'),
(361, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 15:08:45'),
(362, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 15:10:35'),
(363, 1, 'update', 'settings', 'Updated attendance settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 15:18:50'),
(364, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:18:59'),
(365, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-25 15:32:22'),
(366, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:36:45'),
(367, 5, 'update', 'profile', 'Updated agent profile', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-25 15:39:04'),
(368, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-25 15:49:37'),
(369, 5, 'logout', 'auth', 'Agent logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-25 16:00:32'),
(370, 2, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-25 16:01:11'),
(371, 1, 'create', 'agent', 'Created new agent: test agent (AG20269887)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 17:30:45'),
(372, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Agri Hub Store', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 17:39:34'),
(373, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 17:45:16'),
(374, 5, 'update', 'visit', 'Completed visit #2 for shop: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 18:42:45'),
(375, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 18:49:24'),
(376, 5, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-25 18:55:39'),
(377, 5, 'logout', 'auth', 'User logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-25 18:55:39'),
(378, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-25 18:56:49'),
(379, 5, 'create', 'visit', 'Created visit for shop: test shop', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-25 19:05:28'),
(380, 1, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:46:30'),
(381, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:49:24'),
(382, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:50:44'),
(383, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:51:11'),
(384, 7, 'update', 'profile', 'Updated shop profile with location', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-26 11:53:14'),
(385, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:55:49'),
(386, 1, 'update', 'shop', 'Updated shop: sona agro (SH20262430)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:56:21'),
(387, 1, 'update', 'shop', 'Updated shop: Village Mart (SH20261007)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 11:56:46'),
(388, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 12:00:16'),
(389, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 12:00:55'),
(390, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 12:04:06'),
(391, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Organic World Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 12:09:00'),
(392, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 12:13:40'),
(393, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:01:19'),
(394, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:14:41'),
(395, 1, 'delete', 'visit', 'Deleted visit ID: 8', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:15:14'),
(396, 1, 'delete', 'visit', 'Deleted visit ID: 7', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:15:22'),
(397, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Rajesh Agro Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:22:39'),
(398, 1, 'create', 'visit', 'Assigned visit to agent ID: 9 for shop: Agri Hub Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:30:00'),
(399, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: sona agro', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 13:53:51'),
(400, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Organic World Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 14:58:52'),
(401, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 15:21:17'),
(402, 5, 'update', 'visit', 'Completed visit #12 for shop: Organic World Store', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 16:12:20'),
(403, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-08-26 16:27:50'),
(404, 3, 'create', 'visit', 'Created visit for shop: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 16:57:05'),
(405, 3, 'create', 'visit', 'Created visit for shop: raipur agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 17:09:43'),
(406, 3, 'create', 'visit', 'Created visit for shop: new shop', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 17:27:22'),
(407, 3, 'create', 'visit', 'Created visit for shop: Rajesh Agro Store', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 17:35:39'),
(408, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 17:39:42'),
(409, 3, 'create', 'visit', 'Created visit for shop: Focus media', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 17:47:03'),
(410, 3, 'create', 'visit', 'Created visit for shop: Focus media', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 17:47:21'),
(411, 3, 'create', 'visit', 'Created visit for shop: Focus media', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 17:48:27'),
(412, 3, 'create', 'visit', 'Created visit for shop: Focus media', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 17:48:44'),
(413, 1, 'update', 'visit', 'Updated visit status to cancelled', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 18:20:19'),
(414, 1, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 18:20:31'),
(415, 1, 'delete', 'visit', 'Deleted visit ID: 13', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 18:20:51'),
(416, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Nature\'s Basket', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 18:21:17'),
(417, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 18:43:07'),
(418, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-26 18:43:21'),
(419, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.21', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', NULL, NULL, '2026-08-26 18:53:10'),
(420, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 11:14:39'),
(421, 3, 'create', 'visit', 'Created visit for shop: Village Mart', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 11:15:52'),
(422, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 11:31:40'),
(423, 5, 'update', 'visit', 'Completed visit #6 for shop: sona agro', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 12:03:35'),
(424, 1, 'create', 'visit', 'Assigned visit to agent ID: 3 for shop: Village Mart', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 12:41:38'),
(425, 5, 'update', 'visit', 'Completed visit #23 for shop: Village Mart', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 12:44:32'),
(426, 3, 'create', 'visit', 'Created visit for shop: Organic World Store', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 13:18:00'),
(427, 5, 'create', 'visit', 'Created visit #24 for shop: Organic World Store', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 13:18:00'),
(428, 1, 'update', 'staff', 'Toggled staff status to inactive for user ID: 3', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 14:59:57'),
(429, 1, 'update', 'staff', 'Toggled staff status to active for user ID: 3', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:00:04'),
(430, 5, 'check_in', 'attendance', 'Checked in at Unknown location', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:04:48'),
(431, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:05:59'),
(432, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:06:29'),
(433, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:11:10'),
(434, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:13:39'),
(435, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:15:25'),
(436, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:16:51'),
(437, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:23:04'),
(438, 1, 'update', 'attendance', 'Updated staff attendance for Mohan Jaiswal (Status: half_day, Overtime: 0h)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:33:24'),
(439, 1, 'update', 'attendance', 'Updated attendance status for user: Mohan Jaiswal', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:50:03'),
(440, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 15:53:21');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `old_data`, `new_data`, `created_at`) VALUES
(441, 1, 'update', 'attendance', 'Updated attendance status for user: Mohan Jaiswal', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 15:55:05'),
(442, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 17:12:04'),
(443, 1, 'update', 'product', 'Toggled product status to inactive for: drops', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:17:19'),
(444, 1, 'create', 'category', 'Created new category: abcd', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:18:49'),
(445, 1, 'create', 'category', 'Created new category: xyz', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:19:05'),
(446, 1, 'update', 'agent', 'Agent approved: Amit Patel', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:33:22'),
(447, 1, 'update', 'inventory', 'Adjusted stock for kitanashaka: -1 (New: 3)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:36:18'),
(448, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 17:39:06'),
(449, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 17:41:02'),
(450, 5, 'logout', 'auth', 'User logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 17:41:02'),
(451, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-27 17:42:02'),
(452, 1, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:44:13'),
(453, 1, 'update', 'staff', 'Toggled staff status to inactive for user ID: 3', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:50:06'),
(454, 1, 'update', 'staff', 'Toggled staff status to active for user ID: 3', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:50:12'),
(455, 1, 'update', 'agent', 'Agent suspended: test agent', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:50:19'),
(456, 1, 'update', 'inventory', 'Adjusted stock for kitanashaka: +5 (New: 8)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-27 17:53:33'),
(457, 1, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 11:45:56'),
(458, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 11:49:02'),
(459, 1, 'update', 'attendance', 'Updated attendance status for user: bachha yadav', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 12:02:58'),
(460, 7, 'logout', 'auth', 'Shop logged out', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 12:17:49'),
(461, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 12:18:20'),
(462, 5, 'check_in', 'attendance', 'Checked in at Unknown location', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 12:18:30'),
(463, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 12:21:07'),
(464, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 13:50:44'),
(465, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 13:52:31'),
(466, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 13:53:31'),
(467, 7, 'create', 'order', 'Placed order #ORD-2026-98175', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 13:55:19'),
(468, 1, 'update', 'order', 'Updated order status from pending to processing for order #ORD-2026-98175', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 13:59:36'),
(469, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 15:05:49'),
(470, 7, 'create', 'payment', 'Made payment of ₹500 for order #ORD-2026-98175 (Installment 1) to bachha yadav', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 15:06:30'),
(471, 7, 'create', 'payment', 'Made payment of ₹250 for order #ORD-2026-98175 (Installment 2) to Admin', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 15:07:00'),
(472, 7, 'create', 'payment', 'Made payment of ₹320 for order #ORD-2026-98175 (Installment 3) to bachha yadav', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 15:07:10'),
(473, 5, 'update', 'payment', 'Collected payment of ₹6 from sona agro (Receiver: test name)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 15:10:14'),
(474, 7, 'create', 'order', 'Placed order #ORD-2026-79502', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 15:11:07'),
(475, 1, 'update', 'order', 'Updated order status from pending to shipped for order #ORD-2026-79502', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 15:11:29'),
(476, 7, 'create', 'payment', 'Made payment of ₹200 for order #ORD-2026-79502 (Installment 1) to bachha yadav', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 15:11:44'),
(477, 5, 'update', 'payment', 'Collected payment of ₹350 from sona agro (Receiver: test)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 15:38:12'),
(478, 5, 'update', 'payment', 'Submitted payment of ₹550.00 to admin for sona agro', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 15:52:12'),
(479, 7, 'create', 'order', 'Placed order #ORD-2026-09591', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 16:12:35'),
(480, 7, 'create', 'payment', 'Made payment of ₹50 for order #ORD-2026-09591 (Installment 1) to Agent', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 16:13:48'),
(481, 7, 'create', 'payment', 'Made payment of ₹20 for order #ORD-2026-09591 (Installment 2) to Agent', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 16:14:02'),
(482, 7, 'create', 'payment', 'Made payment of ₹130 for order #ORD-2026-09591 (Installment 3) to Admin', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 16:17:02'),
(483, 7, 'create', 'order', 'Placed order #ORD-2026-57829', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 16:34:22'),
(484, 7, 'create', 'payment', 'Made payment of ₹30 for order #ORD-2026-57829 (Installment 1) to bachha yadav', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 16:34:48'),
(485, 7, 'create', 'payment', 'Made payment of ₹60 for order #ORD-2026-57829 (Installment 2) to Admin (Direct)', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-08-31 16:35:15'),
(486, 7, 'create', 'payment', 'Made payment of ₹60 for Order #ORD-2026-57829 (Installment 3) to bachha yadav', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 17:22:00'),
(487, 7, 'create', 'order', 'Placed order #ORD-2026-41989', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 19:04:26'),
(488, 7, 'create', 'payment', 'Made payment of ₹50 for Order #ORD-2026-41989 to Admin', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 19:20:56'),
(489, 7, 'create', 'order', 'Placed order #ORD-2026-92033', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 19:27:07'),
(490, 7, 'login', 'auth', 'User logged in successfully', '10.145.87.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:31:27'),
(491, 1, 'login', 'auth', 'User logged in successfully', '10.145.87.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 23:31:57'),
(492, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-08-31 23:48:45'),
(493, 7, 'logout', 'auth', 'Shop logged out', '10.145.87.166', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:49:11'),
(494, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:49:22'),
(495, 7, 'create', 'order', 'Placed order #ORD-2026-19216', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:50:01'),
(496, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:52:26'),
(497, 7, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-08-31 23:52:35'),
(498, 7, 'create', 'order', 'Placed order #ORD-2026-05100', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 00:24:08'),
(499, 7, 'create', 'order', 'Placed order #ORD-2026-58722', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 00:52:00'),
(500, 7, 'create', 'order', 'Placed order #ORD-2026-92969', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 01:00:23'),
(501, 7, 'create', 'order', 'Placed order #ORD-2026-80213', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 01:13:13'),
(502, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 01:22:25'),
(503, 7, 'create', 'order', 'Placed order #ORD-2026-93887', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 01:38:46'),
(504, 7, 'create', 'payment', 'Made payment of ₹500 to bachha yadav (Payment #1)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 01:40:20'),
(505, 7, 'create', 'order', 'Placed order #ORD-2026-74930', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 01:56:19'),
(506, 7, 'create', 'order', 'Placed order #ORD-2026-07864', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:08:56'),
(507, 7, 'create', 'payment', 'Made payment of ₹400 to bachha yadav (Payment #2)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:09:30'),
(508, 7, 'logout', 'auth', 'Shop logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:09:46'),
(509, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:10:00'),
(510, 7, 'create', 'payment', 'Made payment of ₹200 to bachha yadav (Payment #3)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 02:11:14'),
(511, 5, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:13:07'),
(512, 5, 'check_in', 'attendance', 'Checked in at Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 493332, India', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:13:58'),
(513, 5, 'update', 'payment', 'Collected payment of ₹200.00 from sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:38:34'),
(514, 5, 'update', 'payment', 'Collected payment of ₹400.00 from sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:40:29'),
(515, 5, 'update', 'payment', 'Submitted payment of ₹200.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:43:42'),
(516, 5, 'update', 'payment', 'Submitted payment of ₹400.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 02:45:51'),
(517, 5, 'update', 'payment', 'Collected payment of ₹200.00 from sona agro', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-09-01 02:50:02'),
(518, 5, 'update', 'payment', 'Collected payment of ₹200.00 from sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:10:02'),
(519, 5, 'update', 'payment', 'Submitted payment of ₹200.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:11:22'),
(520, 5, 'update', 'payment', 'Collected payment of ₹200.00 from sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:22:44'),
(521, 5, 'update', 'payment', 'Collected payment of ₹400.00 from sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:22:52'),
(522, 5, 'update', 'payment', 'Submitted payment of ₹400.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:23:23'),
(523, 5, 'update', 'payment', 'Submitted payment of ₹200.00 to admin for sona agro', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 03:23:40'),
(524, 7, 'create', 'payment', 'Made payment of ₹20 to Admin (Payment #4)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 03:24:45'),
(525, 1, 'reject', 'payment', 'Rejected payment of ₹20.00 from sona agro (Reason: test by admin side)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:22:00'),
(526, 1, 'confirm', 'payment', 'Confirmed payment of ₹200.00 from sona agro (Agent: bachha yadav)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:22:22'),
(527, 7, 'logout', 'auth', 'Shop logged out', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:33:04'),
(528, 19, 'login', 'auth', 'User logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:34:03'),
(529, 19, 'create', 'order', 'Placed order #ORD-2026-26561', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:34:41'),
(530, 19, 'create', 'payment', 'Made payment of ₹500 to bachha yadav (Payment #5)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:35:09'),
(531, 19, 'create', 'payment', 'Made payment of ₹300 to Admin (Payment #6)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:35:36'),
(532, 1, 'update', 'order', 'Updated order status from pending to shipped for order #ORD-2026-26561', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:39:32'),
(533, 1, 'update', 'order', 'Updated order status from pending to shipped for order #ORD-2026-07864', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:39:37'),
(534, 1, 'confirm', 'payment', 'Confirmed payment of ₹300.00 from Village Mart (Direct)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:40:00'),
(535, 5, 'update', 'payment', 'Collected payment of ₹500.00 from Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:43:11'),
(536, 5, 'update', 'payment', 'Submitted payment of ₹500.00 to admin for Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:45:39'),
(537, 1, 'confirm', 'payment', 'Confirmed payment of ₹500.00 from Village Mart (Agent: bachha yadav)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:46:14'),
(538, 19, 'create', 'payment', 'Made payment of ₹50 to bachha yadav (Payment #7)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:47:52'),
(539, 19, 'create', 'payment', 'Made payment of ₹60 to Admin (Payment #8)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 12:49:42'),
(540, 5, 'update', 'payment', 'Collected payment of ₹50.00 from Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:54:18'),
(541, 5, 'update', 'payment', 'Submitted payment of ₹50.00 to admin for Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:55:21'),
(542, 1, 'confirm', 'payment', 'Confirmed payment of ₹50.00 from Village Mart (Agent: bachha yadav)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 12:56:00'),
(543, 19, 'create', 'payment', 'Made payment of ₹100 to bachha yadav (Payment #9)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:06:05'),
(544, 19, 'create', 'order', 'Placed order #ORD-2026-33595', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:14:32'),
(545, 19, 'create', 'payment', 'Made payment of ₹500 to bachha yadav (Payment #10)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:15:01'),
(546, 19, 'create', 'payment', 'Made payment of ₹500 to Admin (Payment #11)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:15:51'),
(547, 5, 'update', 'payment', 'Collected payment of ₹500.00 from Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:17:29'),
(548, 5, 'update', 'payment', 'Submitted payment of ₹500.00 to admin for Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:18:07'),
(549, 1, 'confirm', 'payment', 'Confirmed payment of ₹500.00 from Village Mart (Agent: bachha yadav)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:18:23'),
(550, 5, 'update', 'payment', 'Collected payment of ₹100.00 from Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:21:51'),
(551, 5, 'update', 'payment', 'Submitted payment of ₹100.00 to admin for Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:26:57'),
(552, 1, 'confirm', 'payment', 'Confirmed payment of ₹500.00 from Village Mart (Direct)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:53:23'),
(553, 1, 'confirm', 'payment', 'Confirmed payment of ₹100.00 from Village Mart (Agent: bachha yadav)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:54:38'),
(554, 19, 'create', 'payment', 'Made payment of ₹99 to bachha yadav (Payment #12)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:55:47'),
(555, 5, 'update', 'payment', 'Collected payment of ₹99.00 from Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:56:06'),
(556, 5, 'update', 'payment', 'Submitted payment of ₹99.00 to admin for Village Mart', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 13:56:10'),
(557, 19, 'create', 'payment', 'Made payment of ₹100 to Admin (Payment #13)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 13:59:00'),
(558, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 14:55:38'),
(559, 7, 'logout', 'auth', 'User logged out', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 14:55:38'),
(560, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 14:56:19'),
(561, 7, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-01 14:59:00'),
(562, 1, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 15:17:32'),
(563, 1, 'update', 'order', 'Updated order status from shipped to delivered for order #ORD-2026-07864', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 15:18:12'),
(564, 7, 'create', 'payment', 'Made payment of ₹50 to bachha yadav (Payment #14)', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-01 15:34:01'),
(565, 1, 'confirm', 'payment', 'Confirmed payment of ₹400.00 from sona agro (Agent: bachha yadav)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 15:37:15'),
(566, 7, 'logout', 'auth', 'Shop logged out', '192.168.1.4', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-09-01 17:26:59'),
(567, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', NULL, NULL, '2026-09-01 17:27:20'),
(568, 7, 'logout', 'auth', 'Shop logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-01 17:52:34'),
(569, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-01 17:53:00'),
(570, 1, 'create', 'holiday', 'Created holiday: test', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 18:04:44'),
(571, 5, 'check_out', 'attendance', 'Checked out at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-01 18:58:18'),
(572, 1, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 23:39:26'),
(573, 7, 'login', 'auth', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-01 23:40:18'),
(574, 19, 'update', 'order', 'Cancelled order #ORD-2026-33595', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 23:49:07'),
(575, 19, 'create', 'order', 'Placed order #ORD-2026-90490', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:154.0) Gecko/20100101 Firefox/154.0', NULL, NULL, '2026-09-01 23:50:22'),
(576, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 11:42:02'),
(577, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 11:59:36'),
(578, 5, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 12:42:03'),
(579, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:13:39'),
(580, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:13:51'),
(581, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 13:16:51'),
(582, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 13:17:21'),
(583, 1, 'update', 'settings', 'Updated system settings', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:20:55'),
(584, 1, 'update', 'settings', 'Updated system settings', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:21:19'),
(585, 1, 'update', 'staff', 'Toggled staff status to inactive for user ID: 2', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:26:57'),
(586, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:27:25'),
(587, 2, 'logout', 'auth', 'User logged out', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:27:25'),
(588, 1, 'update', 'staff', 'Toggled staff status to active for user ID: 2', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:27:36'),
(589, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:28:08'),
(590, 1, 'update', 'staff', 'Revokeed 41 permissions for staff: Mohan Jaiswal (Create Categories, Delete Categories, Edit Categories, View Categories, View Dashboard and 36 more)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:30:04'),
(591, 1, 'update', 'staff', 'Granted 5 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:41:40'),
(592, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:43:06'),
(593, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:43:40'),
(594, 1, 'update', 'staff', 'Revokeed 4 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:44:37'),
(595, 2, 'unauthorized_access', 'security', 'Attempted to access agent-edit.php without agent.edit permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:44:51'),
(596, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:46:38'),
(597, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:46:47'),
(598, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:46:52'),
(599, 2, 'unauthorized_access', 'security', 'User attempted to access /agro/admin/agents.php?action=delete&id=9&csrf=3ccc24ab77fba26b2b5be1a3e4db2327f3011ffc33a09a83031159ae64cfd3fb without permission: agent.delete', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:47:46'),
(600, 2, 'unauthorized_access', 'security', 'User attempted to access /agro/admin/agents.php?action=toggle&id=9&csrf=3ccc24ab77fba26b2b5be1a3e4db2327f3011ffc33a09a83031159ae64cfd3fb without permission: agent.edit', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 13:56:48'),
(601, 2, 'unauthorized_access', 'security', 'Attempted to access agent-add.php without agent.create permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 14:02:06'),
(602, 2, 'unauthorized_access', 'security', 'Attempted to access agent-edit.php without agent.edit permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 14:05:09'),
(603, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 14:12:47'),
(604, 2, 'unauthorized_access', 'security', 'Attempted to access dashboard.php without dashboard.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 14:13:04'),
(605, 2, 'unauthorized_access', 'security', 'Attempted to access shop-view.php without shop.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:10:28'),
(606, 1, 'update', 'staff', 'Granted 4 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:11:47'),
(607, 1, 'update', 'staff', 'Granted 4 permissions for staff: Mohan Jaiswal (Create Categories, Delete Categories, Edit Categories, View Categories)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:12:34'),
(608, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Dashboard)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:15:09'),
(609, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (Update Inventory, View Inventory)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:16:46'),
(610, 2, 'update', 'inventory', 'Adjusted stock for Drip Irrigation Kit: -1 (New: 8)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:17:42'),
(611, 2, 'unauthorized_access', 'security', 'Attempted to access product-edit.php without product.edit permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:17:58'),
(612, 2, 'unauthorized_access', 'security', 'Attempted to access product-edit.php without product.edit permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:18:20'),
(613, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (Update Inventory, View Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:25:19'),
(614, 1, 'update', 'staff', 'Granted 5 permissions for staff: Mohan Jaiswal (Update Inventory, Create Products, Delete Products, Edit Products, View Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:26:05'),
(615, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (Delete Agents, View Categories)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:34:07'),
(616, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Categories)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:34:27'),
(617, 2, 'update', 'category', 'Toggled category status to inactive for: tool', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:35:30'),
(618, 1, 'update', 'staff', 'Granted 0 permissions for staff: Mohan Jaiswal ()', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:36:16'),
(619, 1, 'update', 'category', 'Toggled category status to active for: tool', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:37:36'),
(620, 1, 'update', 'category', 'Toggled category status to inactive for: tool', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:37:49'),
(621, 1, 'update', 'category', 'Toggled category status to active for: tool', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:38:00'),
(622, 2, 'update', 'category', 'Toggled category status to inactive for: abcd', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:40:12'),
(623, 2, 'update', 'category', 'Toggled category status to active for: abcd', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:40:19'),
(624, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (Create Categories)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:41:26'),
(625, 2, 'unauthorized_access', 'security', 'Attempted to access category-add.php without category.create permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:41:32'),
(626, 1, 'update', 'staff', 'Revokeed 3 permissions for staff: Mohan Jaiswal (Delete Categories, Edit Categories, View Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:47:39'),
(627, 1, 'update', 'staff', 'Granted 4 permissions for staff: Mohan Jaiswal (Create Categories, Delete Categories, Edit Categories, View Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:48:21'),
(628, 1, 'update', 'staff', 'Granted 0 permissions for staff: Mohan Jaiswal ()', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:50:53'),
(629, 2, 'update', 'product', 'Updated product: drops (SKU: PRD-2026-52510)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:51:42'),
(630, 1, 'update', 'staff', 'Revokeed 3 permissions for staff: Mohan Jaiswal (Create Products, Delete Products, Edit Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:56:40'),
(631, 1, 'update', 'staff', 'Granted 3 permissions for staff: Mohan Jaiswal (Create Products, Delete Products, Edit Products)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 15:57:00'),
(632, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (Update Orders, View Orders)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:02:54'),
(633, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (Update Orders)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:05:37'),
(634, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (Update Orders)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:05:58'),
(635, 1, 'update', 'staff', 'Granted 4 permissions for staff: Mohan Jaiswal (Create Staff, Delete Staff, Edit Staff, View Staff)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:11:05'),
(636, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (View Staff)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:16:37'),
(637, 2, 'unauthorized_access', 'security', 'Attempted to access staff.php without staff.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:16:39'),
(638, 2, 'unauthorized_access', 'security', 'Attempted to access staff.php without staff.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:16:58'),
(639, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Staff)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:18:19'),
(640, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (Create Staff, Edit Staff)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:26:29'),
(641, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (Create Staff, Edit Staff)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:28:06'),
(642, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (staff permision)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:51:51'),
(643, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (Delete Agents)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:53:56'),
(644, 1, 'update', 'staff', 'Granted 5 permissions for staff: Mohan Jaiswal (Approve Shops, Create Shops, Delete Shops, Edit Shops, View Shops)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:54:51'),
(645, 2, 'update', 'shop', 'Shop suspended: Rajesh Agro Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:55:24'),
(646, 2, 'update', 'shop', 'Shop activated: Rajesh Agro Store', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 16:55:50'),
(647, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (Delete Shops)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:02:53');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `old_data`, `new_data`, `created_at`) VALUES
(648, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (Delete Shops)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:03:15'),
(649, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Visits)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:04:32'),
(650, 2, 'unauthorized_access', 'security', 'User attempted to access /agro/admin/visits.php without permission: visit.assign', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:09:52'),
(651, 2, 'unauthorized_access', 'security', 'User attempted to access /agro/admin/visits.php?action=cancel&id=21&csrf=3ccc24ab77fba26b2b5be1a3e4db2327f3011ffc33a09a83031159ae64cfd3fb without permission: visit.edit', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:13:52'),
(652, 2, 'unauthorized_access', 'security', 'User attempted to access /agro/admin/visits.php?action=cancel&id=21&csrf=3ccc24ab77fba26b2b5be1a3e4db2327f3011ffc33a09a83031159ae64cfd3fb without permission: visit.edit', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:14:04'),
(653, 1, 'update', 'visit', 'Updated visit status to cancelled', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:14:22'),
(654, 1, 'update', 'staff', 'Granted 3 permissions for staff: Mohan Jaiswal (Assign Visits, Delete Visits, Edit Visits)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:16:46'),
(655, 1, 'delete', 'category', 'Deleted category: xyz', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:20:54'),
(656, 1, 'update', 'product', 'Updated product: dropss (SKU: PRD-2026-52510)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:21:26'),
(657, 1, 'update', 'staff', 'Revokeed 1 permissions for staff: Mohan Jaiswal (Delete Visits)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:24:36'),
(658, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (Delete Visits)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:25:14'),
(659, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (Confirm Payments, View Payments)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:28:52'),
(660, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (Confirm Payments, View Payments)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:29:20'),
(661, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (Confirm Payments, View Payments)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:29:52'),
(662, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (View Attendance Settings)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:43:14'),
(663, 1, 'update', 'staff', 'Granted 1 permissions for staff: Mohan Jaiswal (attendance list)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:52:01'),
(664, 1, 'update', 'staff', 'Granted 0 permissions for staff: Mohan Jaiswal ()', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:53:23'),
(665, 1, 'update', 'staff', 'Revokeed 2 permissions for staff: Mohan Jaiswal (attendance list, View Attendance Settings)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:53:56'),
(666, 2, 'unauthorized_access', 'security', 'Attempted to access attendance-list.php without attendance.list permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:54:02'),
(667, 2, 'unauthorized_access', 'security', 'Attempted to access attendance-settings.php without attendance.settings.view permission', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:54:07'),
(668, 1, 'update', 'staff', 'Granted 2 permissions for staff: Mohan Jaiswal (attendance list, View Attendance Settings)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 17:54:28'),
(669, 1, 'update', 'staff', 'Revokeed 4 permissions for staff: Mohan Jaiswal (Create Shops, Delete Shops, Edit Shops, View Shops)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 18:49:30'),
(670, 1, 'update', 'staff', 'Revokeed 33 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents and 28 more)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 18:50:19'),
(671, 1, 'update', 'staff', 'Granted 5 permissions for staff: Mohan Jaiswal (Approve Agents, Create Agents, Delete Agents, Edit Agents, View Agents)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:01:06'),
(672, 1, 'update', 'staff', 'Granted 4 permissions for staff: Mohan Jaiswal (Create Categories, Delete Categories, Edit Categories, View Categories)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:01:31'),
(673, 1, 'update', 'staff', 'Granted 24 permissions for staff: Mohan Jaiswal (Update Inventory, View Inventory, Update Orders, View Orders, View Payments and 19 more)', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:02:13'),
(674, 5, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:13:41'),
(675, 5, 'logout', 'auth', 'Agent logged out', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:13:55'),
(676, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:14:11'),
(677, 1, 'update', 'settings', 'Updated attendance settings', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:14:55'),
(678, 2, 'check_in', 'attendance', 'Checked in at Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:15:00'),
(679, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:18:04'),
(680, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:18:40'),
(681, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:20:16'),
(682, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Mobile Safari/537.36', NULL, NULL, '2026-09-02 19:21:00'),
(683, 2, 'login', 'auth', 'User logged in successfully', '192.168.1.4', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', NULL, NULL, '2026-09-02 19:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agent_code` varchar(50) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `user_id`, `agent_code`, `company_name`, `gst_number`, `address`, `city`, `state`, `pincode`, `commission_rate`, `status`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 3, 'AG20264450', 'TechCorp Solutions', '', 'gopalpur road sirpur bajar chauk', 'sarsiwa', 'Chhattisgarh', '493559', 0.00, 'approved', 1, '2026-08-11 17:58:06', '2026-08-11 17:58:06', '2026-08-11 17:58:06'),
(2, 4, 'AG20269242', 'test Company Name', '', 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', 0.00, 'approved', 1, '2026-08-12 13:16:03', '2026-08-11 17:59:28', '2026-08-19 12:33:14'),
(3, 5, 'AG20266259', '', '', 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', 0.00, 'approved', 1, '2026-08-12 17:29:30', '2026-08-11 18:00:21', '2026-08-12 17:29:30'),
(4, 8, 'AG20261001', 'Ravi Agro Solutions', '22AAAAA0002A1Z6', '123 Market Road, Sector 15', 'Mumbai', 'Maharashtra', '400015', 8.50, 'approved', 1, '2026-07-29 15:47:08', '2026-07-28 15:47:08', '2026-08-12 15:47:08'),
(5, 9, 'AG20261002', 'Priya Enterprises', '22BBBBB0003B1Z7', '456 Commercial Street', 'Pune', 'Maharashtra', '411015', 10.00, 'approved', 1, '2026-07-24 15:47:08', '2026-07-23 15:47:08', '2026-08-12 15:47:08'),
(6, 10, 'AG20261003', 'Amit Agri Services', '22CCCCC0004C1Z8', '789 Industrial Area', 'Nagpur', 'Maharashtra', '440015', 7.50, 'approved', 1, '2026-08-27 17:33:22', '2026-07-18 15:47:08', '2026-08-27 17:33:22'),
(7, 11, 'AG20261004', 'Sneha Farms', '22DDDDD0005D1Z9', '321 Green Valley', 'Kolhapur', 'Maharashtra', '416015', 12.00, 'approved', 1, '2026-07-14 15:47:08', '2026-07-13 15:47:08', '2026-08-12 15:47:08'),
(8, 12, 'AG20261005', 'Vikram Organic', '22EEEEE0006E1Z0', '654 Eco Park', 'Nashik', 'Maharashtra', '422015', 9.00, 'approved', 1, '2026-08-03 15:47:08', '2026-08-02 15:47:08', '2026-08-12 15:47:08'),
(9, 20, 'AG20269887', 'Samridhi Agro', '', 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', 0.00, 'suspended', 1, '2026-08-25 17:30:45', '2026-08-25 17:30:45', '2026-08-27 17:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('staff','agent') NOT NULL DEFAULT 'staff',
  `date` date NOT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `check_in_location` varchar(255) DEFAULT NULL,
  `check_out_location` varchar(255) DEFAULT NULL,
  `check_in_lat` decimal(10,8) DEFAULT NULL,
  `check_in_lng` decimal(11,8) DEFAULT NULL,
  `check_out_lat` decimal(10,8) DEFAULT NULL,
  `check_out_lng` decimal(11,8) DEFAULT NULL,
  `check_in_ip` varchar(45) DEFAULT NULL,
  `check_out_ip` varchar(45) DEFAULT NULL,
  `status` enum('present','absent','half_day','leave','holiday') NOT NULL DEFAULT 'absent',
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `user_type`, `date`, `check_in_time`, `check_out_time`, `check_in_location`, `check_out_location`, `check_in_lat`, `check_in_lng`, `check_out_lat`, `check_out_lng`, `check_in_ip`, `check_out_ip`, `status`, `overtime_hours`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'staff', '2026-08-12', '2026-08-12 03:43:55', '2026-08-12 10:43:55', 'Office, Mumbai', 'Office, Mumbai', NULL, NULL, NULL, NULL, NULL, NULL, 'present', 0.00, NULL, '2026-08-12 03:43:55', '2026-08-12 11:43:55'),
(2, 2, 'staff', '2026-08-11', '2026-08-11 03:43:55', '2026-08-11 10:43:55', 'Client Site, Pune', 'Office, Mumbai', NULL, NULL, NULL, NULL, NULL, NULL, 'present', 0.00, NULL, '2026-08-11 03:43:55', '2026-08-12 11:43:55'),
(3, 2, 'staff', '2026-08-10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'absent', 0.00, NULL, '2026-08-10 11:43:55', '2026-08-12 11:43:55'),
(4, 2, 'staff', '2026-08-09', '2026-08-09 03:43:55', '2026-08-09 10:43:55', 'Office, Mumbai', 'Office, Mumbai', NULL, NULL, NULL, NULL, NULL, NULL, 'present', 0.00, NULL, '2026-08-09 03:43:55', '2026-08-12 11:43:55'),
(5, 2, 'staff', '2026-08-08', '2026-08-08 03:43:55', NULL, 'Client Site, Mumbai', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'half_day', 0.00, NULL, '2026-08-08 03:43:55', '2026-08-12 11:43:55'),
(40, 5, 'staff', '2026-08-22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'absent', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(41, 5, 'staff', '2026-08-21', '2026-08-21 09:30:00', '2026-08-21 13:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'half_day', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(42, 5, 'staff', '2026-08-20', '2026-08-20 09:10:00', '2026-08-20 18:15:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(43, 5, 'staff', '2026-08-19', '2026-08-19 08:50:00', '2026-08-19 17:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(44, 5, 'staff', '2026-08-18', '2026-08-18 09:45:00', '2026-08-18 19:00:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(45, 5, 'staff', '2026-08-17', '2026-08-17 09:00:00', '2026-08-17 18:00:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(46, 5, 'staff', '2026-08-16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'absent', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(47, 5, 'staff', '2026-08-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'leave', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(48, 5, 'staff', '2026-08-14', '2026-08-14 09:20:00', '2026-08-14 17:45:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(49, 5, 'staff', '2026-08-13', '2026-08-13 08:55:00', '2026-08-13 18:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(50, 5, 'staff', '2026-08-12', '2026-08-12 09:40:00', '2026-08-12 13:15:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'half_day', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(51, 5, 'staff', '2026-08-11', '2026-08-11 09:05:00', '2026-08-11 18:20:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(52, 5, 'staff', '2026-08-10', '2026-08-10 09:15:00', '2026-08-10 17:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(53, 5, 'staff', '2026-08-09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'absent', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(54, 5, 'staff', '2026-08-08', '2026-08-08 09:00:00', '2026-08-08 14:00:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'half_day', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(55, 5, 'staff', '2026-08-07', '2026-08-07 08:45:00', '2026-08-07 18:45:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(56, 5, 'staff', '2026-08-06', '2026-08-06 09:30:00', '2026-08-06 18:00:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(57, 5, 'staff', '2026-08-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'leave', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(58, 5, 'staff', '2026-08-04', '2026-08-04 09:10:00', '2026-08-04 17:40:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(59, 5, 'staff', '2026-08-03', '2026-08-03 09:25:00', '2026-08-03 18:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(60, 5, 'staff', '2026-08-02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'absent', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(61, 5, 'staff', '2026-08-01', '2026-08-01 09:00:00', '2026-08-01 13:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'half_day', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(62, 5, 'staff', '2026-07-31', '2026-07-31 08:50:00', '2026-07-31 17:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(63, 5, 'staff', '2026-07-30', '2026-07-30 09:35:00', '2026-07-30 19:15:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(64, 5, 'staff', '2026-07-29', '2026-07-29 09:00:00', '2026-07-29 18:00:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(65, 5, 'staff', '2026-07-28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'present', 0.00, '', '2026-08-24 15:40:05', '2026-08-31 12:02:58'),
(66, 5, 'staff', '2026-07-27', '2026-07-27 09:15:00', '2026-07-27 17:45:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(67, 5, 'staff', '2026-07-26', '2026-07-26 08:40:00', '2026-07-26 18:30:00', 'Mumbai Office', 'Mumbai Office', 19.07600000, 72.87770000, 19.07600000, 72.87770000, NULL, NULL, 'present', 0.00, NULL, '2026-08-24 15:40:05', '2026-08-24 15:40:05'),
(80, 5, 'agent', '2026-08-24', '2026-08-24 19:19:41', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', NULL, 21.23990620, 81.68396200, NULL, NULL, '127.0.0.1', NULL, 'present', 0.00, NULL, '2026-08-24 19:19:41', '2026-08-24 19:19:41'),
(81, 5, 'agent', '2026-08-25', '2026-08-25 12:40:03', '2026-08-25 15:49:37', 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', 21.23991330, 81.68394340, 21.23990224, 81.68394566, '127.0.0.1', '::1', 'present', 0.00, NULL, '2026-08-25 12:40:03', '2026-08-25 15:49:37'),
(88, 2, 'staff', '2026-08-25', '2026-08-25 15:18:59', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', NULL, 21.23990700, 81.68392730, NULL, NULL, '127.0.0.1', NULL, 'present', 0.00, NULL, '2026-08-25 15:18:59', '2026-08-25 15:18:59'),
(89, 5, 'agent', '2026-08-26', '2026-08-26 11:51:11', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 492001, India', NULL, 21.23990028, 81.68391683, NULL, NULL, '::1', NULL, 'present', 0.00, NULL, '2026-08-26 11:51:11', '2026-08-26 11:51:11'),
(91, 5, 'agent', '2026-08-27', '2026-08-27 15:05:59', '2026-08-27 15:06:29', 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', 21.23990770, 81.68391280, 21.23990730, 81.68392240, '192.168.1.9', '192.168.1.9', 'present', 0.00, NULL, '2026-08-27 15:05:59', '2026-08-27 15:06:29'),
(92, 2, 'staff', '2026-08-27', '2026-08-27 15:15:25', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', NULL, 21.23991330, 81.68394660, NULL, NULL, '192.168.1.9', NULL, 'half_day', 0.00, 'test', '2026-08-27 15:15:25', '2026-08-27 15:55:05'),
(93, 5, 'agent', '2026-08-31', '2026-08-31 12:18:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '192.168.1.4', NULL, 'present', 0.00, NULL, '2026-08-31 12:18:30', '2026-08-31 12:18:30'),
(94, 5, 'agent', '2026-09-01', '2026-09-01 02:13:58', '2026-09-01 18:58:18', 'Raipur, Raipur Tahsil, Raipur, Chhattisgarh, 493332, India', 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', 21.25711558, 81.64131139, 21.23990640, 81.68391180, '::1', '192.168.1.9', 'present', 0.00, NULL, '2026-09-01 02:13:58', '2026-09-01 18:58:18'),
(95, 5, 'agent', '2026-09-02', '2026-09-02 12:42:03', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', NULL, 21.23990730, 81.68394160, NULL, NULL, '192.168.1.9', NULL, 'present', 0.00, NULL, '2026-09-02 12:42:03', '2026-09-02 12:42:03'),
(96, 2, 'staff', '2026-09-02', '2026-09-02 19:15:00', NULL, 'Calvin Kliven, NH53, Raipur, Raipur Tahsil, रायपुर, Chhattisgarh, 492001, India', NULL, 21.23990730, 81.68392360, NULL, NULL, '192.168.1.9', NULL, 'present', 0.00, NULL, '2026-09-02 19:15:00', '2026-09-02 19:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_settings`
--

INSERT INTO `attendance_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'check_in_start_time', '09:00:00', 'Office start time for check-in', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(2, 'check_in_end_time', '21:00', 'Late check-in allowed until this time', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(3, 'check_out_start_time', '09:30', 'Earliest check-out time', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(4, 'check_out_end_time', '11:59', 'Latest check-out time', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(5, 'work_hours', '1', 'Standard work hours per day', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(6, 'allow_geolocation', '1', 'Require geolocation for attendance', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(7, 'geolocation_radius', '500', 'Radius in meters for location validation', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(8, 'office_lat', '21.239918', 'Office latitude for location validation', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(9, 'office_lng', '81.683944', 'Office longitude for location validation', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(10, 'allow_self_checkout', '1', 'Allow staff to check out without approval', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(11, 'attendance_approval_required', '0', 'Require admin approval for attendance', '2026-08-12 11:43:55', '2026-09-02 19:14:55'),
(12, 'agent_allow_anywhere', '1', 'Allow agents to check-in from anywhere', '2026-08-12 12:35:18', '2026-08-12 12:35:18'),
(13, 'agent_check_in_start_time', '08:00:00', 'Agent check-in start time', '2026-08-12 12:35:18', '2026-08-12 12:35:18'),
(14, 'agent_check_in_end_time', '11:00:00', 'Agent check-in end time', '2026-08-12 12:35:18', '2026-08-12 12:35:18'),
(15, 'agent_work_hours', '9.00', 'Agent work hours per day', '2026-08-12 12:35:18', '2026-08-12 12:35:18'),
(82, 'weekly_holidays', 'Sunday', 'Weekly holidays (comma separated, e.g., Sunday, Saturday)', '2026-08-24 13:56:27', '2026-09-02 19:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `category_slug`, `description`, `icon`, `parent_id`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'tool', 'tool', 'tool related sprey', 'tag', NULL, 0, 'active', '2026-08-11 18:35:36', '2026-09-02 15:38:00'),
(2, 'Seeds', 'seeds', 'High-quality seeds for farming', 'seedling', NULL, 1, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(3, 'Fertilizers', 'fertilizers', 'Organic and chemical fertilizers', 'leaf', NULL, 2, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(4, 'Pesticides', 'pesticides', 'Crop protection products', 'shield-alt', NULL, 3, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(5, 'Organic Products', 'organic-products', '100% organic farming products', 'leaf', NULL, 4, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(6, 'Tools & Equipment', 'tools-equipment', 'Farming tools and equipment', 'tools', NULL, 5, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(7, 'Irrigation', 'irrigation', 'Irrigation systems and accessories', 'water', NULL, 6, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(8, 'Animal Feed', 'animal-feed', 'Feed for livestock and poultry', 'paw', NULL, 7, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(9, 'Seeds - Vegetables', 'seeds-vegetables', 'Vegetable seeds for farming', 'carrot', NULL, 8, 'active', '2026-08-11 19:01:30', '2026-08-11 19:01:30'),
(10, 'abcd', 'abcd', '', 'tag', NULL, 0, 'active', '2026-08-27 17:18:49', '2026-09-02 15:40:19');

-- --------------------------------------------------------

--
-- Table structure for table `failed_login_attempts`
--

CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `failed_login_attempts`
--

INSERT INTO `failed_login_attempts` (`id`, `username`, `ip_address`, `attempt_time`) VALUES
(26, 'soni_sona', '::1', '2026-08-18 14:08:26'),
(28, 'sohan', '127.0.0.1', '2026-08-19 16:43:54'),
(29, 'sohan', '127.0.0.1', '2026-08-19 16:44:00'),
(37, 'ana_soni', '::1', '2026-08-22 11:52:59'),
(39, 'jaiswal909123456', '127.0.0.1', '2026-08-24 17:07:07'),
(42, 'Jaiswal900', '192.168.1.9', '2026-08-26 12:03:43'),
(43, 'jaiswal900', '192.168.1.9', '2026-08-26 12:03:56'),
(44, 'jaiswal_909', '192.168.1.9', '2026-08-27 17:40:28'),
(45, 'jaiswal_909', '192.168.1.9', '2026-08-27 17:40:40'),
(46, 'jaiswal_909', '192.168.1.9', '2026-08-27 17:40:50'),
(47, 'aiswal909', '192.168.1.4', '2026-08-31 12:18:13'),
(49, 'shop7@agro.com', '127.0.0.1', '2026-09-01 12:33:15'),
(50, 'sona_soni', '::1', '2026-09-01 23:38:34'),
(51, 'sona_soni', '::1', '2026-09-01 23:38:48'),
(53, 'jaisswal909', '192.168.1.4', '2026-09-02 11:41:20'),
(54, 'jaisswal909', '192.168.1.4', '2026-09-02 11:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `holiday_type` enum('public','company','festival','national') NOT NULL DEFAULT 'public',
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_type` enum('yearly','monthly','weekly') DEFAULT NULL,
  `weekly_holiday` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `holiday_date`, `holiday_name`, `description`, `holiday_type`, `is_recurring`, `recurring_type`, `weekly_holiday`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '2026-01-26', 'Republic Day', 'Indian Republic Day', 'national', 1, 'yearly', NULL, 'active', NULL, '2026-08-24 13:12:13', '2026-08-24 13:12:13'),
(2, '2026-08-15', 'Independence Day', 'Indian Independence Day', 'national', 1, 'yearly', NULL, 'active', NULL, '2026-08-24 13:12:13', '2026-08-24 13:12:13'),
(3, '2026-10-02', 'Gandhi Jayanti', 'Mahatma Gandhi Birthday', 'national', 1, 'yearly', NULL, 'active', NULL, '2026-08-24 13:12:13', '2026-08-24 13:12:13'),
(4, '2026-05-01', 'Labour Day', 'International Workers Day', 'public', 1, 'yearly', NULL, 'active', NULL, '2026-08-24 13:12:13', '2026-08-24 13:12:13'),
(5, '2026-12-25', 'Christmas', 'Christmas Day', 'festival', 1, 'yearly', NULL, 'active', NULL, '2026-08-24 13:12:13', '2026-08-24 13:12:13'),
(6, '2026-08-28', 'Raksha Bandhan', 'रक्षाबंधन भाई-बहन के प्रेम का पवित्र त्योहार है, जो हर साल सावन मास की पूर्णिमा को मनाया जाता है। इस दिन बहनें अपने भाइयों की कलाई पर राखी (रक्षा सूत्र) बांधती हैं, उनकी लंबी उम्र की कामना करती हैं और भाई अपनी बहनों की रक्षा का वचन देते हैं। वर्ष 2026 में यह पर्व 28 अगस्त को मनाया जाएगा', 'public', 0, 'yearly', '', 'active', 1, '2026-08-24 14:02:27', '2026-08-24 15:09:37'),
(7, '2026-09-02', 'test', '', 'company', 0, 'yearly', '', 'active', 1, '2026-09-01 18:04:44', '2026-09-01 18:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reference_type` enum('purchase','sale','adjustment','return','damage') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `product_id`, `quantity_change`, `previous_quantity`, `new_quantity`, `reference_type`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 5, -1, 4, 'adjustment', NULL, '', 1, '2026-08-13 17:35:22'),
(2, 1, -1, 4, 3, 'return', NULL, '', 1, '2026-08-27 17:36:18'),
(3, 1, 5, 3, 8, 'sale', NULL, '', 1, '2026-08-27 17:53:33'),
(4, 19, -1, 9, 8, 'sale', NULL, 'test', 2, '2026-09-02 15:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_payment` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_collected_by_agent` tinyint(1) NOT NULL DEFAULT 0,
  `agent_payment_status` enum('pending','collected','submitted_to_admin','admin_confirmed') DEFAULT 'pending',
  `agent_payment_date` datetime DEFAULT NULL,
  `admin_confirm_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_collected_by` enum('agent','admin') DEFAULT NULL,
  `payment_collected_by_id` int(11) DEFAULT NULL,
  `payment_collected_date` datetime DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(50) DEFAULT NULL,
  `shipping_state` varchar(50) DEFAULT NULL,
  `shipping_pincode` varchar(10) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `shop_id`, `agent_id`, `order_date`, `subtotal`, `tax`, `discount`, `total_amount`, `total_paid_amount`, `remaining_payment`, `status`, `payment_status`, `payment_collected_by_agent`, `agent_payment_status`, `agent_payment_date`, `admin_confirm_date`, `payment_method`, `payment_collected_by`, `payment_collected_by_id`, `payment_collected_date`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_pincode`, `delivery_notes`, `created_by`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(42, 'ORD-2026-07864', 2, 3, '2026-09-01 02:08:56', 770.00, 0.00, 0.00, 770.00, 0.00, 0.00, 'delivered', 'pending', 0, 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', 'test by mohan', 7, NULL, NULL, '2026-09-01 02:08:56', '2026-09-01 15:18:12'),
(43, 'ORD-2026-26561', 49, 3, '2026-09-01 12:34:41', 1110.00, 0.00, 0.00, 1110.00, 0.00, 0.00, 'shipped', 'pending', 0, 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, '123 Village Center', 'Pune', 'Maharashtra', '411002', '', 19, NULL, NULL, '2026-09-01 12:34:41', '2026-09-01 12:39:32'),
(44, 'ORD-2026-33595', 49, 3, '2026-09-01 13:14:32', 1180.00, 0.00, 0.00, 1180.00, 0.00, 0.00, 'cancelled', 'pending', 0, 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, '123 Village Center', 'Pune', 'Maharashtra', '411002', '', 19, NULL, NULL, '2026-09-01 13:14:32', '2026-09-01 23:49:07'),
(45, 'ORD-2026-90490', 49, 3, '2026-09-01 23:50:22', 890.00, 0.00, 0.00, 890.00, 0.00, 0.00, 'pending', 'pending', 0, 'pending', NULL, NULL, 'pending', NULL, NULL, NULL, '123 Village Center', 'Pune', 'Maharashtra', '411002', '', 19, NULL, NULL, '2026-09-01 23:50:22', '2026-09-01 23:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `discount`, `total`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(18, 42, 22, 1, 200.00, 0.00, 200.00, 'pending', NULL, '2026-09-01 02:08:56', '2026-09-01 02:08:56'),
(19, 42, 3, 1, 320.00, 0.00, 320.00, 'pending', NULL, '2026-09-01 02:08:56', '2026-09-01 02:08:56'),
(20, 42, 7, 1, 250.00, 0.00, 250.00, 'pending', NULL, '2026-09-01 02:08:56', '2026-09-01 02:08:56'),
(21, 43, 22, 1, 200.00, 0.00, 200.00, 'pending', NULL, '2026-09-01 12:34:41', '2026-09-01 12:34:41'),
(22, 43, 4, 1, 550.00, 0.00, 550.00, 'pending', NULL, '2026-09-01 12:34:41', '2026-09-01 12:34:41'),
(23, 43, 5, 1, 180.00, 0.00, 180.00, 'pending', NULL, '2026-09-01 12:34:41', '2026-09-01 12:34:41'),
(24, 43, 8, 1, 180.00, 0.00, 180.00, 'pending', NULL, '2026-09-01 12:34:41', '2026-09-01 12:34:41'),
(25, 44, 2, 1, 450.00, 0.00, 450.00, 'pending', NULL, '2026-09-01 13:14:32', '2026-09-01 13:14:32'),
(26, 44, 4, 1, 550.00, 0.00, 550.00, 'pending', NULL, '2026-09-01 13:14:32', '2026-09-01 13:14:32'),
(27, 44, 5, 1, 180.00, 0.00, 180.00, 'pending', NULL, '2026-09-01 13:14:32', '2026-09-01 13:14:32'),
(28, 45, 2, 1, 450.00, 0.00, 450.00, 'pending', NULL, '2026-09-01 23:50:22', '2026-09-01 23:50:22'),
(29, 45, 3, 1, 320.00, 0.00, 320.00, 'pending', NULL, '2026-09-01 23:50:22', '2026-09-01 23:50:22'),
(30, 45, 9, 1, 120.00, 0.00, 120.00, 'pending', NULL, '2026-09-01 23:50:22', '2026-09-01 23:50:22');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `pay_to` enum('agent','admin') NOT NULL DEFAULT 'agent',
  `payment_method` enum('cash','upi','bank_transfer','card','cheque') NOT NULL DEFAULT 'cash',
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','collected','submitted','confirmed') NOT NULL DEFAULT 'pending',
  `agent_collected_at` datetime DEFAULT NULL,
  `agent_collected_by` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `shop_id`, `agent_id`, `amount`, `pay_to`, `payment_method`, `transaction_id`, `notes`, `status`, `agent_collected_at`, `agent_collected_by`, `submitted_at`, `submitted_by`, `confirmed_at`, `confirmed_by`, `admin_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 2, 3, 400.00, 'agent', 'cash', '', '', 'confirmed', '2026-09-01 03:22:52', 3, '2026-09-01 03:23:23', 3, '2026-09-01 15:37:15', 1, '\nConfirmed by admin: ', 7, '2026-09-01 02:09:30', '2026-09-01 15:37:15'),
(3, 2, 3, 200.00, 'agent', 'cash', '4374y47ry4r', 'test', 'confirmed', '2026-09-01 03:22:44', 3, '2026-09-01 03:23:40', 3, '2026-09-01 12:22:22', 1, '\nConfirmed by admin: ', 7, '2026-09-01 02:11:14', '2026-09-01 12:22:22'),
(4, 2, NULL, 20.00, 'admin', 'upi', '125454', 'test', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '', 7, '2026-09-01 03:24:45', '2026-09-01 12:27:46'),
(5, 49, 3, 500.00, 'agent', 'upi', '', '', 'confirmed', '2026-09-01 12:43:11', 3, '2026-09-01 12:45:39', 3, '2026-09-01 12:46:14', 1, '\nConfirmed by admin: ', 19, '2026-09-01 12:35:09', '2026-09-01 12:46:14'),
(6, 49, NULL, 300.00, 'admin', 'cash', '45564', 'test by mj', 'confirmed', NULL, NULL, NULL, NULL, '2026-09-01 12:40:00', 1, '\nConfirmed by admin: ', 19, '2026-09-01 12:35:36', '2026-09-01 12:40:00'),
(7, 49, 3, 50.00, 'agent', 'cash', '', '', 'confirmed', '2026-09-01 12:54:18', 3, '2026-09-01 12:55:21', 3, '2026-09-01 12:56:00', 1, '\nConfirmed by admin: ', 19, '2026-09-01 12:47:52', '2026-09-01 12:56:00'),
(8, 49, NULL, 60.00, 'admin', 'upi', '45641', 'test', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 19, '2026-09-01 12:49:42', '2026-09-01 12:49:42'),
(9, 49, 3, 100.00, 'agent', 'cash', '', '', 'confirmed', '2026-09-01 13:21:51', 3, '2026-09-01 13:26:57', 3, '2026-09-01 13:54:37', 1, '\nConfirmed by admin: ', 19, '2026-09-01 13:06:05', '2026-09-01 13:54:37'),
(10, 49, 3, 500.00, 'agent', 'upi', '354544', 'test by ravi', 'confirmed', '2026-09-01 13:17:29', 3, '2026-09-01 13:18:07', 3, '2026-09-01 13:18:23', 1, '\nConfirmed by admin: ', 19, '2026-09-01 13:15:01', '2026-09-01 13:18:23'),
(11, 49, NULL, 500.00, 'admin', 'cash', '', '', 'confirmed', NULL, NULL, NULL, NULL, '2026-09-01 13:53:23', 1, '\nConfirmed by admin: ', 19, '2026-09-01 13:15:51', '2026-09-01 13:53:23'),
(12, 49, 3, 99.00, 'agent', 'cash', '', '', 'submitted', '2026-09-01 13:56:06', 3, '2026-09-01 13:56:10', 3, NULL, NULL, NULL, 19, '2026-09-01 13:55:46', '2026-09-01 13:56:10'),
(13, 49, NULL, 100.00, 'admin', 'cash', '', '', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 19, '2026-09-01 13:59:00', '2026-09-01 13:59:00'),
(14, 2, 3, 50.00, 'agent', 'cash', '', '', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, '2026-09-01 15:34:01', '2026-09-01 15:34:01');

-- --------------------------------------------------------

--
-- Table structure for table `payment_installments`
--

CREATE TABLE `payment_installments` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `installment_number` int(11) NOT NULL DEFAULT 1,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('cash','upi','bank_transfer','card','cheque') DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `received_by` enum('agent','admin') NOT NULL DEFAULT 'agent',
  `received_by_id` int(11) DEFAULT NULL,
  `received_by_name` varchar(100) DEFAULT NULL,
  `status` enum('pending','collected','submitted','confirmed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_installments`
--

INSERT INTO `payment_installments` (`id`, `payment_id`, `shop_id`, `order_id`, `installment_number`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `received_by`, `received_by_id`, `received_by_name`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 1, 2000.00, '2026-09-01 00:50:33', 'cash', NULL, 'agent', NULL, 'Agent Name', 'pending', NULL, '2026-09-01 00:50:33', '2026-09-01 00:50:33'),
(2, 2, 1, NULL, 1, 2500.00, '2026-09-01 00:50:33', 'upi', NULL, 'admin', NULL, 'Admin', 'collected', NULL, '2026-09-01 00:50:33', '2026-09-01 00:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_slug` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `permission_slug`, `module`, `description`, `created_at`) VALUES
(1, 'View Dashboard', 'dashboard.view', 'dashboard', NULL, '2026-08-11 15:22:46'),
(2, 'View Staff', 'staff.view', 'staff', NULL, '2026-08-11 15:22:46'),
(3, 'Create Staff', 'staff.create', 'staff', NULL, '2026-08-11 15:22:46'),
(4, 'Edit Staff', 'staff.edit', 'staff', NULL, '2026-08-11 15:22:46'),
(6, 'View Agents', 'agent.view', 'agent', NULL, '2026-08-11 15:22:46'),
(7, 'Create Agents', 'agent.create', 'agent', NULL, '2026-08-11 15:22:46'),
(8, 'Edit Agents', 'agent.edit', 'agent', NULL, '2026-08-11 15:22:46'),
(9, 'Delete Agents', 'agent.delete', 'agent', NULL, '2026-08-11 15:22:46'),
(10, 'Approve Agents', 'agent.approve', 'agent', NULL, '2026-08-11 15:22:46'),
(11, 'View Shops', 'shop.view', 'shop', NULL, '2026-08-11 15:22:46'),
(12, 'Create Shops', 'shop.create', 'shop', NULL, '2026-08-11 15:22:46'),
(13, 'Edit Shops', 'shop.edit', 'shop', NULL, '2026-08-11 15:22:46'),
(14, 'Delete Shops', 'shop.delete', 'shop', NULL, '2026-08-11 15:22:46'),
(16, 'View Products', 'product.view', 'product', NULL, '2026-08-11 15:22:46'),
(17, 'Create Products', 'product.create', 'product', NULL, '2026-08-11 15:22:46'),
(18, 'Edit Products', 'product.edit', 'product', NULL, '2026-08-11 15:22:46'),
(19, 'Delete Products', 'product.delete', 'product', NULL, '2026-08-11 15:22:46'),
(20, 'View Categories', 'category.view', 'category', NULL, '2026-08-11 15:22:46'),
(21, 'Create Categories', 'category.create', 'category', NULL, '2026-08-11 15:22:46'),
(22, 'Edit Categories', 'category.edit', 'category', NULL, '2026-08-11 15:22:46'),
(23, 'Delete Categories', 'category.delete', 'category', NULL, '2026-08-11 15:22:46'),
(24, 'View Orders', 'order.view', 'order', NULL, '2026-08-11 15:22:46'),
(25, 'Update Orders', 'order.update', 'order', NULL, '2026-08-11 15:22:46'),
(28, 'View Inventory', 'inventory.view', 'inventory', NULL, '2026-08-11 15:22:46'),
(29, 'Update Inventory', 'inventory.update', 'inventory', NULL, '2026-08-11 15:22:46'),
(30, 'View Reports', 'report.view', 'report', NULL, '2026-08-11 15:22:46'),
(31, 'View Settings', 'settings.view', 'settings', NULL, '2026-08-11 15:22:46'),
(32, 'attendance list', 'attendance.list', 'settings', NULL, '2026-08-11 15:22:46'),
(39, 'View Attendance Settings', 'attendance.settings.view', 'settings', 'View attendance settings', '2026-08-12 12:09:29'),
(41, 'View Payments', 'payment.view', 'payment', 'View all payments', '2026-08-12 15:49:48'),
(44, 'View Visits', 'visit.view', 'visit', 'View all visits', '2026-08-25 17:17:46'),
(45, 'Assign Visits', 'visit.assign', 'visit', 'Assign visits to agents', '2026-08-25 17:17:46'),
(47, 'Delete Visits', 'visit.delete', 'visit', 'Delete visits', '2026-08-25 17:17:46'),
(48, 'staff permision', 'staff.permissions', 'staff', 'give access staff permission like edit add delete etc', '2026-09-02 16:37:47');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_slug` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `gallery` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gallery`)),
  `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `product_slug`, `category_id`, `sku`, `description`, `unit`, `price`, `cost_price`, `quantity`, `min_quantity`, `image`, `gallery`, `status`, `is_featured`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'kitanashaka', 'kitanashak', 1, 'PRD-2026-10765', 'it will be killed 99.9 germs', 'packet', 250.00, 210.00, 8, 1, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 18:48:25', '2026-08-27 17:53:33'),
(2, 'Hybrid Tomato Seeds', 'hybrid-tomato-seeds', 1, 'PRD-2024-00001', 'High-yield hybrid tomato seeds suitable for all seasons', 'kg', 450.00, 350.00, 496, 50, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-08-19 18:25:52'),
(3, 'Organic Wheat Seeds', 'organic-wheat-seeds', 1, 'PRD-2024-00002', 'Premium organic wheat seeds with high protein content', 'kg', 320.00, 250.00, 795, 100, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-09-01 15:18:12'),
(4, 'Basmati Rice Seeds', 'basmati-rice-seeds', 1, 'PRD-2024-00003', 'Premium basmati rice seeds for export quality', 'kg', 550.00, 400.00, 302, 30, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-19 18:26:24'),
(5, 'Green Chilli Seeds', 'green-chilli-seeds', 1, 'PRD-2024-00004', 'Spicy green chilli seeds with high yield', 'g', 180.00, 120.00, 974, 100, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-18 16:31:11'),
(6, 'Organic Compost', 'organic-compost', 2, 'PRD-2024-00005', '100% organic compost for healthy soil', 'kg', 80.00, 50.00, 1987, 200, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(7, 'NPK Fertilizer 20-20-20', 'npk-fertilizer-20-20-20', 2, 'PRD-2024-00006', 'Balanced NPK fertilizer for all crops', 'kg', 250.00, 180.00, 1491, 150, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-09-01 15:18:12'),
(8, 'Urea Fertilizer', 'urea-fertilizer', 2, 'PRD-2024-00007', 'High nitrogen urea fertilizer', 'kg', 180.00, 130.00, 2000, 200, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(9, 'Vermicompost', 'vermicompost', 2, 'PRD-2024-00008', 'Premium vermicompost for organic farming', 'kg', 120.00, 80.00, 989, 100, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(10, 'Neem Oil', 'neem-oil', 3, 'PRD-2024-00009', 'Natural neem oil for pest control', 'l', 350.00, 250.00, 495, 50, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(11, 'Bio Pesticide', 'bio-pesticide', 3, 'PRD-2024-00010', 'Eco-friendly bio pesticide', 'l', 280.00, 200.00, 400, 40, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(12, 'Insecticide Powder', 'insecticide-powder', 3, 'PRD-2024-00011', 'Effective insecticide for crop protection', 'kg', 420.00, 320.00, 287, 30, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(13, 'Organic Manure', 'organic-manure', 4, 'PRD-2024-00012', '100% organic manure for healthy plants', 'kg', 150.00, 100.00, 1500, 150, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(14, 'Organic Pesticide', 'organic-pesticide', 4, 'PRD-2024-00013', 'Natural organic pesticide', 'l', 320.00, 230.00, 298, 30, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(15, 'Hand Tractor', 'hand-tractor', 5, 'PRD-2024-00014', 'Manual hand tractor for small farms', 'piece', 15000.00, 12000.00, 20, 5, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(16, 'Water Pump', 'water-pump', 5, 'PRD-2024-00015', 'High efficiency water pump for irrigation', 'piece', 8500.00, 6500.00, 29, 5, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(17, 'Sprayer Machine', 'sprayer-machine', 5, 'PRD-2024-00016', 'High capacity sprayer for pesticides', 'piece', 5500.00, 4000.00, 25, 5, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(18, 'Plough', 'plough', 5, 'PRD-2024-00017', 'Heavy duty plough for soil preparation', 'piece', 12000.00, 9500.00, 14, 3, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(19, 'Drip Irrigation Kit', 'drip-irrigation-kit', 6, 'PRD-2024-00018', 'Complete drip irrigation system for 1 acre', 'set', 25000.00, 20000.00, 8, 2, NULL, NULL, 'active', 1, 1, NULL, '2026-08-11 19:01:31', '2026-09-02 15:17:42'),
(20, 'Sprinkler System', 'sprinkler-system', 6, 'PRD-2024-00019', 'Sprinkler irrigation system', 'set', 18000.00, 14000.00, 14, 3, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-12 15:47:09'),
(21, 'Irrigation Pipes', 'irrigation-pipes', 6, 'PRD-2024-00020', 'PVC irrigation pipes', 'piece', 450.00, 350.00, 500, 50, NULL, NULL, 'active', 0, 1, NULL, '2026-08-11 19:01:31', '2026-08-11 19:01:31'),
(22, 'kitanashak', 'kitanashak-2', 7, 'PRD-2026-82979', 'test', 'l', 200.00, 100.00, 99, 2, 'ICv3kWMboPJdGRW1jrcR.jpg', NULL, 'active', 0, 1, NULL, '2026-08-18 19:00:49', '2026-09-01 15:18:12'),
(23, 'dropss', 'drops', 3, 'PRD-2026-52510', 'test  Description', 'kg', 1200.00, 1000.00, 100, 0, NULL, NULL, 'active', 0, 1, NULL, '2026-08-21 17:23:09', '2026-09-02 17:21:26');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_slug`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', 'Full system access with all permissions', 1, '2026-08-11 15:22:46', '2026-08-11 15:22:46'),
(2, 'Staff', 'staff', 'Staff member with assigned permissions', 1, '2026-08-11 15:22:46', '2026-08-11 15:22:46'),
(3, 'Agent', 'agent', 'Agent portal access for shop management', 1, '2026-08-11 15:22:46', '2026-08-11 15:22:46'),
(4, 'Shop', 'shop', 'Shop portal access for ordering products', 1, '2026-08-11 15:22:46', '2026-08-11 15:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(1, 1, 6, '2026-08-11 15:22:46'),
(2, 1, 7, '2026-08-11 15:22:46'),
(3, 1, 8, '2026-08-11 15:22:46'),
(4, 1, 9, '2026-08-11 15:22:46'),
(5, 1, 10, '2026-08-11 15:22:46'),
(6, 1, 20, '2026-08-11 15:22:46'),
(7, 1, 21, '2026-08-11 15:22:46'),
(8, 1, 22, '2026-08-11 15:22:46'),
(9, 1, 23, '2026-08-11 15:22:46'),
(10, 1, 1, '2026-08-11 15:22:46'),
(11, 1, 28, '2026-08-11 15:22:46'),
(12, 1, 29, '2026-08-11 15:22:46'),
(13, 1, 24, '2026-08-11 15:22:46'),
(14, 1, 25, '2026-08-11 15:22:46'),
(17, 1, 16, '2026-08-11 15:22:46'),
(18, 1, 17, '2026-08-11 15:22:46'),
(19, 1, 18, '2026-08-11 15:22:46'),
(20, 1, 19, '2026-08-11 15:22:46'),
(21, 1, 30, '2026-08-11 15:22:46'),
(22, 1, 31, '2026-08-11 15:22:46'),
(23, 1, 32, '2026-08-11 15:22:46'),
(24, 1, 11, '2026-08-11 15:22:46'),
(25, 1, 12, '2026-08-11 15:22:46'),
(26, 1, 13, '2026-08-11 15:22:46'),
(27, 1, 14, '2026-08-11 15:22:46'),
(29, 1, 2, '2026-08-11 15:22:46'),
(30, 1, 3, '2026-08-11 15:22:46'),
(31, 1, 4, '2026-08-11 15:22:46');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` int(11) NOT NULL,
  `payload` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `category`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Samridhi Agro', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(2, 'site_tagline', 'Farm to Shop Platform', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(3, 'site_email', 'info@samridhiagro.com', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(4, 'site_phone', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(5, 'site_address', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(6, 'business_name', 'Samridhi Agro Private Limited', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(7, 'business_gst', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(8, 'business_pan', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(9, 'business_license', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(10, 'order_prefix', 'ORD', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(11, 'order_auto_approve', '0', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(12, 'order_timeout', '30', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(13, 'payment_methods', 'cash,upi,bank_transfer,card', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(14, 'default_currency', 'INR', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(15, 'currency_symbol', '₹', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(16, 'email_from', 'noreply@samridhiagro.com', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(17, 'email_from_name', 'Samridhi Agro', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(18, 'smtp_host', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(19, 'smtp_port', '587', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(20, 'smtp_secure', 'tls', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(21, 'smtp_username', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(22, 'smtp_password', '', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(23, 'default_commission', '4.97', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(24, 'agent_commission', '10', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(25, 'maintenance_mode', '1', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19'),
(26, 'maintenance_message', 'We are currently undergoing maintenance. Please check back later.', 'general', 0, '2026-09-02 13:20:55', '2026-09-02 13:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_code` varchar(50) NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `shop_type` enum('retail','wholesale','both') NOT NULL DEFAULT 'retail',
  `owner_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `establishment_year` int(4) DEFAULT NULL,
  `shop_category` varchar(50) NOT NULL DEFAULT 'grocery',
  `delivery_available` tinyint(1) NOT NULL DEFAULT 0,
  `working_hours_start` time DEFAULT NULL,
  `working_hours_end` time DEFAULT NULL,
  `weekend_days` varchar(100) DEFAULT NULL,
  `shop_image` varchar(255) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `user_id`, `shop_code`, `shop_name`, `shop_type`, `owner_name`, `phone`, `email`, `address`, `city`, `state`, `pincode`, `gst_number`, `establishment_year`, `shop_category`, `delivery_available`, `working_hours_start`, `working_hours_end`, `weekend_days`, `shop_image`, `agent_id`, `status`, `approved_by`, `approved_at`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 6, 'SH20260929', 'yadav agro', 'both', 'mohan ji', '6263986166', 'webd455evperofficial@gmail.com', 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', '', NULL, 'grocery', 0, NULL, NULL, NULL, NULL, 2, 'approved', 1, '2026-08-12 16:30:01', NULL, NULL, '2026-08-11 18:14:39', '2026-08-12 19:00:52'),
(2, 7, 'SH20262430', 'sona agro', 'retail', 'sana soni', '6263986888', 'soni@gmail.com', 'Raipur, CT 492001, Raipur-492001', 'Raipur', 'Chhattisgarh', '492001', '', NULL, 'grocery', 1, '09:00:00', '20:40:00', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday', NULL, 3, 'approved', 1, '2026-08-12 16:27:16', 21.23993761, 81.68394195, '2026-08-11 18:16:53', '2026-08-26 11:56:21'),
(43, 13, 'SH20261001', 'Rajesh Agro Store', 'retail', 'Rajesh Kumar', '9876543230', 'shop1@agro.com', '123 Main Street', 'Mumbai', 'Maharashtra', '400001', '22AAAAA0007A1Z1', 2018, 'grocery', 1, '09:00:00', '21:00:00', 'Sunday', NULL, 3, 'approved', 1, '2026-08-08 15:47:08', 19.07600000, 72.87770000, '2026-08-07 15:47:08', '2026-09-02 16:55:50'),
(44, 14, 'SH20261002', 'Green Fields Mart', 'wholesale', 'Sunil Sharma', '9876543231', 'shop2@agro.com', '456 Market Road', 'Pune', 'Maharashtra', '411001', '22BBBBB0008B1Z2', 2015, 'produce', 0, '06:00:00', '18:00:00', 'Sunday,Saturday', NULL, 5, 'approved', 1, '2026-08-05 15:47:08', 18.52040000, 73.85670000, '2026-08-04 15:47:08', '2026-08-12 15:47:08'),
(45, 15, 'SH20261003', 'Organic World Store', 'retail', 'Meera Patel', '9876543232', 'shop3@agro.com', '789 Eco Street', 'Nagpur', 'Maharashtra', '440001', '22CCCCC0009C1Z3', 2020, 'organic', 1, '08:30:00', '20:30:00', 'Sunday,Monday', NULL, 3, 'approved', 1, '2026-08-01 15:47:08', 21.14580000, 79.08820000, '2026-07-31 15:47:08', '2026-08-26 17:11:27'),
(46, 16, 'SH20261004', 'Farm Fresh Market', 'both', 'Anita Reddy', '9876543233', 'shop4@agro.com', '321 Farm Road', 'Kolhapur', 'Maharashtra', '416001', '22DDDDD0010D1Z4', 2019, 'produce', 1, '07:00:00', '20:00:00', 'Sunday', NULL, 7, 'approved', 1, '2026-08-12 19:00:28', 16.70500000, 74.24330000, '2026-08-09 15:47:08', '2026-08-12 19:00:28'),
(47, 17, 'SH20261005', 'Agri Hub Store', 'wholesale', 'Vijay Singh', '9876543234', 'shop5@agro.com', '654 Industrial Zone', 'Nashik', 'Maharashtra', '422001', '22EEEEE0011E1Z5', 2012, 'wholesale', 0, '05:00:00', '17:00:00', 'Sunday', NULL, 8, 'approved', 1, '2026-07-26 15:47:08', 19.99750000, 73.78980000, '2026-07-25 15:47:08', '2026-08-12 15:47:08'),
(48, 18, 'SH20261006', 'Nature\'s Basket', 'retail', 'Kavita Nair', '9876543235', 'shop6@agro.com', '987 Green Valley', 'Mumbai', 'Maharashtra', '400002', '22FFFFF0012F1Z6', 2021, 'organic', 1, '09:30:00', '19:30:00', 'Sunday,Wednesday', NULL, 4, 'approved', 1, '2026-07-22 15:47:08', 19.07600000, 72.87770000, '2026-07-21 15:47:08', '2026-08-12 15:47:08'),
(49, 19, 'SH20261007', 'Village Mart', 'retail', 'Village Mart', '9876543236', 'shop7@agro.com', '123 Village Center', 'Pune', 'Maharashtra', '411002', '22GGGGG0013G1Z7', 2022, 'grocery', 0, '08:00:00', '20:00:00', 'Sunday', NULL, 3, 'approved', 1, '2026-08-12 19:00:23', 18.52040000, 73.85670000, '2026-08-10 15:47:08', '2026-08-26 11:56:46');

-- --------------------------------------------------------

--
-- Table structure for table `shop_payments`
--

CREATE TABLE `shop_payments` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `payment_type` enum('order_payment','subscription','other') NOT NULL DEFAULT 'order_payment',
  `payment_route` enum('agent','admin') NOT NULL DEFAULT 'agent',
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('cash','upi','bank_transfer','card','cheque') DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `collected_by_agent` tinyint(1) NOT NULL DEFAULT 0,
  `agent_collection_date` datetime DEFAULT NULL,
  `submitted_to_admin` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_to_admin_date` datetime DEFAULT NULL,
  `admin_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `admin_confirm_date` datetime DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `status` enum('pending','collected','submitted','confirmed','failed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_payments`
--

INSERT INTO `shop_payments` (`id`, `shop_id`, `agent_id`, `order_id`, `payment_type`, `payment_route`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `collected_by_agent`, `agent_collection_date`, `submitted_to_admin`, `submitted_to_admin_date`, `admin_confirmed`, `admin_confirm_date`, `confirmed_by`, `status`, `notes`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'subscription', 'agent', 5000.00, '2026-09-01 00:50:33', 'cash', NULL, 0, NULL, 0, NULL, 0, NULL, NULL, 'pending', NULL, NULL, '2026-09-01 00:50:33', '2026-09-01 00:50:33'),
(2, 1, 1, NULL, 'other', 'admin', 2500.00, '2026-09-01 00:50:33', 'upi', NULL, 0, NULL, 0, NULL, 0, NULL, NULL, 'pending', NULL, NULL, '2026-09-01 00:50:33', '2026-09-01 00:50:33');

-- --------------------------------------------------------

--
-- Table structure for table `shop_purchase_total`
--

CREATE TABLE `shop_purchase_total` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `total_purchase` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shop_purchase_total`
--

INSERT INTO `shop_purchase_total` (`id`, `shop_id`, `total_purchase`, `total_paid`, `balance_due`, `last_updated`) VALUES
(1, 1, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(2, 2, 1070.00, 0.00, 1070.00, '2026-09-01 01:13:13'),
(3, 43, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(4, 44, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(5, 45, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(6, 46, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(7, 47, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(8, 48, 0.00, 0.00, 0.00, '2026-09-01 01:04:57'),
(9, 49, 0.00, 0.00, 0.00, '2026-09-01 01:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `shop_wallet`
--

CREATE TABLE `shop_wallet` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_debit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leads`
--

CREATE TABLE `staff_leads` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `lead_type` enum('agent','shop','product_enquiry','service') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','qualified','converted','lost') NOT NULL DEFAULT 'new',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `follow_up_date` date DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_leads`
--

INSERT INTO `staff_leads` (`id`, `staff_id`, `agent_id`, `shop_id`, `lead_type`, `title`, `description`, `contact_name`, `contact_phone`, `contact_email`, `status`, `priority`, `follow_up_date`, `converted_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 1, NULL, 'agent', 'New Agent Lead - Mumbai', 'Interested in becoming agent for Samridhi Agro', 'Rajesh Sharma', '9876543210', NULL, 'contacted', 'high', NULL, NULL, NULL, '2026-08-10 11:43:55', '2026-08-12 11:43:55'),
(2, 2, NULL, 1, 'shop', 'Shop Lead - Pune', 'New shop interested in bulk orders', 'Priya Patel', '9876543211', NULL, 'qualified', 'medium', NULL, NULL, NULL, '2026-08-09 11:43:55', '2026-08-12 11:43:55'),
(3, 2, 1, NULL, 'product_enquiry', 'Product Enquiry - Organic Fertilizers', 'Enquired about organic fertilizer prices', 'Amit Kumar', '9876543212', NULL, 'new', 'urgent', NULL, NULL, NULL, '2026-08-11 11:43:55', '2026-08-12 11:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`id`, `user_id`, `department`, `designation`, `joining_date`, `created_at`, `updated_at`) VALUES
(1, 2, 'Sales', 'sale excative', '2026-08-11', '2026-08-11 17:28:22', '2026-08-11 17:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `staff_visits`
--

CREATE TABLE `staff_visits` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `visit_type` enum('agent_visit','shop_visit','delivery','survey','maintenance') NOT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `check_in_lat` decimal(10,8) DEFAULT NULL,
  `check_in_lng` decimal(11,8) DEFAULT NULL,
  `check_out_lat` decimal(10,8) DEFAULT NULL,
  `check_out_lng` decimal(11,8) DEFAULT NULL,
  `purpose` text NOT NULL,
  `notes` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_visits`
--

INSERT INTO `staff_visits` (`id`, `staff_id`, `agent_id`, `shop_id`, `visit_type`, `visit_date`, `visit_time`, `check_in_time`, `check_out_time`, `check_in_lat`, `check_in_lng`, `check_out_lat`, `check_out_lng`, `purpose`, `notes`, `feedback`, `rating`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, NULL, 'agent_visit', '2026-08-12', '10:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Agent meeting for commission review', NULL, NULL, NULL, 'completed', '2026-08-12 07:43:55', '2026-08-12 11:43:55'),
(2, 2, NULL, 1, 'shop_visit', '2026-08-11', '14:30:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Shop visit for product demo', NULL, NULL, NULL, 'completed', '2026-08-11 11:43:55', '2026-08-12 11:43:55'),
(3, 2, 1, NULL, 'delivery', '2026-08-10', '09:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Product delivery to agent warehouse', NULL, NULL, NULL, 'completed', '2026-08-10 11:43:55', '2026-08-12 11:43:55'),
(4, 2, NULL, 1, 'survey', '2026-08-13', '11:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Shop survey for new product launch', NULL, NULL, NULL, 'planned', '2026-08-12 11:43:55', '2026-08-12 11:43:55');

-- --------------------------------------------------------

--
-- Table structure for table `staff_visit_photos`
--

CREATE TABLE `staff_visit_photos` (
  `id` int(11) NOT NULL,
  `visit_id` int(11) NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `photo_type` enum('check_in','check_out','evidence','other') NOT NULL DEFAULT 'other',
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff','agent','shop') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `avatar`, `role`, `status`, `last_login`, `last_ip`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@samridhiagro.com', '$2y$10$/BwH9j0SXqBWKqEd9nVm4.wCJEieooNEQ/mR0C9/nMmxkCbqodi/6', 'System Administrator', '9876543210', '6a85583f071d3_1787123775.webp', 'admin', 'active', '2026-09-01 23:39:26', '::1', '2026-08-11 15:22:46', '2026-09-01 23:39:26'),
(2, 'mohan_jaiswal', 'webdevperofficial@gmail.com', '$2y$10$/BwH9j0SXqBWKqEd9nVm4.wCJEieooNEQ/mR0C9/nMmxkCbqodi/6', 'Mohan Jaiswal', '6263986109', '6a8d50bc06cff_1787646140.webp', 'staff', 'active', '2026-09-02 19:22:49', '192.168.1.4', '2026-08-11 17:28:22', '2026-09-02 19:22:49'),
(3, 'sohan', 'bdevperofficial@gmail.com', '$2y$10$P6nFwWoJK37dRZkqUZL7betj53k718p.0lHjpq/IG6zoITc5cTYMi', 'Sohan Jaiswal', '7263986109', NULL, 'staff', 'active', NULL, NULL, '2026-08-11 17:58:06', '2026-08-27 17:50:11'),
(4, 'jaiswal', 'mohanjaiswal2000@gmail.com', '$2y$10$/BwH9j0SXqBWKqEd9nVm4.wCJEieooNEQ/mR0C9/nMmxkCbqodi/6', 'radhey Jaiswal', '6463986109', '6a85551e77782_1787122974.webp', 'agent', 'active', '2026-08-21 17:32:05', '::1', '2026-08-11 17:59:28', '2026-08-21 17:32:05'),
(5, 'jaiswal909', 'mohanjaiswl2000@gmail.com', '$2y$10$/BwH9j0SXqBWKqEd9nVm4.wCJEieooNEQ/mR0C9/nMmxkCbqodi/6', 'bachha yadav', '6463986109', '6a8d69c085956_1787652544.webp', 'agent', 'active', '2026-09-02 19:13:41', '192.168.1.9', '2026-08-11 18:00:21', '2026-09-02 19:13:41'),
(6, 'mohan_ji', 'webd455evperofficial@gmail.com', '$2y$10$BJA9Vj9..BNudljgHjT6EOtv.NKb4qG4.juk11HcC66TEDeIC1lyO', 'mohan ji', '6263986166', NULL, 'shop', 'active', NULL, NULL, '2026-08-11 18:14:39', '2026-08-11 18:14:39'),
(7, 'sana_soni', 'soni@gmail.com', '$2y$10$UrnYBsUAdG9/qIecyXsjZ.n3UzFuLBmSmwr8eFZy3WD5yYgSdLBkm', 'sana soni', '6263986888', '6a854f55150d0_1787121493.webp', 'shop', 'active', '2026-09-01 23:40:18', '::1', '2026-08-11 18:16:53', '2026-09-01 23:40:18'),
(8, 'ravi_agro', 'ravi@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ravi Kumar', '9876543220', NULL, 'agent', 'active', NULL, NULL, '2026-07-28 15:47:08', '2026-08-12 15:47:08'),
(9, 'priya_enterprise', 'priya@enterprise.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Singh', '9876543221', NULL, 'agent', 'active', NULL, NULL, '2026-07-23 15:47:08', '2026-08-12 15:47:08'),
(10, 'amit_agri', 'amit@agri.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Patel', '9876543222', NULL, 'agent', 'active', NULL, NULL, '2026-07-18 15:47:08', '2026-08-12 15:47:08'),
(11, 'sneha_farms', 'sneha@farms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sneha Reddy', '9876543223', NULL, 'agent', 'active', NULL, NULL, '2026-07-13 15:47:08', '2026-08-12 15:47:08'),
(12, 'vikram_org', 'vikram@organic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Vikram Singh', '9876543224', NULL, 'agent', 'active', NULL, NULL, '2026-08-02 15:47:08', '2026-08-12 15:47:08'),
(13, 'shop_agro1', 'shop1@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rajesh Agro Store', '9876543230', NULL, 'shop', 'active', NULL, NULL, '2026-08-07 15:47:08', '2026-09-02 16:55:50'),
(14, 'shop_agro2', 'shop2@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Green Fields Mart', '9876543231', NULL, 'shop', 'active', NULL, NULL, '2026-08-04 15:47:08', '2026-08-12 15:47:08'),
(15, 'shop_agro3', 'shop3@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Organic World Store', '9876543232', NULL, 'shop', 'active', NULL, NULL, '2026-07-31 15:47:08', '2026-08-12 15:47:08'),
(16, 'shop_agro4', 'shop4@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Farm Fresh Market', '9876543233', NULL, 'shop', 'active', NULL, NULL, '2026-08-09 15:47:08', '2026-08-12 15:47:08'),
(17, 'shop_agro5', 'shop5@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agri Hub Store', '9876543234', NULL, 'shop', 'active', NULL, NULL, '2026-07-25 15:47:08', '2026-08-12 15:47:08'),
(18, 'shop_agro6', 'shop6@agro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nature\'s Basket', '9876543235', NULL, 'shop', 'active', NULL, NULL, '2026-07-21 15:47:08', '2026-08-12 15:47:08'),
(19, 'shop_agro7', 'shop7@agro.com', '$2y$10$/BwH9j0SXqBWKqEd9nVm4.wCJEieooNEQ/mR0C9/nMmxkCbqodi/6', 'Village Mart', '9876543236', NULL, 'shop', 'active', '2026-09-01 12:34:03', '127.0.0.1', '2026-08-10 15:47:08', '2026-09-01 12:34:03'),
(20, 'test_agent', 'test@gmail.com', '$2y$10$5Qv3koYOLq4DS.yhxwME.uS5MXRsM5khW1r1INwVFkzxLkQfshLq.', 'test agent', '6263986166', NULL, 'agent', 'suspended', NULL, NULL, '2026-08-25 17:30:45', '2026-08-27 17:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `user_permissions`
--

CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_permissions`
--

INSERT INTO `user_permissions` (`id`, `user_id`, `permission_id`, `created_at`) VALUES
(71, 2, 1, '2026-09-02 15:15:09'),
(119, 2, 10, '2026-09-02 19:01:06'),
(120, 2, 7, '2026-09-02 19:01:06'),
(121, 2, 9, '2026-09-02 19:01:06'),
(122, 2, 8, '2026-09-02 19:01:06'),
(123, 2, 6, '2026-09-02 19:01:06'),
(124, 2, 21, '2026-09-02 19:01:31'),
(125, 2, 23, '2026-09-02 19:01:31'),
(126, 2, 22, '2026-09-02 19:01:31'),
(127, 2, 20, '2026-09-02 19:01:31'),
(128, 2, 29, '2026-09-02 19:02:12'),
(129, 2, 28, '2026-09-02 19:02:13'),
(130, 2, 25, '2026-09-02 19:02:13'),
(131, 2, 24, '2026-09-02 19:02:13'),
(132, 2, 41, '2026-09-02 19:02:13'),
(133, 2, 17, '2026-09-02 19:02:13'),
(134, 2, 19, '2026-09-02 19:02:13'),
(135, 2, 18, '2026-09-02 19:02:13'),
(136, 2, 16, '2026-09-02 19:02:13'),
(137, 2, 30, '2026-09-02 19:02:13'),
(138, 2, 32, '2026-09-02 19:02:13'),
(139, 2, 39, '2026-09-02 19:02:13'),
(140, 2, 31, '2026-09-02 19:02:13'),
(141, 2, 12, '2026-09-02 19:02:13'),
(142, 2, 14, '2026-09-02 19:02:13'),
(143, 2, 13, '2026-09-02 19:02:13'),
(144, 2, 11, '2026-09-02 19:02:13'),
(145, 2, 3, '2026-09-02 19:02:13'),
(146, 2, 4, '2026-09-02 19:02:13'),
(147, 2, 48, '2026-09-02 19:02:13'),
(148, 2, 2, '2026-09-02 19:02:13'),
(149, 2, 45, '2026-09-02 19:02:13'),
(150, 2, 47, '2026-09-02 19:02:13'),
(151, 2, 44, '2026-09-02 19:02:13');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `visit_type` enum('assigned','self','new_shop') NOT NULL DEFAULT 'self',
  `shop_name` varchar(100) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `accuracy` decimal(10,2) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `photo_thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('assigned','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `agent_id`, `shop_id`, `visit_type`, `shop_name`, `owner_name`, `contact_number`, `address`, `purpose`, `remark`, `visit_date`, `visit_time`, `latitude`, `longitude`, `accuracy`, `photo`, `photo_thumbnail`, `status`, `assigned_by`, `assigned_date`, `created_at`, `updated_at`) VALUES
(1, 5, 47, 'assigned', 'Agri Hub Store', 'Vijay Singh', '9876543234', '654 Industrial Zone', 'test visit', 'seeds sample', '2026-08-25', '17:39:34', NULL, NULL, NULL, NULL, NULL, 'cancelled', 1, '2026-08-25 17:39:34', '2026-08-25 17:39:34', '2026-08-25 18:45:09'),
(2, 5, 2, 'assigned', 'sona agro', 'sana soni', '6263986888', 'Raipur, CT 492001, Raipur-492001', 'seeds sample 2', 'test visit 2\nVisit completed: test', '2026-08-25', '17:45:16', 21.23989168, 81.68395092, 85.00, '6a8d94cc75ff6_1787663564.webp', NULL, 'completed', 1, '2026-08-25 17:45:16', '2026-08-25 17:45:16', '2026-08-25 18:42:45'),
(3, 3, 2, 'assigned', 'sona agro', 'sana soni', '6263986888', 'Raipur, CT 492001, Raipur-492001', 'profile fartilizer', 'test visit 3', '2026-08-25', '18:49:24', NULL, NULL, NULL, NULL, NULL, 'assigned', 1, '2026-08-25 18:49:24', '2026-08-25 18:49:24', '2026-08-25 18:49:24'),
(4, 5, NULL, 'self', 'test shop', 'mohan ji', '6263986109', 'test adress', 'test visit', '', '2026-08-25', '19:05:28', 21.23989954, 81.68393645, 85.00, '6a8d9a20b4f93_1787664928.webp', NULL, 'completed', NULL, NULL, '2026-08-25 19:05:28', '2026-08-25 19:05:28'),
(5, 3, 45, 'assigned', 'Organic World Store', 'Meera Patel', '9876543232', '789 Eco Street', 'send seed', 'seeds sample', '2026-08-26', '12:09:00', NULL, NULL, NULL, NULL, NULL, 'assigned', 1, '2026-08-26 12:09:00', '2026-08-26 12:09:00', '2026-08-26 12:09:00'),
(6, 3, 2, 'assigned', 'sona agro', 'sana soni', '6263986888', 'Raipur, CT 492001, Raipur-492001', 'seeds sample 2', 'test visit 3\nVisit completed: ', '2026-08-26', '12:13:40', 21.23990720, 81.68394830, 11.55, '6a8fda3e57692_1787812414.webp', NULL, 'completed', 1, '2026-08-26 12:13:40', '2026-08-26 12:13:40', '2026-08-27 12:03:35'),
(9, 3, 43, 'assigned', 'Rajesh Agro Store', 'Rajesh Kumar', '9876543230', '123 Main Street', 'test', '', '2026-08-26', '13:22:39', NULL, NULL, NULL, NULL, NULL, 'assigned', 1, '2026-08-26 13:22:39', '2026-08-26 13:22:39', '2026-08-26 13:22:39'),
(10, 9, 47, 'assigned', 'Agri Hub Store', 'Vijay Singh', '9876543234', '654 Industrial Zone', 'test', '', '2026-08-26', '13:30:00', NULL, NULL, NULL, NULL, NULL, 'assigned', 1, '2026-08-26 13:30:00', '2026-08-26 13:30:00', '2026-08-26 13:30:00'),
(11, 3, 2, 'assigned', 'sona agro', 'sana soni', '6263986888', 'Raipur, CT 492001, Raipur-492001', 'seeds sample 2', '', '2026-08-26', '13:53:51', NULL, NULL, NULL, NULL, NULL, 'cancelled', 1, '2026-08-26 13:53:51', '2026-08-26 13:53:51', '2026-08-26 18:20:19'),
(12, 3, 45, 'assigned', 'Organic World Store', 'Meera Patel', '9876543232', '789 Eco Street', 'seeds sample 2', 'test by mj', '2026-08-26', '14:58:52', 21.23989103, 81.68393155, 85.00, '6a8ec30a7bf61_1787740938.webp', NULL, 'completed', 1, '2026-08-26 14:58:52', '2026-08-26 14:58:52', '2026-08-26 16:12:20'),
(14, 3, NULL, 'new_shop', 'raipur agro', 'jai jai', '6235689705', 'raipur', 'new shop', 'new shop visit', '2026-08-26', '17:09:43', 21.23991178, 81.68396149, 85.00, 'visit_1787744383_8412.jpg', 'thumb_visit_1787744383_8412.jpg', 'completed', NULL, NULL, '2026-08-26 17:09:43', '2026-08-26 17:09:43'),
(15, 3, NULL, 'new_shop', 'new shop', 'mj', '6568951202', 'raipur', 'seeds sample 2', 'test', '2026-08-26', '17:27:22', 21.23992929, 81.68388261, 90.00, 'visit_1787745442_6032.jpg', 'thumb_visit_1787745442_6032.jpg', 'completed', NULL, NULL, '2026-08-26 17:27:22', '2026-08-26 17:27:22'),
(16, 3, 43, 'self', 'Rajesh Agro Store', 'Rajesh Kumar', '9876543230', '123 Main Street, Mumbai, Maharashtra', 'seeds sample 2', 'test', '2026-08-26', '17:35:39', 21.23990613, 81.68395072, 85.00, 'visit_1787745939_2786a1eb.jpg', 'thumb_visit_1787745939_2786a1eb.jpg', 'completed', NULL, NULL, '2026-08-26 17:35:39', '2026-08-26 17:35:39'),
(17, 3, NULL, 'new_shop', 'Focus media', 'Ravi', '62639861805', 'Megneto mall raipur', 'Software', 'Na', '2026-08-26', '17:47:03', 21.23990970, 81.68392540, 12.02, 'visit_1787746622_93bea720.jpg', 'thumb_visit_1787746622_93bea720.jpg', 'completed', NULL, NULL, '2026-08-26 17:47:03', '2026-08-26 17:47:03'),
(18, 3, NULL, 'new_shop', 'Focus media', 'Ravi', '62639861805', 'Megneto mall raipur', 'Software', 'Na', '2026-08-26', '17:47:21', 21.23990860, 81.68391680, 13.85, 'visit_1787746641_2f093192.jpg', 'thumb_visit_1787746641_2f093192.jpg', 'completed', NULL, NULL, '2026-08-26 17:47:21', '2026-08-26 17:47:21'),
(19, 3, NULL, 'new_shop', 'Focus media', 'Ravi', '62639861805', 'Magneto', 'Test', '', '2026-08-26', '17:48:27', 21.23990610, 81.68394790, 12.80, 'visit_1787746707_3a979eea.jpg', 'thumb_visit_1787746707_3a979eea.jpg', 'completed', NULL, NULL, '2026-08-26 17:48:27', '2026-08-26 17:48:27'),
(20, 3, NULL, 'new_shop', 'Focus media', 'Ravi', '62639861805', 'Magneto', 'Test', '', '2026-08-26', '17:48:44', 21.23991350, 81.68391020, 14.46, 'visit_1787746724_22818670.jpg', 'thumb_visit_1787746724_22818670.jpg', 'completed', NULL, NULL, '2026-08-26 17:48:44', '2026-08-26 17:48:44'),
(21, 3, 48, 'assigned', 'Nature\'s Basket', 'Kavita Nair', '9876543235', '987 Green Valley', 'test bu mj', '', '2026-08-26', '18:21:17', NULL, NULL, NULL, NULL, NULL, 'cancelled', 1, '2026-08-26 18:21:17', '2026-08-26 18:21:17', '2026-09-02 17:14:22'),
(22, 3, 49, 'self', 'Village Mart', 'Village Mart', '9876543236', '123 Village Center, Pune, Maharashtra', 'Seed provide', 'New seed', '2026-08-27', '11:15:52', 21.23990740, 81.68392430, 12.32, 'visit_1787809552_5102f2df.jpg', 'thumb_visit_1787809552_5102f2df.jpg', 'completed', NULL, NULL, '2026-08-27 11:15:52', '2026-08-27 11:15:52'),
(23, 3, 49, 'assigned', 'Village Mart', 'Village Mart', '9876543236', '123 Village Center', 'seeds sample 2', '', '2026-08-27', '12:41:38', 21.23991360, 81.68393200, 13.18, '6a8fe3d79144d_1787814871.webp', 'thumb_6a8fe3d79144d_1787814871.webp', 'completed', 1, '2026-08-27 12:41:38', '2026-08-27 12:41:38', '2026-08-27 12:44:32'),
(24, 3, 45, 'self', 'Organic World Store', 'Meera Patel', '9876543232', '789 Eco Street, Nagpur, Maharashtra', '', '', '2026-08-27', '13:18:00', 21.23990020, 81.68394430, 21.82, 'visit_1787816880_f422579c.jpg', 'thumb_visit_1787816880_f422579c.jpg', 'completed', NULL, NULL, '2026-08-27 13:18:00', '2026-08-27 13:18:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_agent_code` (`agent_code`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_date` (`user_id`,`date`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting_key` (`setting_key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_category_slug` (`category_slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempt_time` (`attempt_time`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_holiday_date` (`holiday_date`),
  ADD KEY `idx_holiday_date` (`holiday_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_weekly_holiday` (`weekly_holiday`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_order_number` (`order_number`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_order_date` (`order_date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop` (`shop_id`),
  ADD KEY `idx_agent` (`agent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_pay_to` (`pay_to`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payment_installments`
--
ALTER TABLE `payment_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_received_by` (`received_by`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_permission_slug` (`permission_slug`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sku` (`sku`),
  ADD UNIQUE KEY `uk_product_slug` (`product_slug`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_slug` (`role_slug`),
  ADD UNIQUE KEY `uk_role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shop_code` (`shop_code`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `shop_payments`
--
ALTER TABLE `shop_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_route` (`payment_route`),
  ADD KEY `shop_payments_confirmed_by_fk` (`confirmed_by`);

--
-- Indexes for table `shop_purchase_total`
--
ALTER TABLE `shop_purchase_total`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shop_id` (`shop_id`);

--
-- Indexes for table `shop_wallet`
--
ALTER TABLE `shop_wallet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_shop_id` (`shop_id`);

--
-- Indexes for table `staff_leads`
--
ALTER TABLE `staff_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`);

--
-- Indexes for table `staff_visits`
--
ALTER TABLE `staff_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `staff_visit_photos`
--
ALTER TABLE `staff_visit_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_id` (`visit_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`),
  ADD UNIQUE KEY `uk_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_permission` (`user_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agent_id` (`agent_id`),
  ADD KEY `idx_shop_id` (`shop_id`),
  ADD KEY `idx_visit_type` (`visit_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=684;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=311;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payment_installments`
--
ALTER TABLE `payment_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `shop_payments`
--
ALTER TABLE `shop_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shop_purchase_total`
--
ALTER TABLE `shop_purchase_total`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shop_wallet`
--
ALTER TABLE `shop_wallet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_leads`
--
ALTER TABLE `staff_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_visits`
--
ALTER TABLE `staff_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_visit_photos`
--
ALTER TABLE `staff_visit_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `user_permissions`
--
ALTER TABLE `user_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `agents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agents_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_log_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_shop` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_installments`
--
ALTER TABLE `payment_installments`
  ADD CONSTRAINT `payment_installments_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `shop_payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_installments_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_installments_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shops_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shops_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shop_payments`
--
ALTER TABLE `shop_payments`
  ADD CONSTRAINT `shop_payments_confirmed_by_fk` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shop_payments_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_payments_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `shop_payments_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shop_purchase_total`
--
ALTER TABLE `shop_purchase_total`
  ADD CONSTRAINT `shop_purchase_total_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shop_wallet`
--
ALTER TABLE `shop_wallet`
  ADD CONSTRAINT `shop_wallet_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_leads`
--
ALTER TABLE `staff_leads`
  ADD CONSTRAINT `staff_leads_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_leads_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_leads_ibfk_3` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_visits`
--
ALTER TABLE `staff_visits`
  ADD CONSTRAINT `staff_visits_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_visits_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `staff_visits_ibfk_3` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_visit_photos`
--
ALTER TABLE `staff_visit_photos`
  ADD CONSTRAINT `staff_visit_photos_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `staff_visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_permissions`
--
ALTER TABLE `user_permissions`
  ADD CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `visits_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
