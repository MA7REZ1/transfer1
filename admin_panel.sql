-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2024 at 08:24 PM
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
(6, 'admin@system.com', 'المدير العام', '$2y$10$ln7Q/EwgVB3MqEAsezQwX.6/Yl48F.iMYD1TnrYyObsw7/v4Oo5Ty', 'super_admin', NULL, '2024-12-25 18:43:11', 1, '2024-12-21 16:20:53');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `email`, `password`, `phone`, `address`, `commercial_record`, `tax_number`, `logo`, `contact_person`, `contact_phone`, `is_active`, `created_at`) VALUES
(5, 'ahmed ma7rez', 'jj@gmail.com', '$2y$10$Y6E0gSd34p1G3AztVM7fnuVwo29WEZssUtS2AOKAob1mW7MapYGv6', '01011965099', 'eg\r\ngiza', '44444444444444', '44444444444444', NULL, 'ahmed ma7rez', '01011965099', 1, '2024-12-20 16:21:23'),
(8, 'ahmed ma7rez', 'ahahrez.100@gmail.com', '', '01011965099', 'eg\r\ngiza', '44444444444444', '44444444444444', '', 'ahmed ma7rez', '01011965099', 1, '2024-12-20 16:30:01'),
(10, ' ma7rez', 'ahmehrez.100@gmail.com', '$2y$10$uuHYXU9Gn6cC/gRR7F3cr.5WShyr/wa1JnEoce0GmLLJnBmo1wLjW', '01011965099', 'eg\r\ngiza00', '1111111111', '111111111111111', '67659dac67c97_download.jpeg', 'ahmed ma7rez', '01011965099', 1, '2024-12-20 16:39:08'),
(11, 'ma7rez', 'ahmed.0@gmail.com', '$2y$10$IIq92Fn.jUTsUKM7w6TEo.irSuIg3AUybSpyLXoGnrprmSDlJ9D9q', '01011965099', 'eg\r\ngiza', '2222200000', '000000000055555', '6765bd907dee6_423062728_379739214682800_7795385906020126235_n.jpg', '555555', '00000000000000000000', 1, '2024-12-20 18:55:12'),
(12, 'hussen1', 'a@gmail.com', '$2y$10$NknONIQMgtf0qXdpPkxf.udAWO7nOgseTOHHJ4H/.72E2uJJKGtAe', '01011965099', 'مصرف نهاية0', '1111111111', '111111111111111', '6766d6388d65e_html-5.png', 'حسين', '1000000000', 1, '2024-12-21 14:52:41'),
(13, 'ahmed', 'ahmed@gmail.com', '$2y$10$Qmg4Nxtm4Fv6F.HdTpZ9Z.FPr4SXBjcni4z6CRX7DYGl4GAlalAtm', '01011965099', 'القاهرة', '1111111111', '111111111111111', '', 'حسين', '1000000000', 1, '2024-12-24 17:28:15'),
(14, 'x', 'ahmed.mahrez.100@gmail.com', '$2y$10$q7sOkRtJXH19daSVCXfvGO1vI6EzBU0qW0lA5sffcYRhUvQuiIhPi', '01011965099', 'eg\r\ngiza', '00000000000', '00000000000000000', '', 'ahmed ma7rez', '01011965099', 0, '2024-12-25 19:33:17');

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
  `last_login` timestamp NULL DEFAULT NULL,
  `last_location` point DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `username`, `email`, `password`, `phone`, `age`, `about`, `address`, `profile_image`, `id_number`, `license_number`, `vehicle_type`, `vehicle_model`, `plate_number`, `is_active`, `current_status`, `rating`, `total_trips`, `completed_orders`, `cancelled_orders`, `total_earnings`, `last_login`, `last_location`, `created_at`, `updated_at`) VALUES
