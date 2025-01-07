<?php
require_once 'config.php';
require_once 'driver_auth.php';
require_once 'functions.php';

// Check if driver is logged in
if (!isDriverLoggedIn()) {
    header('Location: driver_login.php');
    exit;
}

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    $_SESSION['error_message'] = "لم يتم تحديد رقم الطلب";
    header('Location: driver_dashboard.php');
    exit;
}

$order_id = $_POST['order_id'];
$driver_id = $_SESSION['driver_id'];

try {
    $conn->beginTransaction();

    // Get the order details first
    $stmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("الطلب غير متاح أو تم قبوله من قبل سائق آخر");
    }

    // Update the order
    $stmt = $conn->prepare("UPDATE requests 
                           SET status = 'accepted', 
                               driver_id = ?,
                               updated_at = CURRENT_TIMESTAMP 
                           WHERE id = ? AND status = 'pending'");
    
    if (!$stmt->execute([$driver_id, $order_id])) {
        throw new Exception("فشل في تحديث حالة الطلب");
    }

    // Update driver status to busy
    $stmt = $conn->prepare("UPDATE drivers SET current_status = 'busy' WHERE id = ?");
    if (!$stmt->execute([$driver_id])) {
        throw new Exception("فشل في تحديث حالة السائق");
    }

    $conn->commit();
    $_SESSION['success_message'] = "تم قبول الطلب رقم " . $order['order_number'] . " بنجاح";

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error accepting order: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: driver_dashboard.php');
exit; 