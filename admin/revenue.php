<?php
require_once '../config.php';
require_once '../includes/header.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'accounting') {
    header('Location: ../index.php');
    exit;
}
// جلب تواريخ البداية والنهاية من النموذج
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// دالة لإنشاء شرط التاريخ للاستعلامات
function getDateCondition($start_date, $end_date, $column = 'delivery_date') {
    $condition = "";
    if ($start_date && $end_date) {
        $condition = " AND $column BETWEEN '$start_date' AND '$end_date'";
    } elseif ($start_date) {
        $condition = " AND $column >= '$start_date'";
    } elseif ($end_date) {
        $condition = " AND $column <= '$end_date'";
    }
    return $condition;
}
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
    
    /* تنسيقات البطاقات */
    .bg-gradient-primary { background: linear-gradient(135deg, #2980b9, #3498db); }
    .bg-gradient-success { background: linear-gradient(135deg, #27ae60, #2ecc71); }
    .bg-gradient-warning { background: linear-gradient(135deg, #f39c12, #f1c40f); }
    .bg-gradient-info { background: linear-gradient(135deg, #2c3e50, #34495e); }
    .bg-gradient-danger { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    /* تنسيقات المحاسبة */
    .accounting-row {
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 5px;
    }
    
    .accounting-positive {
        background-color: rgba(46, 204, 113, 0.1);
        border-right: 4px solid #2ecc71;
    }
    
    .accounting-negative {
        background-color: rgba(231, 76, 60, 0.1);
        border-right: 4px solid #e74c3c;
    }
    
    .accounting-warning {
        background-color: rgba(241, 196, 15, 0.1);
        border-right: 4px solid #f1c40f;
    }
    
    /* تنسيقات الجدول */
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .balance-positive {
        color: #2ecc71;
        font-weight: bold;
    }
    
    .balance-negative {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .pending-amount {
        color: #f39c12;
        font-weight: bold;
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

    // إضافة عمود delivery_fee إلى جدول الشركات إذا لم يكن موجوداً
    $conn->query("ALTER TABLE companies ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0");

    // تحديث رسوم التوصيل للشركة إذا تم تقديم النموذج
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_delivery_fee'])) {
        $company_id = intval($_POST['company_id']);
        $new_fee = floatval($_POST['company_delivery_fee']);
        
        // تحديث رسوم التوصيل للشركة
        $stmt = $conn->prepare("UPDATE companies SET delivery_fee = ? WHERE id = ?");
        $stmt->execute([$new_fee, $company_id]);
        
        // تحديث رسوم التوصيل فقط للطلبات الجديدة والمعلقة
        $stmt = $conn->prepare("
            UPDATE requests 
            SET delivery_fee = ? 
            WHERE company_id = ? 
            AND status IN ('pending', 'accepted')
        ");
        $stmt->execute([$new_fee, $company_id]);
        
        $success_message = "تم تحديث رسوم التوصيل للشركة بنجاح";
    }

    // Calculate statistics
    $stats = [
        'completed_orders' => 0,
        'total_amount' => 0,
        'delivery_revenue' => 0,
        'total_minus_delivery' => 0
    ];

    // Get total orders and revenue
    $query = "SELECT 
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_cost ELSE 0 END), 0) as total_amount,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN delivery_fee ELSE 0 END), 0) as total_delivery_fees,
        COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_cost ELSE 0 END), 0) as total_minus_delivery
    FROM requests 
    WHERE status = 'delivered'";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);

    $stmt = $conn->query($query);
    if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['completed_orders'] = intval($row['total_orders']);
        $stats['total_amount'] = floatval($row['total_amount']);
        $stats['delivery_revenue'] = floatval($row['total_delivery_fees']);
        $stats['total_minus_delivery'] = floatval($row['total_minus_delivery']);
    }

    // Get company statistics
    $companies = [];
    
    // استعلام لحساب إجمالي رسوم التوصيل للطلبات الموصلة فقط
    $delivery_fees_query = "SELECT 
        r.company_id,
        c.delivery_fee as current_fee,
        COALESCE(SUM(r.delivery_fee), 0) as total_delivery_fees,
        COUNT(*) as total_orders
    FROM requests r
    JOIN companies c ON r.company_id = c.id 
    WHERE r.status = 'delivered'";

    // إضافة شرط التاريخ إذا تم تحديده
    $delivery_fees_query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
    $delivery_fees_query .= " GROUP BY r.company_id, c.delivery_fee";
    
    $delivery_fees_stmt = $conn->query($delivery_fees_query);
    $company_delivery_fees = [];
    while ($row = $delivery_fees_stmt->fetch(PDO::FETCH_ASSOC)) {
        $company_delivery_fees[$row['company_id']] = [
            'total' => $row['total_delivery_fees'],
            'per_order' => $row['total_orders'] > 0 ? ($row['total_delivery_fees'] / $row['total_orders']) : 0
        ];
    }

    $query = "SELECT 
        c.id,
        c.name as company_name,
        COALESCE(c.delivery_fee, 0) as delivery_fee,
        COALESCE(COUNT(DISTINCT CASE WHEN r.status = 'delivered' THEN r.id END), 0) as completed_orders,
        COALESCE(SUM(CASE 
            WHEN r.status = 'delivered' 
            THEN r.total_cost
            ELSE 0 
        END), 0) as total_amount,
        COALESCE(SUM(CASE 
            WHEN r.status = 'delivered' 
            THEN r.total_cost
            ELSE 0 
        END), 0) as company_payable,
        COALESCE((
            SELECT SUM(amount)
            FROM company_payments 
            WHERE company_id = c.id AND status = 'completed' AND payment_type = 'outgoing'
        ), 0) as paid_to_company,  -- مدفوع منا إلى الشركة
        COALESCE((
            SELECT SUM(amount)
            FROM company_payments 
            WHERE company_id = c.id AND status = 'completed' AND payment_type = 'incoming'
        ), 0) as paid_by_company  -- مدفوع من الشركة
    FROM companies c
    LEFT JOIN requests r ON c.id = r.company_id
    WHERE 1=1";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
    $query .= " GROUP BY c.id, c.name, c.delivery_fee ORDER BY c.name";

    $stmt = $conn->query($query);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['completed_orders'] = intval($row['completed_orders']);
            $row['total_amount'] = floatval($row['total_amount']);
            $row['delivery_revenue'] = floatval($company_delivery_fees[$row['id']]['total'] ?? 0);
            $row['company_payable'] = floatval($row['company_payable']);
            $row['paid_amount'] = floatval($row['paid_to_company'] - $row['paid_by_company']);
            $row['remaining'] = $row['company_payable'] - $row['paid_amount'] - $row['delivery_revenue'];
            $companies[] = $row;
        }
    }

    // حساب إجمالي المبالغ المتبقية لجميع الشركات
    $total_remaining = 0;
    foreach ($companies as $company) {
        $total_remaining += $company['remaining'];
    }

    // Get monthly revenue data for chart
    $monthly_data = [];
    $query = "SELECT 
        DATE_FORMAT(delivery_date, '%Y-%m') as month,
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
    FROM requests 
    WHERE status = 'delivered'";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);
    $query .= " GROUP BY DATE_FORMAT(delivery_date, '%Y-%m') ORDER BY month";

    $stmt = $conn->query($query);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $monthly_data[] = [
                'month' => $row['month'],
                'orders' => intval($row['total_orders']),
                'revenue' => floatval($row['total_delivery_fees'])
            ];
        }
    }

    // Get payment method distribution for chart
    $payment_data = [];
    $query = "SELECT 
        payment_method,
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
    FROM requests 
    WHERE status = 'delivered'";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);
    $query .= " GROUP BY payment_method";

    $stmt = $conn->query($query);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $payment_data[] = [
                'method' => $row['payment_method'],
                'orders' => intval($row['total_orders']),
                'revenue' => floatval($row['total_delivery_fees'])
            ];
        }
    }
    ?>

    <!-- رأس الصفحة -->
    <div class="container-fluid px-4">
        <h1 class="mt-4">تحليلات الإيرادات</h1>

        <!-- تصفية حسب التاريخ -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-filter me-1"></i>
                    تصفية حسب التاريخ
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">تاريخ البداية</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">تاريخ النهاية</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>
                            تطبيق التصفية
                        </button>
                        <div class="col-md-3">
                    <a href="revenue.php" class="btn btn-secondary mt-4">إعادة تعيين</a>
                </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- التقرير المحاسبي -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <i class="fas fa-calculator me-1"></i>
                التقرير المحاسبي
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- إجمالي المبالغ -->
                    <div class="col-md-4">
                        <div class="accounting-row accounting-positive">
                            <h5 class="text-success">
                                <i class="fas fa-plus-circle"></i>
                                إجمالي المبالغ
                            </h5>
                            <ul class="list-unstyled mb-0">
                                <li>• إجمالي المبالغ: <?php echo number_format($stats['total_amount'], 2); ?> ر.س</li>
                                <li>• عدد الطلبات: <?php echo number_format($stats['completed_orders']); ?> طلب</li>
                                <li>• صافي المبالغ (بدون التوصيل): <?php echo number_format($stats['total_minus_delivery'], 2); ?> ر.س</li>
                            </ul>
                        </div>
                    </div>

                    <!-- المستحقات للشركات -->
                    <div class="col-md-4">
                        <div class="accounting-row accounting-warning">
                            <h5 class="text-warning">
                                <i class="fas fa-exclamation-circle"></i>
                                المستحقات للشركات
                            </h5>
                            <ul class="list-unstyled mb-0">
                                <li>• إجمالي المبالغ المتبقية: <?php echo number_format($total_remaining, 2); ?> ر.س</li>
                                <li>• عدد الطلبات: <?php echo number_format($stats['completed_orders']); ?> طلب</li>
                                <li>• متوسط قيمة الطلب: <?php echo number_format($stats['completed_orders'] ? $stats['total_minus_delivery'] / $stats['completed_orders'] : 0, 2); ?> ر.س</li>
                            </ul>
                        </div>
                    </div>

                    <!-- إجمالي رسوم التوصيل -->
                    <div class="col-md-4">
                        <div class="accounting-row accounting-positive">
                            <h5 class="text-success">
                                <i class="fas fa-truck"></i>
                                رسوم التوصيل
                            </h5>
                            <ul class="list-unstyled mb-0">
                                <li>• إجمالي رسوم التوصيل: <?php echo number_format($stats['delivery_revenue'], 2); ?> ر.س</li>
                                <li>• عدد الطلبات المكتملة: <?php echo number_format($stats['completed_orders']); ?> طلب</li>
                                <li>• متوسط رسوم التوصيل: <?php echo number_format($stats['completed_orders'] ? $stats['delivery_revenue'] / $stats['completed_orders'] : 0, 2); ?> ر.س</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقات الإحصائيات -->
        <div class="row">
            <!-- إجمالي الطلبات -->
            <div class="col-xl-3 col-md-6">
                <div class="card mb-4">
                    <div class="card-body bg-gradient-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-2">إجمالي الطلبات</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['completed_orders']); ?></h3>
                                <small>طلبات مكتملة</small>
                                <small class="d-block">إجمالي المبالغ: <?php echo number_format($stats['total_amount'], 2); ?> ر.س</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إجمالي المبالغ -->
            <div class="col-xl-3 col-md-6">
                <div class="card mb-4">
                    <div class="card-body bg-gradient-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-2">إجمالي المبالغ</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_amount'], 2); ?> ر.س</h3>
                                <small>جميع الطلبات</small>
                                <small class="d-block">صافي المبالغ (بدون التوصيل): <?php echo number_format($stats['total_minus_delivery'], 2); ?> ر.س</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المستحقات للشركات -->
            <div class="col-xl-3 col-md-6">
                <div class="card mb-4">
                    <div class="card-body bg-gradient-warning text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-2"><?php if ($company): 
                            // حساب المبلغ المتبقي بعد خصم رسوم التوصيل
                           

                            // تحديد حالة الشركة
                            $status = '';
                            $status_color = '';
                            if ($total_remaining > 0) {
                                    $status = 'مستحق علينا ⚠️';
                                $status_color = 'text-danger';
                            } elseif ($total_remaining < 0) {
                              $status = 'مستحق لنا ✅';
                                $status_color = 'text-success';
                            } else {
                                $status = 'لا يوجد مستحقات✅';
                                $status_color = 'text-success';
                            }

                            // عرض الحالة
                            echo $status;
                        endif; ?></h6>
                                <h3 class="mb-0"><?php echo number_format($total_remaining, 2); ?> ر.س</h3>
                                <small>إجمالي المبالغ المتبقية</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-usd fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إجمالي رسوم التوصيل -->
            <div class="col-xl-3 col-md-6">
                <div class="card mb-4">
                    <div class="card-body bg-gradient-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-2">رسوم التوصيل</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['delivery_revenue'], 2); ?> ر.س</h3>
                                <small><?php echo number_format($stats['completed_orders']); ?> طلب مكتمل</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-truck fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول الشركات -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-table me-1"></i>
                    تفاصيل حسابات الشركات
                </div>
                <a href="export_payments.php" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>
                    تصدير العمليات
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="table-light">
                            <tr>
                                <th>الشركة</th>
                                <th>رسوم التوصيل</th>
                                <th>الطلبات المكتملة</th>
                                <th>إجمالي المبلغ</th>
                                <th>رسوم التوصيل</th>
                                <th> المستحقات بعد خصم رسوم التوصيل </th>
                                <th>المبلغ المدفوع</th>
                                <th>المتبقي</th>
                                <th>حالة الشركة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): 
                                // حساب المبلغ المتبقي بعد خصم رسوم التوصيل
                                $remaining = $company['remaining'];

                                // تحديد حالة الشركة
                                $status = '';
                                $status_color = '';
                                if ($remaining > 0) {
                                    $status = 'يوجد مستحقات علينا';
                                    $status_color = 'text-danger';
                                } elseif ($remaining < 0) {
                                    $status = 'يوجد مستحقات لنا';
                                    $status_color = 'text-success';
                                } else {
                                    $status = 'لايوجد مستحقات';
                                    $status_color = 'text-success';
                                }
                            ?>
                            <tr data-company-id="<?php echo $company['id']; ?>">
                                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                <td>
                                    <form method="POST" class="delivery-fee-form">
                                        <div class="input-group input-group-sm">
                                            <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                            <input type="number" 
                                                   step="0.01" 
                                                   min="0" 
                                                   name="company_delivery_fee" 
                                                   class="form-control form-control-sm" 
                                                   value="<?php echo $company['delivery_fee'] ?? 0; ?>" 
                                                   placeholder="أدخل السعر"
                                                   required>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td><?php echo number_format($company['completed_orders']); ?></td>
                                <td><?php echo number_format($company['total_amount'], 2); ?> ر.س</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">سعر التوصيل للطلب: <?php 
                                            $per_order = isset($company_delivery_fees[$company['id']]) ? $company_delivery_fees[$company['id']]['per_order'] : 0;
                                            echo number_format($per_order, 2) . ' ر.س'; 
                                        ?></small>
                                        <strong class="text-success">إجمالي التوصيل: <?php 
                                            $total = isset($company_delivery_fees[$company['id']]) ? $company_delivery_fees[$company['id']]['total'] : 0;
                                            echo number_format($total, 2); 
                                        ?> ر.س</strong>
                                    </div>
                                </td>
                                <td class="pending-amount"><?php echo number_format($company['company_payable']-$total, 2); ?> ر.س</td>
                                <td class="text-success">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">مدفوع من الشركة: <?php echo number_format($company['paid_by_company'], 2); ?> ر.س</small>
                                        <small class="text-muted">مدفوع منا إلى الشركة: <?php echo number_format($company['paid_to_company'], 2); ?> ر.س</small>
                                    </div>
                                </td>
                                <td class="<?php echo $remaining > 0 ? 'text-danger' : ($remaining < 0 ? 'text-primary' : 'text-success'); ?>">
                                    <?php echo number_format($remaining, 2); ?> ر.س
                                    <?php if ($remaining > 0): ?>
                                        <small class="text-danger d-block">⚠️ مستحق علينا</small>
                                    <?php elseif ($remaining < 0): ?>
                                        <small class="text-primary d-block">💰 مستحق لنا</small>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $status_color; ?>">
                                    <?php echo $status; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-primary" 
                                            onclick="showPaymentModal(<?php echo $company['id']; ?>, '<?php echo htmlspecialchars($company['company_name']); ?>', <?php echo $remaining; ?>)">
                                        <i class="fas fa-money-bill-wave"></i> تسجيل دفعة
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-info text-white" 
                                            onclick="window.open('get_payment_history.php?company_id=<?php echo $company['id']; ?>', '_blank', 'width=800,height=600')">
                                        <i class="fas fa-history me-1"></i> السجل
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">لا يوجد شركات حالياً</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- نافذة تسجيل دفعة جديدة -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تسجيل دفعة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="paymentForm" method="POST" action="process_payment.php">
                        <div class="modal-body">
                            <input type="hidden" name="company_id" id="payment_company_id">
                            <div class="mb-3">
                                <label class="form-label">اسم الشركة</label>
                                <input type="text" class="form-control" id="payment_company_name" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">المبلغ المتبقي</label>
                                <input type="text" class="form-control" id="payment_remaining" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نوع الدفعة</label>
                                <select class="form-select" name="payment_type" required>
                                    <option value="">اختر نوع الدفعة</option>
                                    <option value="outgoing">دفع للشركة</option>
                                    <option value="incoming">استلام من الشركة</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">المبلغ</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">طريقة الدفع</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">اختر طريقة الدفع</option>
                                    <option value="cash">نقدي</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                    <option value="check">شيك</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رقم المرجع</label>
                                <input type="text" class="form-control" name="reference_number">
                                <small class="text-muted">رقم الشيك أو رقم التحويل البنكي</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات</label>
                                <textarea class="form-control" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">تسجيل الدفعة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- نافذة سجل المدفوعات -->
        <div class="modal fade" id="historyModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">سجل المدفوعات</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="payment_history">
                        <!-- سيتم تحميل السجل هنا -->
                    </div>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية -->
        <div class="row">
            <!-- رسم بياني للإيرادات  -->
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-chart-line me-1"></i>
                        الإيرادات 
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- رسم بياني لتوزيع طرق الدفع -->
            <div class="col-xl-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-chart-pie me-1"></i>
                        توزيع طرق الدفع
                    </div>
                    <div class="card-body">
                        <canvas id="paymentMethodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- تضمين مكتبات الرسوم البيانية -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // تهيئة البيانات للرسوم البيانية
            const monthlyData = <?php echo json_encode($monthly_data); ?>;
            const paymentData = <?php echo json_encode($payment_data); ?>;

            // رسم بياني للإيرادات 
            const monthlyChart = new Chart(document.getElementById('monthlyRevenueChart'), {
                type: 'line',
                data: {
                    labels: monthlyData.map(item => {
                        const [year, month] = item.month.split('-');
                        const date = new Date(year, month - 1);
                        return date.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
                    }),
                    datasets: [
                        {
                            label: 'رسوم التوصيل',
                            data: monthlyData.map(item => item.revenue),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            fill: true
                        },
                        {
                            label: 'عدد الطلبات',
                            data: monthlyData.map(item => item.orders),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            tension: 0.1,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'الإيرادات  وعدد الطلبات',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'رسوم التوصيل (ر.س)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'عدد الطلبات'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

            // رسم بياني لتوزيع طرق الدفع
            const paymentChart = new Chart(document.getElementById('paymentMethodChart'), {
                type: 'doughnut',
                data: {
                    labels: paymentData.map(item => {
                        const methods = {
                            'cash': 'نقدي',
                            'card': 'بطاقة',
                            'wallet': 'محفظة'
                        };
                        return methods[item.method] || item.method;
                    }),
                    datasets: [{
                        data: paymentData.map(item => item.revenue),
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)'
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'توزيع طرق الدفع',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            function showPaymentModal(companyId, companyName, remaining) {
                document.getElementById('payment_company_id').value = companyId;
                document.getElementById('payment_company_name').value = companyName;
                document.getElementById('payment_remaining').value = remaining.toFixed(2) + ' ر.س';
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }

            function showPaymentHistory(companyId, companyName) {
                const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
                const historyContent = document.getElementById('payment_history');
                historyContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> جاري التحميل...</div>';
                historyModal.show();
                
                fetch('get_payment_history.php?company_id=' + companyId)
                    .then(response => response.text())
                    .then(html => {
                        historyContent.innerHTML = html;
                    })
                    .catch(error => {
                        historyContent.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل السجل</div>';
                    });
            }

            // معالجة نموذج الدفع
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('process_payment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // إغلاق النافذة المنبثقة
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        
                        // تحديث البيانات في الجدول
                        const companyRow = document.querySelector(`tr[data-company-id="${formData.get('company_id')}"]`);
                        if (companyRow) {
                            const remainingCell = companyRow.querySelector('td:nth-child(7)');
                            const paidCell = companyRow.querySelector('td:nth-child(6)');
                            
                            if (remainingCell && paidCell) {
                                const amount = parseFloat(formData.get('amount'));
                                const currentPaid = parseFloat(paidCell.textContent.replace(/[^\d.-]/g, ''));
                                // عند الاستلام من الشركة نقوم بطرح المبلغ من المدفوعات
                                const newPaid = currentPaid + (formData.get('payment_type') === 'outgoing' ? amount : -amount);
                                
                                paidCell.textContent = newPaid.toFixed(2) + ' ر.س';
                                remainingCell.textContent = parseFloat(data.updated_stats.remaining).toFixed(2) + ' ر.س';
                                
                                // تحديث لون الخلية بناءً على القيمة
                                remainingCell.className = parseFloat(data.updated_stats.remaining) > 0 ? 'text-danger' : 'text-success';
                            }
                        }
                        
                        // عرض تفاصيل العملية
                        const paymentDetails = `
                            <div class="alert alert-success alert-dismissible fade show">
                                <h5 class="alert-heading mb-2">
                                    <i class="fas fa-check-circle me-1"></i>
                                    تم تسجيل الدفعة بنجاح
                                </h5>
                                <hr>
                                <ul class="list-unstyled mb-2">
                                    <li><strong>رقم العملية:</strong> #${data.payment.id}</li>
                                    <li><strong>نوع الدفعة:</strong> ${data.payment.payment_type === 'outgoing' ? 'دفع للشركة' : 'استلام من الشركة'}</li>
                                    <li><strong>المبلغ:</strong> ${data.payment.amount} ر.س</li>
                                    <li><strong>طريقة الدفع:</strong> ${data.payment.payment_method}</li>
                                    <li><strong>رقم المرجع:</strong> ${data.payment.reference_number || 'لا يوجد'}</li>
                                    <li><strong>التاريخ:</strong> ${data.payment.date}</li>
                                </ul>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>المبلغ المتبقي:</strong> ${data.updated_stats.remaining} ر.س
                                    </div>
                                    <div class="col-md-6">
                                        <strong>رسوم التوصيل:</strong> ${data.updated_stats.delivery_fees} ر.س
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                        
                        document.querySelector('.container-fluid').insertBefore(
                            document.createRange().createContextualFragment(paymentDetails), 
                            document.querySelector('.container-fluid').firstChild
                        );
                        
                        // إعادة تعيين النموذج
                        this.reset();
                        
                        // تحديث الإحصائيات في الصفحة
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء معالجة الطلب');
                });
            });

            // إضافة JavaScript للتعامل مع نماذج تحديث رسوم التوصيل
            document.querySelectorAll('.delivery-fee-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(() => {
                        // تحديث الصفحة لعرض التغييرات
                        location.reload();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('حدث خطأ أثناء تحديث رسوم التوصيل');
                    });
                });
            });
        </script>
    </div>

<?php
} catch (Exception $e) {
    die("<div class='alert alert-danger'><h4>خطأ:</h4>" . $e->getMessage() . "</div>");
}
?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>