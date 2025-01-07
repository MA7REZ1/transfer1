<?php
require_once 'config.php';

try {
    // Check drivers table
    $stmt = $conn->query("DESCRIBE drivers");
    echo "Drivers table structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode($row) . "\n";
    }
    
    // Check login_attempts table
    $stmt = $conn->query("DESCRIBE login_attempts");
    echo "\nLogin attempts table structure:\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode($row) . "\n";
    }
    
    // Check if any drivers exist
    $stmt = $conn->query("SELECT COUNT(*) as count FROM drivers");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nNumber of drivers in database: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 