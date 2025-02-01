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

if (!isset($data['driver_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID is required']);
    exit();
}

$driver_id = $data['driver_id'];

try {
    // التحقق من وجود السائق
    $checkStmt = $conn->prepare("SELECT id FROM drivers WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$driver_id]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => false, 'message' => 'Driver not found or inactive']);
        exit();
    }

    // تجهيز البيانات القابلة للتحديث
    $updateFields = [];
    $params = [];
    
    // تحديد الحقول المسموح تحديثها
    $allowedFields = [
        'username' => 'اسم المستخدم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'age' => 'العمر',
        'about' => 'نبذة عن السائق',
        'address' => 'العنوان',
        'id_number' => 'رقم الهوية',
        'license_number' => 'رقم الرخصة',
        'vehicle_type' => 'نوع المركبة',
        'vehicle_model' => 'موديل المركبة',
        'plate_number' => 'رقم اللوحة'
    ];

    // التحقق من البيانات المرسلة وإضافتها للتحديث
    foreach ($allowedFields as $field => $arabicName) {
        if (isset($data[$field]) && !empty($data[$field])) {
            // التحقق من صحة البريد الإلكتروني
            if ($field === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Invalid email format']);
                exit();
            }
            
            // التحقق من صحة رقم الهاتف
            if ($field === 'phone' && !preg_match('/^[0-9]{10,15}$/', $data[$field])) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Invalid phone number']);
                exit();
            }
            
            // التحقق من العمر
            if ($field === 'age' && (!is_numeric($data[$field]) || $data[$field] < 18 || $data[$field] > 70)) {
                http_response_code(400);
                echo json_encode(['status' => false, 'message' => 'Invalid age (must be between 18 and 70)']);
                exit();
            }

            $updateFields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    // إذا لم يتم إرسال أي بيانات للتحديث
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'No data provided for update']);
        exit();
    }

    // إضافة معرف السائق للباراميترز
    $params[] = $driver_id;

    // بدء المعاملة
    $conn->beginTransaction();

    // تحديث بيانات السائق
    $updateQuery = "UPDATE drivers SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    
    if (!$updateStmt->execute($params)) {
        throw new Exception('Failed to update profile');
    }

    // إضافة إشعار للسائق
    $notifyStmt = $conn->prepare("
        INSERT INTO driver_notifications 
        (driver_id, message, type) 
        VALUES (?, ?, 'profile_update')
    ");
    $message = "تم تحديث بيانات حسابك بنجاح";
    if (!$notifyStmt->execute([$driver_id, $message])) {
        throw new Exception('Failed to create notification');
    }

    // تسجيل النشاط
    $logStmt = $conn->prepare("
        INSERT INTO activity_log 
        (driver_id, action, details) 
        VALUES (?, 'profile_update', ?)
    ");
    $details = "Driver updated profile information";
    if (!$logStmt->execute([$driver_id, $details])) {
        throw new Exception('Failed to log activity');
    }

    $conn->commit();

    // جلب البيانات المحدثة
    $selectStmt = $conn->prepare("
        SELECT username, email, phone, age, about, address, 
               id_number, license_number, vehicle_type, vehicle_model, plate_number,
               rating, total_trips, completed_orders, cancelled_orders, total_earnings,
               current_status, is_active
        FROM drivers 
        WHERE id = ?
    ");
    $selectStmt->execute([$driver_id]);
    $updatedProfile = $selectStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'message' => 'Profile updated successfully',
        'data' => $updatedProfile
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