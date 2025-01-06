<?php
require_once '../../config.php';

// تحقق من تسجيل الدخول
if (!isset($_SESSION['company_email'])) {
    die(json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']));
}

// تحقق من وجود البيانات المطلوبة
if (!isset($_POST['staff_id']) || !isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['phone'])) {
    die(json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']));
}

$staff_id = $_POST['staff_id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$role = $_POST['role'] ?? 'staff';
$is_active = isset($_POST['is_active']) ? 1 : 0;
$company_id = $_SESSION['company_id'];

try {
    // تحقق من أن الموظف ينتمي للشركة
    $stmt = $conn->prepare("SELECT id FROM company_staff WHERE id = ? AND company_id = ?");
    $stmt->execute([$staff_id, $company_id]);
    if (!$stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'لا يمكنك تعديل بيانات هذا الموظف']));
    }

    // تحقق من عدم تكرار البريد الإلكتروني
    $stmt = $conn->prepare("SELECT id FROM company_staff WHERE email = ? AND id != ? AND company_id = ?");
    $stmt->execute([$email, $staff_id, $company_id]);
    if ($stmt->fetch()) {
        die(json_encode(['success' => false, 'message' => 'البريد الإلكتروني مستخدم من قبل']));
    }

    // تحديث بيانات الموظف
    $stmt = $conn->prepare("
        UPDATE company_staff 
        SET name = ?, email = ?, phone = ?, role = ?, is_active = ?
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([
        $name,
        $email,
        $phone,
        $role,
        $is_active,
        $staff_id,
        $company_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث بيانات الموظف بنجاح'
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء تحديث البيانات'
    ]);
} 