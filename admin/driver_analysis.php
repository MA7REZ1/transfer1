<?php
require_once '../config.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$total_drivers = 0;
$top_drivers = [];
$monthly_performance = [];
$error = '';

// Get driver statistics
try {
    // Enable PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Total drivers count
    $stmt = $conn->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1");
    $total_drivers = $stmt->fetchColumn();

    if ($total_drivers > 0) {
        // Top performing drivers (by completed orders)
        $stmt = $conn->query("
            SELECT 
                d.id,
                d.username,
                COUNT(o.id) as total_orders,
                AVG(TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at)) as avg_delivery_time,
                COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled_orders
            FROM drivers d
            LEFT JOIN requests o ON o.driver_id = d.id
            WHERE d.is_active = 1
            GROUP BY d.id, d.username
            ORDER BY completed_orders DESC
            LIMIT 10
        ");
        $top_drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Driver performance by month
        $stmt = $conn->query("
            SELECT 
                DATE_FORMAT(o.created_at, '%Y-%m') as month,
                COUNT(DISTINCT d.id) as active_drivers,
                COUNT(o.id) as total_orders,
                COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled_orders
            FROM drivers d
            LEFT JOIN requests o ON o.driver_id = d.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $monthly_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "حدث خطأ في جلب البيانات: " . $e->getMessage();
    error_log("SQL Error in driver_analysis.php: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">تحليل أداء السائقين</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>طباعة التقرير
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($total_drivers === 0): ?>
                <div class="alert alert-info">
                    لا يوجد سائقين نشطين في النظام حتى الآن
                </div>
            <?php else: ?>
                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">إجمالي السائقين النشطين</h5>
                                <h2 class="card-text"><?php echo number_format($total_drivers); ?></h2>
                                <i class="fas fa-users card-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <h5 class="card-title">متوسط الطلبات لكل سائق</h5>
                                <?php
                                $total_monthly_orders = 0;
                                $months_count = count($monthly_performance);
                                foreach ($monthly_performance as $month) {
                                    $total_monthly_orders += $month['total_orders'];
                                }
                                $avg_orders_per_driver = $total_drivers > 0 && $months_count > 0 ? 
                                    round($total_monthly_orders / ($total_drivers * $months_count), 1) : 0;
                                ?>
                                <h2 class="card-text"><?php echo $avg_orders_per_driver; ?></h2>
                                <i class="fas fa-box card-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <h5 class="card-title">نسبة إكمال الطلبات</h5>
                                <?php
                                $total_completed = 0;
                                $total_orders = 0;
                                foreach ($monthly_performance as $month) {
                                    $total_completed += $month['completed_orders'];
                                    $total_orders += $month['total_orders'];
                                }
                                $completion_rate = $total_orders > 0 ? round(($total_completed / $total_orders) * 100, 1) : 0;
                                ?>
                                <h2 class="card-text"><?php echo $completion_rate; ?>%</h2>
                                <i class="fas fa-check-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Performance Chart -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">الأداء الشهري</h5>
                        <canvas id="monthlyPerformanceChart"></canvas>
                    </div>
                </div>

                <!-- Top Drivers Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">أفضل السائقين أداءً</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم السائق</th>
                                        <th>إجمالي الطلبات</th>
                                        <th>الطلبات المكتملة</th>
                                        <th>متوسط وقت التوصيل</th>
                                        <th>نسبة الإكمال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_drivers)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">لا توجد بيانات للعرض</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_drivers as $driver): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($driver['username']); ?></td>
                                            <td><?php echo number_format($driver['total_orders']); ?></td>
                                            <td><?php echo number_format($driver['completed_orders']); ?></td>
                                            <td><?php echo round($driver['avg_delivery_time'] / 60, 1); ?> ساعة</td>
                                            <td>
                                                <?php 
                                                $completion_rate = $driver['total_orders'] > 0 ? 
                                                    round(($driver['completed_orders'] / $driver['total_orders']) * 100, 1) : 0;
                                                echo $completion_rate . '%';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php if ($total_drivers > 0 && !empty($monthly_performance)): ?>
<script>
// Monthly Performance Chart
const monthlyPerformanceCtx = document.getElementById('monthlyPerformanceChart').getContext('2d');
new Chart(monthlyPerformanceCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column(array_reverse($monthly_performance), 'month')); ?>,
        datasets: [{
            label: 'السائقين النشطين',
            data: <?php echo json_encode(array_column(array_reverse($monthly_performance), 'active_drivers')); ?>,
            borderColor: '#2563eb',
            tension: 0.1
        }, {
            label: 'الطلبات المكتملة',
            data: <?php echo json_encode(array_column(array_reverse($monthly_performance), 'completed_orders')); ?>,
            borderColor: '#10b981',
            tension: 0.1
        }, {
            label: 'الطلبات الملغاة',
            data: <?php echo json_encode(array_column(array_reverse($monthly_performance), 'cancelled_orders')); ?>,
            borderColor: '#ef4444',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php endif; ?>

<style>
.card-icon {
    position: absolute;
    top: 1rem;
    inset-inline-end: 1rem;
    font-size: 2rem;
    opacity: 0.3;
}

.card {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
}

.bg-primary {
    background-color: #2563eb !important;
}

.bg-info {
    background-color: #6366f1 !important;
}

.bg-warning {
    background-color: #f59e0b !important;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

@media print {
    .sidebar, .navbar, .breadcrumb, .btn-toolbar {
        display: none !important;
    }
    
    main {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
    }
}
</style>

<?php include '../includes/footer.php'; ?>