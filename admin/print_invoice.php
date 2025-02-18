<?php
require_once '../config.php';

// التحقق من تسجيل الدخول والصلاحيات
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من وجود معرف الطلب
if (!isset($_GET['order_id'])) {
    die('معرف الطلب غير موجود');
}

$order_id = $_GET['order_id'];

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
    WHERE r.id = ?
");

$stmt->execute([$order_id]);
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
    <title>فاتورة طلب #<?php echo $order['id']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body {
                margin: 1cm;
            }
            .no-print {
                display: none !important;
            }
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .invoice-header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        .info-title {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .total-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- زر الطباعة -->
        <div class="text-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> طباعة الفاتورة
            </button>
        </div>

        <!-- رأس الفاتورة -->
        <div class="invoice-header">
            <div class="row align-items-center">
                <div class="col-6">
                    <div class="invoice-title">فاتورة طلب #<?php echo $order['id']; ?></div>
                    <div>رقم الطلب: <?php echo $order['order_number']; ?></div>
                </div>
                <div class="col-6 text-start">
                    <div>تاريخ الطلب: <?php echo $order_date->format('Y/m/d H:i'); ?></div>
                    <div>تاريخ التوصيل: <?php echo $delivery_date->format('Y/m/d H:i'); ?></div>
                </div>
            </div>
        </div>

        <!-- معلومات الشركة -->
        <div class="info-section">
            <div class="info-title">معلومات الشركة</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">اسم الشركة:</span>
                        <?php echo htmlspecialchars($order['company_name']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">رقم الهاتف:</span>
                        <?php echo htmlspecialchars($order['company_phone']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">العنوان:</span>
                        <?php echo htmlspecialchars($order['company_address']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات العميل -->
        <div class="info-section">
            <div class="info-title">معلومات العميل</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">اسم العميل:</span>
                        <?php echo htmlspecialchars($order['customer_name']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">رقم الهاتف:</span>
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">نوع الطلب:</span>
                        <?php echo $order['order_type'] === 'delivery' ? 'توصيل' : 'نقل'; ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">حالة الطلب:</span>
                        <span class="status-badge bg-<?php 
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
                </div>
            </div>
        </div>

        <!-- معلومات المواقع -->
        <div class="info-section">
            <div class="info-title">معلومات المواقع</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">موقع الاستلام:</span>
                        <?php echo htmlspecialchars($order['pickup_location']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">موقع التوصيل:</span>
                        <?php echo htmlspecialchars($order['delivery_location']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات السائق -->
        <?php if ($order['driver_id']): ?>
        <div class="info-section">
            <div class="info-title">معلومات السائق</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">اسم السائق:</span>
                        <?php echo htmlspecialchars($order['driver_name']); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">رقم الهاتف:</span>
                        <?php echo htmlspecialchars($order['driver_phone']); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- التكلفة والدفع -->
        <div class="total-section">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-item">
                        <span class="info-label">حالة الدفع:</span>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-6 text-start">
                    <div class="info-item">
                        <span class="info-label">إجمالي التكلفة:</span>
                        <strong><?php echo number_format($order['total_cost'], 2); ?> ريال</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code للتحقق -->
        <div class="qr-code">
            <?php
            // بناء الرابط الكامل للتحقق من الفاتورة
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $domain = $_SERVER['HTTP_HOST'];
            $verification_url = $protocol . $domain . '/verify_order.php?order_id=' . $order['id'] . '&order_number=' . $order['order_number'];
            ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($verification_url); ?>" alt="رمز QR للتحقق">
            <div class="mt-2">
                <small class="text-muted">امسح الرمز للتحقق من صحة الفاتورة</small>
                <div class="text-muted" style="font-size: 0.8rem;"><?php echo $verification_url; ?></div>
            </div>
        </div>
    </div>
</body>
</html> 