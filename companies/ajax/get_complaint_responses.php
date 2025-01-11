<?php
require_once '../../config.php';

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

// Get complaint ID from POST data
$complaint_id = $_POST['complaint_id'] ?? null;

if (!$complaint_id) {
    echo json_encode(['success' => false, 'message' => 'رقم الشكوى مطلوب']);
    exit;
}

try {
    // First verify the complaint exists and belongs to the company
    $stmt = $conn->prepare("
        SELECT id 
        FROM complaints 
        WHERE complaint_number = ? 
        AND company_id = ?
    ");
    $stmt->execute([$complaint_id, $_SESSION['company_id']]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        throw new Exception('الشكوى غير موجودة');
    }

    // Get responses for this specific complaint
    $stmt = $conn->prepare("
        SELECT 
            cr.id,
            cr.response,
            cr.created_at,
            CASE 
                WHEN cr.admin_id IS NULL THEN 1
                ELSE 0
            END as is_company_reply,
            CASE 
                WHEN cr.admin_id IS NULL THEN comp.name
                ELSE a.username
            END as responder_name
        FROM complaint_responses cr
        LEFT JOIN admins a ON cr.admin_id = a.id
        JOIN complaints c ON cr.complaint_id = c.id
        JOIN companies comp ON c.company_id = comp.id
        WHERE cr.complaint_id = ?
        ORDER BY cr.created_at DESC
    ");

    $stmt->execute([$complaint['id']]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the responses for debugging
    error_log("Responses found for complaint {$complaint_id}: " . count($responses));

    echo json_encode([
        'success' => true,
        'responses' => array_map(function($response) {
            return [
                'id' => $response['id'],
                'response' => nl2br(htmlspecialchars($response['response'])),
                'is_company_reply' => (bool)$response['is_company_reply'],
                'admin_name' => htmlspecialchars($response['responder_name']),
                'created_at' => $response['created_at']
            ];
        }, $responses)
    ]);

} catch (Exception $e) {
    error_log("Error fetching complaint responses: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب الردود: ' . $e->getMessage()
    ]);
}
?> 