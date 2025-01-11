<?php
require_once '../../config.php';

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    die(json_encode(['success' => false, 'message' => 'Invalid notification ID']));
}

try {
    // Update notification status
    $stmt = $conn->prepare("
        UPDATE company_notifications 
        SET is_read = 1 
        WHERE id = ? AND company_id = ?
    ");
    
    $result = $stmt->execute([$notification_id, $_SESSION['company_id']]);
    
    if ($result) {
        // Get remaining unread count
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM company_notifications 
            WHERE company_id = ? AND is_read = 0
        ");
        $stmt->execute([$_SESSION['company_id']]);
        $unread_count = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read',
            'unread_count' => $unread_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
    }
} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 