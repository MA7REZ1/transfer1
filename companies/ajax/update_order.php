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
    $order_id = $_POST['order_id'] ?? null;

    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'معرف الطلب مطلوب']);
        exit();
    }

    // Check if order exists and belongs to company
    $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND company_id = ? AND status = 'pending'");
    $stmt->execute([$order_id, $_SESSION['company_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود أو لا يمكن تعديله']);
        exit();
    }

    // Validate required fields with specific messages
    $validation_errors = [];
    $required_fields = [
        'order_type' => 'نوع الطلب',
        'customer_name' => 'اسم العميل',
        'customer_phone' => 'رقم هاتف العميل',
        'delivery_date' => 'تاريخ التوصيل',
        'delivery_time' => 'وقت التوصيل',
        'pickup_location' => 'موقع الاستلام',
        'delivery_location' => 'موقع التوصيل',
        'items_count' => 'عدد القطع',
        'payment_method' => 'طريقة الدفع'
    ];

    foreach ($required_fields as $field => $label) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $validation_errors[] = "حقل {$label} مطلوب";
        }
    }

    // Validate phone number format
    if (isset($_POST['customer_phone']) && $_POST['customer_phone'] !== '') {
        if (!preg_match('/^[0-9]{10}$/', $_POST['customer_phone'])) {
            $validation_errors[] = 'رقم الهاتف يجب أن يتكون من 10 أرقام';
        }
    }

    // Validate delivery date is not in the past
    if (isset($_POST['delivery_date']) && isset($_POST['delivery_time']) && 
        $_POST['delivery_date'] !== '' && $_POST['delivery_time'] !== '') {
        $delivery_datetime = date('Y-m-d H:i:s', strtotime($_POST['delivery_date'] . ' ' . $_POST['delivery_time']));
        if (strtotime($delivery_datetime) < time()) {
            $validation_errors[] = 'لا يمكن تحديد تاريخ ووقت توصيل في الماضي';
        }
    }

    // Validate items count
    if (isset($_POST['items_count']) && $_POST['items_count'] !== '') {
        if (!is_numeric($_POST['items_count']) || $_POST['items_count'] < 1) {
            $validation_errors[] = 'عدد القطع يجب أن يكون رقماً موجباً';
        }
    }

    // Validate total cost - allow zero values
    $total_cost = isset($_POST['total_cost']) ? trim($_POST['total_cost']) : '';
    if ($total_cost === '') {
        $validation_errors[] = 'التكلفة الإجمالية مطلوبة';
    } else if (!is_numeric($total_cost)) {
        $validation_errors[] = 'التكلفة الإجمالية يجب أن تكون رقماً';
    } else if (floatval($total_cost) < 0) {
        $validation_errors[] = 'التكلفة الإجمالية يجب أن تكون صفر أو أكبر';
    }

    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'يوجد أخطاء في النموذج',
            'errors' => $validation_errors
        ]);
        exit();
    }

    // Combine date and time
    $delivery_datetime = date('Y-m-d H:i:s', strtotime($_POST['delivery_date'] . ' ' . $_POST['delivery_time']));

    try {
        // Start transaction
        $conn->beginTransaction();

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
            $_POST['pickup_location_link'] ?? null,
            $_POST['delivery_location'],
            $_POST['delivery_location_link'] ?? null,
            $_POST['items_count'],
            $total_cost,
            $_POST['payment_method'],
            isset($_POST['is_fragile']) ? 1 : 0,
            $_POST['additional_notes'] ?? null,
            $order_id,
            $company_id
        ]);

        if ($result) {
            // Get updated order data
            $stmt = $conn->prepare("
                SELECT r.*, d.username as driver_name, d.phone as driver_phone
                FROM requests r
                LEFT JOIN drivers d ON r.driver_id = d.id
                WHERE r.id = ?
            ");
            $stmt->execute([$order_id]);
            $updated_order = $stmt->fetch(PDO::FETCH_ASSOC);

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث الطلب بنجاح',
                'order' => $updated_order
            ]);
        } else {
            throw new Exception('فشل في تحديث الطلب');
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log("Order update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء تحديث الطلب: ' . $e->getMessage()
    ]);
}