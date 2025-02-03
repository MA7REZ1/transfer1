-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2025 at 06:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (
    `id`,
    `driver_id`,
    `action`,
    `details`,
    `created_at`
  )
VALUES (
    1,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:30:47'
  ),
  (
    2,
    1,
    'logout',
    'Driver logged out',
    '2025-01-06 21:33:27'
  ),
  (
    3,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:33:38'
  ),
  (
    4,
    1,
    'logout',
    'Driver logged out',
    '2025-01-06 21:33:38'
  ),
  (
    5,
    NULL,
    'login_failed',
    'Failed login attempt for email: test@driver.com',
    '2025-01-06 21:36:47'
  ),
  (
    6,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:37:36'
  ),
  (
    7,
    1,
    'logout',
    'Driver logged out',
    '2025-01-06 21:37:36'
  ),
  (
    8,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:50:57'
  ),
  (
    9,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:51:28'
  ),
  (
    10,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:51:41'
  ),
  (
    11,
    1,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 21:54:56'
  ),
  (
    12,
    2,
    'login_success',
    'Driver logged in successfully',
    '2025-01-06 23:27:19'
  ),
  (
    13,
    NULL,
    'login_failed',
    'Failed login attempt for email: admin@system.com',
    '2025-01-11 22:46:54'
  ),
  (
    14,
    NULL,
    'login_failed',
    'Failed login attempt for email: admin@system.com',
    '2025-01-11 22:50:38'
  ),
  (
    15,
    NULL,
    'login_failed',
    'Failed login attempt for email: admin@system.com',
    '2025-01-11 22:50:47'
  ),
  (
    16,
    NULL,
    'login_failed',
    'Failed login attempt for email: admin@system.com',
    '2025-01-11 22:50:52'
  ),
  (
    17,
    2,
    'login_success',
    'Driver logged in successfully',
    '2025-01-13 16:59:27'
  ),
  (
    18,
    2,
    'login_success',
    'Driver logged in successfully',
    '2025-01-13 17:17:48'
  ),
  (
    19,
    2,
    'logout',
    'Driver logged out',
    '2025-01-13 17:27:24'
  );
-- --------------------------------------------------------
--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin', 'admin', 'support') DEFAULT 'admin',
  `department` enum('drivers', 'companies', 'complaints', 'orders') DEFAULT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (
    `id`,
    `email`,
    `username`,
    `password`,
    `role`,
    `department`,
    `last_login`,
    `is_active`,
    `created_at`
  )
VALUES (
    6,
    'admin@system.com',
    'المدير العام',
    '$2y$10$hmFJLWRj5AG3PH20tigvA.3FCLefGkMYQbzR.oNevJncRyXbwL.iy',
    'super_admin',
    NULL,
    '2025-01-13 16:54:20',
    1,
    '2024-12-21 16:20:53'
  ),
  (
    8,
    'admin1@system.com',
    'المدير العام',
    '$2y$10$xceTxvPZpAWzSc/mIQwH1eCYEdPm6iQv745q.6TiEVM26pNeqHoQq',
    'super_admin',
    NULL,
    '2024-12-31 00:23:01',
    1,
    '2024-12-31 00:23:01'
  );
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (
    `id`,
    `name`,
    `email`,
    `password`,
    `phone`,
    `address`,
    `commercial_record`,
    `tax_number`,
    `logo`,
    `contact_person`,
    `contact_phone`,
    `is_active`,
    `created_at`
  )
VALUES (
    5,
    'ahmed ma7rez',
    'jj@gmail.com',
    '$2y$10$Y6E0gSd34p1G3AztVM7fnuVwo29WEZssUtS2AOKAob1mW7MapYGv6',
    '01011965099',
    'eg\r\ngiza',
    '44444444444444',
    '44444444444444',
    NULL,
    'ahmed ma7rez',
    '01011965099',
    1,
    '2024-12-20 16:21:23'
  ),
  (
    8,
    'ahmed ma7rez',
    'ahahrez.100@gmail.com',
    '',
    '01011965099',
    'eg\r\ngiza',
    '44444444444444',
    '44444444444444',
    '',
    'ahmed ma7rez',
    '01011965099',
    1,
    '2024-12-20 16:30:01'
  ),
  (
    10,
    ' ma7rez',
    'ahmehrez.100@gmail.com',
    '$2y$10$uuHYXU9Gn6cC/gRR7F3cr.5WShyr/wa1JnEoce0GmLLJnBmo1wLjW',
    '01011965099',
    'eg\r\ngiza00',
    '1111111111',
    '111111111111111',
    '67659dac67c97_download.jpeg',
    'ahmed ma7rez',
    '01011965099',
    1,
    '2024-12-20 16:39:08'
  ),
  (
    11,
    'ma7rez',
    'ahmed.0@gmail.com',
    '$2y$10$0UlfSROvJfPRdsC7eJ15TeKCu90/w9IHQlyPig.3g3bCvGBlJDJy2',
    '01011965099',
    'eg\r\ngiza',
    '2222200000',
    '000000000055555',
    '67738bd6925ad.jpeg',
    '555555',
    '00000000000000000000',
    1,
    '2024-12-20 18:55:12'
  ),
  (
    12,
    'hussen1',
    'a@gmail.com',
    '$2y$10$NknONIQMgtf0qXdpPkxf.udAWO7nOgseTOHHJ4H/.72E2uJJKGtAe',
    '01011965099',
    'مصرف نهاية0',
    '1111111111',
    '111111111111111',
    '6766d6388d65e_html-5.png',
    'حسين',
    '1000000000',
    1,
    '2024-12-21 14:52:41'
  ),
  (
    13,
    'ahmed',
    'ahmed@gmail.com',
    '$2y$10$Qmg4Nxtm4Fv6F.HdTpZ9Z.FPr4SXBjcni4z6CRX7DYGl4GAlalAtm',
    '01011965099',
    'القاهرة',
    '1111111111',
    '111111111111111',
    '',
    'حسين',
    '1000000000',
    1,
    '2024-12-24 17:28:15'
  ),
  (
    14,
    'x',
    'ahmed.mahrez.100@gmail.com',
    '$2y$10$q7sOkRtJXH19daSVCXfvGO1vI6EzBU0qW0lA5sffcYRhUvQuiIhPi',
    '01011965099',
    'eg\r\ngiza',
    '00000000000',
    '00000000000000000',
    '',
    'ahmed ma7rez',
    '01011965099',
    1,
    '2024-12-25 19:33:17'
  );
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `company_notifications`
--

