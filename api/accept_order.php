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

if (!isset($data['driver_id']) || !isset($data['request_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID and Request ID are required']);
    exit();
}

$driver_id = $data['driver_id'];
$request_id = $data['request_id'];

try {
    // التحقق من وجود السائق ونشاطه
    $checkDriverStmt = $conn->prepare("SELECT id, current_status FROM drivers WHERE id = ? AND is_active = 1");
    $checkDriverStmt->execute([$driver_id]);
    $driver = $checkDriverStmt->fetch();

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found or inactive']);
        exit();
    }

    // التحقق من أن السائق متاح
    // if ($driver['current_status'] !== 'available') {
    //     http_response_code(400);
    //     echo json_encode(['status' => false, 'message' => 'Driver is not available']);
    //     exit();
    // }

    // التحقق من وجود الطلب وحالته
    $checkRequestStmt = $conn->prepare("SELECT id, status FROM requests WHERE id = ?");
    $checkRequestStmt->execute([$request_id]);
    $request = $checkRequestStmt->fetch();

    if (!$request) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Request not found']);
        exit();
    }

    if ($request['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Request is not pending']);
        exit();
    }

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث حالة الطلب وربطه بالسائق
    $updateRequestStmt = $conn->prepare("
        UPDATE requests 
        SET driver_id = ?, status = 'accepted' 
        WHERE id = ? AND status = 'pending'
    ");
    
    if (!$updateRequestStmt->execute([$driver_id, $request_id])) {
        throw new Exception('Failed to update request status');
    }

    // تحديث حالة السائق إلى مشغول
    $updateDriverStmt = $conn->prepare("UPDATE drivers SET current_status = 'busy' WHERE id = ?");
    if (!$updateDriverStmt->execute([$driver_id])) {
        throw new Exception('Failed to update driver status');
    }

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'order_accepted')
    ");
    $message = "تم قبول الطلب رقم " . $request_id . " بنجاح";
    if (!$notifyStmt->execute([$driver_id, $message])) {
        throw new Exception('Failed to create notification');
    }

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, 'accept_order', ?)
    ");
    $details = "Driver accepted request #" . $request_id;
    if (!$logStmt->execute([$driver_id, $details])) {
        throw new Exception('Failed to log activity');
    }

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Request accepted successfully',
        'data' => [
            'request_id' => $request_id,
            'driver_id' => $driver_id,
            'status' => 'accepted'
        ]
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