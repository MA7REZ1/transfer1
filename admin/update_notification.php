<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id']) || !isset($input['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$notification_id = $input['notification_id'];
$action = $input['action'];
$admin_id = $_SESSION['admin_id'];

try {
    if ($action === 'mark_read') {
        // Update notification status
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND admin_id = ?");
        $stmt->execute([$notification_id, $admin_id]);

        // Get updated unread count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE admin_id = ? AND is_read = 0");
        $stmt->execute([$admin_id]);
        $unread_count = $stmt->fetchColumn();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>