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
            cr.is_company_reply,
            CASE 
                WHEN cr.is_company_reply = 1 THEN (SELECT name FROM companies WHERE id = ?)
                WHEN cr.admin_id IS NOT NULL THEN COALESCE(a.username, 'مدير النظام')
                WHEN cr.employee_id IS NOT NULL THEN COALESCE(e.full_name, 'موظف')
                ELSE 'غير معروف'
            END as responder_name,
            CASE
                WHEN cr.is_company_reply = 1 THEN 'company'
                WHEN cr.admin_id IS NOT NULL THEN 'admin'
                WHEN cr.employee_id IS NOT NULL THEN 'employee'
                ELSE 'unknown'
            END as responder_type
        FROM complaint_responses cr
        LEFT JOIN admins a ON cr.admin_id = a.id
        LEFT JOIN employees e ON cr.employee_id = e.id
        LEFT JOIN complaints c ON cr.complaint_id = c.id
        WHERE cr.complaint_id = ?
        ORDER BY cr.created_at DESC
    ");

    $stmt->execute([$_SESSION['company_id'], $complaint['id']]);
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
                'responder_type' => $response['responder_type'],
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