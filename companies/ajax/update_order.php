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

    // Validate required fields
    $required_fields = [
        'order_type',
        'customer_name',
        'customer_phone',
        'delivery_date',
        'delivery_time',
        'pickup_location',
        'delivery_location',
        'items_count',
        'total_cost',
        'payment_method'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit();
        }
    }

    // Combine date and time
    $delivery_datetime = date('Y-m-d H:i:s', strtotime($_POST['delivery_date'] . ' ' . $_POST['delivery_time']));

    // Update order
    $stmt = $conn->prepare("
        UPDATE requests 
        SET 
            order_type = ?,
            customer_name = ?,
            customer_phone = ?,
            delivery_date = ?,
            pickup_location = ?,
            pickup_location_link = ?,
            delivery_location = ?,
            delivery_location_link = ?,
            items_count = ?,
            total_cost = ?,
            payment_method = ?,
            is_fragile = ?,
            additional_notes = ?,
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");

    $result = $stmt->execute([
        $_POST['order_type'],
        $_POST['customer_name'],
        $_POST['customer_phone'],
        $delivery_datetime,
        $_POST['pickup_location'],
        $_POST['pickup_location_link'],
        $_POST['delivery_location'],
        $_POST['delivery_location_link'],
        $_POST['items_count'],
        $_POST['total_cost'],
        $_POST['payment_method'],
        isset($_POST['is_fragile']) ? 1 : 0,
        $_POST['additional_notes'] ?? null,
        $order_id,
        $_SESSION['company_id']
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الطلب بنجاح'
        ]);
    } else {
        throw new Exception('فشل في تحديث الطلب');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}