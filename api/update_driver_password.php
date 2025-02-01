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

if (!isset($data['driver_id']) || !isset($data['old_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID, old password and new password are required']);
    exit();
}

$driver_id = $data['driver_id'];
$old_password = $data['old_password'];
$new_password = $data['new_password'];

// التحقق من طول كلمة المرور الجديدة
if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'New password must be at least 6 characters long']);
    exit();
}

try {
    // التحقق من وجود السائق وكلمة المرور القديمة
    $checkStmt = $conn->prepare("SELECT id, password FROM drivers WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$driver_id]);
    $driver = $checkStmt->fetch();
    
    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found or inactive']);
        exit();
    }

    // التحقق من صحة كلمة المرور القديمة
    if (!password_verify($old_password, $driver['password'])) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Old password is incorrect']);
        exit();
    }

    // تشفير كلمة المرور الجديدة
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث كلمة المرور
    $updateStmt = $conn->prepare("UPDATE drivers SET password = ? WHERE id = ?");
    if (!$updateStmt->execute([$hashed_password, $driver_id])) {
        throw new Exception('Failed to update password');
    }

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'password_update')
    ");
    $message = "تم تحديث كلمة المرور الخاصة بك بنجاح";
    if (!$notifyStmt->execute([$driver_id, $message])) {
        throw new Exception('Failed to create notification');
    }

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, 'password_update', ?)
    ");
    $details = "Driver updated password";
    if (!$logStmt->execute([$driver_id, $details])) {
        throw new Exception('Failed to log activity');
    }

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Password updated successfully'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Error occurred',
        'error' => $e->getMessage()
    ]);
} 