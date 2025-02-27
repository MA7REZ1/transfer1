<?php
require_once '../../config.php';

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit();
}

// Validate required fields
$required_fields = [
    'order_type' => 'نوع الطلب',
    'customer_name' => 'اسم العميل',
    'customer_phone' => 'هاتف العميل',
    'delivery_date' => 'تاريخ التوصيل',
    'delivery_time' => 'وقت التوصيل',
    'pickup_location' => 'موقع الاستلام',
    'delivery_location' => 'موقع التوصيل',
    'items_count' => 'عدد القطع',
  
    'payment_method' => 'طريقة الدفع'
];

foreach ($required_fields as $field => $label) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'الحقل ' . $label . ' مطلوب']);
        exit();
    }
}

try {
    $company_id = $_SESSION['company_id'];
    
    // Get company delivery fee
    $stmt = $conn->prepare("
        SELECT delivery_fee
        FROM companies 
        WHERE id = ?
    ");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get delivery fee from company settings
    $delivery_fee = $company['delivery_fee'] ?? 0;
    
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Combine date and time
    $delivery_datetime = date('Y-m-d H:i:s', strtotime($_POST['delivery_date'] . ' ' . $_POST['delivery_time']));
    
    // Start transaction
    $conn->beginTransaction();
    
    // Insert order into database with delivery fee
    $stmt = $conn->prepare("
        INSERT INTO requests (
            company_id,
            order_number,
            order_type,
            customer_name,
            customer_phone,
            delivery_date,
            pickup_location,
            pickup_location_link,
            delivery_location,
            delivery_location_link,
            items_count,
            total_cost,
            delivery_fee,
            payment_method,
            is_fragile,
            additional_notes,
            status,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, 'pending', NOW(), NOW()
        )
    ");

    $result = $stmt->execute([
        $_SESSION['company_id'],
        $order_number,
        $_POST['order_type'],
        $_POST['customer_name'],
        $_POST['customer_phone'],
        $delivery_datetime,
        $_POST['pickup_location'],
        $_POST['pickup_location_link'],
        $_POST['delivery_location'],
        $_POST['delivery_location_link'],
        $_POST['items_count'],
        $_POST['total_cost'],
        $delivery_fee,
        $_POST['payment_method'],
        isset($_POST['is_fragile']) ? 1 : 0,
        $_POST['additional_notes'] ?? null
    ]);

    if ($result) {
        // Handle invoice file upload if provided
        $invoice_file = null;
        if (isset($_FILES['invoice_file']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['invoice_file']['type'], $allowed_types)) {
                throw new Exception('نوع الملف غير مسموح به. يرجى رفع صورة بصيغة JPG أو PNG');
            }
            
            if ($_FILES['invoice_file']['size'] > $max_size) {
                throw new Exception('حجم الصورة كبير جداً. الحد الأقصى هو 5 ميجابايت');
            }
            
            $invoice_extension = pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION);
            $invoice_file = $order_number . '.' . $invoice_extension;
            $upload_path = '../../uploads/invoices/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['invoice_file']['tmp_name'], $upload_path . $invoice_file)) {
                throw new Exception('فشل في رفع صورة الفاتورة');
            }

            // Update request with invoice file
            $stmt = $conn->prepare("UPDATE requests SET invoice_file = ? WHERE order_number = ?");
            $stmt->execute([$invoice_file, $order_number]);
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح. رقم الطلب: ' . $order_number
        ]);
    } else {
        throw new Exception('فشل في إنشاء الطلب');
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete uploaded file if it exists
    if (isset($invoice_file) && file_exists($upload_path . $invoice_file)) {
        @unlink($upload_path . $invoice_file);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} 