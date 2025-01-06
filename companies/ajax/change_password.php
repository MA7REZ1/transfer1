<?php
require_once '../../config.php';

header('Content-Type: application/json');

// تأكد من تسجيل دخول الموظف
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['current_password']) || !isset($_POST['new_password']) || !isset($_POST['confirm_password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit();
}

$staff_id = $_SESSION['staff_id'];
$current_password = trim($_POST['current_password']);
$new_password = trim($_POST['new_password']);
$confirm_password = trim($_POST['confirm_password']);

// التحقق من عدم وجود مسافات في كلمة المرور
if (strpos($new_password, ' ') !== false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'لا يمكن أن تحتوي كلمة المرور على مسافات']);
    exit();
}

// تحقق من تطابق كلمة المرور الجديدة
if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'كلمة المرور الجديدة غير متطابقة']);
    exit();
}

// التحقق من طول كلمة المرور
if (strlen($new_password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل']);
    exit();
}

// التحقق من قوة كلمة المرور
$password_strength = 0;
if (preg_match('/[A-Z]/', $new_password)) $password_strength++;
if (preg_match('/[a-z]/', $new_password)) $password_strength++;
if (preg_match('/[0-9]/', $new_password)) $password_strength++;
if (preg_match('/[^A-Za-z0-9]/', $new_password)) $password_strength++;

if ($password_strength < 3) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'كلمة المرور ضعيفة. يجب أن تحتوي على مزيج من الأحرف الكبيرة والصغيرة والأرقام والرموز'
    ]);
    exit();
}

try {
    // جلب كلمة المرور الحالية للموظف
    $stmt = $conn->prepare("SELECT password, name FROM company_staff WHERE id = ? AND active = 1");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على الموظف أو تم تعطيل الحساب']);
        exit();
    }

    // تحقق من صحة كلمة المرور الحالية
    if (!password_verify($current_password, $staff['password'])) {
        // تسجيل محاولة فاشلة لتغيير كلمة المرور
        $stmt = $conn->prepare("
            INSERT INTO password_change_attempts (staff_id, status, ip_address) 
            VALUES (?, 'failed', ?)
        ");
        $stmt->execute([$staff_id, $_SERVER['REMOTE_ADDR']]);

        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة']);
        exit();
    }

    // التأكد من أن كلمة المرور الجديدة مختلفة عن الحالية
    if (password_verify($new_password, $staff['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لا يمكن استخدام كلمة المرور الحالية. يرجى اختيار كلمة مرور جديدة']);
        exit();
    }

    // تحديث كلمة المرور
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT, ['cost' => 12]);
    $stmt = $conn->prepare("UPDATE company_staff SET password = ?, password_changed_at = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $staff_id]);

    // تسجيل عملية تغيير كلمة المرور الناجحة
    $stmt = $conn->prepare("
        INSERT INTO password_change_attempts (staff_id, status, ip_address) 
        VALUES (?, 'success', ?)
    ");
    $stmt->execute([$staff_id, $_SERVER['REMOTE_ADDR']]);

    // إرسال بريد إلكتروني للإشعار بتغيير كلمة المرور
    // TODO: Implement email notification

    echo json_encode([
        'success' => true, 
        'message' => 'تم تغيير كلمة المرور بنجاح',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'staff_name' => $staff['name']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تغيير كلمة المرور']);
} 