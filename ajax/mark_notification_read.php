<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

try {
    // Mark notification as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND admin_id = ?");
    $stmt->execute([$notification_id, $_SESSION['admin_id']]);
    
    // Get updated unread count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE admin_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['admin_id']]);
    $unread_count = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
