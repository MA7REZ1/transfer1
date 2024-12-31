<?php
require_once '../config.php';
require_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحليلات الإيرادات</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* تنسيقات عامة */
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    /* تنسيقات الهيدر */
    .navbar {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        padding: 1rem;
        margin-bottom: 2rem;
    }
    
    .navbar-brand {
        color: white !important;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        margin: 0 0.5rem;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover {
        color: white !important;
        transform: translateY(-2px);
    }
    
    /* تنسيقات البطاقات */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #2980b9, #3498db);
    }
    
    .bg-gradient-success {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
    }
    
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f39c12, #f1c40f);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #2c3e50, #34495e);
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        opacity: 0.8;
    }
    
    /* تنسيقات الجداول */
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    /* تنسيقات التنبيهات */
    .alert {
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    
    /* تنسيقات الأزرار */
    .btn {
        border-radius: 5px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    /* تنسيقات النموذج */
    .input-group {
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    
    .input-group-text {
        background-color: #f8f9fa;
        border: none;
    }
    
    .form-control {
        border: none;
        padding: 0.75rem;
    }
    
    .form-control:focus {
        box-shadow: none;
        border-color: #3498db;
    }
    </style>
</head>
<body>

<?php
// تمكين عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // التحقق من اتصال قاعدة البيانات
    if (!$conn) {
        throw new Exception("فشل الاتصال بقاعدة البيانات");
    }

    // إنشاء جدول الإعدادات إذا لم يكن موجوداً
 
    // إدخال رسوم التوصيل الافتراضية
    $stmt = $conn->prepare("INSERT IGNORE INTO settings (name, value) VALUES ('delivery_fee', '20')");
    $stmt->execute();

    // جلب رسوم التوصيل من الإعدادات
    $stmt = $conn->query("SELECT value FROM settings WHERE name = 'delivery_fee'");
    $delivery_fee = floatval($stmt->fetchColumn() ?: 20);

    // تحديث رسوم التوصيل إذا تم تقديم النموذج
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_fee'])) {
        $new_fee = floatval($_POST['delivery_fee']);
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'delivery_fee'");
        $stmt->execute([$new_fee]);
        $delivery_fee = $new_fee;
    }

    // إضافة بيانات تجريبية إذا لم تكن هناك بيانات
    $stmt = $conn->prepare("SELECT COUNT(*) FROM requests");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // إضافة شركة تجريبية
        $conn->query("INSERT IGNORE INTO companies (name, email, phone) VALUES ('شركة تجريبية', 'test@example.com', '0500000000')");
        $company_id = $conn->lastInsertId();

        // إضافة طلبات تجريبية
        $sample_orders = [
            [
                'order_number' => 'ORD-001',
                'total_cost' => 150.00,
                'status' => 'delivered',
                'payment_method' => 'cash',
                'payment_status' => 'paid'
            ],
            [
                'order_number' => 'ORD-002',
                'total_cost' => 200.00,
                'status' => 'delivered',
                'payment_method' => 'card',
                'payment_status' => 'paid'
            ],
            [
                'order_number' => 'ORD-003',
                'total_cost' => 300.00,
                'status' => 'delivered',
                'payment_method' => 'bank_transfer',
                'payment_status' => 'paid'
            ]
        ];

        $stmt = $conn->prepare("
            INSERT INTO requests (
                order_number, company_id, customer_name, customer_phone,
                order_type, delivery_date, pickup_location, delivery_location,
                items_count, total_cost, payment_method, payment_status, status
            ) VALUES (
                :order_number, :company_id, 'عميل تجريبي', '0500000000',
                'delivery', NOW(), 'موقع الاستلام', 'موقع التسليم',
                2, :total_cost, :payment_method, :payment_status, :status
            )
        ");

        foreach ($sample_orders as $order) {
            $order['company_id'] = $company_id;
            $stmt->execute($order);
        }
    }

    // جلب إحصائيات الإيرادات
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE 
                WHEN status = 'delivered' AND payment_method = 'cash' 
                THEN total_cost 
                ELSE 0 
            END) as cash_revenue,
            SUM(CASE 
                WHEN status = 'delivered' AND payment_method != 'cash' 
                THEN total_cost 
                ELSE 0 
            END) as online_revenue,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN payment_method = 'cash' AND status = 'delivered' THEN 1 END) as cash_orders,
            COUNT(CASE WHEN payment_method != 'cash' AND status = 'delivered' THEN 1 END) as online_orders,
            COUNT(CASE WHEN payment_status = 'paid' AND status = 'delivered' THEN 1 END) as paid_orders
        FROM requests 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // حساب الإيرادات
    $stats['delivery_revenue'] = $stats['completed_orders'] * $delivery_fee; // رسوم التوصيل لجميع الطلبات المكتملة
    $stats['company_revenue'] = $stats['cash_revenue'] - $stats['delivery_revenue']; // إيرادات الشركة من الطلبات النقدية فقط
    $stats['total_revenue'] = $stats['cash_revenue']; // إجمالي الإيرادات من الطلبات النقدية فقط

    // عرض معلومات التشخيص
    echo "<div class='alert alert-info'>";
    echo "<h4>معلومات التشخيص:</h4>";
    echo "<ul>";
    echo "<li>إجمالي الطلبات: " . $stats['total_orders'] . "</li>";
    echo "<li>الطلبات المكتملة: " . $stats['completed_orders'] . "</li>";
    echo "<li>الطلبات النقدية: " . $stats['cash_orders'] . " (الإيرادات: " . number_format($stats['cash_revenue'], 2) . " ريال)</li>";
    echo "<li>الطلبات الإلكترونية: " . $stats['online_orders'] . " (الإيرادات: " . number_format($stats['online_revenue'], 2) . " ريال)</li>";
    echo "<li>إيرادات التوصيل: " . number_format($stats['delivery_revenue'], 2) . " ريال (لجميع الطلبات المكتملة)</li>";
    echo "<li>إيرادات الشركة: " . number_format($stats['company_revenue'], 2) . " ريال (من الطلبات النقدية فقط)</li>";
    echo "</ul>";
    echo "</div>";

    // عرض الصفحة
    require_once 'views/revenue_view.php';
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'><h4>خطأ:</h4>" . $e->getMessage() . "</div>");
}
?> 

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html> 