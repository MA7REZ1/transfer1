<?php
require_once '../config.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}
// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'drivers_supervisor') {
    header('Location: ../index.php');
    exit;
}
// Initialize variables
$total_orders = 0;
$orders_by_status = [];
$orders_by_month = [];
$avg_delivery_time = 0;
$active_areas = [];
$error = '';

// Get order statistics
try {
    // Enable PDO error mode
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Total orders count
    $stmt = $conn->query("SELECT COUNT(*) FROM requests");
    $total_orders = $stmt->fetchColumn();

    if ($total_orders > 0) {
        // Orders by status
        $stmt = $conn->query("
            SELECT 
                status, 
                COUNT(*) as count 
            FROM requests 
            GROUP BY status
        ");
        $orders_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Orders by month (last 6 months)
        $stmt = $conn->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
            FROM requests 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $orders_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average delivery time
        $stmt = $conn->query("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_delivery_time
            FROM requests 
            WHERE status = 'delivered'
        ");
        $avg_delivery_time = round($stmt->fetchColumn(), 1);

        // Most active areas
        $stmt = $conn->query("
            SELECT 
                COALESCE(delivery_location, 'غير محدد') as area, 
                COUNT(*) as count
            FROM requests
            GROUP BY delivery_location
            ORDER BY count DESC
            LIMIT 5
        ");
        $active_areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "حدث خطأ في جلب البيانات: " . $e->getMessage();
    error_log("SQL Error in order_analysis.php: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">تحليل الطلبات</h1>
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

            <?php if ($total_orders === 0): ?>
                <div class="alert alert-info">
                    لا توجد طلبات مسجلة في النظام حتى الآن
                </div>
            <?php else: ?>
                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">إجمالي الطلبات</h5>
                                <h2 class="card-text"><?php echo number_format($total_orders); ?></h2>
                                <i class="fas fa-box-open card-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">متوسط وقت التوصيل</h5>
                                <h2 class="card-text"><?php echo $avg_delivery_time; ?> ساعة</h2>
                                <i class="fas fa-clock card-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <h5 class="card-title">نسبة اكتمال الطلبات</h5>
                                <?php 
                                $completed_count = 0;
                                foreach ($orders_by_status as $status) {
                                    if ($status['status'] === 'delivered') {
                                        $completed_count = $status['count'];
                                        break;
                                    }
                                }
                                $completion_rate = $total_orders > 0 ? round(($completed_count / $total_orders) * 100, 1) : 0;
                                ?>
                                <h2 class="card-text"><?php echo $completion_rate; ?>%</h2>
                                <i class="fas fa-check-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <h5 class="card-title">معدل الإلغاء</h5>
                                <?php 
                                $cancelled_count = 0;
                                foreach ($orders_by_status as $status) {
                                    if ($status['status'] === 'cancelled') {
                                        $cancelled_count = $status['count'];
                                        break;
                                    }
                                }
                                $cancellation_rate = $total_orders > 0 ? round(($cancelled_count / $total_orders) * 100, 1) : 0;
                                ?>
                                <h2 class="card-text"><?php echo $cancellation_rate; ?>%</h2>
                                <i class="fas fa-times-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <!-- Monthly Orders Chart -->
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3">تحليل الطلبات الشهري</h5>
                                <canvas id="monthlyOrdersChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Orders by Status Chart -->
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-3">توزيع حالات الطلبات</h5>
                                <canvas id="orderStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Areas Table -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">المناطق الأكثر نشاطاً</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>المنطقة</th>
                                        <th>عدد الطلبات</th>
                                        <th>النسبة المئوية</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($active_areas)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">لا توجد بيانات للعرض</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($active_areas as $area): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($area['area']); ?></td>
                                            <td><?php echo number_format($area['count']); ?></td>
                                            <td><?php echo round(($area['count'] / $total_orders) * 100, 1); ?>%</td>
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

<?php if ($total_orders > 0): ?>
<script>
// Monthly Orders Chart
const monthlyOrdersCtx = document.getElementById('monthlyOrdersChart').getContext('2d');
new Chart(monthlyOrdersCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column(array_reverse($orders_by_month), 'month')); ?>,
        datasets: [{
            label: 'إجمالي الطلبات',
            data: <?php echo json_encode(array_column(array_reverse($orders_by_month), 'total_orders')); ?>,
            borderColor: '#2563eb',
            tension: 0.1
        }, {
            label: 'الطلبات المكتملة',
            data: <?php echo json_encode(array_column(array_reverse($orders_by_month), 'completed_orders')); ?>,
            borderColor: '#10b981',
            tension: 0.1
        }, {
            label: 'الطلبات الملغاة',
            data: <?php echo json_encode(array_column(array_reverse($orders_by_month), 'cancelled_orders')); ?>,
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

// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($orders_by_status, 'status')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($orders_by_status, 'count')); ?>,
            backgroundColor: [
                '#2563eb',
                '#10b981',
                '#ef4444',
                '#f59e0b',
                '#6366f1'
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
    border: none;
}

.bg-primary {
    background-color: #2563eb !important;
}

.bg-success {
    background-color: #10b981 !important;
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