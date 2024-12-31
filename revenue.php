<?php
require_once 'config.php';
require_once 'includes/header.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
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

    // إنشاء جدول الإعدادات إذا لم يكن موجوداً
   
    // Add delivery_fee column to requests table if it doesn't exist
 


    // جلب رسوم التوصيل من الإعدادات
    $stmt = $conn->query("SELECT value FROM settings WHERE name = 'delivery_fee'");
    $delivery_fee = floatval($stmt->fetchColumn() ?: 20);

    // تحديث رسوم التوصيل إذا تم تقديم النموذج
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_fee'])) {
        $new_fee = floatval($_POST['delivery_fee']);
        
        // تحديث القيمة في جدول الإعدادات
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE name = 'delivery_fee'");
        $stmt->execute([$new_fee]);
        
        // تحديث رسوم التوصيل للطلبات المعلقة
        $stmt = $conn->prepare("
            UPDATE requests 
            SET delivery_fee = ? 
            WHERE status IN ('pending', 'accepted') 
            OR (status = 'delivered' AND payment_status = 'unpaid')
        ");
        $stmt->execute([$new_fee]);
        
        $delivery_fee = $new_fee;
        
        // إضافة رسالة نجاح إضافية
        $success_message = "تم تحديث رسوم التوصيل بنجاح وتطبيقها على الطلبات المعلقة";
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
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_cost ELSE 0 END), 0) as total_amount,
        COALESCE(SUM(delivery_fee), 0) as total_delivery_fees,
        COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_cost ELSE 0 END) - SUM(delivery_fee), 0) as total_minus_delivery
    FROM requests 
    WHERE status = 'delivered'";

    $stmt = $conn->query($query);
    if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['completed_orders'] = intval($row['total_orders']);
        $stats['total_amount'] = floatval($row['total_amount']);
        $stats['delivery_revenue'] = floatval($row['total_delivery_fees']);
        $stats['total_minus_delivery'] = floatval($row['total_minus_delivery']);
    }

    // Get company statistics
    $companies = [];
    $query = "SELECT 
        c.id,
        c.name as company_name,
        COALESCE(COUNT(r.id), 0) as completed_orders,
        COALESCE(SUM(CASE WHEN r.payment_method = 'cash' THEN r.total_cost ELSE 0 END), 0) as total_amount,
        COALESCE(SUM(r.delivery_fee), 0) as total_delivery_fees,
        COALESCE(SUM(CASE WHEN r.payment_method = 'cash' THEN r.total_cost ELSE 0 END) - SUM(r.delivery_fee), 0) as company_payable
    FROM companies c
    LEFT JOIN requests r ON c.id = r.company_id AND r.status = 'delivered'
    GROUP BY c.id, c.name
    ORDER BY c.name";

    $stmt = $conn->query($query);
    if ($stmt) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['completed_orders'] = intval($row['completed_orders']);
            $row['total_amount'] = floatval($row['total_amount']);
            $row['delivery_revenue'] = floatval($row['total_delivery_fees']);
            $row['company_payable'] = floatval($row['company_payable']);
            $companies[] = $row;
        }
    }

    // Get monthly revenue data for chart
    $monthly_data = [];
    $query = "SELECT 
        DATE_FORMAT(delivery_date, '%Y-%m') as month,
        COALESCE(COUNT(*), 0) as total_orders,
        COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
    FROM requests 
    WHERE status = 'delivered'
        AND delivery_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(delivery_date, '%Y-%m')
    ORDER BY month";

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
    WHERE status = 'delivered'
    GROUP BY payment_method";

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
        <!-- <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="dashboard.php">لوحة التحكم</a></li>
            <li class="breadcrumb-item active">تحليلات الإيرادات</li>
        </ol> -->

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
                                <li>• مبالغ مستحقة للشركات: <?php echo number_format($stats['total_minus_delivery'], 2); ?> ر.س</li>
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
                                <h6 class="mb-2">المستحقات للشركات</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_minus_delivery'], 2); ?> ر.س</h3>
                                <small>مبالغ يجب تسديدها</small>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding-usd fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- صجمالي رسوم التوصيل -->
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

        <!-- جعدادات رسوم التوصيل -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <i class="fas fa-cog me-1"></i>
                إعدادات رسوم التوصيل
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-bold mb-2">تعديل رسوم التوصيل</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-money-bill-wave"></i>
                                </span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       name="delivery_fee" 
                                       class="form-control form-control-lg" 
                                       value="<?php echo $delivery_fee; ?>" 
                                       required>
                                <span class="input-group-text bg-light">ر.س</span>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-1"></i>
                                    حفظ التعديلات
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                معلومات هامة
                            </h6>
                            <ul class="mb-0">
                                <li>رسوم التوصيل الحالية: <strong><?php echo number_format($delivery_fee, 2); ?> ر.س</strong></li>
                                <li>سيتم تطبيق الرسوم الجديدة على الطلبات القادمة فقط</li>
                                <li>الطلبات السابقة ستحتفظ برسوم التوصيل القديمة</li>
                            </ul>
                        </div>
                    </div>
                </form>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivery_fee'])): ?>
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-check-circle me-1"></i>
                    <?php echo $success_message; ?>
                    <br>
                    <small class="text-muted">
                        • تم تحديث السعر إلى: <?php echo number_format(floatval($_POST['delivery_fee']), 2); ?> ر.س
                        <br>
                        • سيتم تطبيق السعر الجديد على الطلبات المعلقة والجديدة
                        <br>
                        • الطلبات المكتملة والمدفوعة ستحتفظ بأسعارها السابقة
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- جدول الشركات -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <i class="fas fa-table me-1"></i>
                تفاصيل حسابات الشركات
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>الشركة</th>
                                <th>الطلبات المكتملة</th>
                                <th>إجمالي المبلغ</th>
                                <th>رسوم التوصيل</th>
                                <th>مستحقات للشركة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                <td><?php echo number_format($company['completed_orders']); ?></td>
                                <td><?php echo number_format($company['total_amount'], 2); ?> ر.س</td>
                                <td><?php echo number_format($company['delivery_revenue'], 2); ?> ر.س</td>
                                <td class="pending-amount"><?php echo number_format($company['company_payable'], 2); ?> ر.س</td>
                                <td>
                                    <?php if ($company['completed_orders'] > 0): ?>
                                        <span class="badge bg-success">مكتمل</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">لا يوجد طلبات</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية -->
        <div class="row">
            <!-- رسم بياني للإيرادات الشهرية -->
            <div class="col-xl-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-chart-line me-1"></i>
                        الإيرادات الشهرية
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

            // رسم بياني للإيرادات الشهرية
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
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'عدد الطلبات',
                            data: monthlyData.map(item => item.orders),
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1,
                            fill: false,
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
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
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

</body>
</html> 