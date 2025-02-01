<?php
require_once '../config.php';

if (!isset($_SESSION['company_email'])) {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// جلب معرف الشركة من الجلسة

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

// جلب تواريخ البداية والنهاية من النموذج
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// جلب عمليات الدفع من وإلى الشركة مع التصفية حسب التاريخ
$payments_query = "SELECT 
    id,
    payment_type,
    amount,
    payment_method,
    reference_number,
    notes,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as payment_date
FROM company_payments
WHERE company_id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
if ($start_date && $end_date) {
    $payments_query .= " AND DATE(created_at) BETWEEN :start_date AND :end_date";
} elseif ($start_date) {
    $payments_query .= " AND DATE(created_at) >= :start_date";
} elseif ($end_date) {
    $payments_query .= " AND DATE(created_at) <= :end_date";
}

$payments_query .= " ORDER BY created_at DESC";

$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bindValue(':company_id', $company_id);

if ($start_date && $end_date) {
    $payments_stmt->bindValue(':start_date', $start_date);
    $payments_stmt->bindValue(':end_date', $end_date);
} elseif ($start_date) {
    $payments_stmt->bindValue(':start_date', $start_date);
} elseif ($end_date) {
    $payments_stmt->bindValue(':end_date', $end_date);
}

$payments_stmt->execute();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحليلات مالية</title>
    
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

<?php include '../includes/comHeader.php'; 
// تمكين عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // التحقق من اتصال قاعدة البيانات
    if (!$conn) {
        throw new Exception("فشل الاتصال بقاعدة البيانات");
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
    WHERE status = 'delivered' AND company_id = :company_id";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);

    $stmt = $conn->prepare($query);
    $stmt->execute(['company_id' => $company_id]);
    if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['completed_orders'] = intval($row['total_orders']);
        $stats['total_amount'] = floatval($row['total_amount']);
        $stats['delivery_revenue'] = floatval($row['total_delivery_fees']);
        $stats['total_minus_delivery'] = floatval($row['total_minus_delivery']);
    }

    // Get company statistics
    $company = [];
    
    // استعلام لحساب إجمالي رسوم التوصيل للطلبات الموصلة فقط
    $delivery_fees_query = "SELECT 
        r.company_id,
        c.delivery_fee as current_fee,
        COALESCE(SUM(r.delivery_fee), 0) as total_delivery_fees,
        COUNT(*) as total_orders
    FROM requests r
    JOIN companies c ON r.company_id = c.id 
    WHERE r.status = 'delivered' AND r.company_id = :company_id";

    // إضافة شرط التاريخ إذا تم تحديده
    $delivery_fees_query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
    $delivery_fees_query .= " GROUP BY r.company_id, c.delivery_fee";
    
    $delivery_fees_stmt = $conn->prepare($delivery_fees_query);
    $delivery_fees_stmt->execute(['company_id' => $company_id]);
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
    WHERE c.id = :company_id";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
    $query .= " GROUP BY c.id, c.name, c.delivery_fee ORDER BY c.name";

    $stmt = $conn->prepare($query);
    $stmt->execute(['company_id' => $company_id]);
    if ($stmt) {
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($company) {
            $company['completed_orders'] = intval($company['completed_orders']);
            $company['total_amount'] = floatval($company['total_amount']);
            $company['delivery_revenue'] = floatval($company_delivery_fees[$company['id']]['total'] ?? 0);
            $company['company_payable'] = floatval($company['company_payable']);
            $company['paid_amount'] = floatval($company['paid_to_company'] - $company['paid_by_company']);
            $company['remaining'] = $company['company_payable'] - $company['paid_amount'] - $company['delivery_revenue'];
        }
    }

    // حساب إجمالي المبالغ المتبقية
    $total_remaining = $company['remaining'] ?? 0;

    // Get monthly revenue data for chart
    $monthly_data = [];
    $query = "SELECT 
        DATE_FORMAT(delivery_date, '%Y-%m') as month,
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
    FROM requests 
    WHERE status = 'delivered' AND company_id = :company_id";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);
    $query .= " GROUP BY DATE_FORMAT(delivery_date, '%Y-%m') ORDER BY month";

    $stmt = $conn->prepare($query);
    $stmt->execute(['company_id' => $company_id]);
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
    WHERE status = 'delivered' AND company_id = :company_id";

    // إضافة شرط التاريخ إذا تم تحديده
    $query .= getDateCondition($start_date, $end_date);
    $query .= " GROUP BY payment_method";

    $stmt = $conn->prepare($query);
    $stmt->execute(['company_id' => $company_id]);
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
        <h1 class="mt-4">تحليلات مالية</h1>

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
                    <a href="statistics.php" class="btn btn-secondary mt-4">إعادة تعيين</a>
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
                    <h6 class="mb-2">
                        <?php if ($company): 
                            // حساب المبلغ المتبقي بعد خصم رسوم التوصيل
                            $remaining = $company['remaining'];

                            // تحديد حالة الشركة
                            $status = '';
                            $status_color = '';
                            if ($remaining > 0) {
                                $status = 'مستحق لنا ✅';
                                $status_color = 'text-success';
                            } elseif ($remaining < 0) {
                                $status = 'مستحق علينا ⚠️';
                                $status_color = 'text-danger';
                            } else {
                                $status = 'لا يوجد مستحقات✅';
                                $status_color = 'text-success';
                            }

                            // عرض الحالة
                            echo $status;
                        endif; ?>
                    </h6>
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

        <!-- جدول الشركة -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <i class="fas fa-table me-1"></i>
                تفاصيل حسابات الشركة
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="table-light">
                            <tr>
                                <th>الشركة</th>
                                <th>رسوم التوصيل</th>
                                <th>الطلبات المكتملة</th>
                                <th>اجمالي مستحقات لنا </th>
                                <th>رسوم التوصيل</th>
                                <th>اجمالي مستحقات لنا بعد خصم رسوم التوصيل</th>
                                <th>اجمالي المبلغ المدفوع	 </th>
                                <th>المتبقي</th>
                                <th>حالة الشركة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($company): 
                                // حساب المبلغ المتبقي بعد خصم رسوم التوصيل
                                $remaining = $company['remaining'];

                                // تحديد حالة الشركة
                                $status = '';
                                $status_color = '';
                                if ($remaining > 0) {
                                    $status = 'مستحق لنا ✅';
                                    $status_color = 'text-success';
                                } elseif ($remaining < 0) {
                                    $status = 'مستحق علينا ⚠️';
                                    $status_color = 'text-danger';
                                } else {
                                    $status = 'لا يوجد مستحقات✅';
                                    $status_color = 'text-success';
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                <td><?php echo number_format($company['delivery_fee'], 2); ?> ر.س</td>
                                <td><?php echo number_format($company['completed_orders']); ?></td>
                                <td><?php echo number_format($company['total_amount'], 2); ?> ر.س</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1"> متوسط سعر التوصيل للطلب: <?php 
                                            $per_order = isset($company_delivery_fees[$company['id']]) ? $company_delivery_fees[$company['id']]['per_order'] : 0;
                                            echo number_format($per_order, 2) . ' ر.س'; 
                                        ?></small>
                                        <strong class="text-success">إجمالي التوصيل: <?php 
                                            $total = isset($company_delivery_fees[$company['id']]) ? $company_delivery_fees[$company['id']]['total'] : 0;
                                            echo number_format($total, 2); 
                                        ?> ر.س</strong>
                                    </div>
                                </td>
                                <td class="pending-amount"><?php echo number_format($company['company_payable']-$company_delivery_fees[$company['id']]['total'], 2); ?> ر.س</td>
                                <td class="text-success">
                                    <div class="d-flex flex-column">
                                        <small class="text-muted mb-1">مدفوع من الشركة: <?php echo number_format($company['paid_to_company'], 2); ?>  ر.س</small>
                                        <small class="text-muted">مدفوع منا إلى الشركة:<?php echo number_format($company['paid_by_company'], 2); ?> ر.س</small>
                                    </div>
                                </td>
                                <td class="<?php echo $remaining > 0 ? 'text-danger' : ($remaining < 0 ? 'text-primary' : 'text-success'); ?>">
                                    <?php echo number_format($remaining, 2); ?> ر.س
                                    <?php if ($remaining > 0): ?>
                                     <small class="text-primary d-block">💰 مستحق لنا</small>
                                    <?php elseif ($remaining < 0): ?>
                                     <small class="text-danger d-block">⚠️ مستحق علينا</small>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $status_color; ?>">
                                    <?php echo $status; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">لا يوجد بيانات للشركة</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-money-bill-wave me-1"></i>
            عمليات الدفع
        </div>
        <div>
            <!-- زر طباعة -->
            <button onclick="printTable()" class="btn btn-primary me-2">
                <i class="fas fa-print me-1"></i>
                طباعة
            </button>
            <!-- زر تصدير إلى Excel -->
            <a href="export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i>
                تصدير إلى Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center" id="paymentsTable">
                <thead class="table-light">
                    <tr>
                        <th>نوع الدفعة</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>رقم المرجع</th>
                        <th>التاريخ</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <?php if ($payment['payment_type'] === 'incoming'): ?>
                                     <span class="text-danger">صادر (مننا)</span>
                                    <?php else: ?>
                                          <span class="text-success">وارد (إلينا)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($payment['amount'], 2); ?> ر.س</td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td><?php echo htmlspecialchars($payment['reference_number'] ?? 'لا يوجد'); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['notes'] ?? 'لا يوجد'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p class="mb-0">لا يوجد عمليات دفع</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
        </script>
    </div>

<?php
} catch (Exception $e) {
    die("<div class='alert alert-danger'><h4>خطأ:</h4>" . $e->getMessage() . "</div>");
}
?>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function printTable() {
    var printContents = document.getElementById('paymentsTable').outerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();

    document.body.innerHTML = originalContents;
    window.location.reload(); // إعادة تحميل الصفحة بعد الطباعة
}
</script>
</body>
</html>