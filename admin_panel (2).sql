-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2025 at 10:12 AM
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
-- Database: `admin_panel`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `driver_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:30:47'),
(2, 1, 'logout', 'Driver logged out', '2025-01-06 21:33:27'),
(3, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:33:38'),
(4, 1, 'logout', 'Driver logged out', '2025-01-06 21:33:38'),
(5, NULL, 'login_failed', 'Failed login attempt for email: test@driver.com', '2025-01-06 21:36:47'),
(6, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:37:36'),
(7, 1, 'logout', 'Driver logged out', '2025-01-06 21:37:36'),
(8, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:50:57'),
(9, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:51:28'),
(10, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:51:41'),
(11, 1, 'login_success', 'Driver logged in successfully', '2025-01-06 21:54:56'),
(12, 2, 'login_success', 'Driver logged in successfully', '2025-01-06 23:27:19'),
(13, NULL, 'login_failed', 'Failed login attempt for email: admin@system.com', '2025-01-11 22:46:54'),
(14, NULL, 'login_failed', 'Failed login attempt for email: admin@system.com', '2025-01-11 22:50:38'),
(15, NULL, 'login_failed', 'Failed login attempt for email: admin@system.com', '2025-01-11 22:50:47'),
(16, NULL, 'login_failed', 'Failed login attempt for email: admin@system.com', '2025-01-11 22:50:52'),
(17, 2, 'login_success', 'Driver logged in successfully', '2025-01-13 16:59:27'),
(18, 2, 'login_success', 'Driver logged in successfully', '2025-01-13 17:17:48'),
(19, 2, 'logout', 'Driver logged out', '2025-01-13 17:27:24'),
(20, 1, 'login_success', 'Driver logged in successfully', '2025-01-18 14:05:27'),
(21, NULL, 'login_failed', 'Failed login attempt for email: a@gmail.com', '2025-01-18 14:06:31'),
(22, 1, 'login_success', 'Driver logged in successfully', '2025-01-18 14:08:01'),
(23, 1, 'login_success', 'Driver logged in successfully', '2025-01-18 15:38:33'),
(24, 1, 'login_success', 'Driver logged in successfully', '2025-01-18 17:07:05'),
(25, 1, 'accept_order', 'Driver accepted request #9', '2025-01-19 21:42:28'),
(26, 1, 'order_cancelled', 'Driver cancelled order #ORD-20250107-6037', '2025-01-19 21:58:15'),
(27, 1, 'accept_order', 'Driver accepted request #9', '2025-01-19 22:22:05'),
(28, 1, 'order_cancelled', 'Driver cancelled order #ORD-20250107-6037', '2025-01-19 22:22:33'),
(29, 1, 'accept_order', 'Driver accepted request #9', '2025-01-19 22:31:49'),
(30, 1, 'order_cancelled', 'Driver cancelled order #ORD-20250107-6037', '2025-01-19 22:31:58'),
(31, 1, 'accept_order', 'Driver accepted request #9', '2025-01-19 22:32:14'),
(32, 1, 'in_transit', 'تم بدء توصيل الطلب رقم ORD-20250107-6037', '2025-01-19 22:43:48'),
(33, 1, 'delivered', 'تم تسليم الطلب رقم ORD-20250107-6037', '2025-01-19 22:44:29'),
(34, 1, 'order_cancelled', 'Driver cancelled order #ORD-20250107-6037', '2025-01-20 13:24:25'),
(35, 2, 'accept_order', 'Driver accepted request #9', '2025-01-20 13:31:53'),
(36, 2, 'order_cancelled', 'Driver cancelled order #ORD-20250107-6037', '2025-01-20 14:30:09'),
(37, 1, 'accept_order', 'Driver accepted request #9', '2025-01-20 14:31:24'),
(38, 1, 'accept_order', 'Driver accepted order #12', '2025-01-21 14:44:45'),
(39, 1, 'in_transit', 'تم بدء توصيل الطلب رقم ORD-20250121-7181', '2025-01-21 14:49:00'),
(40, 1, 'order_cancelled', 'Driver cancelled order #ORD-20250121-7181', '2025-01-21 14:54:03'),
(41, 1, 'accept_order', 'Driver accepted request #12', '2025-01-30 19:56:16'),
(42, 1, 'in_transit', 'تم بدء توصيل الطلب رقم ORD-20250121-7181', '2025-01-30 19:56:57'),
(43, 1, 'delivered', 'تم تسليم الطلب رقم ORD-20250121-7181', '2025-01-30 19:57:14'),
(44, 1, 'accept_order', 'Driver accepted request #1', '2025-01-30 20:37:37'),
(45, 1, 'accept_order', 'Driver accepted request #1', '2025-01-30 20:40:43'),
(46, 1, 'in_transit', 'تم بدء توصيل الطلب رقم ORD-20241224-8458', '2025-01-30 20:42:25'),
(47, 1, 'delivered', 'تم تسليم الطلب رقم ORD-20241224-8458', '2025-01-30 20:42:47'),
(48, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:15:48'),
(49, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:17:23'),
(50, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:18:13'),
(51, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:20:50'),
(52, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:20:58'),
(53, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:21:22'),
(54, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:21:29'),
(55, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:27:18'),
(56, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:27:27'),
(57, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:31:07'),
(58, NULL, 'update', 'تم تحديث بيانات السائق', '2025-02-02 01:31:23'),
(59, 1, 'login_success', 'Driver logged in successfully', '2025-02-02 23:51:51'),
(60, 1, 'accept_order', 'Driver accepted request #16', '2025-02-03 19:51:18'),
(61, 1, 'in_transit', 'تم بدء توصيل الطلب رقم ORD-20250203-1495', '2025-02-03 19:52:22'),
(62, 1, 'delivered', 'تم تسليم الطلب رقم ORD-20250203-1495', '2025-02-03 19:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','support') DEFAULT 'admin',
  `department` enum('drivers','companies','complaints','orders') DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `username`, `password`, `role`, `department`, `last_login`, `is_active`, `created_at`) VALUES
(6, 'admin@system.com', 'مدير عام نظام', '$2y$10$1wyzVv8cXg61RMPg/fs6ZOIZYsDD.BsiWCZqwNfm17GeewshyhjG2', 'super_admin', NULL, '2025-02-06 20:11:08', 1, '2024-12-21 16:20:53'),
(8, 'admin1@system.com', 'المدير العام', '$2y$10$xceTxvPZpAWzSc/mIQwH1eCYEdPm6iQv745q.6TiEVM26pNeqHoQq', 'super_admin', NULL, '2024-12-31 00:23:01', 1, '2024-12-31 00:23:01');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `commercial_record` varchar(50) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_fee` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `email`, `password`, `phone`, `address`, `commercial_record`, `tax_number`, `logo`, `contact_person`, `contact_phone`, `is_active`, `created_at`, `delivery_fee`) VALUES
(5, 'ahmed ma7rez', 'jj@gmail.com', '$2y$10$Y6E0gSd34p1G3AztVM7fnuVwo29WEZssUtS2AOKAob1mW7MapYGv6', '01011965099', 'eg\r\ngiza', '44444444444444', '44444444444444', NULL, 'ahmed ma7rez', '01011965099', 1, '2024-12-20 16:21:23', 100.00),
(8, 'ahmed ma7rez', 'ahahrez.100@gmail.com', '', '01011965099', 'eg\r\ngiza', '44444444444444', '44444444444444', '', 'ahmed ma7rez', '01011965099', 0, '2024-12-20 16:30:01', 10.00),
(10, ' ma7rez', 'ahmehrez.100@gmail.com', '$2y$10$uuHYXU9Gn6cC/gRR7F3cr.5WShyr/wa1JnEoce0GmLLJnBmo1wLjW', '01011965099', 'eg\r\ngiza00', '1111111111', '111111111111111', '67659dac67c97_download.jpeg', 'ahmed ma7rez', '01011965099', 0, '2024-12-20 16:39:08', 50.00),
(11, 'ma7rez', 'ahmed.0@gmail.com', '$2y$10$0UlfSROvJfPRdsC7eJ15TeKCu90/w9IHQlyPig.3g3bCvGBlJDJy2', '01011965099', 'eg\r\ngiza', '2222200000', '000000000055555', '679e5db3c5fcb.png', '555555', '00000000000000000000', 1, '2024-12-20 18:55:12', 50.00),
(12, 'hussen1', 'a@gmail.com', '$2y$10$NknONIQMgtf0qXdpPkxf.udAWO7nOgseTOHHJ4H/.72E2uJJKGtAe', '01011965099', 'مصرف نهاية0', '1111111111', '111111111111111', '6766d6388d65e_html-5.png', 'حسين', '1000000000', 1, '2024-12-21 14:52:41', 50.00),
(13, 'ahmed', 'ahmed@gmail.com', '$2y$10$Qmg4Nxtm4Fv6F.HdTpZ9Z.FPr4SXBjcni4z6CRX7DYGl4GAlalAtm', '01011965099', 'القاهرة', '1111111111', '111111111111111', '', 'حسين', '1000000000', 1, '2024-12-24 17:28:15', 25.00),
(14, 'A', 'ahmed.mahrez.100@gmail.com', '$2y$10$q7sOkRtJXH19daSVCXfvGO1vI6EzBU0qW0lA5sffcYRhUvQuiIhPi', '01011965099', 'eg\r\ngiza', '00000000000', '00000000000000000', '679e54a54efd1_m&s.png', 'ahmed ma7rez', '01011965099', 0, '2024-12-25 19:33:17', 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `company_notifications`
--

CREATE TABLE `company_notifications` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reference_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_notifications`
--

INSERT INTO `company_notifications` (`id`, `company_id`, `title`, `message`, `type`, `link`, `is_read`, `created_at`, `reference_id`) VALUES
(10, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501074676 وتم تغيير حالة الشكوى إلى تم الحل', 'complaint_response', '#', 1, '2025-01-11 20:26:03', 11),
(11, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501078781 وتم تغيير حالة الشكوى إلى تم الحل', 'complaint_response', '#', 1, '2025-01-11 21:10:44', 10),
(12, 11, 'تم تسجيل دفعة جديدة', 'تم تسجيل دفعة بمبلغ 10.00 ريال', 'payment', NULL, 1, '2025-01-13 09:58:33', NULL),
(13, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)', 'payment', NULL, 0, '2025-01-13 11:03:38', NULL),
(14, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)', 'payment', NULL, 0, '2025-01-13 11:05:07', NULL),
(15, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)', 'payment', NULL, 0, '2025-01-13 11:07:22', NULL),
(16, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)', 'payment', NULL, 0, '2025-01-13 11:10:50', NULL),
(17, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي  (رقم المرجع: 111111111111)', 'payment', NULL, 0, '2025-01-13 11:11:23', NULL),
(18, 13, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 40.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:12:28', NULL),
(19, 13, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 10.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:13:02', NULL),
(20, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:15:26', NULL),
(21, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 1010)', 'payment', NULL, 1, '2025-01-13 11:16:15', NULL),
(22, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 30.00 ريال عن طريق نقدي ', 'payment', NULL, 1, '2025-01-13 11:16:37', NULL),
(23, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:25:23', NULL),
(24, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:27:06', NULL),
(25, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 30.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:27:50', NULL),
(26, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 50.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:33:05', NULL),
(27, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:37:29', NULL),
(28, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:39:08', NULL),
(29, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:42:24', NULL),
(30, 12, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:43:49', NULL),
(31, 12, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:47:53', NULL),
(32, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 120.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:52:17', NULL),
(33, 12, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 250.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 11:53:06', NULL),
(34, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 80.00 ريال عن طريق تحويل بنكي ', 'payment', NULL, 1, '2025-01-13 11:56:12', NULL),
(35, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 140.00 ريال عن طريق نقدي  (رقم المرجع: 1010)', 'payment', NULL, 0, '2025-01-13 12:02:05', NULL),
(36, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ', 'payment', NULL, 0, '2025-01-13 12:02:28', NULL),
(37, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 25.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:40:16', NULL),
(38, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 25.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:41:41', NULL),
(39, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 25.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:42:00', NULL),
(40, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 25.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:45:29', NULL),
(41, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:46:34', NULL),
(42, 10, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 15:50:43', NULL),
(43, 13, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 16:06:53', NULL),
(44, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 1,000.00 ريال عن طريق نقدي  (رقم المرجع: 12222222222)', 'payment', NULL, 0, '2025-01-21 17:32:51', NULL),
(45, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 12222222222)', 'payment', NULL, 0, '2025-01-21 17:32:55', NULL),
(46, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:43:08', NULL),
(47, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:44:58', NULL),
(48, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:48:04', NULL),
(49, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:51:13', NULL),
(50, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:54:43', NULL),
(51, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:56:13', NULL),
(52, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 17:59:21', NULL),
(53, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق تحويل بنكي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-01-21 18:03:02', NULL),
(54, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 1000)', 'payment', NULL, 1, '2025-01-21 18:06:14', NULL),
(55, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 1, '2025-01-22 19:11:07', NULL),
(56, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 80.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 1, '2025-01-24 17:22:00', NULL),
(57, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 80.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 1, '2025-01-24 19:09:03', NULL),
(58, 12, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 9.00 ريال عن طريق تحويل بنكي  (رقم المرجع: 12222222222)', 'payment', NULL, 0, '2025-01-25 00:18:33', NULL),
(59, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة واردة بمبلغ 80.00 ريال عن طريق نقدي  (رقم المرجع: 12222222222)', 'payment', NULL, 1, '2025-01-25 20:59:53', NULL),
(60, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 80.00 ريال عن طريق نقدي  (رقم المرجع: 100000)', 'payment', NULL, 0, '2025-01-26 22:55:59', NULL),
(61, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665 وتم تغيير حالة الشكوى إلى تم الحل', 'complaint_response', '#', 1, '2025-02-01 00:33:47', 9),
(62, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665', 'complaint_response', '#', 0, '2025-02-01 00:45:46', 9),
(63, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665', 'complaint_response', '#', 0, '2025-02-01 00:51:37', 9),
(64, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 1, '2025-02-01 01:57:26', NULL),
(65, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-02-01 02:00:42', NULL),
(66, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 2000000000)', 'payment', NULL, 0, '2025-02-01 02:02:13', NULL),
(67, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665', 'complaint_response', '#', 0, '2025-02-01 02:05:02', 9),
(68, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665', 'complaint_response', '#', 0, '2025-02-01 15:43:43', 9),
(69, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 12222222222)', 'payment', NULL, 0, '2025-02-01 15:59:02', NULL),
(70, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501077665', 'complaint_response', '#', 0, '2025-02-02 02:45:15', 9),
(71, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501078781', 'complaint_response', '#', 0, '2025-02-02 03:57:23', 10),
(72, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501078781', 'complaint_response', '#', 0, '2025-02-02 04:00:59', 10),
(73, 11, 'رد جديد على الشكوى', 'تم إضافة رد جديد على الشكوى رقم COMP202501074676 وتم تغيير حالة الشكوى إلى قيد المعالجة', 'complaint_response', '#', 0, '2025-02-02 05:47:28', 11),
(74, 11, 'تم تسجيل دفعة صادرة', 'تم تسجيل دفعة واردة بمبلغ 80.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000)', 'payment', NULL, 0, '2025-02-03 19:45:32', NULL),
(75, 11, 'تم تسجيل دفعة واردة', 'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي  (رقم المرجع: 1111111111111)', 'payment', NULL, 0, '2025-02-03 19:46:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `company_payments`
--

CREATE TABLE `company_payments` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('cash','bank_transfer','check') NOT NULL,
  `payment_type` enum('incoming','outgoing') NOT NULL DEFAULT 'outgoing',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `company_payments`
--

INSERT INTO `company_payments` (`id`, `company_id`, `amount`, `payment_date`, `payment_method`, `payment_type`, `reference_number`, `notes`, `status`, `created_by`, `created_at`) VALUES
(38, 11, 100.00, '2025-01-21 19:43:08', 'cash', 'outgoing', '2000000000', 'ااااااااااااااااااااااااااااا', 'completed', 6, '2025-01-21 17:43:08'),
(39, 11, 100.00, '2025-01-21 19:44:58', 'cash', 'incoming', '2000000000', 'تتتتتتتتتتتتتتتتتت', 'completed', 6, '2025-01-21 17:44:58'),
(40, 11, 100.00, '2025-01-21 19:48:04', 'cash', 'outgoing', '2000000000', '000000000000', 'completed', 6, '2025-01-21 17:48:04'),
(41, 11, 100.00, '2025-01-21 19:51:13', 'cash', 'incoming', '2000000000', 'ىىىىىىى', 'completed', 6, '2025-01-21 17:51:13'),
(42, 11, 100.00, '2025-01-21 19:54:43', 'cash', 'outgoing', '2000000000', '1000000000000000', 'completed', 6, '2025-01-21 17:54:43'),
(43, 11, 100.00, '2025-01-21 19:56:13', 'cash', 'incoming', '2000000000', 'ااااااااااااااا', 'completed', 6, '2025-01-21 17:56:13'),
(44, 11, 100.00, '2025-01-21 19:59:21', 'cash', 'outgoing', '2000000000', '2222222222222222222222', 'completed', 6, '2025-01-21 17:59:21'),
(45, 11, 100.00, '2025-01-21 20:03:02', 'bank_transfer', 'incoming', '2000000000', 'تتتت', 'completed', 6, '2025-01-21 18:03:02'),
(46, 11, 100.00, '2025-01-21 20:06:14', 'cash', 'outgoing', '1000', '0000000000000', 'completed', 6, '2025-01-21 18:06:14'),
(47, 11, 20.00, '2025-01-22 21:11:07', 'cash', 'incoming', '2000000000', '222222222222222', 'completed', 6, '2025-01-22 19:11:07'),
(48, 11, 80.00, '2025-01-24 19:22:00', 'cash', 'incoming', '2000000000', 'ليييييييييييييييي', 'completed', 6, '2025-01-24 17:22:00'),
(49, 11, 80.00, '2025-01-24 21:09:03', 'cash', 'outgoing', '2000000000', '1000000000', 'completed', 6, '2025-01-24 19:09:03'),
(50, 12, 9.00, '2025-01-25 02:18:33', 'bank_transfer', 'incoming', '12222222222', 'تم الاستلام', 'completed', 6, '2025-01-25 00:18:33'),
(51, 11, 80.00, '2025-01-25 22:59:53', 'cash', 'incoming', '12222222222', '101', 'completed', 6, '2025-01-25 20:59:53'),
(52, 11, 80.00, '2025-01-27 00:55:59', 'cash', 'outgoing', '100000', 'بيبي', 'completed', 6, '2025-01-26 22:55:59'),
(53, 11, 100.00, '2025-02-01 03:57:26', 'cash', 'outgoing', '2000000000', 'بببببببببببببببببب', 'completed', 3, '2025-02-01 01:57:26'),
(54, 11, 100.00, '2025-02-01 04:00:42', 'cash', 'incoming', '2000000000', 'للللللللللللل', 'completed', 3, '2025-02-01 02:00:42'),
(55, 11, 100.00, '2025-02-01 04:02:13', 'cash', 'outgoing', '2000000000', 'رللللللللللل', 'completed', 3, '2025-02-01 02:02:13'),
(56, 11, 100.00, '2025-02-01 17:59:02', 'cash', 'incoming', '12222222222', '10000000000', 'completed', 5, '2025-02-01 15:59:02'),
(57, 11, 80.00, '2025-02-03 21:45:32', 'cash', 'outgoing', '0000000000', 'dsvddxv', 'completed', 3, '2025-02-03 19:45:32'),
(58, 11, 100.00, '2025-02-03 21:46:28', 'cash', 'incoming', '1111111111111', 'dsssssss', 'completed', 3, '2025-02-03 19:46:28');

-- --------------------------------------------------------

--
-- Table structure for table `company_staff`
--

CREATE TABLE `company_staff` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('order_manager','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_staff`
--

INSERT INTO `company_staff` (`id`, `company_id`, `name`, `email`, `password`, `phone`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 11, 'ahmed ma7rez', 'a100@gmail.com', '$2y$10$nKL5beZ9TSUVsS3m/JfWyObcdh7K1hIaJG3FlwcKu/2bUmYQvZ4fu', '01011965099', 'staff', 1, NULL, '2025-01-02 21:27:53', '2025-01-06 19:57:36'),
(2, 11, 'ahmed mahrez', 'ahmed@gmail.com', '$2y$10$GoDDApX43sx5u27WQyZtae/N5q2ezgt.voL8rM/YZPj0S.Ehx.B7y', '01011965099', 'order_manager', 1, '2025-02-02 21:29:20', '2025-01-02 22:01:17', '2025-02-02 21:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `complaint_number` varchar(20) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `type` enum('company','driver','request','other') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `status` enum('new','in_progress','resolved','closed') DEFAULT 'new',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `complaint_number`, `company_id`, `driver_id`, `request_id`, `type`, `subject`, `description`, `status`, `priority`, `assigned_to`, `resolution_notes`, `created_at`, `updated_at`) VALUES
(8, 'COMP202501078686', 11, 2, 11, 'driver', 'السواق', 'تااا', 'new', 'medium', NULL, NULL, '2025-01-07 04:58:47', '2025-01-07 04:58:47'),
(9, 'COMP202501077665', 11, 1, 8, 'driver', 'سسسسس', 'ففففففففف', 'resolved', 'medium', NULL, NULL, '2025-01-07 04:59:16', '2025-02-01 00:33:47'),
(10, 'COMP202501078781', 11, 1, 10, 'driver', 'يييييييييي', 'يييييييييييييييييييييييييي', 'resolved', 'high', NULL, NULL, '2025-01-07 04:59:26', '2025-01-11 21:10:44'),
(11, 'COMP202501074676', 11, 1, 9, 'driver', 'يييييييييي', 'لابلارؤ', 'in_progress', 'medium', NULL, NULL, '2025-01-07 05:01:41', '2025-02-02 05:47:28');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_responses`
--

CREATE TABLE `complaint_responses` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `response` text NOT NULL,
  `is_company_reply` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint_responses`
--

INSERT INTO `complaint_responses` (`id`, `complaint_id`, `admin_id`, `company_id`, `response`, `is_company_reply`, `created_at`, `updated_at`, `employee_id`) VALUES
(1, 11, NULL, 11, 'هل يمكن الرد', 1, '2025-01-11 18:55:25', '2025-01-11 18:55:25', NULL),
(2, 11, 6, NULL, 'تم حلها', 0, '2025-01-11 19:18:53', '2025-01-11 19:18:53', NULL),
(3, 11, 6, NULL, 'تم حلها يافندم', 0, '2025-01-11 20:26:03', '2025-01-11 20:26:03', NULL),
(4, 11, NULL, 11, 'تم شكرا\r\n', 1, '2025-01-11 20:31:50', '2025-01-11 20:31:50', NULL),
(11, 10, NULL, 11, 'تتتتتتتتتتتتتتتتتتتتتتت', 1, '2025-01-11 21:07:50', '2025-01-11 21:07:50', NULL),
(12, 10, NULL, 11, 'تم الرد', 1, '2025-01-11 21:09:34', '2025-01-11 21:09:34', NULL),
(13, 10, 6, NULL, 'تم الحل', 0, '2025-01-11 21:10:44', '2025-01-11 21:10:44', NULL),
(14, 11, NULL, 11, 'تم شكرا\r\n', 1, '2025-01-11 21:23:28', '2025-01-11 21:23:28', NULL),
(19, 9, NULL, NULL, 'تم', 0, '2025-02-01 00:33:47', '2025-02-01 00:33:47', 5),
(20, 9, NULL, 11, 'شكرا', 1, '2025-02-01 00:39:14', '2025-02-01 00:39:14', NULL),
(21, 9, NULL, NULL, 'تا', 0, '2025-02-01 00:45:46', '2025-02-01 00:45:46', 5),
(22, 9, NULL, NULL, '00000', 0, '2025-02-01 00:51:37', '2025-02-01 00:51:37', 5),
(23, 9, NULL, NULL, 'كيف حالكم', 0, '2025-02-01 02:05:02', '2025-02-01 02:05:02', 4),
(24, 9, NULL, NULL, 'تم', 0, '2025-02-01 15:43:43', '2025-02-01 15:43:43', 5),
(25, 9, NULL, NULL, 'تم حلها', 0, '2025-02-02 02:45:15', '2025-02-02 02:45:15', 5),
(36, 10, NULL, 11, '1000', 1, '2025-02-02 03:32:11', '2025-02-02 03:32:11', NULL),
(43, 10, NULL, NULL, 'الللللللللللللللللللل', 0, '2025-02-02 03:57:23', '2025-02-02 03:57:23', 5),
(44, 10, NULL, 11, 'تم الحل', 1, '2025-02-02 03:57:51', '2025-02-02 03:57:51', NULL),
(45, 10, NULL, NULL, 'لا', 0, '2025-02-02 04:00:59', '2025-02-02 04:00:59', 4),
(46, 11, NULL, NULL, 'وضع', 0, '2025-02-02 05:47:28', '2025-02-02 05:47:28', 4);

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `feedback` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_feedback`
--

INSERT INTO `customer_feedback` (`id`, `request_id`, `customer_phone`, `feedback`, `created_at`) VALUES
(1, 13, '0111111111', 'جميلة جدا', '2025-02-02 22:54:31'),
(2, 11, '0111111111', 'تاخر كثير', '2025-02-02 23:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(50) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `current_status` enum('available','busy','offline') DEFAULT 'offline',
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_trips` int(11) DEFAULT 0,
  `completed_orders` int(11) DEFAULT 0,
  `cancelled_orders` int(11) DEFAULT 0,
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_location` point DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `username`, `email`, `password`, `phone`, `age`, `about`, `address`, `profile_image`, `id_number`, `license_number`, `vehicle_type`, `vehicle_model`, `plate_number`, `is_active`, `current_status`, `rating`, `total_trips`, `completed_orders`, `cancelled_orders`, `total_earnings`, `notes`, `last_login`, `last_location`, `created_at`, `updated_at`) VALUES
(1, 'ahmed', 'a@gmail.com', '$2y$10$u8ONso5w7EZLISUaYqFYL.N3dDsdM/y3pG4sxgCCRH9kln.vqxRvq', '01011965099', 22, 'ماهر جدا خبرة 5 سنوات', 'مصر القاهرة الجديده', 'profile_1_1738540419.jpeg', '222222', '22222222222', '222222', '22222222222', '2222222', 1, 'available', 3.00, 0, 1, 5, 100.00, 'تم التحصيل عليه 150', '2025-02-02 23:51:51', NULL, '2024-12-24 17:26:31', '2025-02-03 19:52:59'),
(2, 'mohmed', 'a100@gmail.com', '$2y$10$0fhtaPfJb1loIC2nKHJz8O5aegz/DDAQO0h3PX/HalbDJJXFfHo1a', '0123465448', 20, 'ماهر', 'الرياض', NULL, '2222222', '2222', '22222', '2222222222', '222222222', 1, 'available', 4.00, 0, 1, 0, 0.00, NULL, '2025-01-13 17:17:48', NULL, '2025-01-06 23:22:26', '2025-02-02 04:01:31');

-- --------------------------------------------------------

--
-- Table structure for table `driver_company_assignments`
--

CREATE TABLE `driver_company_assignments` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_documents`
--

CREATE TABLE `driver_documents` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `document_type` enum('id','license','insurance','vehicle_registration','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_earnings`
--

CREATE TABLE `driver_earnings` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('delivery_fee','tip','bonus') NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_locations`
--

CREATE TABLE `driver_locations` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy` decimal(10,2) DEFAULT NULL,
  `speed` decimal(10,2) DEFAULT NULL,
  `heading` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `driver_notifications`
--

CREATE TABLE `driver_notifications` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_notifications`
--

INSERT INTO `driver_notifications` (`id`, `driver_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 1, 'تم قبول الطلب رقم ORD-20250103-7959 بنجاح', 'order_accepted', 0, '2025-01-03 06:15:51'),
(2, 1, 'تم بدء توصيل الطلب رقم 3', 'in_transit', 0, '2025-01-06 22:51:14'),
(3, 1, 'تم بدء توصيل الطلب رقم 3', 'in_transit', 0, '2025-01-06 22:52:23'),
(4, 1, 'تم تسليم الطلب رقم 3', 'delivered', 0, '2025-01-06 22:52:24'),
(5, 1, 'تم بدء توصيل الطلب رقم 8', 'in_transit', 0, '2025-01-06 22:52:27'),
(6, 1, 'تم بدء توصيل الطلب رقم 8', 'in_transit', 0, '2025-01-06 22:58:18'),
(7, 1, 'تم تسليم الطلب رقم 8', 'delivered', 0, '2025-01-06 22:58:26'),
(8, 1, 'تم تسليم الطلب رقم 8', 'delivered', 0, '2025-01-06 22:59:54'),
(9, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-06 23:00:13'),
(10, 1, 'تم بدء توصيل الطلب رقم 9', 'in_transit', 0, '2025-01-06 23:00:21'),
(11, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:00:50'),
(12, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:03:32'),
(13, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:03:39'),
(14, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:04:00'),
(15, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:05:36'),
(16, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:06:13'),
(17, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:06:50'),
(18, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-06 23:08:29'),
(19, 1, 'تم إلغاء الطلب رقم 10', 'order_cancelled', 0, '2025-01-06 23:08:38'),
(20, 1, 'تم إلغاء الطلب رقم 10', 'order_cancelled', 0, '2025-01-06 23:08:42'),
(21, 1, 'تم إلغاء الطلب رقم 10 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-06 23:13:13'),
(22, 1, 'تم إلغاء الطلب رقم 9 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-06 23:13:15'),
(23, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-06 23:13:17'),
(24, 1, 'تم بدء توصيل الطلب رقم 9', 'in_transit', 0, '2025-01-06 23:13:24'),
(25, 1, 'تم إلغاء الطلب رقم 9 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-06 23:17:17'),
(26, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-07 00:13:33'),
(27, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 00:48:34'),
(28, 2, 'تم إلغاء الطلب رقم 11 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-07 00:48:43'),
(29, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 00:48:45'),
(30, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-07 00:49:08'),
(31, 1, 'تم بدء توصيل الطلب رقم 9', 'in_transit', 0, '2025-01-07 00:55:30'),
(32, 1, 'تم تسليم الطلب رقم 9', 'delivered', 0, '2025-01-07 00:55:35'),
(33, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 01:05:16'),
(34, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 01:21:14'),
(35, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 01:46:27'),
(36, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 01:47:15'),
(37, 2, 'تم إلغاء الطلب رقم 11 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-07 01:47:20'),
(38, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-07 02:16:49'),
(39, 1, 'تم قبول الطلب رقم 10 بنجاح', 'order_accepted', 0, '2025-01-07 02:17:06'),
(40, 2, 'تم قبول الطلب رقم 11 بنجاح', 'order_accepted', 0, '2025-01-07 04:46:15'),
(41, 2, 'تم بدء توصيل الطلب رقم 11', 'in_transit', 0, '2025-01-07 04:46:19'),
(42, 2, 'تم تسليم الطلب رقم 11', 'delivered', 0, '2025-01-07 04:54:45'),
(43, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-19 21:42:28'),
(44, 1, 'تم إلغاء الطلب رقم ORD-20250107-6037 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-19 21:58:15'),
(45, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-19 22:22:05'),
(46, 1, 'تم إلغاء الطلب رقم ORD-20250107-6037 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-19 22:22:33'),
(47, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-19 22:31:49'),
(48, 1, 'تم إلغاء الطلب رقم ORD-20250107-6037 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-19 22:31:58'),
(49, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-19 22:32:14'),
(50, 1, 'تم بدء توصيل الطلب رقم ORD-20250107-6037', 'in_transit', 0, '2025-01-19 22:43:48'),
(51, 1, 'تم تسليم الطلب رقم ORD-20250107-6037', 'delivered', 0, '2025-01-19 22:44:29'),
(52, 1, 'تم إلغاء الطلب رقم ORD-20250107-6037 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-20 13:24:25'),
(53, 2, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-20 13:31:53'),
(54, 2, 'تم إلغاء الطلب رقم ORD-20250107-6037 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-20 14:30:08'),
(55, 1, 'تم قبول الطلب رقم 9 بنجاح', 'order_accepted', 0, '2025-01-20 14:31:24'),
(56, 1, 'تم قبول الطلب رقم 12 بنجاح', 'order_accepted', 0, '2025-01-21 14:44:45'),
(57, 1, 'تم بدء توصيل الطلب رقم ORD-20250121-7181', 'in_transit', 0, '2025-01-21 14:49:00'),
(58, 1, 'تم إلغاء الطلب رقم ORD-20250121-7181 وإعادته للقائمة العامة', 'order_cancelled', 0, '2025-01-21 14:54:03'),
(59, 1, 'تم قبول الطلب رقم 12 بنجاح', 'order_accepted', 0, '2025-01-30 19:56:16'),
(60, 1, 'تم بدء توصيل الطلب رقم ORD-20250121-7181', 'in_transit', 0, '2025-01-30 19:56:57'),
(61, 1, 'تم تسليم الطلب رقم ORD-20250121-7181', 'delivered', 0, '2025-01-30 19:57:14'),
(62, 1, 'تم قبول الطلب رقم 1 بنجاح', 'order_accepted', 0, '2025-01-30 20:37:37'),
(63, 1, 'تم قبول الطلب رقم 1 بنجاح', 'order_accepted', 0, '2025-01-30 20:40:43'),
(64, 1, 'تم بدء توصيل الطلب رقم ORD-20241224-8458', 'in_transit', 0, '2025-01-30 20:42:25'),
(65, 1, 'تم تسليم الطلب رقم ORD-20241224-8458', 'delivered', 0, '2025-01-30 20:42:47'),
(66, 1, 'تم قبول الطلب رقم 16 بنجاح', 'order_accepted', 0, '2025-02-03 19:51:18'),
(67, 1, 'تم بدء توصيل الطلب رقم ORD-20250203-1495', 'in_transit', 0, '2025-02-03 19:52:22'),
(68, 1, 'تم تسليم الطلب رقم ORD-20250203-1495', 'delivered', 0, '2025-02-03 19:52:59'),
(69, 1, 'تم قبول الطلب رقم 16 بنجاح', 'order_accepted', 0, '2025-02-03 20:10:08'),
(70, 1, 'تم قبول الطلب رقم 14 بنجاح', 'order_accepted', 0, '2025-02-03 22:41:32'),
(71, 1, 'تم بدء توصيل الطلب رقم 14', 'in_transit', 0, '2025-02-03 22:41:48');

-- --------------------------------------------------------

--
-- Table structure for table `driver_ratings`
--

CREATE TABLE `driver_ratings` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_ratings`
--

INSERT INTO `driver_ratings` (`id`, `request_id`, `driver_id`, `company_id`, `rating`, `comment`, `created_at`) VALUES
(1, 8, 1, 11, 4, 'ت', '2025-01-07 00:46:16'),
(2, 9, 1, 11, 2, 'ه', '2025-01-07 01:34:05'),
(3, 11, 2, 11, 4, 'تتتتتتتتتتتت', '2025-01-07 04:54:54');

-- --------------------------------------------------------

--
-- Table structure for table `driver_sessions`
--

CREATE TABLE `driver_sessions` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `device_info` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) NOT NULL,
  `role` enum('مدير_عام','موظف') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `department`, `role`, `status`, `created_at`, `updated_at`) VALUES
(3, 'احمد', '$2y$10$bwGJQNGvUexXhpVv3iAMY.goYahFIP3mHtRuKnVPCzEUV6z6S2IyW', 'احمد', 'aalright54@gmail.com', '01011965099', 'accounting', 'موظف', 'active', '2025-01-12 01:12:17', '2025-02-03 19:34:20'),
(4, 'ahmed', '$2y$10$I2kMtsRufBPc7m04RIb3puAjiPBTS0h4S2eHe9b8C5hgjzCKiNKi.', 'ahmed mahrez', 'a@gmail.com', '01011965099', 'drivers_supervisor', 'موظف', 'active', '2025-01-13 08:00:27', '2025-02-03 18:03:20'),
(5, 'ادمن', '$2y$10$LZOw1lc8ebARhNNuF4r/dudhUCTCOLtaFm2QXBqglMLqx2ooohmxa', 'ahmed ma7rez', 'ahme@gmail.com', '01011965099', 'management', 'مدير_عام', 'inactive', '2025-01-31 23:01:15', '2025-02-03 19:23:28');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(20) DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(50, 6, '', 'تم تحديث بيانات الشركة: hussen\nكلمة المرور الجديدة: hussen@123', 'info', 'companies.php', 1, '2024-12-21 16:23:00'),
(51, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2024-12-21 16:24:57'),
(52, 6, '', 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 1, '2024-12-21 16:25:01'),
(53, 6, '', 'تم تحديث بيانات الشركة: hussen1\nكلمة المرور الجديدة: hussen1@123', 'info', 'companies.php', 1, '2024-12-21 17:43:48'),
(54, 6, '', 'تم حذف الشركة', 'danger', 'companies.php', 1, '2024-12-21 17:58:47'),
(55, 6, '', 'تم تعطيل الشركة', 'warning', 'companies.php', 1, '2024-12-21 18:05:53'),
(56, 6, '', 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 1, '2024-12-21 18:06:00'),
(57, 6, '', 'تم إضافة سائق جديد: ffff', 'success', 'drivers.php', 1, '2024-12-24 01:47:11'),
(58, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2024-12-24 01:49:40'),
(59, 6, '', 'تم تعطيل الشركة', 'warning', 'companies.php', 1, '2024-12-24 03:14:29'),
(60, 6, '', 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 1, '2024-12-24 03:14:32'),
(61, 6, '', 'تم إضافة سائق جديد: aaaaaaa', 'success', 'drivers.php', 1, '2024-12-24 03:19:25'),
(62, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2024-12-24 03:19:33'),
(63, 6, '', 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 1, '2024-12-24 03:19:36'),
(64, 6, '', 'تم إضافة سائق جديد: ahmed', 'success', 'drivers.php', 1, '2024-12-24 17:26:31'),
(65, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2024-12-24 17:26:37'),
(66, 6, '', 'تم إضافة شركة جديدة: ahmed\nكلمة المرور: ahmed@123', 'success', 'companies.php', 1, '2024-12-24 17:28:15'),
(67, 6, '', 'تم تعطيل الشركة', 'warning', 'companies.php', 1, '2024-12-24 17:28:53'),
(68, 6, '', 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 1, '2024-12-24 17:28:58'),
(69, 6, '', 'تم تحديث بيانات الشركة:  ma7rez\nكلمة المرور الجديدة:  ma7rez@123', 'info', 'companies.php', 1, '2024-12-25 19:11:40'),
(70, 6, '', 'تم إضافة شركة جديدة: x\nكلمة المرور: x@123', 'success', 'companies.php', 1, '2024-12-25 19:33:17'),
(71, 6, '', 'تم تعطيل الشركة', 'warning', 'companies.php', 1, '2024-12-25 19:38:21'),
(72, 6, '', 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 1, '2024-12-30 23:44:29'),
(73, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-03 02:51:06'),
(74, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-03 03:31:10'),
(75, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-03 07:25:43'),
(76, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-03 07:29:45'),
(77, 6, '', 'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123', 'info', 'companies.php', 1, '2025-01-06 18:21:53'),
(78, 6, '', 'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123', 'info', 'companies.php', 1, '2025-01-06 19:52:48'),
(79, 6, '', 'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123', 'info', 'companies.php', 1, '2025-01-06 19:55:40'),
(80, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 20:45:10'),
(81, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 20:45:16'),
(82, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 20:45:59'),
(83, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 21:00:03'),
(84, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 21:08:54'),
(85, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-06 21:11:27'),
(86, 6, '', 'تم إضافة سائق جديد: mohmed', 'success', 'drivers.php', 1, '2025-01-06 23:22:26'),
(87, 6, '', 'شكوى جديدة رقم: COMP202501077471 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 00:46:28'),
(88, 8, '', 'شكوى جديدة رقم: COMP202501077471 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 00:46:28'),
(90, 6, '', 'شكوى جديدة رقم: COMP202501075013 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:45:48'),
(91, 8, '', 'شكوى جديدة رقم: COMP202501075013 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:45:48'),
(93, 6, '', 'شكوى جديدة رقم: COMP202501072019 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:46:34'),
(94, 8, '', 'شكوى جديدة رقم: COMP202501072019 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:46:34'),
(96, 6, '', 'شكوى جديدة رقم: COMP202501077783 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:48:39'),
(97, 8, '', 'شكوى جديدة رقم: COMP202501077783 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:48:39'),
(99, 6, '', 'شكوى جديدة رقم: COMP202501079239 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:52:58'),
(100, 8, '', 'شكوى جديدة رقم: COMP202501079239 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:52:58'),
(102, 6, '', 'شكوى جديدة رقم: COMP202501073583 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:53:15'),
(103, 8, '', 'شكوى جديدة رقم: COMP202501073583 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:53:15'),
(105, 6, '', 'شكوى جديدة رقم: COMP202501079390 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:55:24'),
(106, 8, '', 'شكوى جديدة رقم: COMP202501079390 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:55:24'),
(108, 6, '', 'شكوى جديدة رقم: COMP202501078686 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:58:47'),
(109, 8, '', 'شكوى جديدة رقم: COMP202501078686 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:58:47'),
(111, 6, '', 'شكوى جديدة رقم: COMP202501077665 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:59:16'),
(112, 8, '', 'شكوى جديدة رقم: COMP202501077665 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:59:16'),
(114, 6, '', 'شكوى جديدة رقم: COMP202501078781 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:59:26'),
(115, 8, '', 'شكوى جديدة رقم: COMP202501078781 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 04:59:26'),
(117, 6, '', 'شكوى جديدة رقم: COMP202501074676 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 05:01:41'),
(118, 8, '', 'شكوى جديدة رقم: COMP202501074676 من شركة رقم: 11', 'complaint', 'complaints.php', 1, '2025-01-07 05:01:41'),
(120, 6, '', 'تم الرد على الشكوى رقم: COMP202501074676', 'complaint_response', 'complaints.php', 1, '2025-01-09 23:28:02'),
(121, 8, '', 'تم الرد على الشكوى رقم: COMP202501074676', 'complaint_response', 'complaints.php', 1, '2025-01-09 23:28:02'),
(122, 6, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 00:32:51'),
(123, 8, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 00:32:51'),
(124, 6, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 00:52:53'),
(125, 8, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 00:52:53'),
(126, 6, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 01:13:14'),
(127, 8, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 01:13:14'),
(128, 6, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 16:22:41'),
(129, 8, '', 'تم الرد على الشكوى رقم: COMP202501078781', 'complaint_response', 'complaints.php', 1, '2025-01-10 16:22:41'),
(130, 6, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:07:50'),
(131, 8, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:07:50'),
(132, 6, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:09:34'),
(133, 8, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:09:34'),
(134, 6, '', 'رد جديد على الشكوى #COMP202501074676 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:23:28'),
(135, 8, '', 'رد جديد على الشكوى #COMP202501074676 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-01-11 21:23:28'),
(136, 6, '', 'تم تحديث بيانات السائق: mohmed', 'info', 'drivers.php', 1, '2025-01-13 16:59:07'),
(137, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-13 17:00:36'),
(138, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2025-01-22 20:05:30'),
(139, 6, '', 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 1, '2025-01-22 20:05:50'),
(141, 6, '', 'تم إضافة سائق جديد: gad', 'success', 'drivers.php', 1, '2025-01-29 21:45:19'),
(142, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2025-01-29 21:46:06'),
(143, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-30 20:55:48'),
(144, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-30 20:55:53'),
(145, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-30 21:00:16'),
(146, 6, '', 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 1, '2025-01-30 21:01:02'),
(147, 6, '', 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2025-01-30 22:25:30'),
(148, 6, '', 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 1, '2025-01-30 22:28:33'),
(157, 6, '', 'رد جديد على الشكوى #COMP202501077665 - ma7rez: سسسسس', 'complaint_response', 'complaints.php', 1, '2025-02-01 00:39:14'),
(158, 8, '', 'رد جديد على الشكوى #COMP202501077665 - ma7rez: سسسسس', 'complaint_response', 'complaints.php', 1, '2025-02-01 00:39:14'),
(168, 5, 'مدير_عام', 'تم تحديث بيانات الشركة: x', 'info', 'companies.php', 1, '2025-02-01 17:06:45'),
(169, 6, 'super_admin', 'تم تحديث بيانات السائق: محمد10', 'info', 'drivers.php', 1, '2025-02-02 01:39:40'),
(170, 6, 'super_admin', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:39:52'),
(171, 6, 'super_admin', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:40:06'),
(172, 6, 'super_admin', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:40:43'),
(173, 5, 'مدير_عام', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:46:10'),
(174, 5, 'مدير_عام', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:46:50'),
(175, 5, 'مدير_عام', 'تم تحديث بيانات الشركة: A', 'info', 'companies.php', 1, '2025-02-02 01:47:43'),
(176, 5, 'مدير_عام', 'تم تحديث بيانات السائق: محرز1', 'info', 'drivers.php', 1, '2025-02-02 01:51:52'),
(177, 4, 'موظف', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 01:53:50'),
(178, 4, 'موظف', 'تم تحديث بيانات السائق: محرز10', 'info', 'drivers.php', 1, '2025-02-02 02:01:47'),
(179, 4, 'موظف', 'تم تحديث بيانات السائق: محرز1', 'info', 'drivers.php', 1, '2025-02-02 02:02:02'),
(180, 4, 'موظف', 'تم تحديث بيانات السائق: محرز', 'info', 'drivers.php', 1, '2025-02-02 02:02:49'),
(181, 5, 'مدير_عام', 'تم تعطيل شركة: A', 'warning', 'companies.php', 1, '2025-02-02 02:29:31'),
(182, 5, 'مدير_عام', 'تم تفعيل شركة: ahmed ma7rez', 'success', 'companies.php', 1, '2025-02-02 02:29:41'),
(183, 5, 'مدير_عام', 'تم تعطيل السائق: محرز', 'warning', 'drivers.php', 1, '2025-02-02 02:31:34'),
(184, 5, 'مدير_عام', 'تم تفعيل السائق: محرز', 'success', 'drivers.php', 1, '2025-02-02 02:31:45'),
(185, 5, 'مدير_عام', 'تم حذف السائق: محرز', 'danger', 'drivers.php', 1, '2025-02-02 02:32:33'),
(186, 6, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-02-02 03:32:11'),
(187, 8, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-02-02 03:32:11'),
(188, 6, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-02-02 03:57:51'),
(189, 8, '', 'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي', 'complaint_response', 'complaints.php', 1, '2025-02-02 03:57:51'),
(190, 4, 'موظف', 'تم تعطيل السائق: mohmed', 'warning', 'drivers.php', 1, '2025-02-02 04:01:24'),
(191, 4, 'موظف', 'تم تفعيل السائق: mohmed', 'success', 'drivers.php', 1, '2025-02-02 04:01:31');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `order_type` enum('delivery','transport') NOT NULL,
  `delivery_date` datetime NOT NULL,
  `pickup_location` text NOT NULL,
  `delivery_location` text NOT NULL,
  `items_count` int(11) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 20.00,
  `payment_method` enum('cash','card','bank_transfer') NOT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `is_fragile` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pickup_location_link` varchar(500) DEFAULT NULL,
  `delivery_location_link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `order_number`, `company_id`, `driver_id`, `customer_name`, `customer_phone`, `order_type`, `delivery_date`, `pickup_location`, `delivery_location`, `items_count`, `total_cost`, `delivery_fee`, `payment_method`, `payment_status`, `is_fragile`, `invoice_file`, `status`, `additional_notes`, `created_at`, `updated_at`, `pickup_location_link`, `delivery_location_link`) VALUES
(1, 'ORD-20241224-8458', 11, 1, 'احمد', '0222222222222', 'delivery', '2024-12-24 00:00:00', 'الجيزة', 'القاهرة', 3, 20.00, 20.00, 'cash', 'unpaid', 1, NULL, 'delivered', 'لابات ', '2024-12-24 17:34:10', '2025-01-30 20:42:47', NULL, NULL),
(2, 'ORD-20241224-3554', 11, NULL, 'محمد', '0222222222222', 'transport', '2024-12-24 00:00:00', 'القاهرة', 'الجيزة', 5, 200.00, 20.00, 'cash', 'unpaid', 1, NULL, 'pending', 'pc', '2024-12-24 18:00:04', '2025-02-06 19:26:16', NULL, NULL),
(3, 'ORD-20241224-5110', 12, 1, 'محمود', '0111111111', 'transport', '2025-01-10 00:00:00', 'ا', 'ب', 3, 11.00, 20.00, 'bank_transfer', 'unpaid', 1, NULL, 'delivered', '0000000', '2024-12-24 18:50:13', '2025-01-22 19:09:37', NULL, NULL),
(4, 'ORD-20241225-8375', 13, NULL, 'ahmed', '0222222222222', 'delivery', '2024-12-31 00:00:00', 'a', 'b', 1, 20.00, 20.00, 'card', 'unpaid', 1, NULL, 'delivered', 'h', '2024-12-25 18:41:30', '2025-01-22 19:09:37', NULL, NULL),
(5, 'ORD-20241230-6280', 11, NULL, 'محمود', '0111111111', 'delivery', '2024-12-30 11:11:00', '1', '1', 1, 0.00, 40.00, 'card', 'paid', 1, NULL, 'delivered', '', '2024-12-30 12:34:24', '2025-01-22 19:09:37', NULL, NULL),
(6, 'ORD-20241231-4347', 11, NULL, 'محمود', '0111111111', 'delivery', '2024-12-31 23:19:00', 'حسين', 'عند حسين', 111, 10.00, 100.00, 'card', 'paid', 1, NULL, 'delivered', 'ارحب ياحسين', '2024-12-30 23:13:23', '2025-01-22 19:09:37', 'https://www.openstreetmap.org/?mlat=24.638916&mlon=46.7160104', 'https://www.openstreetmap.org/?mlat=21.420847&mlon=39.826869'),
(7, 'ORD-20241231-2025', 11, NULL, 'محمود', '0111111111', 'delivery', '2024-12-31 11:00:00', 'ؤؤؤؤؤؤؤ', 'بببببببببب', 1, 10.00, 0.00, 'cash', 'unpaid', 1, NULL, 'delivered', 'ةةةةةةةةةةة', '2024-12-31 04:15:32', '2025-01-21 20:07:18', 'https://www.openstreetmap.org/?mlat=24.638916&mlon=46.7160104', 'https://www.openstreetmap.org/?mlat=16.05405&mlon=43.70669'),
(8, 'ORD-20250103-7959', 11, 1, 'محمود1', '0111111111', 'delivery', '2025-01-28 16:39:00', '111111111', '1111111', 2, 20.00, 0.00, 'cash', 'unpaid', 1, NULL, 'delivered', '111111111111111111', '2025-01-03 03:34:56', '0000-00-00 00:00:00', 'https://www.openstreetmap.org/?mlat=24.716797632384722&mlon=46.684765862287435', 'https://www.openstreetmap.org/?mlat=24.699292833787997&mlon=46.67653016346712'),
(9, 'ORD-20250107-6037', 11, 1, 'hhhh', '0148848484', 'delivery', '2025-01-28 12:22:00', 'nnn', 'تتتتتتت', 1, 0.01, 50.00, 'cash', 'unpaid', 1, NULL, 'accepted', 'تتتتتتتتتتتتتتتت', '2025-01-06 22:17:30', '2025-01-22 21:27:18', 'https://www.openstreetmap.org/?mlat=24.722247914167436&mlon=46.68720251043721', 'https://www.openstreetmap.org/?mlat=23.333333&mlon=45.333333'),
(10, 'ORD-20250107-1912', 11, 1, 'محمود', '0111111111', 'delivery', '2025-01-07 01:42:00', 'منزل عشرة', 'ب', 1, 0.01, 20.00, 'cash', 'unpaid', 1, NULL, 'in_transit', 'بببببببببب', '2025-01-06 22:45:58', '2025-01-09 20:49:29', 'https://www.openstreetmap.org/?mlat=23.333333&mlon=45.333333', 'https://www.openstreetmap.org/?mlat=24.70100604556386&mlon=46.69282998404894'),
(11, 'ORD-20250107-6301', 11, 2, 'محمود محمد', '0111111111', 'transport', '2025-01-20 12:59:00', 'العباس بن مرداس، السليمانية، الرياض، منطقة الرياض، السعودية', 'القيروان، السليمانية، الرياض، منطقة الرياض، السعودية', 4, 0.00, 20.00, 'card', 'unpaid', 1, NULL, 'delivered', '222222222222222222', '2025-01-06 23:49:28', '2025-02-02 23:47:04', 'https://www.openstreetmap.org/?mlat=24.716330456496394&mlon=46.685999724202716', 'https://www.openstreetmap.org/?mlat=24.71368307934326&mlon=46.69201365537513'),
(12, 'ORD-20250121-7181', 11, 1, 'gh', 'ghhhhhhhh', 'delivery', '2025-01-27 02:24:00', 'gh', 'gh', 1, 100.00, 0.00, 'card', 'unpaid', 1, NULL, 'delivered', 'ghhhhhhh', '2025-01-20 23:25:14', '2025-01-30 19:57:14', '', ''),
(13, 'ORD-20250121-4589', 11, NULL, 'محمود', '0111111111', 'delivery', '2025-01-28 22:14:00', 'ff', 'ff', 1, 0.00, 0.00, 'card', 'unpaid', 1, NULL, 'delivered', 'gggggggggggggggggg', '2025-01-21 19:16:03', '2025-01-21 20:07:18', 'https://www.openstreetmap.org/?mlat=24.714192821508753&mlon=46.68480848503133', 'https://www.openstreetmap.org/?mlat=24.71298635000665&mlon=46.68515214804042'),
(14, 'ORD-20250121-7191', 11, 1, 'محمود محمد', '0111111111', 'delivery', '2025-01-28 22:43:00', '11', '11', 1, 0.00, 50.00, 'card', 'unpaid', 1, NULL, 'in_transit', '00000000', '2025-01-21 19:46:32', '2025-02-03 22:41:48', 'https://www.openstreetmap.org/?mlat=24.731352495638525&mlon=46.70113247796448', 'https://www.openstreetmap.org/?mlat=24.70830598767172&mlon=46.69168174521369'),
(15, 'ORD-20250202-6233', 11, NULL, '13232', '0111111111', 'delivery', '2025-02-19 00:00:00', '10', '10', 1, 100.00, 0.00, 'cash', 'unpaid', 1, NULL, 'cancelled', '100000000', '2025-02-02 04:17:13', '2025-02-02 21:56:40', 'https://www.openstreetmap.org/?mlat=23.333333&mlon=45.333333', 'https://www.openstreetmap.org/?mlat=25.237267850000002&mlon=55.195494157283456'),
(16, 'ORD-20250203-1495', 11, 1, 'محمود محمود', '0111111111', 'transport', '2025-02-17 01:40:00', 'اللللللللللل', ' ةةةةةةةة', 2, 0.00, 50.00, 'cash', 'unpaid', 1, 'ORD-20250203-1495.png', 'delivered', 'تممم', '2025-02-03 00:11:00', '2025-02-03 19:52:59', 'https://www.google.com/maps?q=24.703640308779683,46.680049896240234', 'https://www.google.com/maps?q=24.71083206622398,46.683677062622436');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`, `created_at`, `updated_at`) VALUES
('delivery_fee', '20', '2024-12-31 01:41:26', '2025-01-21 19:22:11');

-- --------------------------------------------------------

--
-- Table structure for table `users_backup`
--

CREATE TABLE `users_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_trips` int(11) DEFAULT 0,
  `age` int(11) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `license_number` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(50) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_backup`
--

INSERT INTO `users_backup` (`id`, `email`, `username`, `password`, `phone`, `profile_image`, `rating`, `total_trips`, `age`, `about`, `id_number`, `license_number`, `vehicle_type`, `vehicle_model`, `vehicle_plate`, `is_active`, `created_at`) VALUES
(3, 'asd0@gmail.com', 'Tmqafg', '$2y$10$y/xy1tYcOy7CmjiaWpjnheFBO4tpKLKlSjfu.QuZhoUfC/Y5DeotK', '4444444444444444', NULL, 0.00, 0, 2147483647, '444444444444444', '44444444444444444444', '444444', '444444444444444444', '444444444444444444', '44444444444444444444', 1, '2024-12-20 15:04:10'),
(4, 'ahme100@gmail.com', 'Tmqafg', '$2y$10$FkeNBSPRJJwOKibT1Dt6L.lA79Nz9AJQwEzIeQFdx0SQUsZzpQV.K', '01011965099', NULL, 0.00, 0, 55, '444444444444444444444444', '444444444444444', '22222222', '4444444444444', '555', '22222222222', 1, '2024-12-20 16:56:38'),
(6, 'ah4100@gmail.com', 'Tmqafg4444444', '', '4444444444444', NULL, 0.00, 0, 2147483647, '444444444444444444', '444444444444444', '44444444444', '444444444444444', '444444444444', '44444444444444444444', 1, '2024-12-20 16:59:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `company_notifications`
--
ALTER TABLE `company_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `reference_id` (`reference_id`);

--
-- Indexes for table `company_payments`
--
ALTER TABLE `company_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `company_staff`
--
ALTER TABLE `company_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `complaint_number` (`complaint_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `complaint_responses`
--
ALTER TABLE `complaint_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `complaint_id` (`complaint_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `driver_company_assignments`
--
ALTER TABLE `driver_company_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `driver_company_unique` (`driver_id`,`company_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `driver_documents`
--
ALTER TABLE `driver_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `driver_earnings`
--
ALTER TABLE `driver_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `driver_notifications`
--
ALTER TABLE `driver_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `driver_sessions`
--
ALTER TABLE `driver_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`,`ip_address`,`attempt_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `company_notifications`
--
ALTER TABLE `company_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `company_payments`
--
ALTER TABLE `company_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `company_staff`
--
ALTER TABLE `company_staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `complaint_responses`
--
ALTER TABLE `complaint_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `driver_company_assignments`
--
ALTER TABLE `driver_company_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_documents`
--
ALTER TABLE `driver_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_earnings`
--
ALTER TABLE `driver_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_locations`
--
ALTER TABLE `driver_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_notifications`
--
ALTER TABLE `driver_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `driver_sessions`
--
ALTER TABLE `driver_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `company_notifications`
--
ALTER TABLE `company_notifications`
  ADD CONSTRAINT `company_notifications_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_payments`
--
ALTER TABLE `company_payments`
  ADD CONSTRAINT `company_payments_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `company_staff`
--
ALTER TABLE `company_staff`
  ADD CONSTRAINT `company_staff_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `complaints_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
  ADD CONSTRAINT `complaints_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`);

--
-- Constraints for table `complaint_responses`
--
ALTER TABLE `complaint_responses`
  ADD CONSTRAINT `complaint_responses_ibfk_1` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_responses_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_responses_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `complaint_responses_ibfk_4` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `driver_company_assignments`
--
ALTER TABLE `driver_company_assignments`
  ADD CONSTRAINT `driver_company_assignments_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `driver_company_assignments_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `driver_documents`
--
ALTER TABLE `driver_documents`
  ADD CONSTRAINT `driver_documents_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`);

--
-- Constraints for table `driver_earnings`
--
ALTER TABLE `driver_earnings`
  ADD CONSTRAINT `driver_earnings_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `driver_earnings_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`);

--
-- Constraints for table `driver_locations`
--
ALTER TABLE `driver_locations`
  ADD CONSTRAINT `driver_locations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`);

--
-- Constraints for table `driver_notifications`
--
ALTER TABLE `driver_notifications`
  ADD CONSTRAINT `driver_notifications_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`);

--
-- Constraints for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  ADD CONSTRAINT `driver_ratings_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
  ADD CONSTRAINT `driver_ratings_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `driver_ratings_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`);

--
-- Constraints for table `driver_sessions`
--
ALTER TABLE `driver_sessions`
  ADD CONSTRAINT `driver_sessions_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
