<?php
require_once '../../config.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers
header('Content-Type: application/json; charset=utf-8');

// Log the request
error_log("Staff update request received: " . print_r($_POST, true));

// Check if the request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Check if user is logged in
if (!isset($_SESSION['company_email']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'يرجى تسجيل الدخول']));
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF validation failed. Session token: " . ($_SESSION['csrf_token'] ?? 'not set') . ", Post token: " . ($_POST['csrf_token'] ?? 'not set'));
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'رمز الحماية غير صالح']));
}

try {
    // Validate required fields
    $required_fields = ['staff_id', 'name', 'email', 'phone', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("الحقل {$field} مطلوب");
        }
    }

    // Sanitize and validate input
    $staff_id = filter_var($_POST['staff_id'], FILTER_VALIDATE_INT);
    $name = trim(htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8'));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim(htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8'));
    $role = in_array($_POST['role'], ['staff', 'order_manager']) ? $_POST['role'] : 'staff';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $company_id = $_SESSION['company_id'];

    // Validate input
    if (!$staff_id) {
        throw new Exception('معرف الموظف غير صالح');
    }

    if (mb_strlen($name) < 3) {
        throw new Exception('يجب أن يكون الاسم 3 أحرف على الأقل');
    }

    if (!$email) {
        throw new Exception('البريد الإلكتروني غير صالح');
    }

    if (strlen($phone) < 10) {
        throw new Exception('رقم الهاتف غير صالح');
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Check if staff exists and belongs to the company
        $stmt = $conn->prepare("SELECT id FROM company_staff WHERE id = ? AND company_id = ?");
        $stmt->execute([$staff_id, $company_id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('لم يتم العثور على الموظف');
        }

        // Check if email already exists for other staff members
        $stmt = $conn->prepare("SELECT id FROM company_staff WHERE email = ? AND id != ? AND company_id = ?");
        $stmt->execute([$email, $staff_id, $company_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('البريد الإلكتروني مستخدم بالفعل');
        }

        // Update staff member
        $stmt = $conn->prepare("
            UPDATE company_staff 
            SET name = ?, 
                email = ?, 
                phone = ?, 
                role = ?, 
                is_active = ?
            WHERE id = ? AND company_id = ?
        ");
        
        $result = $stmt->execute([
            $name,
            $email,
            $phone,
            $role,
            $is_active,
            $staff_id,
            $company_id
        ]);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Database error: " . json_encode($errorInfo));
            throw new Exception('فشل في تحديث بيانات الموظف: ' . $errorInfo[2]);
        }

        if ($stmt->rowCount() === 0) {
            throw new Exception('لم يتم إجراء أي تغييرات');
        }

        try {
            // Try to log the activity
            $log_stmt = $conn->prepare("
                INSERT INTO activity_log (user_id, action_type, action_details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $log_details = "تم تحديث بيانات الموظف: " . $name;
            $log_stmt->execute([$company_id, 'update_staff', $log_details]);
        } catch (PDOException $e) {
            // If logging fails, just log the error but don't stop the process
            error_log("Failed to log activity: " . $e->getMessage());
        }

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث بيانات الموظف بنجاح'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Staff update error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في قاعدة البيانات'
    ]);
} 