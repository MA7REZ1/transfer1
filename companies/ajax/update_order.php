<?php
require_once '../../config.php';

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit();
}

try {
    $company_id = $_SESSION['company_id'];
    $order_id = $_POST['order_id'];

    // Check if order exists and belongs to company
    $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND company_id = ? AND status = 'pending'");
    $stmt->execute([$order_id, $_SESSION['company_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود أو لا يمكن تعديله']);
        exit();
    }

    // Update order
    $stmt = $conn->prepare("
        UPDATE requests 
        SET 
            order_type = ?,
            customer_name = ?,
            customer_phone = ?,
            delivery_date = ?,
            pickup_location = ?,
            delivery_location = ?,
            items_count = ?,
            total_cost = ?,
            payment_method = ?,
            is_fragile = ?,
            additional_notes = ?,
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");

    $stmt->execute([
        $order_type,
        $customer_name,
        $customer_phone,
        $delivery_date,
        $pickup_location,
        $delivery_location,
        $items_count,
        $total_cost,
        $payment_method,
        $is_fragile,
        $additional_notes,
        $order_id,
        $_SESSION['company_id']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث الطلب بنجاح'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}