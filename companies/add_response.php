<?php
require_once '../config.php';

// Check if staff member is logged in
if (!isset($_SESSION['staff_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$order_id = $_POST['order_id'] ?? null;
$type = $_POST['type'] ?? null;
$message = $_POST['message'] ?? null;
$notify_customer = isset($_POST['notify_customer']) ? 1 : 0;
$staff_id = $_SESSION['staff_id'];

// Validate required fields
if (!$order_id || !$type || !$message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Insert response
    $stmt = $conn->prepare("
        INSERT INTO order_responses (
            order_id,
            staff_id,
            response_type,
            message,
            notify_customer,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $order_id,
        $staff_id,
        $type,
        $message,
        $notify_customer
    ]);

    // If notification is requested, send it to the customer
    if ($notify_customer) {
        // Get order and customer details
        $stmt = $conn->prepare("
            SELECT r.customer_phone, r.customer_name, r.order_number
            FROM requests r
            WHERE r.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Here you would implement the notification logic
            // For example, sending SMS or WhatsApp message
            // This is just a placeholder
            $notification_message = sprintf(
                "عزيزي %s،\nتم إضافة رد جديد على طلبك رقم #%s:\n%s",
                $order['customer_name'],
                $order['order_number'],
                $message
            );
            
            // You would implement your notification service here
            // sendNotification($order['customer_phone'], $notification_message);
        }
    }

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
} 