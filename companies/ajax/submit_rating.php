<?php
require_once '../../config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Log request details
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Raw POST data: " . file_get_contents("php://input"));
error_log("POST array: " . print_r($_POST, true));

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    error_log("Authentication failed: company_id=" . ($_SESSION['company_id'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

$company_id = $_SESSION['company_id'];

// Validate input
if (!isset($_POST['request_id']) || !isset($_POST['driver_id']) || !isset($_POST['rating'])) {
    error_log("Missing required fields: request_id=" . ($_POST['request_id'] ?? 'not set') . 
              ", driver_id=" . ($_POST['driver_id'] ?? 'not set') . 
              ", rating=" . ($_POST['rating'] ?? 'not set'));
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

$request_id = (int)$_POST['request_id'];
$driver_id = (int)$_POST['driver_id'];
$rating = (int)$_POST['rating'];

try {
    // Check database connection
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }

    // Log connection status
    error_log("Database connection status: " . ($conn ? "Connected" : "Not connected"));

    // Validate rating value
    if ($rating < 1 || $rating > 5) {
        throw new Exception('قيمة التقييم غير صالحة');
    }

    // Begin transaction
    $conn->beginTransaction();
    error_log("Transaction started");

    // Check if request exists and is delivered
    $stmt = $conn->prepare("
        SELECT id, status 
        FROM requests 
        WHERE id = ? AND driver_id = ? AND company_id = ? AND status = 'delivered'
    ");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . print_r($conn->errorInfo(), true));
    }
    
    $stmt->execute([$request_id, $driver_id, $company_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Request check result: " . print_r($request, true));
    
    if (!$request) {
        throw new Exception('لا يمكن تقييم هذا الطلب - الطلب غير موجود أو لم يتم تسليمه بعد');
    }

    // Check if already rated
    $stmt = $conn->prepare("
        SELECT id FROM driver_ratings 
        WHERE request_id = ? AND driver_id = ? AND company_id = ?
    ");
    $stmt->execute([$request_id, $driver_id, $company_id]);
    $existing_rating = $stmt->fetch();
    error_log("Existing rating check result: " . ($existing_rating ? "Found" : "Not found"));
    
    if ($existing_rating) {
        throw new Exception('تم تقييم هذا الطلب مسبقاً');
    }

    // Insert rating
    $stmt = $conn->prepare("
        INSERT INTO driver_ratings (
            request_id, 
            driver_id, 
            company_id, 
            rating, 
            comment
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $comment = isset($_POST['rating_comment']) ? trim($_POST['rating_comment']) : null;
    $result = $stmt->execute([
        $request_id,
        $driver_id,
        $company_id,
        $rating,
        $comment
    ]);
    error_log("Rating insertion result: " . ($result ? "Success" : "Failed"));

    if (!$result) {
        throw new Exception('Failed to insert rating: ' . print_r($stmt->errorInfo(), true));
    }

    // Update driver's average rating
    $stmt = $conn->prepare("
        UPDATE drivers 
        SET rating = (
            SELECT ROUND(AVG(rating), 2)
            FROM driver_ratings 
            WHERE driver_id = ?
        )
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$driver_id, $driver_id]);
    error_log("Driver rating update result: " . ($result ? "Success" : "Failed"));
    
    if (!$result) {
        throw new Exception('Failed to update driver rating: ' . print_r($stmt->errorInfo(), true));
    }

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed successfully");

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم إرسال التقييم بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إرسال التقييم']);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    error_log("Error submitting rating: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        error_log("Transaction rolled back");
    }
    error_log("PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات']);
} 