(1, 'ahmed', 'a@gmail.com', '$2y$10$esilaoYv1RfKiPe9RC4LsefvuAjYy4YXsG98KsY318jxfEwm1J7UW', '01011965099', 22, 'ماهر جدا خبرة 5 سنوات', 'مصر القاهرة', NULL, '222222', '22222222222', '222222', '22222222222', '2222222', 1, 'available', 0.00, 0, 0, 0, 0.00, NULL, NULL, '2024-12-24 17:26:31', '2024-12-24 17:26:31');

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
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(20) DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `admin_id`, `message`, `type`, `link`, `is_read`, `created_at`) VALUES
(50, 6, 'تم تحديث بيانات الشركة: hussen\nكلمة المرور الجديدة: hussen@123', 'info', 'companies.php', 1, '2024-12-21 16:23:00'),
(51, 6, 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2024-12-21 16:24:57'),
(52, 6, 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 1, '2024-12-21 16:25:01'),
(53, 6, 'تم تحديث بيانات الشركة: hussen1\nكلمة المرور الجديدة: hussen1@123', 'info', 'companies.php', 1, '2024-12-21 17:43:48'),
(54, 6, 'تم حذف الشركة', 'danger', 'companies.php', 1, '2024-12-21 17:58:47'),
(55, 6, 'تم تعطيل الشركة', 'warning', 'companies.php', 1, '2024-12-21 18:05:53'),
(56, 6, 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 1, '2024-12-21 18:06:00'),
(57, 6, 'تم إضافة سائق جديد: ffff', 'success', 'drivers.php', 1, '2024-12-24 01:47:11'),
(58, 6, 'تم تعطيل السائق', 'warning', 'drivers.php', 1, '2024-12-24 01:49:40'),
(59, 6, 'تم تعطيل الشركة', 'warning', 'companies.php', 0, '2024-12-24 03:14:29'),
(60, 6, 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 0, '2024-12-24 03:14:32'),
(61, 6, 'تم إضافة سائق جديد: aaaaaaa', 'success', 'drivers.php', 0, '2024-12-24 03:19:25'),
(62, 6, 'تم تعطيل السائق', 'warning', 'drivers.php', 0, '2024-12-24 03:19:33'),
(63, 6, 'تم تفعيل السائق بنجاح', 'success', 'drivers.php', 0, '2024-12-24 03:19:36'),
(64, 6, 'تم إضافة سائق جديد: ahmed', 'success', 'drivers.php', 0, '2024-12-24 17:26:31'),
(65, 6, 'تم تحديث بيانات السائق: ahmed', 'info', 'drivers.php', 0, '2024-12-24 17:26:37'),
(66, 6, 'تم إضافة شركة جديدة: ahmed\nكلمة المرور: ahmed@123', 'success', 'companies.php', 0, '2024-12-24 17:28:15'),
(67, 6, 'تم تعطيل الشركة', 'warning', 'companies.php', 0, '2024-12-24 17:28:53'),
(68, 6, 'تم تفعيل الشركة بنجاح', 'success', 'companies.php', 0, '2024-12-24 17:28:58'),
(69, 6, 'تم تحديث بيانات الشركة:  ma7rez\nكلمة المرور الجديدة:  ma7rez@123', 'info', 'companies.php', 0, '2024-12-25 19:11:40'),
(70, 6, 'تم إضافة شركة جديدة: x\nكلمة المرور: x@123', 'success', 'companies.php', 0, '2024-12-25 19:33:17'),
(71, 6, 'تم تعطيل الشركة', 'warning', 'companies.php', 0, '2024-12-25 19:38:21');

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
  `delivery_date` date NOT NULL,
  `pickup_location` text NOT NULL,
  `delivery_location` text NOT NULL,
  `items_count` int(11) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer') NOT NULL,
  `payment_status` enum('paid','unpaid') NOT NULL DEFAULT 'unpaid',
  `is_fragile` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `order_number`, `company_id`, `driver_id`, `customer_name`, `customer_phone`, `order_type`, `delivery_date`, `pickup_location`, `delivery_location`, `items_count`, `total_cost`, `payment_method`, `payment_status`, `is_fragile`, `invoice_file`, `status`, `additional_notes`, `created_at`, `updated_at`) VALUES
(1, 'ORD-20241224-8458', 11, NULL, 'احمد', '0222222222222', 'delivery', '2024-12-24', 'الجيزة', 'القاهرة', 3, 20.00, 'cash', 'unpaid', 1, NULL, 'pending', 'لابات ', '2024-12-24 17:34:10', '2024-12-24 17:34:10'),
(2, 'ORD-20241224-3554', 11, NULL, 'محمد', '0222222222222', 'transport', '2024-12-24', 'القاهرة', 'الجيزة', 5, 200.00, 'card', 'unpaid', 1, NULL, 'pending', 'pc', '2024-12-24 18:00:04', '2024-12-24 18:00:04'),
(3, 'ORD-20241224-5110', 12, NULL, 'محمود', '0111111111', 'transport', '2025-01-10', 'ا', 'ب', 3, 11.00, 'bank_transfer', 'unpaid', 1, NULL, 'pending', '0000000', '2024-12-24 18:50:13', '2024-12-24 18:50:13'),
(4, 'ORD-20241225-8375', 13, NULL, 'ahmed', '0222222222222', 'delivery', '2024-12-31', 'a', 'b', 1, 20.00, 'cash', 'unpaid', 1, NULL, 'pending', 'h', '2024-12-25 18:41:30', '2024-12-25 18:41:30');

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `driver_sessions`
--
ALTER TABLE `driver_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  ADD CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
  ADD CONSTRAINT `complaints_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
  ADD CONSTRAINT `complaints_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`);

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

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
