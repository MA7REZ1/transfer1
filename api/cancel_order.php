<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['request_id']) || !isset($data['driver_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Order ID and Driver ID are required']);
    exit();
}

$request_id = $data['request_id'];
$driver_id = $data['driver_id'];

try {
    // التحقق من أن الطلب مخصص لهذا السائق
    $checkStmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND driver_id = ?");
    $checkStmt->execute([$request_id, $driver_id]);
    $order = $checkStmt->fetch();

    if (!$order) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Order not found or not assigned to this driver']);
        exit();
    }

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث حالة الطلب إلى ملغي وإزالة تخصيص السائق
    $updateStmt = $conn->prepare("
        UPDATE requests 
        SET status = 'pending', 
            driver_id = NULL 
        WHERE id = ?
    ");
    $updateStmt->execute([$request_id]);

    // تحديث حالة السائق إلى متاح
    $updateDriverStmt = $conn->prepare("
        UPDATE drivers 
        SET current_status = 'available' 
        WHERE id = ?
    ");
    $updateDriverStmt->execute([$driver_id]);

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'order_cancelled')
    ");
    $message = "تم إلغاء الطلب رقم {$order['order_number']} وإعادته للقائمة العامة";
    $notifyStmt->execute([$driver_id, $message]);

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, 'order_cancelled', ?)
    ");
    $logStmt->execute([$driver_id, "Driver cancelled order #{$order['order_number']}"]);

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Order cancelled successfully',
        'data' => [
            'order_number' => $order['order_number'],
            'status' => 'pending'
        ]
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} 