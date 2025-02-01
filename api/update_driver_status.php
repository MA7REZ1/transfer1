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

if (!isset($data['driver_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID and status are required']);
    exit();
}

$driver_id = $data['driver_id'];
$new_status = $data['status'];

// التحقق من صحة الحالة
$allowed_statuses = ['available', 'busy', 'offline'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode([
        'status' => false, 
        'message' => 'Invalid status. Allowed statuses are: available, busy, offline'
    ]);
    exit();
}

try {
    // التحقق من وجود السائق
    $checkStmt = $conn->prepare("SELECT username, current_status FROM drivers WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$driver_id]);
    $driver = $checkStmt->fetch();

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found or inactive']);
        exit();
    }

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث حالة السائق
    $updateStmt = $conn->prepare("UPDATE drivers SET current_status = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $driver_id]);

    // تحديد رسالة الإشعار بناءً على الحالة الجديدة
    $status_messages = [
        'available' => 'متاح للطلبات',
        'busy' => 'مشغول',
        'offline' => 'غير متصل'
    ];

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'status_update')
    ");
    $message = "تم تحديث حالتك إلى: " . $status_messages[$new_status];
    $notifyStmt->execute([$driver_id, $message]);

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, 'status_update', ?)
    ");
    $logDetails = "Driver status updated from {$driver['current_status']} to {$new_status}";
    $logStmt->execute([$driver_id, $logDetails]);

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Driver status updated successfully',
        'data' => [
            'driver_name' => $driver['username'],
            'previous_status' => $driver['current_status'],
            'new_status' => $new_status
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