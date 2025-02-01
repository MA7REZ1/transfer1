<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Driver ID is required']);
    exit;
}

$driver_id = $_GET['id'];

try {


    // Fetch driver details and total trips
    $stmt = $conn->prepare("SELECT username, email, phone, cancelled_orders, is_active, vehicle_type, COUNT(r.id) as total_trips FROM drivers d LEFT JOIN requests r ON d.id = r.driver_id WHERE d.id = ? AND r.status = 'delivered'");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch order statuses from requests
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_orders,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_orders
    FROM requests WHERE driver_id = ?");
    $stmt->execute([$driver_id]);
    $order_statuses = $stmt->fetch(PDO::FETCH_ASSOC);

 $stmt = $conn->prepare("SELECT 
    COALESCE(action, 'بدون إجراء') AS activity_type,
    COALESCE(details, 'لا توجد تفاصيل') AS activity_details,
    created_at
FROM activity_log 
WHERE driver_id = ?
ORDER BY created_at DESC");

$stmt->execute([$driver_id]);
$activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($driver) {
        echo json_encode(['success' => true, 'driver' => $driver, 'order_statuses' => $order_statuses, 'activity_logs' => $activity_logs]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Driver not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 