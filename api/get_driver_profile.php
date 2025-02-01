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
    // جلب بيانات السائق
    $query = "
        SELECT 
            username,
            email,
            phone,
            age,
            about,
            profile_image,
            total_trips,
            rating,
            vehicle_type,
            vehicle_model,
            plate_number,
            completed_orders,
            cancelled_orders,
            total_earnings,
            current_status
        FROM drivers 
        WHERE id = ? 
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found']);
        exit();
    }

    // تنسيق البيانات
    $response = [
        'status' => true,
        'message' => 'Driver profile retrieved successfully',
        'data' => [
            'personal_info' => [
                'name' => $driver['username'],
                'email' => $driver['email'],
                'phone' => $driver['phone'],
                'age' => $driver['age'],
                'profile_image' => $driver['profile_image'],
                'about' => $driver['about']
            ],
            'vehicle_info' => [
                'type' => $driver['vehicle_type'],
                'model' => $driver['vehicle_model'],
                'plate_number' => $driver['plate_number']
            ],
            'statistics' => [
                'rating' => (float)$driver['rating'],
                'total_trips' => (int)$driver['total_trips'],
                'completed_orders' => (int)$driver['completed_orders'],
                'cancelled_orders' => (int)$driver['cancelled_orders'],
                'total_earnings' => (float)$driver['total_earnings']
            ],
            'current_status' => $driver['current_status']
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} 