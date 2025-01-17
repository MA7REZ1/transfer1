<?php
require_once '../config.php';

if (!isset($_SESSION['company_email'])) {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Get company information
$stmt = $conn->prepare("SELECT name, logo FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get company statistics (same as dashboard.php)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests,
        AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_delivery_time,
        COALESCE(SUM(CASE 
            WHEN status = 'delivered' AND payment_method = 'cash'
            THEN total_cost
            ELSE 0 
        END), 0) as cash_in_hand,
        COALESCE(
            (SELECT 
                (SUM(CASE 
                    WHEN status = 'delivered' AND payment_method = 'cash'
                    THEN total_cost
                    ELSE 0 
                END) - SUM(delivery_fee)) - 
                COALESCE((
                    SELECT SUM(amount) 
                    FROM company_payments 
                    WHERE company_id = ? 
                    AND status = 'completed'
                ), 0)
            FROM requests 
            WHERE company_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND status = 'delivered'
        ), 0) as amount_owed,
        COALESCE(SUM(CASE 
            WHEN status = 'delivered'
            THEN delivery_fee
            ELSE 0 
        END), 0) as amount_due
    FROM requests 
    WHERE company_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$company_id, $company_id, $company_id]);
$stats = $stmt->fetch();

// Initialize stats if null
if (!$stats) {
    $stats = [
        'active_count' => 0,
        'pending_count' => 0,
        'delivered_count' => 0,
        'total_requests' => 0,
        'cancelled_requests' => 0,
        'avg_delivery_time' => 0,
        'amount_owed' => 0,
        'amount_due' => 0,
        'cash_in_hand' => 0
    ];
}

// إحصائيات المدفوعات
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_payments,
        COALESCE(SUM(amount), 0) as total_paid,
        SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
        SUM(CASE WHEN payment_method = 'bank_transfer' THEN 1 ELSE 0 END) as bank_count,
        SUM(CASE WHEN payment_method = 'check' THEN 1 ELSE 0 END) as check_count
    FROM company_payments 
    WHERE company_id = ? AND status = 'completed'
");
$stmt->execute([$company_id]);
$payment_stats = $stmt->fetch();

// إحصائيات شهرية
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        COALESCE(SUM(total_cost), 0) as revenue,
        COALESCE(SUM(delivery_fee), 0) as delivery_fees
    FROM requests 
    WHERE company_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute([$company_id]);
$monthly_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات المالية - <?php echo htmlspecialchars($company['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .stat-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
    </style>
</head>
<body>
    <?php include '../includes/comHeader.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-graph-up"></i>
                الإحصائيات المالية
            </h2>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-right"></i>
                العودة للوحة التحكم
            </a>
        </div>

        <!-- إحصائيات مالية -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #FF416C, #FF4B2B);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">المبلغ المستحق عليه (نقدي)</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['cash_in_hand'], 2); ?> ر.س</h3>
                                <small class="text-white">المبلغ بعد خصم التوصيل: <?php echo number_format($stats['amount_owed'], 2); ?> ر.س</small>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #11998e, #38ef7d);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">المبلغ المستحق له</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['amount_due'], 2); ?> ر.س</h3>
                                <small class="text-white">إجمالي رسوم التوصيل</small>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #4158D0, #C850C0);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">إجمالي المدفوعات</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($payment_stats['total_paid'], 2); ?> ر.س</h3>
                                <small class="text-white">عدد المدفوعات: <?php echo number_format($payment_stats['total_payments']); ?></small>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات الطلبات -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #FF8008, #FFC837);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">قيد الانتظار</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['pending_count']); ?></h3>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-clock-history"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #4158D0, #C850C0);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">جاري التوصيل</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['active_count']); ?></h3>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #11998e, #38ef7d);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">تم التوصيل</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['delivered_count']); ?></h3>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #FF416C, #FF4B2B);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">ملغية</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['cancelled_requests']); ?></h3>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- المخطط البياني -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">إحصائيات شهرية</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // إعداد بيانات المخطط الشهري
        const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;
        const months = monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('ar', { month: 'long', year: 'numeric' });
        });
        const revenue = monthlyData.map(item => item.revenue);
        const delivery_fees = monthlyData.map(item => item.delivery_fees);

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'الإيرادات',
                    data: revenue,
                    borderColor: '#FF416C',
                    tension: 0.1
                }, {
                    label: 'رسوم التوصيل',
                    data: delivery_fees,
                    borderColor: '#38ef7d',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html> 