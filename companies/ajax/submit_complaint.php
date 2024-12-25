<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit();
}

$company_id = $_SESSION['company_id'];
$request_id = (int)$_POST['request_id'];
$subject = $_POST['subject'];
$details = $_POST['details'];

// Verify that the request belongs to the company
$check_sql = "SELECT driver_id FROM requests WHERE id = ? AND company_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->execute([$request_id, $company_id]);
$result = $check_stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
    exit();
}

$driver_id = $result['driver_id'];

$sql = "INSERT INTO complaints (company_id, request_id, driver_id, subject, details, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
$stmt = $conn->prepare($sql);
$stmt->execute([$company_id, $request_id, $driver_id, $subject, $details]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'تم إرسال الشكوى بنجاح']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إرسال الشكوى']);
} 