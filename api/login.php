<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'config/database.php';
require_once 'config/auth.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// استلام البيانات
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من وجود البيانات المطلوبة
if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Email and password are required']);
    exit();
}

$email = $data['email'];
$password = $data['password'];

try {
    // البحث عن السائق في قاعدة البيانات
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $driver = $stmt->fetch($conn::FETCH_ASSOC);

    if ($driver && password_verify($password, $driver['password'])) {
        // تحديث آخر تسجيل دخول
        $updateStmt = $conn->prepare("UPDATE drivers SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$driver['id']]);

        // تسجيل نشاط تسجيل الدخول
        $logStmt = $conn->prepare("INSERT INTO activity_log (driver_id, action, details) VALUES (?, 'login_success', 'Driver logged in successfully')");
        $logStmt->execute([$driver['id']]);

        // إنشاء توكن للمستخدم
        $token = Auth::generateToken($driver['id']);

        // إزالة كلمة المرور من البيانات المُرجعة
        unset($driver['password']);

        echo json_encode([
            'status' => true,
            'message' => 'Login successful',
            'data' => $driver,
            'token' => $token
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => false,
            'message' => 'Invalid email or password'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred'
    ]);
} 