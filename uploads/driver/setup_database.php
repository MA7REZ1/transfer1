<?php
require_once '../config.php';

try {
    // Create drivers table
    $conn->exec("CREATE TABLE IF NOT EXISTS drivers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_trips INT DEFAULT 0,
        age INT DEFAULT NULL,
        about TEXT DEFAULT NULL,
        id_number VARCHAR(20) DEFAULT NULL,
        license_number VARCHAR(20) DEFAULT NULL,
        vehicle_type VARCHAR(50) DEFAULT NULL,
        vehicle_model VARCHAR(50) DEFAULT NULL,
        vehicle_plate VARCHAR(20) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    // Create driver_company_assignments table
    $conn->exec("CREATE TABLE IF NOT EXISTS driver_company_assignments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        driver_id INT NOT NULL,
        company_id INT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES drivers(id),
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");

    // Create driver_orders table
    $conn->exec("CREATE TABLE IF NOT EXISTS driver_orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        driver_id INT NOT NULL,
        order_id INT NOT NULL,
        company_id INT NOT NULL,
        status ENUM('pending', 'accepted', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
        pickup_location TEXT NOT NULL,
        delivery_location TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES drivers(id),
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (company_id) REFERENCES companies(id)
    )");

    // Create driver_notifications table
    $conn->exec("CREATE TABLE IF NOT EXISTS driver_notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        driver_id INT NOT NULL,
        order_id INT NOT NULL,
        message TEXT NOT NULL,
        type ENUM('new_order', 'order_update', 'company_message', 'system') DEFAULT 'system',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (driver_id) REFERENCES drivers(id),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )");

    echo "All tables created successfully!";
} catch(PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
