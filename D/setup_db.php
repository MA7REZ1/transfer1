<?php
require_once 'config.php';

try {
    // Create or update drivers table
    $conn->exec("CREATE TABLE IF NOT EXISTS drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        current_status ENUM('available', 'busy', 'offline') DEFAULT 'offline',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (current_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create or update login_attempts table
    $conn->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email, ip_address, attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create or update activity_log table
    $conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT,
        action VARCHAR(50) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (driver_id),
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "Database structure has been set up successfully.\n";

    // Check if we need to create a test driver account
    $stmt = $conn->query("SELECT COUNT(*) as count FROM drivers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Create a test driver account
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO drivers (username, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test Driver', 'test@driver.com', $password, '0500000000']);
        echo "Test driver account created:\nEmail: test@driver.com\nPassword: 123456\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 