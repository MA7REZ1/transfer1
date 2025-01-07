<?php
require_once '../../config.php';

if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

// Get POST data
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
    exit();
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'كلمة المرور الجديدة غير متطابقة']);
    exit();
}

if (strlen($new_password) < 8) {
    echo json_encode(['success' => false, 'message' => 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل']);
    exit();
}

try {
    // Get current staff member
    $stmt = $conn->prepare("SELECT password FROM company_staff WHERE id = ? AND company_id = ?");
    $stmt->execute([$_SESSION['staff_id'], $_SESSION['company_id']]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على المستخدم']);
        exit();
    }

    // Verify current password
    if (!password_verify($current_password, $staff['password'])) {
        echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة']);
        exit();
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE company_staff SET password = ? WHERE id = ? AND company_id = ?");
    $stmt->execute([$hashed_password, $_SESSION['staff_id'], $_SESSION['company_id']]);

    echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح']);

} catch (PDOException $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث كلمة المرور']);
} 