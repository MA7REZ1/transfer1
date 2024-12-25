-- Create drivers table with additional fields for company integration
CREATE TABLE IF NOT EXISTS drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    id_number VARCHAR(20) DEFAULT NULL,
    license_number VARCHAR(20) DEFAULT NULL,
    vehicle_type VARCHAR(50) DEFAULT NULL,
    vehicle_model VARCHAR(50) DEFAULT NULL,
    vehicle_plate VARCHAR(20) DEFAULT NULL,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_trips INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Create driver_company_assignments table
CREATE TABLE IF NOT EXISTS driver_company_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    company_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
-- Create driver_orders table
CREATE TABLE IF NOT EXISTS driver_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    order_id INT NOT NULL,
    company_id INT NOT NULL,
    status ENUM(
        'pending',
        'accepted',
        'in_transit',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',
    pickup_location TEXT NOT NULL,
    delivery_location TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
-- Create driver_notifications table
CREATE TABLE IF NOT EXISTS driver_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    order_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM(
        'new_order',
        'order_update',
        'company_message',
        'system'
    ) DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);