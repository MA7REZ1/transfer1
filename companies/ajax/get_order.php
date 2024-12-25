<?php
require_once '../../config.php';

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف الطلب مطلوب']);
    exit();
}

try {
    $company_id = $_SESSION['company_id'];
    $order_id = $_GET['id'];

    // Get order details
    $stmt = $conn->prepare("
        SELECT r.*, d.username as driver_name, d.phone as driver_phone
        FROM requests r
        LEFT JOIN drivers d ON r.driver_id = d.id
        WHERE r.id = ? AND r.company_id = ?
    ");
    $stmt->execute([$order_id, $company_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على الطلب']);
        exit();
    }

    // Return order data
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}