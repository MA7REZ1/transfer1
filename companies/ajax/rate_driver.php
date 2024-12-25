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
$driver_id = (int)$_POST['driver_id'];
$rating = (int)$_POST['rating'];
$comment = $_POST['comment'] ?? '';

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'التقييم يجب أن يكون بين 1 و 5']);
    exit();
}

$sql = "INSERT INTO driver_ratings (driver_id, company_id, rating, comment, created_at) 
        VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->execute([$driver_id, $company_id, $rating, $comment]);

if ($stmt->rowCount() > 0) {
    // Update driver's average rating
    $update_sql = "UPDATE drivers SET 
                   average_rating = (
                       SELECT AVG(rating) 
                       FROM driver_ratings 
                       WHERE driver_id = ?
                   )
                   WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$driver_id, $driver_id]);
    
    echo json_encode(['success' => true, 'message' => 'تم إرسال التقييم بنجاح']);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إرسال التقييم']);
}
  