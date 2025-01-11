<?php
// Define BASEPATH constant
define('BASEPATH', true);

// Get the absolute path to the root directory
$root_path = dirname(dirname(dirname(__FILE__)));

require_once $root_path . '/../../config.php';


// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if there are any unread notifications
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM company_notifications 
        WHERE company_id = ? AND is_read = 0
        FOR UPDATE
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $unread_count = $stmt->fetchColumn();

    if ($unread_count === 0) {
        $conn->rollBack();
        echo json_encode(['success' => true, 'message' => 'جميع الإشعارات مقروءة بالفعل']);
        exit;
    }

    // Mark all notifications as read for this company
    $stmt = $conn->prepare("
        UPDATE company_notifications 
        SET is_read = 1,
            updated_at = CURRENT_TIMESTAMP
        WHERE company_id = ? AND is_read = 0
    ");
    
    if (!$stmt->execute([$_SESSION['company_id']])) {
        throw new Exception('فشل في تحديث حالة الإشعارات');
    }

    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error marking all notifications as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الإشعارات']);
} 