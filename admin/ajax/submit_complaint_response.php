<?php
require_once '../../config.php';

// Set headers
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try both POST and JSON input
    $complaint_id = $data['complaint_id'] ?? $_POST['complaint_id'] ?? null;
    $response = $data['response'] ?? $_POST['response'] ?? null;
    $new_status = $data['status'] ?? $_POST['status'] ?? null;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate required data
if (!$complaint_id || !$response) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال الرد']);
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Verify complaint exists and get company info
    $stmt = $conn->prepare("
        SELECT id, company_id, complaint_number, status 
        FROM complaints 
        WHERE id = ?
    ");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        throw new Exception('الشكوى غير موجودة');
    }

    // Insert response
    $stmt = $conn->prepare("
        INSERT INTO complaint_responses (
            complaint_id, 
            admin_id, 
            response
        ) VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $complaint_id,
        $_SESSION['admin_id'],
        $response
    ]);

    // Update complaint status if provided
    if ($new_status && $new_status !== $complaint['status']) {
        $stmt = $conn->prepare("
            UPDATE complaints 
            SET status = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $complaint_id]);

        // Add status change to response message
        $status_labels = [
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة'
        ];
        $status_message = isset($status_labels[$new_status]) ? 
            " وتم تغيير حالة الشكوى إلى " . $status_labels[$new_status] : '';
    }

        // Create notification for company
        $stmt = $conn->prepare("
            INSERT INTO company_notifications (
                company_id,
                type,
                title,
                message,
                reference_id,
                link
            ) VALUES (?, 'complaint_response', ?, ?, ?, '#')
        ");

    $notification_message = 'تم إضافة رد جديد على الشكوى رقم ' . $complaint['complaint_number'];
    if (isset($status_message)) {
        $notification_message .= $status_message;
    }

        $stmt->execute([
            $complaint['company_id'],
            'رد جديد على الشكوى',
        $notification_message,
            $complaint_id
        ]);

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الرد بنجاح' . ($status_message ?? '')
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
    $conn->rollBack();
    }
    error_log("Error submitting complaint response: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 