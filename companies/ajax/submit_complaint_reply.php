<?php
require_once '../../config.php';

// Check if company is logged in
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

// Debug: Log raw input
error_log("Raw input: " . file_get_contents('php://input'));

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Log decoded data
error_log("Decoded data: " . print_r($data, true));

// Check if using POST instead of JSON
if (empty($data)) {
    $complaint_number = $_POST['complaint_id'] ?? $_POST['complaint_number'] ?? null;
    $response = $_POST['response'] ?? null;
} else {
    $complaint_number = $data['complaint_id'] ?? $data['complaint_number'] ?? null;
    $response = $data['response'] ?? null;
}

// Debug: Log final values
error_log("Complaint number: " . $complaint_number);
error_log("Response: " . $response);

// Validate inputs
if (!$response) {
    echo json_encode([
        'success' => false, 
        'message' => 'الرجاء كتابة الرد',
        'debug' => [
            'complaint_number' => $complaint_number,
            'response' => $response,
            'data' => $data,
            'post' => $_POST
        ]
    ]);
    exit;
}

if (!$complaint_number) {
    echo json_encode([
        'success' => false, 
        'message' => 'رقم الشكوى مطلوب',
        'debug' => [
            'complaint_number' => $complaint_number,
            'response' => $response,
            'data' => $data,
            'post' => $_POST
        ]
    ]);
    exit;
}

try {
    $conn->beginTransaction();

    // Get the actual complaint ID and company name from complaint number
    $stmt = $conn->prepare("
        SELECT c.id, c.subject, comp.name as company_name
        FROM complaints c
        JOIN companies comp ON c.company_id = comp.id
        WHERE c.complaint_number = ? 
        AND c.company_id = ?
    ");
    $stmt->execute([$complaint_number, $_SESSION['company_id']]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'الشكوى غير موجودة']);
        exit;
    }

    // Insert the response
    $stmt = $conn->prepare("
        INSERT INTO complaint_responses 
        (complaint_id, company_id, response, admin_id, is_company_reply) 
        VALUES (?, ?, ?, NULL, 1)
    ");
    
    $stmt->execute([
        $complaint['id'],
        $_SESSION['company_id'],
        $response
    ]);

    // Create notification for admins
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (admin_id, message, type, link) 
        VALUES (?, ?, ?, ?)
    ");
    
    $message = "رد جديد على الشكوى #{$complaint_number} - {$complaint['company_name']}: {$complaint['subject']}";
    
    // Get all admin IDs
    $adminStmt = $conn->prepare("SELECT id FROM admins WHERE role = 'admin' OR role = 'super_admin'");
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Send notification to each admin
    foreach ($admins as $admin_id) {
        $stmt->execute([$admin_id, $message, 'complaint_response', 'complaints.php']);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الرد بنجاح',
        'status' => 'success'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error submitting complaint reply: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء إرسال الرد: ' . $e->getMessage()
    ]);
}
?> 