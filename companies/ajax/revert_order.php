<?php
require_once '../../config.php';

// Check if staff is logged in
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$company_id = $_SESSION['company_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$request_id = filter_var($data['request_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صالح']);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if order exists and belongs to company
    $stmt = $conn->prepare("
        SELECT status, driver_id 
        FROM requests 
        WHERE id = ? AND company_id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$request_id, $company_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    if ($order['status'] !== 'cancelled') {
        throw new Exception('لا يمكن إرجاع الطلب في حالته الحالية');
    }

    // Update order status to pending
    $stmt = $conn->prepare("
        UPDATE requests 
        SET status = 'pending',
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$request_id, $company_id]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 