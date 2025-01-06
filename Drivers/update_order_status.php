<?php
require_once 'config.php';
require_once 'driver_auth.php';

// Check if driver is logged in
if (!isDriverLoggedIn()) {
    header('Location: driver_login.php');
    exit;
}

// Get current driver info
$driver = getCurrentDriver();
if (!$driver) {
    logoutDriver();
    header('Location: driver_login.php');
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    $_SESSION['error'] = 'بيانات غير صالحة';
    header('Location: driver_dashboard.php');
    exit;
}

$order_id = (int)$_POST['order_id'];
$new_status = $_POST['status'];

// Validate status
$valid_statuses = ['picked_up', 'on_way', 'delivered'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'حالة غير صالحة';
    header('Location: driver_dashboard.php');
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Get current order info
    $stmt = $conn->prepare("
        SELECT id, status, customer_id, company_id, order_number 
        FROM requests 
        WHERE id = ? AND driver_id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$order_id, $driver['id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('الطلب غير موجود');
    }

    // Validate status transition
    $valid_transition = false;
    switch ($order['status']) {
        case 'accepted':
            $valid_transition = ($new_status === 'picked_up');
            break;
        case 'picked_up':
            $valid_transition = ($new_status === 'on_way');
            break;
        case 'on_way':
            $valid_transition = ($new_status === 'delivered');
            break;
    }

    if (!$valid_transition) {
        throw new Exception('لا يمكن تغيير الحالة');
    }

    // Update order status
    $stmt = $conn->prepare("
        UPDATE requests 
        SET status = ?,
            {$new_status}_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $order_id]);

    // Add status history
    $status_messages = [
        'picked_up' => 'تم استلام الطلب من الشركة',
        'on_way' => 'الطلب في الطريق للتوصيل',
        'delivered' => 'تم توصيل الطلب'
    ];

    $stmt = $conn->prepare("
        INSERT INTO request_status_history 
        (request_id, status, notes, created_by, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order_id,
        $new_status,
        $status_messages[$new_status],
        $driver['id']
    ]);

    // Add notifications
    $notification_messages = [
        'picked_up' => 'تم استلام طلبك من الشركة',
        'on_way' => 'طلبك في الطريق إليك',
        'delivered' => 'تم توصيل طلبك'
    ];

    // Notify customer
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type, message, related_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order['customer_id'],
        'order_' . $new_status,
        $notification_messages[$new_status],
        $order_id
    ]);

    // Notify company
    $company_messages = [
        'picked_up' => 'تم استلام الطلب رقم #' . $order['order_number'] . ' من قبل السائق',
        'on_way' => 'الطلب رقم #' . $order['order_number'] . ' في طريقه للعميل',
        'delivered' => 'تم توصيل الطلب رقم #' . $order['order_number'] . ' بنجاح'
    ];

    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type, message, related_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order['company_id'],
        'order_' . $new_status,
        $company_messages[$new_status],
        $order_id
    ]);

    $conn->commit();
    $_SESSION['success'] = 'تم تحديث حالة الطلب بنجاح';
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: driver_dashboard.php');
exit; 