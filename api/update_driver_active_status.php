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

if (!isset($data['driver_id']) || !isset($data['is_active'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID and active status are required']);
    exit();
}

$driver_id = $data['driver_id'];
$is_active = (bool)$data['is_active'];

try {
    // التحقق من وجود السائق
    $checkStmt = $conn->prepare("SELECT username, is_active FROM drivers WHERE id = ?");
    $checkStmt->execute([$driver_id]);
    $driver = $checkStmt->fetch();

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found']);
        exit();
    }

    // إذا كانت الحالة الحالية هي نفس الحالة المطلوبة
    if ((bool)$driver['is_active'] === $is_active) {
        echo json_encode([
            'status' => true,
            'message' => 'Driver status is already ' . ($is_active ? 'active' : 'inactive'),
            'data' => [
                'driver_name' => $driver['username'],
                'is_active' => $is_active
            ]
        ]);
        exit();
    }

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث حالة نشاط السائق
    $updateStmt = $conn->prepare("UPDATE drivers SET is_active = ?, current_status = ? WHERE id = ?");
    $newStatus = $is_active ? 'available' : 'offline';
    $updateStmt->execute([$is_active, $newStatus, $driver_id]);

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'account_status')
    ");
    $message = $is_active ? 'تم تفعيل حسابك' : 'تم تعطيل حسابك';
    $notifyStmt->execute([$driver_id, $message]);

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, ?, ?)
    ");
    $action = $is_active ? 'account_activated' : 'account_deactivated';
    $details = "Driver account " . ($is_active ? 'activated' : 'deactivated');
    $logStmt->execute([$driver_id, $action, $details]);

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Driver status updated successfully',
        'data' => [
            'driver_name' => $driver['username'],
            'previous_status' => (bool)$driver['is_active'],
            'new_status' => $is_active,
            'current_status' => $newStatus
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