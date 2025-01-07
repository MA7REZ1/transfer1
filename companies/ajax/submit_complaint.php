<?php
require_once '../../config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Log all request data
error_log("Complaint submission - POST data: " . print_r($_POST, true));
error_log("Complaint submission - SESSION data: " . print_r($_SESSION, true));

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    error_log("Complaint submission - Authentication failed: No company_id in session");
    echo json_encode(['success' => false, 'message' => 'يرجى تسجيل الدخول أولاً']);
    exit;
}

$company_id = $_SESSION['company_id'];

// Validate input
if (!isset($_POST['request_id']) || !isset($_POST['driver_id'])) {
    error_log("Complaint submission - Validation failed: Missing request_id or driver_id");
    echo json_encode(['success' => false, 'message' => 'بيانات الطلب غير مكتملة']);
    exit;
}

if (empty($_POST['complaint_subject'])) {
    error_log("Complaint submission - Validation failed: Missing subject");
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال موضوع الشكوى']);
    exit;
}

if (empty($_POST['complaint_description'])) {
    error_log("Complaint submission - Validation failed: Missing description");
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال وصف الشكوى']);
    exit;
}

$request_id = (int)$_POST['request_id'];
$driver_id = (int)$_POST['driver_id'];

try {
    // Check database connection
    if (!$conn) {
        error_log("Complaint submission - Database connection failed");
        throw new Exception('حدث خطأ في الاتصال بقاعدة البيانات، يرجى المحاولة مرة أخرى');
    }

    // Check if request exists and is in valid status
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM requests 
        WHERE id = ? AND driver_id = ? AND company_id = ? AND status IN ('accepted', 'in_transit', 'delivered')
    ");
    
    if (!$stmt) {
        error_log("Complaint submission - Statement preparation failed: " . $conn->error);
        throw new Exception('حدث خطأ في النظام، يرجى المحاولة مرة أخرى');
    }
    
    error_log("Complaint submission - Checking request with: request_id=$request_id, driver_id=$driver_id, company_id=$company_id");
    
    $stmt->execute([$request_id, $driver_id, $company_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        error_log("Complaint submission - Request validation failed: Request not found or invalid status");
        throw new Exception('لا يمكن تقديم شكوى على هذا الطلب - يجب أن يكون الطلب مقبول أو قيد التوصيل أو تم التوصيل');
    }

    // Check if complaint already exists for this request
    $stmt = $conn->prepare("
        SELECT id 
        FROM complaints 
        WHERE request_id = ? AND company_id = ? AND driver_id = ?
    ");
    
    if (!$stmt) {
        error_log("Complaint submission - Check duplicate statement preparation failed: " . $conn->error);
        throw new Exception('حدث خطأ في النظام، يرجى المحاولة مرة أخرى');
    }
    
    $stmt->execute([$request_id, $company_id, $driver_id]);
    $existing_complaint = $stmt->fetch();
    
    if ($existing_complaint) {
        error_log("Complaint submission - Duplicate complaint found for request_id: $request_id");
        throw new Exception('تم تقديم شكوى على هذا الطلب مسبقاً');
    }

    // Generate complaint number
    $complaint_number = 'COMP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert complaint
    $stmt = $conn->prepare("
        INSERT INTO complaints (
            complaint_number,
            company_id,
            driver_id,
            request_id,
            type,
            subject,
            description,
            priority,
            status
        ) VALUES (?, ?, ?, ?, 'driver', ?, ?, ?, 'new')
    ");
    
    if (!$stmt) {
        error_log("Complaint submission - Insert statement preparation failed: " . $conn->error);
        throw new Exception('حدث خطأ في حفظ الشكوى، يرجى المحاولة مرة أخرى');
    }
    
    $result = $stmt->execute([
        $complaint_number,
        $company_id,
        $driver_id,
        $request_id,
        $_POST['complaint_subject'],
        $_POST['complaint_description'],
        $_POST['complaint_priority'] ?? 'medium'
    ]);

    if (!$result) {
        error_log("Complaint submission - Insert failed: " . $stmt->error);
        throw new Exception('فشل في حفظ الشكوى، يرجى المحاولة مرة أخرى');
    }

    // Add notification for admin
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            admin_id,
            message,
            type,
            link
        ) SELECT 
            id,
            CONCAT('شكوى جديدة رقم: ', ?, ' من شركة رقم: ', ?),
            'complaint',
            'complaints.php'
        FROM admins 
        WHERE role = 'super_admin' OR department = 'complaints'
    ");
    
    if (!$stmt->execute([$complaint_number, $company_id])) {
        error_log("Complaint submission - Notification insert failed: " . $stmt->error);
    }

    error_log("Complaint submission - Successfully submitted complaint: " . $complaint_number);
    echo json_encode(['success' => true, 'message' => 'تم إرسال الشكوى بنجاح']);

} catch (Exception $e) {
    error_log("Complaint submission - Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 