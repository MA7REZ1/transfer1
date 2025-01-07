<?php
require_once '../../config.php';

// Check if user is logged in
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

$company_id = $_SESSION['company_id'];
$staff_id = $_SESSION['staff_id'];

// Validate input
if (!isset($_POST['request_id']) || !isset($_POST['driver_id'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

$request_id = (int)$_POST['request_id'];
$driver_id = (int)$_POST['driver_id'];

try {
    $conn->beginTransaction();

    // Handle rating submission if provided
    if (isset($_POST['rating']) && is_numeric($_POST['rating'])) {
        $rating = (int)$_POST['rating'];
        if ($rating >= 1 && $rating <= 5) {
            $stmt = $conn->prepare("
                INSERT INTO driver_ratings (
                    request_id, 
                    driver_id, 
                    company_id, 
                    rating, 
                    comment
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $request_id,
                $driver_id,
                $company_id,
                $rating,
                $_POST['rating_comment'] ?? null
            ]);
        }
    }

    // Handle complaint submission if subject and description are provided
    if (!empty($_POST['complaint_subject']) && !empty($_POST['complaint_description'])) {
        // Generate complaint number
        $complaint_number = 'COMP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("
            INSERT INTO complaints (
                complaint_number,
                company_id,
                driver_id,
                request_id,
                type,
                subject,
                description,
                priority
            ) VALUES (?, ?, ?, ?, 'driver', ?, ?, ?)
        ");
        $stmt->execute([
            $complaint_number,
            $company_id,
            $driver_id,
            $request_id,
            $_POST['complaint_subject'],
            $_POST['complaint_description'],
            $_POST['complaint_priority'] ?? 'medium'
        ]);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error submitting complaint/rating: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ البيانات']);
} 