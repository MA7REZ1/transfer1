<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM requests 
        WHERE status = 'pending' 
        AND driver_id IS NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);
} catch (PDOException $e) {
    error_log("Error checking pending orders: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ أثناء التحقق من الطلبات المعلقة'
    ]);
}
?> 