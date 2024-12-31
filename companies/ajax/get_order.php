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
    $order_id = $_GET['id'];
    
    // التحقق من وجود الطلب وملكيته للشركة
    $stmt = $conn->prepare("
        SELECT * FROM requests 
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([$order_id, $_SESSION['company_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
        exit();
    }
    
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