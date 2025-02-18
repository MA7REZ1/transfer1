<?php
require_once 'config.php';

// التحقق من وجود معرف الطلب ورقم الطلب
if (!isset($_GET['order_id']) || !isset($_GET['order_number'])) {
    die('بيانات الطلب غير مكتملة');
}

$order_id = $_GET['order_id'];
$order_number = $_GET['order_number'];

// جلب تفاصيل الطلب
$stmt = $conn->prepare("
    SELECT 
        r.*,
        c.name as company_name,
        c.phone as company_phone,
        c.address as company_address,
        d.username as driver_name,
        d.phone as driver_phone
    FROM requests r 
    LEFT JOIN companies c ON r.company_id = c.id
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.id = ? AND r.order_number = ?
");

$stmt->execute([$order_id, $order_number]);
$order = $stmt->fetch();

if (!$order) {
    die('الطلب غير موجود');
}

// تنسيق التاريخ بالتوقيت السعودي
$saudi_timezone = new DateTimeZone('Asia/Riyadh');
$order_date = new DateTime($order['created_at'], $saudi_timezone);
$delivery_date = new DateTime($order['delivery_date'], $saudi_timezone);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الطلب #<?php echo $order['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .verification-container {
            max-width: 800px;
            margin: 2rem auto;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .verification-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #dee2e6;
        }
        .verification-status {
            text-align: center;
            margin-bottom: 2rem;
        }
        .info-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .info-item {
            margin-bottom: 0.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
            display: inline-block;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .verification-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #dee2e6;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <!-- رأس الصفحة -->
        <div class="verification-header">
            <?php if (file_exists('assets/img/logo.png')): ?>
            <img src="assets/img/logo.png" alt="شعار الشركة" class="logo">
            <?php endif; ?>
            <h1 class="h3">التحقق من الطلب</h1>
        </div>

        <!-- حالة التحقق -->
        <div class="verification-status">
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                تم التحقق من صحة الطلب
            </div>
        </div>

        <!-- معلومات الطلب -->
        <div class="info-section">
            <div class="info-title">
                <i class="bi bi-info-circle me-2"></i>
                معلومات الطلب
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">رقم الطلب:</span>
                        <?php echo htmlspecialchars($order['order_number']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">تاريخ الطلب:</span>
                        <?php echo $order_date->format('Y/m/d H:i'); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">حالة الطلب:</span>
                        <span class="badge bg-<?php 
                            switch ($order['status']) {
                                case 'pending': echo 'warning'; break;
                                case 'accepted': echo 'info'; break;
                                case 'in_transit': echo 'primary'; break;
                                case 'delivered': echo 'success'; break;
                                case 'cancelled': echo 'danger'; break;
                                default: echo 'secondary';
                            }
                        ?>">
                            <?php
                            switch ($order['status']) {
                                case 'pending': echo 'قيد الانتظار'; break;
                                case 'accepted': echo 'تم القبول'; break;
                                case 'in_transit': echo 'جاري التوصيل'; break;
                                case 'delivered': echo 'تم التوصيل'; break;
                                case 'cancelled': echo 'ملغي'; break;
                                default: echo 'غير معروف';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">نوع الطلب:</span>
                        <?php echo $order['order_type'] === 'delivery' ? 'توصيل' : 'نقل'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات الشركة -->
        <div class="info-section">
            <div class="info-title">
                <i class="bi bi-building me-2"></i>
                معلومات الشركة
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">اسم الشركة:</span>
                        <?php echo htmlspecialchars($order['company_name']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">رقم الهاتف:</span>
                        <?php echo htmlspecialchars($order['company_phone']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات التوصيل -->
        <div class="info-section">
            <div class="info-title">
                <i class="bi bi-geo-alt me-2"></i>
                معلومات التوصيل
            </div>
            <div class="info-item">
                <span class="info-label">موقع الاستلام:</span>
                <?php echo htmlspecialchars($order['pickup_location']); ?>
            </div>
            <div class="info-item">
                <span class="info-label">موقع التوصيل:</span>
                <?php echo htmlspecialchars($order['delivery_location']); ?>
            </div>
            <div class="info-item">
                <span class="info-label">تاريخ التوصيل:</span>
                <?php echo $delivery_date->format('Y/m/d H:i'); ?>
            </div>
        </div>

        <!-- معلومات الدفع -->
        <div class="info-section">
            <div class="info-title">
                <i class="bi bi-credit-card me-2"></i>
                معلومات الدفع
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">حالة الدفع:</span>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">إجمالي التكلفة:</span>
                        <strong><?php echo number_format($order['total_cost'], 2); ?> ريال</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- تذييل الصفحة -->
        <div class="verification-footer">
            <small class="text-muted">
                تم التحقق من هذا الطلب في <?php echo date('Y/m/d H:i'); ?>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 