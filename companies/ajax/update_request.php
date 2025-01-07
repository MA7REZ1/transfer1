<?php
require_once '../../config.php';

// Check if staff is logged in
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$company_id = $_SESSION['company_id'];

// Get request data
$request_id = filter_var($_POST['request_id'] ?? null, FILTER_SANITIZE_NUMBER_INT);

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صالح']);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if order exists and belongs to company
    $stmt = $conn->prepare("
        SELECT status 
        FROM requests 
        WHERE id = ? AND company_id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$request_id, $company_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    if ($order['status'] !== 'pending') {
        throw new Exception('لا يمكن تعديل الطلب في حالته الحالية');
    }

    // Validate required fields
    $required_fields = [
        'customer_name',
        'customer_phone',
        'delivery_date',
        'pickup_location',
        'delivery_location',
        'items_count',
        'total_cost'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception('جميع الحقول المطلوبة يجب تعبئتها');
        }
    }

    // Update order
    $stmt = $conn->prepare("
        UPDATE requests 
        SET customer_name = ?,
            customer_phone = ?,
            delivery_date = ?,
            pickup_location = ?,
            delivery_location = ?,
            items_count = ?,
            total_cost = ?,
            additional_notes = ?,
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([
        $_POST['customer_name'],
        $_POST['customer_phone'],
        $_POST['delivery_date'],
        $_POST['pickup_location'],
        $_POST['delivery_location'],
        $_POST['items_count'],
        $_POST['total_cost'],
        $_POST['additional_notes'] ?? null,
        $request_id,
        $company_id
    ]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'تم تحديث الطلب بنجاح']);

} catch (Exception $e) {
    $conn->rollBack();
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 