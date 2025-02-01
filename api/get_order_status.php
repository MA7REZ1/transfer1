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

// التحقق من وجود معرف الطلب
if (!isset($_GET['request_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Request ID is required']);
    exit();
}

$request_id = $_GET['request_id'];

try {
    // الاستعلام للحصول على حالة الطلب
    $query = "
        SELECT 
            r.id as order_id,
            r.order_number,
            r.status as order_status,
            r.driver_id,
            d.username as driver_name,
            d.phone as driver_phone
        FROM requests r
        LEFT JOIN drivers d ON r.driver_id = d.id
        WHERE r.id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([$request_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'message' => 'Order not found'
        ]);
        exit();
    }

    // تنسيق البيانات
    $response = [
        'order_id' => $order['order_id'],
        'order_number' => $order['order_number'],
        'status' => $order['order_status'],
        'driver' => null
    ];

    // إضافة معلومات السائق إذا كان مخصصاً للطلب
    if ($order['driver_id']) {
        $response['driver'] = [
            'id' => $order['driver_id'],
            'name' => $order['driver_name'],
            'phone' => $order['driver_phone']
        ];
    }

    echo json_encode([
        'status' => true,
        'message' => 'Order status retrieved successfully',
        'data' => $response
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} 