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

if (!isset($data['request_id']) || !isset($data['driver_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Order ID, Driver ID and Status are required']);
    exit();
}

$request_id = $data['request_id'];
$driver_id = $data['driver_id'];
$new_status = $data['status'];

// التحقق من صحة الحالة
$allowed_statuses = ['accepted', 'in_transit', 'delivered'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid status']);
    exit();
}

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

    // التحقق من تسلسل الحالة
    $current_status = $order['status'];
    $valid_sequence = [
        'pending' => ['accepted'],
        'accepted' => ['in_transit'],
        'in_transit' => ['delivered']
    ];

    if (!isset($valid_sequence[$current_status]) || !in_array($new_status, $valid_sequence[$current_status])) {
        http_response_code(400);
        echo json_encode([
            'status' => false, 
            'message' => 'Invalid status sequence. Current status: ' . $current_status . '. Cannot update to: ' . $new_status
        ]);
        exit();
    }

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث حالة الطلب
    $updateStmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $updateStmt->execute([$new_status, $request_id]);

    // إذا تم التوصيل، تحديث حالة السائق إلى متاح
   if ($new_status === 'delivered') {
    // تحديث حالة السائق إلى متاح
    $updateDriverStmt = $conn->prepare("UPDATE drivers SET current_status = 'available' WHERE id = ?");
    $updateDriverStmt->execute([$driver_id]);

    // إضافة تكلفة الطلب إلى إجمالي أرباح السائق
    if (isset($order['total_cost']) && is_numeric($order['total_cost'])) {
        $total_cost = $order['total_cost'];
        $updateEarningsStmt = $conn->prepare("UPDATE drivers SET total_earnings = total_earnings + ? WHERE id = ?");
        $updateEarningsStmt->execute([$total_cost, $driver_id]);
    } else {
        // يمكنك تسجيل خطأ أو إدارة الحالة حيث لا توجد تكلفة صالحة
        throw new PDOException("Invalid total cost for the order");
    }
}

    // تحديد نوع الإشعار والرسالة
    $notification_type = '';
    $notification_message = '';
    switch ($new_status) {
        case 'accepted':
            $notification_type = 'order_accepted';
            $notification_message = "تم قبول الطلب رقم {$order['order_number']} بنجاح";
            break;
        case 'in_transit':
            $notification_type = 'in_transit';
            $notification_message = "تم بدء توصيل الطلب رقم {$order['order_number']}";
            break;
        case 'delivered':
            $notification_type = 'delivered';
            $notification_message = "تم تسليم الطلب رقم {$order['order_number']}";
            break;
    }

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, ?)
    ");
    $notifyStmt->execute([$driver_id, $notification_message, $notification_type]);

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, ?, ?)
    ");
    $logStmt->execute([$driver_id, $notification_type, $notification_message]);

    $conn->commit();

    echo json_encode([
        'status' => true,
        'message' => 'Order status updated successfully',
        'data' => [
            'order_number' => $order['order_number'],
            'status' => $new_status
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