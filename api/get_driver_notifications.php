<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// التحقق من وجود معرف السائق
if (!isset($_GET['driver_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID is required']);
    exit();
}

$driver_id = $_GET['driver_id'];

try {
    // التحقق من وجود السائق
    $checkStmt = $conn->prepare("SELECT id FROM drivers WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$driver_id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found or inactive']);
        exit();
    }

    // جلب الإشعارات
    $stmt = $conn->prepare("
        SELECT 
            id,
            message,
            type,
            is_read,
            created_at
        FROM driver_notifications 
        WHERE driver_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$driver_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تحديث حالة الإشعارات إلى مقروءة
    $updateStmt = $conn->prepare("
        UPDATE driver_notifications 
        SET is_read = 1 
        WHERE driver_id = ? AND is_read = 0
    ");
    $updateStmt->execute([$driver_id]);

    // عدد الإشعارات غير المقروءة
    $unreadStmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM driver_notifications 
        WHERE driver_id = ? AND is_read = 0
    ");
    $unreadStmt->execute([$driver_id]);
    $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

    echo json_encode([
        'status' => true,
        'message' => 'Notifications retrieved successfully',
        'data' => [
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount,
            'total_count' => count($notifications)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
}
