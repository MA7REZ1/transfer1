<?php
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

try {
    // التحقق من البيانات المطلوبة
    $required_fields = ['company_id', 'amount', 'payment_method', 'payment_type'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("الحقل {$field} مطلوب");
        }
    }

    // تنظيف وتحضير البيانات
    $company_id = intval($_POST['company_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = htmlspecialchars(trim($_POST['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8');
    $payment_type = htmlspecialchars(trim($_POST['payment_type'] ?? ''), ENT_QUOTES, 'UTF-8');
    $reference_number = htmlspecialchars(trim($_POST['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $notes = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
    
    // استخدام معرف المدير مباشرة من الجلسة
    $created_by = $_SESSION['admin_id'];
    
    if (!$created_by) {
        throw new Exception("خطأ في بيانات المستخدم");
    }

    // التحقق من وجود المدير
    $stmt = $conn->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->execute([$created_by]);
    if (!$stmt->fetch()) {
        throw new Exception("خطأ في بيانات المستخدم");
    }

    // التحقق من وجود الشركة
    $stmt = $conn->prepare("SELECT id, name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    if (!$company) {
        throw new Exception("الشركة غير موجودة");
    }

    // التحقق من المبلغ المتبقي
    $stmt = $conn->prepare("
        SELECT 
            (COALESCE(SUM(CASE WHEN r.payment_method = 'cash' THEN r.total_cost ELSE 0 END) - SUM(r.delivery_fee), 0) - 
            COALESCE((SELECT SUM(CASE 
                WHEN payment_type = 'outgoing' THEN amount 
                WHEN payment_type = 'incoming' THEN -amount 
                ELSE 0 END) 
            FROM company_payments 
            WHERE company_id = ? AND status = 'completed'), 0)
        ) as remaining
        FROM companies c
        LEFT JOIN requests r ON c.id = r.company_id AND r.status = 'delivered'
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$company_id, $company_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        throw new Exception("لا يمكن التحقق من المبلغ المتبقي");
    }
    
    // التحقق من المبلغ المدخل
    if ($payment_type === 'outgoing' && $amount > $result['remaining']) {
        throw new Exception("المبلغ المدخل أكبر من المبلغ المتبقي");
    }

    // إدخال المدفوعات
    $stmt = $conn->prepare("
        INSERT INTO company_payments 
        (company_id, amount, payment_date, payment_method, payment_type, reference_number, notes, created_by, status) 
        VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, 'completed')
    ");

    if (!$stmt->execute([
        $company_id,
        $amount,
        $payment_method,
        $payment_type,
        $reference_number,
        $notes,
        $created_by
    ])) {
        throw new Exception("فشل في تسجيل الدفعة");
    }

    $payment_id = $conn->lastInsertId();

    // إضافة إشعار للشركة
    $payment_method_text = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك'
    ][$payment_method];

    $notification_title = "تم تسجيل دفعة " . ($payment_type === 'incoming' ? 'واردة' : 'صادرة');
    $notification_message = sprintf(
        "تم تسجيل دفعة %s بمبلغ %s ريال عن طريق %s %s",
        $payment_type === 'incoming' ? 'واردة' : 'صادرة',
        number_format($amount, 2),
        $payment_method_text,
        $reference_number ? " (رقم المرجع: $reference_number)" : ""
    );
    
    $stmt = $conn->prepare("
        INSERT INTO company_notifications 
        (company_id, title, message, type, created_at) 
        VALUES (?, ?, ?, 'payment', NOW())
    ");
    
    $stmt->execute([
        $company_id,
        $notification_title,
        $notification_message
    ]);

    // تحديث الإحصائيات
    $stmt = $conn->prepare("
        SELECT 
            (COALESCE(SUM(CASE WHEN r.payment_method = 'cash' THEN r.total_cost ELSE 0 END) - SUM(r.delivery_fee), 0) - 
            COALESCE((SELECT SUM(CASE 
                WHEN payment_type = 'outgoing' THEN amount 
                WHEN payment_type = 'incoming' THEN -amount 
                ELSE 0 END) 
            FROM company_payments 
            WHERE company_id = ? AND status = 'completed'), 0)
        ) as remaining,
            COALESCE(SUM(delivery_fee), 0) as delivery_fees
        FROM companies c
        LEFT JOIN requests r ON c.id = r.company_id AND r.status = 'delivered'
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$company_id, $company_id]);
    $updated_stats = $stmt->fetch();

    // إرجاع النتيجة
    echo json_encode([
        'status' => 'success',
        'message' => 'تم تسجيل الدفعة بنجاح',
        'payment' => [
            'id' => $payment_id,
            'amount' => $amount,
            'payment_type' => $payment_type,
            'payment_method' => $payment_method_text,
            'reference_number' => $reference_number,
            'date' => date('Y-m-d H:i:s')
        ],
        'updated_stats' => [
            'remaining' => $updated_stats['remaining'],
            'delivery_fees' => $updated_stats['delivery_fees']
        ]
    ]);
    exit();

} catch (Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
} 