INSERT INTO `company_notifications` (
    `id`,
    `company_id`,
    `title`,
    `message`,
    `type`,
    `link`,
    `is_read`,
    `created_at`,
    `reference_id`
  )
VALUES (
    10,
    11,
    'رد جديد على الشكوى',
    'تم إضافة رد جديد على الشكوى رقم COMP202501074676 وتم تغيير حالة الشكوى إلى تم الحل',
    'complaint_response',
    '#',
    1,
    '2025-01-11 20:26:03',
    11
  ),
  (
    11,
    11,
    'رد جديد على الشكوى',
    'تم إضافة رد جديد على الشكوى رقم COMP202501078781 وتم تغيير حالة الشكوى إلى تم الحل',
    'complaint_response',
    '#',
    1,
    '2025-01-11 21:10:44',
    10
  ),
  (
    12,
    11,
    'تم تسجيل دفعة جديدة',
    'تم تسجيل دفعة بمبلغ 10.00 ريال',
    'payment',
    NULL,
    1,
    '2025-01-13 09:58:33',
    NULL
  ),
  (
    13,
    13,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)',
    'payment',
    NULL,
    0,
    '2025-01-13 11:03:38',
    NULL
  ),
  (
    14,
    13,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)',
    'payment',
    NULL,
    0,
    '2025-01-13 11:05:07',
    NULL
  ),
  (
    15,
    13,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)',
    'payment',
    NULL,
    0,
    '2025-01-13 11:07:22',
    NULL
  ),
  (
    16,
    13,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 0000000000000)',
    'payment',
    NULL,
    0,
    '2025-01-13 11:10:50',
    NULL
  ),
  (
    17,
    13,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي  (رقم المرجع: 111111111111)',
    'payment',
    NULL,
    0,
    '2025-01-13 11:11:23',
    NULL
  ),
  (
    18,
    13,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 40.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:12:28',
    NULL
  ),
  (
    19,
    13,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 10.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:13:02',
    NULL
  ),
  (
    20,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:15:26',
    NULL
  ),
  (
    21,
    11,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي  (رقم المرجع: 1010)',
    'payment',
    NULL,
    1,
    '2025-01-13 11:16:15',
    NULL
  ),
  (
    22,
    11,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 30.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    1,
    '2025-01-13 11:16:37',
    NULL
  ),
  (
    23,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:25:23',
    NULL
  ),
  (
    24,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 20.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:27:06',
    NULL
  ),
  (
    25,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 30.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:27:50',
    NULL
  ),
  (
    26,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 50.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:33:05',
    NULL
  ),
  (
    27,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:37:29',
    NULL
  ),
  (
    28,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:39:08',
    NULL
  ),
  (
    29,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 100.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:42:24',
    NULL
  ),
  (
    30,
    12,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:43:49',
    NULL
  ),
  (
    31,
    12,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 100.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:47:53',
    NULL
  ),
  (
    32,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 120.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:52:17',
    NULL
  ),
  (
    33,
    12,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 250.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 11:53:06',
    NULL
  ),
  (
    34,
    11,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 80.00 ريال عن طريق تحويل بنكي ',
    'payment',
    NULL,
    1,
    '2025-01-13 11:56:12',
    NULL
  ),
  (
    35,
    11,
    'تم تسجيل دفعة صادرة',
    'تم تسجيل دفعة صادرة بمبلغ 140.00 ريال عن طريق نقدي  (رقم المرجع: 1010)',
    'payment',
    NULL,
    0,
    '2025-01-13 12:02:05',
    NULL
  ),
  (
    36,
    12,
    'تم تسجيل دفعة واردة',
    'تم تسجيل دفعة واردة بمبلغ 10.00 ريال عن طريق نقدي ',
    'payment',
    NULL,
    0,
    '2025-01-13 12:02:28',
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `company_payments`
--

CREATE TABLE `company_payments` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `payment_method` enum('cash', 'bank_transfer', 'check') NOT NULL,
  `payment_type` enum('incoming', 'outgoing') NOT NULL DEFAULT 'outgoing',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending', 'completed', 'cancelled') DEFAULT 'completed',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
--
-- Dumping data for table `company_payments`
--

INSERT INTO `company_payments` (
    `id`,
    `company_id`,
    `amount`,
    `payment_date`,
    `payment_method`,
    `payment_type`,
    `reference_number`,
    `notes`,
    `status`,
    `created_by`,
    `created_at`
  )
VALUES (
    27,
    11,
    140.00,
    '2025-01-13 14:02:05',
    'cash',
    'outgoing',
    '1010',
    '',
    'completed',
    6,
    '2025-01-13 12:02:05'
  ),
  (
    28,
    12,
    10.00,
    '2025-01-13 14:02:28',
    'cash',
    'incoming',
    '',
    '',
    'completed',
    6,
    '2025-01-13 12:02:28'
  );
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
  `role` enum('order_manager', 'staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `company_staff`
--

INSERT INTO `company_staff` (
    `id`,
    `company_id`,
    `name`,
    `email`,
    `password`,
    `phone`,
    `role`,
    `is_active`,
    `last_login`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    11,
    'ahmed ma7rez',
    'a100@gmail.com',
    '$2y$10$nKL5beZ9TSUVsS3m/JfWyObcdh7K1hIaJG3FlwcKu/2bUmYQvZ4fu',
    '01011965099',
    'staff',
    1,
    NULL,
    '2025-01-02 21:27:53',
    '2025-01-06 19:57:36'
  ),
  (
    2,
    11,
    'ahmed mahrez',
    'ahmed@gmail.com',
    '$2y$10$GoDDApX43sx5u27WQyZtae/N5q2ezgt.voL8rM/YZPj0S.Ehx.B7y',
    '01011965099',
    'order_manager',
    1,
    '2025-01-13 17:25:52',
    '2025-01-02 22:01:17',
    '2025-01-13 17:25:52'
  );
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
  `type` enum('company', 'driver', 'request', 'other') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `status` enum('new', 'in_progress', 'resolved', 'closed') DEFAULT 'new',
  `priority` enum('low', 'medium', 'high') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (
    `id`,
    `complaint_number`,
    `company_id`,
    `driver_id`,
    `request_id`,
    `type`,
    `subject`,
    `description`,
    `status`,
    `priority`,
    `assigned_to`,
    `resolution_notes`,
    `created_at`,
    `updated_at`
  )
VALUES (
    8,
    'COMP202501078686',
    11,
    2,
    11,
    'driver',
    'السواق',
    'تااا',
    'new',
    'medium',
    NULL,
    NULL,
    '2025-01-07 04:58:47',
    '2025-01-07 04:58:47'
  ),
  (
    9,
    'COMP202501077665',
    11,
    1,
    8,
    'driver',
    'سسسسس',
    'ففففففففف',
    'new',
    'medium',
    NULL,
    NULL,
    '2025-01-07 04:59:16',
    '2025-01-07 04:59:16'
  ),
  (
    10,
    'COMP202501078781',
    11,
    1,
    10,
    'driver',
    'يييييييييي',
    'يييييييييييييييييييييييييي',
    'resolved',
    'high',
    NULL,
    NULL,
    '2025-01-07 04:59:26',
    '2025-01-11 21:10:44'
  ),
  (
    11,
    'COMP202501074676',
    11,
    1,
    9,
    'driver',
    'يييييييييي',
    'لابلارؤ',
    'resolved',
    'medium',
    NULL,
    NULL,
    '2025-01-07 05:01:41',
    '2025-01-11 20:26:03'
  );
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `complaint_responses`
--

INSERT INTO `complaint_responses` (
    `id`,
    `complaint_id`,
    `admin_id`,
    `company_id`,
    `response`,
    `is_company_reply`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    11,
    NULL,
    11,
    'هل يمكن الرد',
    1,
    '2025-01-11 18:55:25',
    '2025-01-11 18:55:25'
  ),
  (
    2,
    11,
    6,
    NULL,
    'تم حلها',
    0,
    '2025-01-11 19:18:53',
    '2025-01-11 19:18:53'
  ),
  (
    3,
    11,
    6,
    NULL,
    'تم حلها يافندم',
    0,
    '2025-01-11 20:26:03',
    '2025-01-11 20:26:03'
  ),
  (
    4,
    11,
    NULL,
    11,
    'تم شكرا\r\n',
    1,
    '2025-01-11 20:31:50',
    '2025-01-11 20:31:50'
  ),
  (
    11,
    10,
    NULL,
    11,
    'تتتتتتتتتتتتتتتتتتتتتتت',
    1,
    '2025-01-11 21:07:50',
    '2025-01-11 21:07:50'
  ),
  (
    12,
    10,
    NULL,
    11,
    'تم الرد',
    1,
    '2025-01-11 21:09:34',
    '2025-01-11 21:09:34'
  ),
  (
    13,
    10,
    6,
    NULL,
    'تم الحل',
    0,
    '2025-01-11 21:10:44',
    '2025-01-11 21:10:44'
  ),
  (
    14,
    11,
    NULL,
    11,
    'تم شكرا\r\n',
    1,
    '2025-01-11 21:23:28',
    '2025-01-11 21:23:28'
  );
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
  `current_status` enum('available', 'busy', 'offline') DEFAULT 'offline',
  `rating` decimal(3, 2) DEFAULT 0.00,
  `total_trips` int(11) DEFAULT 0,
  `completed_orders` int(11) DEFAULT 0,
  `cancelled_orders` int(11) DEFAULT 0,
  `total_earnings` decimal(10, 2) DEFAULT 0.00,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_location` point DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (
    `id`,
    `username`,
    `email`,
    `password`,
    `phone`,
    `age`,
    `about`,
    `address`,
    `profile_image`,
    `id_number`,
    `license_number`,
    `vehicle_type`,
    `vehicle_model`,
    `plate_number`,
    `is_active`,
    `current_status`,
    `rating`,
    `total_trips`,
    `completed_orders`,
    `cancelled_orders`,
    `total_earnings`,
    `last_login`,
    `last_location`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    'ahmed',
    'a@gmail.com',
    '$2y$10$u8ONso5w7EZLISUaYqFYL.N3dDsdM/y3pG4sxgCCRH9kln.vqxRvq',
    '01011965099',
    22,
    'ماهر جدا خبرة 5 سنوات',
    'مصر القاهرة',
    NULL,
    '222222',
    '22222222222',
    '222222',
    '22222222222',
    '2222222',
    1,
    'available',
    3.00,
    0,
    1,
    0,
    0.00,
    '2025-01-06 21:54:56',
    NULL,
    '2024-12-24 17:26:31',
    '2025-01-13 17:00:36'
  ),
  (
    2,
    'mohmed',
    'a100@gmail.com',
    '$2y$10$0fhtaPfJb1loIC2nKHJz8O5aegz/DDAQO0h3PX/HalbDJJXFfHo1a',
    '0123465448',
    20,
    'ماهر',
    'الرياض',
    NULL,
    '2222222',
    '2222',
    '22222',
    '2222222222',
    '222222222',
    1,
    'available',
    4.00,
    0,
    1,
    0,
    0.00,
    '2025-01-13 17:17:48',
    NULL,
    '2025-01-06 23:22:26',
    '2025-01-13 17:17:48'
  );
-- --------------------------------------------------------
--
-- Table structure for table `driver_company_assignments`
--

CREATE TABLE `driver_company_assignments` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
--
-- Table structure for table `driver_documents`
--

CREATE TABLE `driver_documents` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `document_type` enum(
    'id',
    'license',
    'insurance',
    'vehicle_registration',
    'other'
  ) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
--
-- Table structure for table `driver_earnings`
--

CREATE TABLE `driver_earnings` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `amount` decimal(10, 2) NOT NULL,
  `type` enum('delivery_fee', 'tip', 'bonus') NOT NULL,
  `status` enum('pending', 'paid', 'cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
-- --------------------------------------------------------
--
-- Table structure for table `driver_locations`
--

CREATE TABLE `driver_locations` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `latitude` decimal(10, 8) NOT NULL,
  `longitude` decimal(11, 8) NOT NULL,
  `accuracy` decimal(10, 2) DEFAULT NULL,
  `speed` decimal(10, 2) DEFAULT NULL,
  `heading` decimal(10, 2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `driver_notifications`
--

INSERT INTO `driver_notifications` (
    `id`,
    `driver_id`,
    `message`,
    `type`,
    `is_read`,
    `created_at`
  )
VALUES (
    1,
    1,
    'تم قبول الطلب رقم ORD-20250103-7959 بنجاح',
    'order_accepted',
    0,
    '2025-01-03 06:15:51'
  ),
  (
    2,
    1,
    'تم بدء توصيل الطلب رقم 3',
    'in_transit',
    0,
    '2025-01-06 22:51:14'
  ),
  (
    3,
    1,
    'تم بدء توصيل الطلب رقم 3',
    'in_transit',
    0,
    '2025-01-06 22:52:23'
  ),
  (
    4,
    1,
    'تم تسليم الطلب رقم 3',
    'delivered',
    0,
    '2025-01-06 22:52:24'
  ),
  (
    5,
    1,
    'تم بدء توصيل الطلب رقم 8',
    'in_transit',
    0,
    '2025-01-06 22:52:27'
  ),
  (
    6,
    1,
    'تم بدء توصيل الطلب رقم 8',
    'in_transit',
    0,
    '2025-01-06 22:58:18'
  ),
  (
    7,
    1,
    'تم تسليم الطلب رقم 8',
    'delivered',
    0,
    '2025-01-06 22:58:26'
  ),
  (
    8,
    1,
    'تم تسليم الطلب رقم 8',
    'delivered',
    0,
    '2025-01-06 22:59:54'
  ),
  (
    9,
    1,
    'تم قبول الطلب رقم 9 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:00:13'
  ),
  (
    10,
    1,
    'تم بدء توصيل الطلب رقم 9',
    'in_transit',
    0,
    '2025-01-06 23:00:21'
  ),
  (
    11,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:00:50'
  ),
  (
    12,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:03:32'
  ),
  (
    13,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:03:39'
  ),
  (
    14,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:04:00'
  ),
  (
    15,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:05:36'
  ),
  (
    16,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:06:13'
  ),
  (
    17,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:06:50'
  ),
  (
    18,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:08:29'
  ),
  (
    19,
    1,
    'تم إلغاء الطلب رقم 10',
    'order_cancelled',
    0,
    '2025-01-06 23:08:38'
  ),
  (
    20,
    1,
    'تم إلغاء الطلب رقم 10',
    'order_cancelled',
    0,
    '2025-01-06 23:08:42'
  ),
  (
    21,
    1,
    'تم إلغاء الطلب رقم 10 وإعادته للقائمة العامة',
    'order_cancelled',
    0,
    '2025-01-06 23:13:13'
  ),
  (
    22,
    1,
    'تم إلغاء الطلب رقم 9 وإعادته للقائمة العامة',
    'order_cancelled',
    0,
    '2025-01-06 23:13:15'
  ),
  (
    23,
    1,
    'تم قبول الطلب رقم 9 بنجاح',
    'order_accepted',
    0,
    '2025-01-06 23:13:17'
  ),
  (
    24,
    1,
    'تم بدء توصيل الطلب رقم 9',
    'in_transit',
    0,
    '2025-01-06 23:13:24'
  ),
  (
    25,
    1,
    'تم إلغاء الطلب رقم 9 وإعادته للقائمة العامة',
    'order_cancelled',
    0,
    '2025-01-06 23:17:17'
  ),
  (
    26,
    1,
    'تم قبول الطلب رقم 9 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 00:13:33'
  ),
  (
    27,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 00:48:34'
  ),
  (
    28,
    2,
    'تم إلغاء الطلب رقم 11 وإعادته للقائمة العامة',
    'order_cancelled',
    0,
    '2025-01-07 00:48:43'
  ),
  (
    29,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 00:48:45'
  ),
  (
    30,
    1,
    'تم قبول الطلب رقم 9 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 00:49:08'
  ),
  (
    31,
    1,
    'تم بدء توصيل الطلب رقم 9',
    'in_transit',
    0,
    '2025-01-07 00:55:30'
  ),
  (
    32,
    1,
    'تم تسليم الطلب رقم 9',
    'delivered',
    0,
    '2025-01-07 00:55:35'
  ),
  (
    33,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 01:05:16'
  ),
  (
    34,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 01:21:14'
  ),
  (
    35,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 01:46:27'
  ),
  (
    36,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 01:47:15'
  ),
  (
    37,
    2,
    'تم إلغاء الطلب رقم 11 وإعادته للقائمة العامة',
    'order_cancelled',
    0,
    '2025-01-07 01:47:20'
  ),
  (
    38,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 02:16:49'
  ),
  (
    39,
    1,
    'تم قبول الطلب رقم 10 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 02:17:06'
  ),
  (
    40,
    2,
    'تم قبول الطلب رقم 11 بنجاح',
    'order_accepted',
    0,
    '2025-01-07 04:46:15'
  ),
  (
    41,
    2,
    'تم بدء توصيل الطلب رقم 11',
    'in_transit',
    0,
    '2025-01-07 04:46:19'
  ),
  (
    42,
    2,
    'تم تسليم الطلب رقم 11',
    'delivered',
    0,
    '2025-01-07 04:54:45'
  );
-- --------------------------------------------------------
--
-- Table structure for table `driver_ratings`
--

CREATE TABLE `driver_ratings` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (
    `rating` between 1 and 5
  ),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `driver_ratings`
--

INSERT INTO `driver_ratings` (
    `id`,
    `request_id`,
    `driver_id`,
    `company_id`,
    `rating`,
    `comment`,
    `created_at`
  )
VALUES (1, 8, 1, 11, 4, 'ت', '2025-01-07 00:46:16'),
  (2, 9, 1, 11, 2, 'ه', '2025-01-07 01:34:05'),
  (
    3,
    11,
    2,
    11,
    4,
    'تتتتتتتتتتت',
    '2025-01-07 04:54:54'
  );
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
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
  `role` enum('مدير_عام', 'موظف') NOT NULL,
  `status` enum('active', 'inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (
    `id`,
    `username`,
    `password`,
    `full_name`,
    `email`,
    `phone`,
    `department`,
    `role`,
    `status`,
    `created_at`,
    `updated_at`
  )
VALUES (
    3,
    'محمد',
    '$2y$10$csC34xsqL9CQXxq8GTCv7exBCvua3UWNTwIZIP/1N5lYfuNY5uY52',
    'احمد',
    'aalright54@gmail.com',
    '01011965099',
    'إدارة',
    'مدير_عام',
    'active',
    '2025-01-12 01:12:17',
    '2025-01-13 08:05:49'
  ),
  (
    4,
    'ahmed',
    '$2y$10$Qbb3nZSEkIL0bSz8bJMxKOaaiqQj7YPxF.FBNEo2IRxU2gmgYNMda',
    'ahmed mahrez',
    'a@gmail.com',
    '01011965099',
    'إدارة',
    'موظف',
    'active',
    '2025-01-13 08:00:27',
    '2025-01-13 16:10:45'
  );
-- --------------------------------------------------------
--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (
    `id`,
    `admin_id`,
    `message`,
    `type`,
    `link`,
    `is_read`,
    `created_at`
  )
VALUES (
    50,
    6,
    'تم تحديث بيانات الشركة: hussen\nكلمة المرور الجديدة: hussen@123',
    'info',
    'companies.php',
    1,
    '2024-12-21 16:23:00'
  ),
  (
    51,
    6,
    'تم تعطيل السائق',
    'warning',
    'drivers.php',
    1,
    '2024-12-21 16:24:57'
  ),
  (
    52,
    6,
    'تم تفعيل السائق بنجاح',
    'success',
    'drivers.php',
    1,
    '2024-12-21 16:25:01'
  ),
  (
    53,
    6,
    'تم تحديث بيانات الشركة: hussen1\nكلمة المرور الجديدة: hussen1@123',
    'info',
    'companies.php',
    1,
    '2024-12-21 17:43:48'
  ),
  (
    54,
    6,
    'تم حذف الشركة',
    'danger',
    'companies.php',
    1,
    '2024-12-21 17:58:47'
  ),
  (
    55,
    6,
    'تم تعطيل الشركة',
    'warning',
    'companies.php',
    1,
    '2024-12-21 18:05:53'
  ),
  (
    56,
    6,
    'تم تفعيل الشركة بنجاح',
    'success',
    'companies.php',
    1,
    '2024-12-21 18:06:00'
  ),
  (
    57,
    6,
    'تم إضافة سائق جديد: ffff',
    'success',
    'drivers.php',
    1,
    '2024-12-24 01:47:11'
  ),
  (
    58,
    6,
    'تم تعطيل السائق',
    'warning',
    'drivers.php',
    1,
    '2024-12-24 01:49:40'
  ),
  (
    59,
    6,
    'تم تعطيل الشركة',
    'warning',
    'companies.php',
    0,
    '2024-12-24 03:14:29'
  ),
  (
    60,
    6,
    'تم تفعيل الشركة بنجاح',
    'success',
    'companies.php',
    0,
    '2024-12-24 03:14:32'
  ),
  (
    61,
    6,
    'تم إضافة سائق جديد: aaaaaaa',
    'success',
    'drivers.php',
    0,
    '2024-12-24 03:19:25'
  ),
  (
    62,
    6,
    'تم تعطيل السائق',
    'warning',
    'drivers.php',
    0,
    '2024-12-24 03:19:33'
  ),
  (
    63,
    6,
    'تم تفعيل السائق بنجاح',
    'success',
    'drivers.php',
    0,
    '2024-12-24 03:19:36'
  ),
  (
    64,
    6,
    'تم إضافة سائق جديد: ahmed',
    'success',
    'drivers.php',
    0,
    '2024-12-24 17:26:31'
  ),
  (
    65,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2024-12-24 17:26:37'
  ),
  (
    66,
    6,
    'تم إضافة شركة جديدة: ahmed\nكلمة المرور: ahmed@123',
    'success',
    'companies.php',
    0,
    '2024-12-24 17:28:15'
  ),
  (
    67,
    6,
    'تم تعطيل الشركة',
    'warning',
    'companies.php',
    0,
    '2024-12-24 17:28:53'
  ),
  (
    68,
    6,
    'تم تفعيل الشركة بنجاح',
    'success',
    'companies.php',
    0,
    '2024-12-24 17:28:58'
  ),
  (
    69,
    6,
    'تم تحديث بيانات الشركة:  ma7rez\nكلمة المرور الجديدة:  ma7rez@123',
    'info',
    'companies.php',
    0,
    '2024-12-25 19:11:40'
  ),
  (
    70,
    6,
    'تم إضافة شركة جديدة: x\nكلمة المرور: x@123',
    'success',
    'companies.php',
    0,
    '2024-12-25 19:33:17'
  ),
  (
    71,
    6,
    'تم تعطيل الشركة',
    'warning',
    'companies.php',
    0,
    '2024-12-25 19:38:21'
  ),
  (
    72,
    6,
    'تم تفعيل الشركة بنجاح',
    'success',
    'companies.php',
    0,
    '2024-12-30 23:44:29'
  ),
  (
    73,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-03 02:51:06'
  ),
  (
    74,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-03 03:31:10'
  ),
  (
    75,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-03 07:25:43'
  ),
  (
    76,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-03 07:29:45'
  ),
  (
    77,
    6,
    'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123',
    'info',
    'companies.php',
    0,
    '2025-01-06 18:21:53'
  ),
  (
    78,
    6,
    'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123',
    'info',
    'companies.php',
    0,
    '2025-01-06 19:52:48'
  ),
  (
    79,
    6,
    'تم تحديث بيانات الشركة: ma7rez\nكلمة المرور الجديدة: ma7rez@123',
    'info',
    'companies.php',
    0,
    '2025-01-06 19:55:40'
  ),
  (
    80,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 20:45:10'
  ),
  (
    81,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 20:45:16'
  ),
  (
    82,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 20:45:59'
  ),
  (
    83,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 21:00:03'
  ),
  (
    84,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 21:08:54'
  ),
  (
    85,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-06 21:11:27'
  ),
  (
    86,
    6,
    'تم إضافة سائق جديد: mohmed',
    'success',
    'drivers.php',
    0,
    '2025-01-06 23:22:26'
  ),
  (
    87,
    6,
    'شكوى جديدة رقم: COMP202501077471 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 00:46:28'
  ),
  (
    88,
    8,
    'شكوى جديدة رقم: COMP202501077471 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 00:46:28'
  ),
  (
    90,
    6,
    'شكوى جديدة رقم: COMP202501075013 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:45:48'
  ),
  (
    91,
    8,
    'شكوى جديدة رقم: COMP202501075013 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:45:48'
  ),
  (
    93,
    6,
    'شكوى جديدة رقم: COMP202501072019 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:46:34'
  ),
  (
    94,
    8,
    'شكوى جديدة رقم: COMP202501072019 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:46:34'
  ),
  (
    96,
    6,
    'شكوى جديدة رقم: COMP202501077783 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:48:39'
  ),
  (
    97,
    8,
    'شكوى جديدة رقم: COMP202501077783 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:48:39'
  ),
  (
    99,
    6,
    'شكوى جديدة رقم: COMP202501079239 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:52:58'
  ),
  (
    100,
    8,
    'شكوى جديدة رقم: COMP202501079239 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:52:58'
  ),
  (
    102,
    6,
    'شكوى جديدة رقم: COMP202501073583 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:53:15'
  ),
  (
    103,
    8,
    'شكوى جديدة رقم: COMP202501073583 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:53:15'
  ),
  (
    105,
    6,
    'شكوى جديدة رقم: COMP202501079390 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:55:24'
  ),
  (
    106,
    8,
    'شكوى جديدة رقم: COMP202501079390 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:55:24'
  ),
  (
    108,
    6,
    'شكوى جديدة رقم: COMP202501078686 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:58:47'
  ),
  (
    109,
    8,
    'شكوى جديدة رقم: COMP202501078686 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:58:47'
  ),
  (
    111,
    6,
    'شكوى جديدة رقم: COMP202501077665 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:59:16'
  ),
  (
    112,
    8,
    'شكوى جديدة رقم: COMP202501077665 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:59:16'
  ),
  (
    114,
    6,
    'شكوى جديدة رقم: COMP202501078781 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:59:26'
  ),
  (
    115,
    8,
    'شكوى جديدة رقم: COMP202501078781 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 04:59:26'
  ),
  (
    117,
    6,
    'شكوى جديدة رقم: COMP202501074676 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 05:01:41'
  ),
  (
    118,
    8,
    'شكوى جديدة رقم: COMP202501074676 من شركة رقم: 11',
    'complaint',
    'complaints.php',
    0,
    '2025-01-07 05:01:41'
  ),
  (
    120,
    6,
    'تم الرد على الشكوى رقم: COMP202501074676',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-09 23:28:02'
  ),
  (
    121,
    8,
    'تم الرد على الشكوى رقم: COMP202501074676',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-09 23:28:02'
  ),
  (
    122,
    6,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 00:32:51'
  ),
  (
    123,
    8,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 00:32:51'
  ),
  (
    124,
    6,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 00:52:53'
  ),
  (
    125,
    8,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 00:52:53'
  ),
  (
    126,
    6,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 01:13:14'
  ),
  (
    127,
    8,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 01:13:14'
  ),
  (
    128,
    6,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 16:22:41'
  ),
  (
    129,
    8,
    'تم الرد على الشكوى رقم: COMP202501078781',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-10 16:22:41'
  ),
  (
    130,
    6,
    'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:07:50'
  ),
  (
    131,
    8,
    'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:07:50'
  ),
  (
    132,
    6,
    'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:09:34'
  ),
  (
    133,
    8,
    'رد جديد على الشكوى #COMP202501078781 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:09:34'
  ),
  (
    134,
    6,
    'رد جديد على الشكوى #COMP202501074676 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:23:28'
  ),
  (
    135,
    8,
    'رد جديد على الشكوى #COMP202501074676 - ma7rez: يييييييييي',
    'complaint_response',
    'complaints.php',
    0,
    '2025-01-11 21:23:28'
  ),
  (
    136,
    6,
    'تم تحديث بيانات السائق: mohmed',
    'info',
    'drivers.php',
    0,
    '2025-01-13 16:59:07'
  ),
  (
    137,
    6,
    'تم تحديث بيانات السائق: ahmed',
    'info',
    'drivers.php',
    0,
    '2025-01-13 17:00:36'
  );
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
  `order_type` enum('delivery', 'transport') NOT NULL,
  `delivery_date` datetime NOT NULL,
  `pickup_location` text NOT NULL,
  `delivery_location` text NOT NULL,
  `items_count` int(11) NOT NULL,
  `total_cost` decimal(10, 2) NOT NULL,
  `delivery_fee` decimal(10, 2) NOT NULL DEFAULT 20.00,
  `payment_method` enum('cash', 'card', 'bank_transfer') NOT NULL,
  `payment_status` enum('paid', 'unpaid') NOT NULL DEFAULT 'unpaid',
  `is_fragile` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_file` varchar(255) DEFAULT NULL,
  `status` enum(
    'pending',
    'accepted',
    'in_transit',
    'delivered',
    'cancelled'
  ) NOT NULL DEFAULT 'pending',
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pickup_location_link` varchar(500) DEFAULT NULL,
  `delivery_location_link` varchar(500) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (
    `id`,
    `order_number`,
    `company_id`,
    `driver_id`,
    `customer_name`,
    `customer_phone`,
    `order_type`,
    `delivery_date`,
    `pickup_location`,
    `delivery_location`,
    `items_count`,
    `total_cost`,
    `delivery_fee`,
    `payment_method`,
    `payment_status`,
    `is_fragile`,
    `invoice_file`,
    `status`,
    `additional_notes`,
    `created_at`,
    `updated_at`,
    `pickup_location_link`,
    `delivery_location_link`
  )
VALUES (
    1,
    'ORD-20241224-8458',
    11,
    NULL,
    'احمد',
    '0222222222222',
    'delivery',
    '2024-12-24 00:00:00',
    'الجيزة',
    'القاهرة',
    3,
    20.00,
    10.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'delivered',
    'لابات ',
    '2024-12-24 17:34:10',
    '2024-12-31 04:24:04',
    NULL,
    NULL
  ),
  (
    2,
    'ORD-20241224-3554',
    11,
    NULL,
    'محمد',
    '0222222222222',
    'transport',
    '2024-12-24 00:00:00',
    'القاهرة',
    'الجيزة',
    5,
    200.00,
    10.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'delivered',
    'pc',
    '2024-12-24 18:00:04',
    '2024-12-31 04:20:44',
    NULL,
    NULL
  ),
  (
    3,
    'ORD-20241224-5110',
    12,
    1,
    'محمود',
    '0111111111',
    'transport',
    '2025-01-10 00:00:00',
    'ا',
    'ب',
    3,
    11.00,
    10.00,
    'bank_transfer',
    'unpaid',
    1,
    NULL,
    'delivered',
    '0000000',
    '2024-12-24 18:50:13',
    '2025-01-06 22:52:24',
    NULL,
    NULL
  ),
  (
    4,
    'ORD-20241225-8375',
    13,
    NULL,
    'ahmed',
    '0222222222222',
    'delivery',
    '2024-12-31 00:00:00',
    'a',
    'b',
    1,
    20.00,
    10.00,
    'card',
    'unpaid',
    1,
    NULL,
    'delivered',
    'h',
    '2024-12-25 18:41:30',
    '2024-12-31 04:25:31',
    NULL,
    NULL
  ),
  (
    5,
    'ORD-20241230-6280',
    11,
    NULL,
    'محمود',
    '0111111111',
    'delivery',
    '2024-12-30 11:11:00',
    '1',
    '1',
    1,
    0.00,
    20.00,
    'card',
    'paid',
    1,
    NULL,
    'delivered',
    '',
    '2024-12-30 12:34:24',
    '2024-12-31 03:56:03',
    NULL,
    NULL
  ),
  (
    6,
    'ORD-20241231-4347',
    11,
    NULL,
    'محمود',
    '0111111111',
    'delivery',
    '2024-12-31 23:19:00',
    'حسين',
    'عند حسين',
    111,
    10.00,
    20.00,
    'card',
    'paid',
    1,
    NULL,
    'delivered',
    'ارحب ياحسين',
    '2024-12-30 23:13:23',
    '2024-12-31 05:22:58',
    'https://www.openstreetmap.org/?mlat=24.638916&mlon=46.7160104',
    'https://www.openstreetmap.org/?mlat=21.420847&mlon=39.826869'
  ),
  (
    7,
    'ORD-20241231-2025',
    11,
    NULL,
    'محمود',
    '0111111111',
    'delivery',
    '2024-12-31 11:00:00',
    'ؤؤؤؤؤؤؤ',
    'بببببببببب',
    1,
    10.00,
    10.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'delivered',
    'ةةةةةةةةةةة',
    '2024-12-31 04:15:32',
    '2024-12-31 04:20:44',
    'https://www.openstreetmap.org/?mlat=24.638916&mlon=46.7160104',
    'https://www.openstreetmap.org/?mlat=16.05405&mlon=43.70669'
  ),
  (
    8,
    'ORD-20250103-7959',
    11,
    1,
    'محمود1',
    '0111111111',
    'delivery',
    '2025-01-28 16:39:00',
    '111111111',
    '1111111',
    2,
    20.00,
    20.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'delivered',
    '111111111111111111',
    '2025-01-03 03:34:56',
    '2025-01-06 22:58:26',
    'https://www.openstreetmap.org/?mlat=24.716797632384722&mlon=46.684765862287435',
    'https://www.openstreetmap.org/?mlat=24.699292833787997&mlon=46.67653016346712'
  ),
  (
    9,
    'ORD-20250107-6037',
    11,
    1,
    'hhhh',
    '0148848484',
    'delivery',
    '2025-01-28 12:22:00',
    'nnn',
    'تتتتتتت',
    1,
    0.01,
    20.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'delivered',
    'تتتتتتتتتتتتتتتت',
    '2025-01-06 22:17:30',
    '2025-01-07 00:55:35',
    'https://www.openstreetmap.org/?mlat=24.722247914167436&mlon=46.68720251043721',
    'https://www.openstreetmap.org/?mlat=23.333333&mlon=45.333333'
  ),
  (
    10,
    'ORD-20250107-1912',
    11,
    1,
    'محمود',
    '0111111111',
    'delivery',
    '2025-01-07 01:42:00',
    'منزل عشرة',
    'ب',
    1,
    0.01,
    20.00,
    'cash',
    'unpaid',
    1,
    NULL,
    'in_transit',
    'بببببببببب',
    '2025-01-06 22:45:58',
    '2025-01-09 20:49:29',
    'https://www.openstreetmap.org/?mlat=23.333333&mlon=45.333333',
    'https://www.openstreetmap.org/?mlat=24.70100604556386&mlon=46.69282998404894'
  ),
  (
    11,
    'ORD-20250107-6301',
    11,
    2,
    'محمود محمد',
    '0111111111',
    'transport',
    '2025-01-20 12:59:00',
    'العباس بن مرداس، السليمانية، الرياض، منطقة الرياض، السعودية',
    'القيروان، السليمانية، الرياض، منطقة الرياض، السعودية',
    4,
    0.00,
    20.00,
    'card',
    'unpaid',
    1,
    NULL,
    'in_transit',
    '222222222222222222',
    '2025-01-06 23:49:28',
    '2025-01-09 22:00:26',
    'https://www.openstreetmap.org/?mlat=24.716330456496394&mlon=46.685999724202716',
    'https://www.openstreetmap.org/?mlat=24.71368307934326&mlon=46.69201365537513'
  );
-- --------------------------------------------------------
--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`, `created_at`, `updated_at`)
VALUES (
    'delivery_fee',
    '10',
    '2024-12-31 01:41:26',
    '2024-12-31 04:14:11'
  );
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
  `rating` decimal(3, 2) DEFAULT 0.00,
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
--
-- Dumping data for table `users_backup`
--

INSERT INTO `users_backup` (
    `id`,
    `email`,
    `username`,
    `password`,
    `phone`,
    `profile_image`,
    `rating`,
    `total_trips`,
    `age`,
    `about`,
    `id_number`,
    `license_number`,
    `vehicle_type`,
    `vehicle_model`,
    `vehicle_plate`,
    `is_active`,
    `created_at`
  )
VALUES (
    3,
    'asd0@gmail.com',
    'Tmqafg',
    '$2y$10$y/xy1tYcOy7CmjiaWpjnheFBO4tpKLKlSjfu.QuZhoUfC/Y5DeotK',
    '4444444444444444',
    NULL,
    0.00,
    0,
    2147483647,
    '444444444444444',
    '44444444444444444444',
    '444444',
    '444444444444444444',
    '444444444444444444',
    '44444444444444444444',
    1,
    '2024-12-20 15:04:10'
  ),
  (
    4,
    'ahme100@gmail.com',
    'Tmqafg',
    '$2y$10$FkeNBSPRJJwOKibT1Dt6L.lA79Nz9AJQwEzIeQFdx0SQUsZzpQV.K',
    '01011965099',
    NULL,
    0.00,
    0,
    55,
    '444444444444444444444444',
    '444444444444444',
    '22222222',
    '4444444444444',
    '555',
    '22222222222',
    1,
    '2024-12-20 16:56:38'
  ),
  (
    6,
    'ah4100@gmail.com',
    'Tmqafg4444444',
    '',
    '4444444444444',
    NULL,
    0.00,
    0,
    2147483647,
    '444444444444444444',
    '444444444444444',
    '44444444444',
    '444444444444444',
    '444444444444',
    '44444444444444444444',
    1,
    '2024-12-20 16:59:08'
  );
-- --------------------------------------------------------
--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `feedback` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;
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
  ADD KEY `company_id` (`company_id`);
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
  ADD UNIQUE KEY `driver_company_unique` (`driver_id`, `company_id`),
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
  ADD KEY `email` (`email`, `ip_address`, `attempt_time`);
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
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 20;
--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 10;
--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 15;
--
-- AUTO_INCREMENT for table `company_notifications`
--
ALTER TABLE `company_notifications`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 37;
--
-- AUTO_INCREMENT for table `company_payments`
--
ALTER TABLE `company_payments`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 29;
--
-- AUTO_INCREMENT for table `company_staff`
--
ALTER TABLE `company_staff`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 12;
--
-- AUTO_INCREMENT for table `complaint_responses`
--
ALTER TABLE `complaint_responses`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 15;
--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
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
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 43;
--
-- AUTO_INCREMENT for table `driver_ratings`
--
ALTER TABLE `driver_ratings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 4;
--
-- AUTO_INCREMENT for table `driver_sessions`
--
ALTER TABLE `driver_sessions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 5;
--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 138;
--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 12;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE
SET NULL;
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
  ADD CONSTRAINT `complaint_responses_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
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
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE
SET NULL;
COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;