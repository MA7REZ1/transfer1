-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;
-- Create admins table first (needed for foreign keys)
CREATE TABLE IF NOT EXISTS `admins` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create companies table (needed for foreign keys)
CREATE TABLE IF NOT EXISTS `companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `phone` varchar(20) NOT NULL,
    `address` text,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Create company_staff table
CREATE TABLE IF NOT EXISTS `company_staff` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `role` enum('order_manager', 'staff') DEFAULT 'staff',
    `is_active` tinyint(1) DEFAULT 1,
    `last_login` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `company_staff_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Drop old tables if they exist (in correct order)
DROP TABLE IF EXISTS `driver_earnings`;
DROP TABLE IF EXISTS `driver_ratings`;
DROP TABLE IF EXISTS `driver_company_assignments`;
DROP TABLE IF EXISTS `driver_documents`;
DROP TABLE IF EXISTS `driver_locations`;
DROP TABLE IF EXISTS `driver_notifications`;
DROP TABLE IF EXISTS `driver_sessions`;
DROP TABLE IF EXISTS `complaint_logs`;
DROP TABLE IF EXISTS `payment_logs`;
DROP TABLE IF EXISTS `complaints`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `users`;
-- Create drivers table with all required fields
CREATE TABLE IF NOT EXISTS `drivers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
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
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `type` varchar(50) DEFAULT NULL,
    `link` varchar(255) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `admin_id` (`admin_id`),
    CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create requests table
CREATE TABLE IF NOT EXISTS `requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_number` varchar(20) NOT NULL,
    `company_id` int(11) NOT NULL,
    `driver_id` int(11) DEFAULT NULL,
    `customer_name` varchar(100) NOT NULL,
    `customer_phone` varchar(20) NOT NULL,
    `order_type` enum('delivery', 'transport') NOT NULL,
    `delivery_date` date NOT NULL,
    `pickup_location` text NOT NULL,
    `delivery_location` text NOT NULL,
    `items_count` int(11) NOT NULL,
    `total_cost` decimal(10, 2) NOT NULL,
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
    `additional_notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `pickup_location_link` VARCHAR(500) DEFAULT NULL,
    `delivery_location_link` VARCHAR(500) DEFAULT NULL,
    `pickup_lat` DECIMAL(10, 8) DEFAULT NULL,
    `pickup_lng` DECIMAL(11, 8) DEFAULT NULL,
    `delivery_lat` DECIMAL(10, 8) DEFAULT NULL,
    `delivery_lng` DECIMAL(11, 8) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`order_number`),
    KEY `company_id` (`company_id`),
    KEY `driver_id` (`driver_id`),
    CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_ratings table
CREATE TABLE IF NOT EXISTS `driver_ratings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `request_id` int(11) NOT NULL,
    `driver_id` int(11) NOT NULL,
    `company_id` int(11) NOT NULL,
    `rating` int(11) NOT NULL CHECK (
        `rating` BETWEEN 1 AND 5
    ),
    `comment` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `request_id` (`request_id`),
    KEY `driver_id` (`driver_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `driver_ratings_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
    CONSTRAINT `driver_ratings_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
    CONSTRAINT `driver_ratings_ibfk_3` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_documents table
CREATE TABLE IF NOT EXISTS `driver_documents` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
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
    `notes` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `driver_id` (`driver_id`),
    CONSTRAINT `driver_documents_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_earnings table
CREATE TABLE IF NOT EXISTS `driver_earnings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `driver_id` int(11) NOT NULL,
    `request_id` int(11) NOT NULL,
    `amount` decimal(10, 2) NOT NULL,
    `type` enum('delivery_fee', 'tip', 'bonus') NOT NULL,
    `status` enum('pending', 'paid', 'cancelled') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `driver_id` (`driver_id`),
    KEY `request_id` (`request_id`),
    CONSTRAINT `driver_earnings_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
    CONSTRAINT `driver_earnings_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_locations table
CREATE TABLE IF NOT EXISTS `driver_locations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `driver_id` int(11) NOT NULL,
    `latitude` decimal(10, 8) NOT NULL,
    `longitude` decimal(11, 8) NOT NULL,
    `accuracy` decimal(10, 2) DEFAULT NULL,
    `speed` decimal(10, 2) DEFAULT NULL,
    `heading` decimal(10, 2) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `driver_id` (`driver_id`),
    CONSTRAINT `driver_locations_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_sessions table
CREATE TABLE IF NOT EXISTS `driver_sessions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `driver_id` int(11) NOT NULL,
    `session_token` varchar(255) NOT NULL,
    `device_info` text,
    `ip_address` varchar(45) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `driver_id` (`driver_id`),
    CONSTRAINT `driver_sessions_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create driver_company_assignments table
CREATE TABLE IF NOT EXISTS `driver_company_assignments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `driver_id` int(11) NOT NULL,
    `company_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `driver_company_unique` (`driver_id`, `company_id`),
    KEY `driver_id` (`driver_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `driver_company_assignments_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
    CONSTRAINT `driver_company_assignments_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Create complaints table
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
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
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `complaint_number` (`complaint_number`),
    KEY `company_id` (`company_id`),
    KEY `driver_id` (`driver_id`),
    KEY `request_id` (`request_id`),
    KEY `assigned_to` (`assigned_to`),
    CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
    CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`),
    CONSTRAINT `complaints_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`),
    CONSTRAINT `complaints_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Insert default admin user
INSERT INTO `admins` (`username`, `email`, `password`, `is_active`)
VALUES (
        'admin',
        'admin@example.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        1
    );
-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
-- تحديث جدول الطلبات لإضافة حقل وقت التوصيل
ALTER TABLE requests
MODIFY COLUMN delivery_date DATETIME NOT NULL;
-- تحديث البيانات القديمة
UPDATE requests
SET delivery_date = CONCAT(DATE(delivery_date), ' 00:00:00')
WHERE TIME(delivery_date) = '00:00:00';
-- إضافة أعمدة روابط المواقع
ALTER TABLE requests
ADD COLUMN pickup_location_link VARCHAR(500) NULL
AFTER pickup_location,
    ADD COLUMN delivery_location_link VARCHAR(500) NULL
AFTER delivery_location;