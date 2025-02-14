<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم


require_once '../includes/header.php';

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) FROM companies WHERE is_active = 1");
$companies_count = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1");
$drivers_count = $stmt->fetchColumn();

$stmt = $conn->query("SELECT COUNT(*) FROM requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$monthly_orders = $stmt->fetchColumn();

// Get delivery fee statistics
$stmt = $conn->query("
    SELECT 
        ROUND(AVG(delivery_fee), 2) as avg_delivery_fee,
        ROUND(SUM(delivery_fee), 2) as total_delivery_fee
    FROM requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$delivery_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'new'");
$new_complaints = $stmt->fetchColumn();

// Get monthly orders data for chart
$stmt = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as total_orders,
           SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
           SUM(total_cost) as total_amount
    FROM requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$orders_data = $stmt->fetchAll();

// Get advanced statistics
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        ROUND(AVG(CASE WHEN status = 'delivered' THEN total_cost ELSE NULL END), 2) as avg_order_value,
        ROUND(SUM(total_cost), 2) as total_revenue
    FROM requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get driver performance metrics
$stmt = $conn->query("
    SELECT 
        d.username,
        COUNT(r.id) as total_orders,
        ROUND(AVG(dr.rating), 1) as avg_rating,
        ROUND((COUNT(CASE WHEN r.status = 'delivered' THEN 1 END) * 100.0 / NULLIF(COUNT(r.id), 0)), 1) as completion_rate,
        ROUND(AVG(TIMESTAMPDIFF(MINUTE, r.created_at, 
            CASE WHEN r.status = 'delivered' 
                THEN r.updated_at 
                ELSE NULL 
            END
        )), 0) as avg_delivery_time
    FROM drivers d
    LEFT JOIN requests r ON d.id = r.driver_id
    LEFT JOIN driver_ratings dr ON r.id = dr.request_id
    WHERE d.is_active = 1
    AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY d.id
    HAVING COUNT(r.id) > 0
    ORDER BY completion_rate DESC, avg_rating DESC
    LIMIT 5
");
$driver_performance = $stmt->fetchAll();

// Get hourly order distribution
$stmt = $conn->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as order_count
    FROM requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour
");
$hourly_distribution = $stmt->fetchAll();

// Get customer satisfaction metrics
$stmt = $conn->query("
    SELECT 
        ROUND(AVG(rating), 1) as avg_rating,
        COUNT(DISTINCT request_id) as total_rated_orders,
        SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as satisfaction_rate
    FROM driver_ratings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$satisfaction_metrics = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!-- Add this style section after your existing styles -->
<style>
.stats-card {
    position: relative;
    padding: 20px;
    overflow: hidden;
    color: white;
}

.stats-card i {
    position: absolute;
    font-size: 2.5rem;
    opacity: 0.2;
    transition: all 0.3s ease;
    color: rgba(255, 255, 255, 0.8);
}

/* RTL Icons Position */
[dir="rtl"] .stats-card i.float-left {
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
}

[dir="rtl"] .stats-card:hover i.float-left {
    transform: translateY(-50%) translateX(5px);
}

/* LTR Icons Position */
[dir="ltr"] .stats-card i.float-right {
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

[dir="ltr"] .stats-card:hover i.float-right {
    transform: translateY(-50%) translateX(-5px);
}

.stats-card:hover i {
    opacity: 0.4;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 5px;
    position: relative;
    z-index: 2;
}

.stat-label {
    font-size: 1rem;
    opacity: 0.9;
    position: relative;
    z-index: 2;
}

.progress {
    position: relative;
    z-index: 2;
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
}

.progress-bar {
    transition: width 1s ease-in-out;
}

.stats-card:hover .progress-bar {
    background: rgba(255, 255, 255, 0.3) !important;
}

/* Card Colors */
.stats-card:nth-child(1) {
    background: linear-gradient(135deg, #ff8f00 0%, #e65100 100%);
}

.stats-card:nth-child(2) {
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
}

.stats-card:nth-child(3) {
    background: linear-gradient(135deg, #1e88e5 0%, #1565c0 100%);
}

.stats-card:nth-child(4) {
    background: linear-gradient(135deg, #e53935 0%, #c62828 100%);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .stats-card {
        margin-bottom: 15px;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
    }
    
    .stats-card i {
        font-size: 2rem;
    }
}
</style>

<!-- Statistics Cards -->
<div class="row fade-in-up">
    <div class="col-md-3 mb-4">
        <div class="stats-card">
            <div class="stat-value"><?php echo number_format($companies_count); ?></div>
            <div class="stat-label"><?php echo __('active_companies'); ?></div>
            <i class="fas fa-building <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: var(--success-gradient);">
            <div class="stat-value"><?php echo number_format($drivers_count); ?></div>
            <div class="stat-label"><?php echo __('active_drivers'); ?></div>
            <i class="fas fa-user-tie <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: var(--secondary-gradient);">
            <div class="stat-value"><?php echo number_format($monthly_orders); ?></div>
            <div class="stat-label"><?php echo __('monthly_orders'); ?></div>
            <i class="fas fa-box <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
            <div class="stat-value"><?php echo number_format($new_complaints); ?></div>
            <div class="stat-label"><?php echo __('new_complaints'); ?></div>
            <i class="fas fa-exclamation-circle <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
</div>

<!-- Additional Statistics Cards -->
<div class="row fade-in-up">
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);">
            <div class="stat-value"><?php echo number_format($delivery_stats['avg_delivery_fee'], 2); ?> <?php echo __('sar'); ?></div>
            <div class="stat-label"><?php echo __('average_order_value'); ?></div>
            <i class="fas fa-coins <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);">
            <div class="stat-value"><?php echo number_format($delivery_stats['total_delivery_fee'], 2); ?> <?php echo __('sar'); ?></div>
            <div class="stat-label"><?php echo __('total_revenue'); ?></div>
            <i class="fas fa-chart-line <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="stat-value"><?php echo round(($order_stats['delivered_orders'] / $order_stats['total_orders']) * 100, 1); ?>%</div>
            <div class="stat-label"><?php echo __('orders_completion_rate'); ?></div>
            <i class="fas fa-check-circle <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: 100%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="stats-card" style="background: linear-gradient(135deg, #FF9966 0%, #FF5E62 100%);">
            <div class="stat-value"><?php echo $satisfaction_metrics['avg_rating']; ?>/5</div>
            <div class="stat-label"><?php echo __('customer_satisfaction'); ?></div>
            <i class="fas fa-smile <?php echo $_SESSION['lang'] == 'ar' ? 'float-left' : 'float-left'; ?>"></i>
            <div class="progress mt-2">
                <div class="progress-bar" style="width: <?php echo ($satisfaction_metrics['avg_rating']/5)*100; ?>%; background: rgba(255,255,255,0.2)"></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row fade-in-up" style="margin-top: 20px;">
    <div class="col-lg-8 mb-4">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-chart-line"></i>
                    <?php echo __('monthly_orders_analysis'); ?>
                </div>
                <div class="btn-group chart-period-selector">
                    <button type="button" class="btn btn-sm btn-outline-primary active" data-period="6">6 أشهر</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-period="12">سنة</button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i>
                <?php echo __('orders_status_distribution'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row fade-in-up">
    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i>
                <?php echo __('drivers_performance'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="driversPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i>
                <?php echo __('orders_growth_rate'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="ordersGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add new charts -->
<div class="row fade-in-up">
    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <i class="fas fa-clock"></i>
                <?php echo __('hourly_orders_distribution'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="hourlyDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card chart-card">
            <div class="card-header">
                <i class="fas fa-trophy"></i>
                <?php echo __('top_performing_drivers'); ?>
            </div>
            <div class="card-body">
                <div class="chart-container" style="height: 300px;">
                    <canvas id="driverPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
// إضافة صوت الجرس
const bellSound = new Audio('../assets/sounds/notification.mp3');

// Utility function to create gradients
function createGradient(ctx, color1, color2, opacity1 = 0.4, opacity2 = 0.0) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color1.replace('1)', opacity1 + ')'));
    gradient.addColorStop(1, color2.replace('1)', opacity2 + ')'));
    return gradient;
}

// Orders Chart with Hierarchical Progress
const ctx = document.getElementById('ordersChart').getContext('2d');
const gradient1 = createGradient(ctx, 'rgba(230, 126, 34, 1)', 'rgba(211, 84, 0, 1)');
const gradient2 = createGradient(ctx, 'rgba(243, 156, 18, 1)', 'rgba(241, 196, 15, 1)');

const ordersData = <?php 
    $stmt = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
               COUNT(*) as total_orders,
               SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
               SUM(total_cost) as total_amount
        FROM requests
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    echo json_encode(array_map(function($row) {
        return [
            'month' => date('M Y', strtotime($row['month'] . '-01')),
            'total' => (int)$row['total_orders'],
            'completed' => (int)$row['completed_orders'],
            'amount' => (float)$row['total_amount']
        ];
    }, $stmt->fetchAll()));
?>;

const ordersChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ordersData.map(row => row.month),
        datasets: [{
            label: '<?php echo __("total_orders"); ?>',
            data: ordersData.map(row => row.total),
            borderColor: '#e67e22',
            backgroundColor: gradient1,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#e67e22',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            segment: {
                borderColor: ctx => {
                    const current = ctx.p0.parsed.y;
                    const next = ctx.p1.parsed.y;
                    return current < next ? '#e67e22' : '#c0392b';
                }
            },
            pointStyle: (ctx) => {
                if (ctx.dataIndex === 0) return 'circle';
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 'triangle' : 'triangle';
            },
            pointRotation: (ctx) => {
                if (ctx.dataIndex === 0) return 0;
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 0 : 180;
            }
        }, {
            label: '<?php echo __("completed_orders"); ?>',
            data: ordersData.map(row => row.completed),
            borderColor: '#f39c12',
            backgroundColor: gradient2,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#f39c12',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            segment: {
                borderColor: ctx => {
                    const current = ctx.p0.parsed.y;
                    const next = ctx.p1.parsed.y;
                    return current < next ? '#f39c12' : '#d35400';
                }
            },
            pointStyle: (ctx) => {
                if (ctx.dataIndex === 0) return 'circle';
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 'triangle' : 'triangle';
            },
            pointRotation: (ctx) => {
                if (ctx.dataIndex === 0) return 0;
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 0 : 180;
            }
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1000,
            easing: 'easeInOutQuart',
            delay: (context) => context.dataIndex * 100
        },
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                rtl: true,
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12,
                        family: 'system-ui'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                titleFont: { size: 14, weight: 'bold' },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                displayColors: true,
                boxWidth: 8,
                boxHeight: 8,
                boxPadding: 5,
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        const current = context.parsed.y;
                        const prev = context.dataIndex > 0 ? 
                            context.dataset.data[context.dataIndex - 1] : current;
                        const growth = ((current - prev) / prev * 100).toFixed(1);
                        const arrow = current >= prev ? '↑' : '↓';
                        return [
                            `${context.dataset.label}: ${current} <?php echo __("orders"); ?>`,
                            `<?php echo __("growth"); ?>: ${arrow} ${Math.abs(growth)}%`
                        ];
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: true,
                    drawBorder: false,
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    font: { size: 12 },
                    color: '#666',
                    padding: 10
                }
            },
            x: {
                grid: { display: false },
                ticks: {
                    font: { size: 12 },
                    color: '#666',
                    padding: 10
                }
            }
        }
    }
});

// Order Status Chart
const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
<?php
$stmt = $conn->query("
    SELECT status,
           COUNT(*) as count
    FROM requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
");
$statusData = $stmt->fetchAll();
?>

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: [
            'قيد الإنتظار',
            'تم القبول',
            'جاري التوصيل',
            'تم التوصيل',
            'ملغي'
        ],
        datasets: [{
            data: [
                <?php
                $statuses = ['pending', 'accepted', 'in_transit', 'delivered', 'cancelled'];
                foreach ($statuses as $status) {
                    $count = 0;
                    foreach ($statusData as $row) {
                        if ($row['status'] === $status) {
                            $count = $row['count'];
                            break;
                        }
                    }
                    echo $count . ',';
                }
                ?>
            ],
            backgroundColor: [
                '#f1c40f',
                '#3498db',
                '#e67e22',
                '#2ecc71',
                '#e74c3c'
            ],
            borderWidth: 0,
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'right',
                rtl: true,
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                usePointStyle: true,
                callbacks: {
                    label: function(context) {
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${context.label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Drivers Performance Chart
const driversCtx = document.getElementById('driversPerformanceChart').getContext('2d');
<?php
$stmt = $conn->query("
    SELECT 
        d.username,
        COUNT(r.id) as total_orders,
        ROUND(AVG(dr.rating), 1) as avg_rating,
        ROUND((COUNT(CASE WHEN r.status = 'delivered' THEN 1 END) * 100.0 / NULLIF(COUNT(r.id), 0)), 1) as completion_rate,
        ROUND(AVG(TIMESTAMPDIFF(MINUTE, r.created_at, 
            CASE WHEN r.status = 'delivered' 
                THEN r.updated_at 
                ELSE NULL 
            END
        )), 0) as avg_delivery_time
    FROM drivers d
    LEFT JOIN requests r ON d.id = r.driver_id
    LEFT JOIN driver_ratings dr ON r.id = dr.request_id
    WHERE d.is_active = 1
    AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY d.id
    HAVING COUNT(r.id) > 0
    ORDER BY completion_rate DESC, avg_rating DESC
    LIMIT 5
");
$driversData = $stmt->fetchAll();
?>

new Chart(driversCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($row) {
            return $row['username'];
        }, $driversData)); ?>,
        datasets: [{
            label: '<?php echo __("completion_rate"); ?>',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['completion_rate'];
            }, $driversData)); ?>,
            backgroundColor: createGradient(driversCtx, 'rgba(46, 204, 113, 1)', 'rgba(39, 174, 96, 1)', 0.8, 0.6),
            borderRadius: 5,
            yAxisID: 'y'
        }, {
            label: '<?php echo __("rating"); ?>',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['avg_rating'] * 20; // Convert to percentage
            }, $driversData)); ?>,
            backgroundColor: createGradient(driversCtx, 'rgba(241, 196, 15, 1)', 'rgba(243, 156, 18, 1)', 0.8, 0.6),
            borderRadius: 5,
            yAxisID: 'y'
        }, {
            label: '<?php echo __("average_delivery_time"); ?> (<?php echo __("minutes"); ?>)',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['avg_delivery_time'];
            }, $driversData)); ?>,
            type: 'line',
            borderColor: 'rgba(52, 152, 219, 1)',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            pointStyle: 'circle',
            pointRadius: 6,
            pointBackgroundColor: '#fff',
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
                rtl: true,
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        switch(context.dataset.label) {
                            case '<?php echo __("completion_rate"); ?>':
                                return `<?php echo __("completion_rate"); ?>: ${context.parsed.y}%`;
                            case '<?php echo __("rating"); ?>':
                                return `<?php echo __("rating"); ?>: ${(context.parsed.y / 20).toFixed(1)}/5`;
                            case '<?php echo __("average_delivery_time"); ?> (<?php echo __("minutes"); ?>)':
                                return `<?php echo __("average_delivery_time"); ?>: ${context.parsed.y} <?php echo __("minutes"); ?>`;
                            default:
                                return context.parsed.y;
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                position: 'left',
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    callback: function(value) {
                        return value + ' د';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Orders Growth Chart
const growthCtx = document.getElementById('ordersGrowthChart').getContext('2d');
const growthGradient = createGradient(growthCtx, 'rgba(46, 204, 113, 1)', 'rgba(39, 174, 96, 1)');

new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: ordersData.map(row => row.month),
        datasets: [{
            label: '<?php echo __("sales_amount"); ?>',
            data: ordersData.map(row => row.amount),
            borderColor: '#2ecc71',
            backgroundColor: growthGradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#2ecc71',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8,
            segment: {
                borderColor: ctx => {
                    const current = ctx.p0.parsed.y;
                    const next = ctx.p1.parsed.y;
                    return current < next ? '#2ecc71' : '#e74c3c';
                },
                borderWidth: 3,
            },
            pointStyle: (ctx) => {
                if (ctx.dataIndex === 0) return 'circle';
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 'triangle' : 'triangle';
            },
            pointRotation: (ctx) => {
                if (ctx.dataIndex === 0) return 0;
                const current = ctx.dataset.data[ctx.dataIndex];
                const prev = ctx.dataset.data[ctx.dataIndex - 1];
                return current > prev ? 0 : 180;
            }
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                titleFont: { size: 14, weight: 'bold' },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        const current = context.parsed.y;
                        const prev = context.dataIndex > 0 ? 
                            context.dataset.data[context.dataIndex - 1] : current;
                        const growth = ((current - prev) / prev * 100).toFixed(1);
                        const arrow = current >= prev ? '↑' : '↓';
                        return [
                            `<?php echo __("sales_amount"); ?>: ${current.toLocaleString()} <?php echo __("sar"); ?>`,
                            `<?php echo __("growth_rate"); ?>: ${arrow} ${Math.abs(growth)}%`
                        ];
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' ر.س';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Handle period selector
document.querySelectorAll('.chart-period-selector button').forEach(button => {
    button.addEventListener('click', function() {
        const period = parseInt(this.dataset.period);
        const data = ordersData.slice(-period);
        
        ordersChart.data.labels = data.map(row => row.month);
        ordersChart.data.datasets[0].data = data.map(row => row.total);
        ordersChart.data.datasets[1].data = data.map(row => row.completed);
        ordersChart.update();
        
        // Update active state
        document.querySelectorAll('.chart-period-selector button').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Update period selector buttons text
document.querySelectorAll('.chart-period-selector button').forEach(button => {
    if (button.dataset.period === '6') {
        button.textContent = '<?php echo __("6_months"); ?>';
    } else {
        button.textContent = '<?php echo __("1_year"); ?>';
    }
});

// Hourly Distribution Chart
const hourlyCtx = document.getElementById('hourlyDistributionChart').getContext('2d');
new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($row) {
            return sprintf('%02d:00', $row['hour']);
        }, $hourly_distribution)); ?>,
        datasets: [{
            label: '<?php echo __("orders_count"); ?>',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['order_count'];
            }, $hourly_distribution)); ?>,
            backgroundColor: createGradient(hourlyCtx, 'rgba(52, 152, 219, 1)', 'rgba(41, 128, 185, 1)', 0.8, 0.6),
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        return `<?php echo __("orders_count"); ?>: ${context.parsed.y}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Top Drivers Performance Chart
const performanceCtx = document.getElementById('driverPerformanceChart').getContext('2d');
new Chart(performanceCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($row) {
            return $row['username'];
        }, $driver_performance)); ?>,
        datasets: [{
            label: '<?php echo __("completion_rate"); ?>',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['completion_rate'];
            }, $driver_performance)); ?>,
            backgroundColor: createGradient(performanceCtx, 'rgba(46, 204, 113, 1)', 'rgba(39, 174, 96, 1)', 0.8, 0.6),
            borderRadius: 5
        }, {
            label: '<?php echo __("rating"); ?>',
            data: <?php echo json_encode(array_map(function($row) {
                return $row['avg_rating'] * 20; // Convert to percentage
            }, $driver_performance)); ?>,
            backgroundColor: createGradient(performanceCtx, 'rgba(241, 196, 15, 1)', 'rgba(243, 156, 18, 1)', 0.8, 0.6),
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                rtl: true,
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: { size: 12 }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 255, 255, 0.9)',
                titleColor: '#2c3e50',
                bodyColor: '#2c3e50',
                bodyFont: { size: 13 },
                padding: 15,
                borderColor: 'rgba(0,0,0,0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label === '<?php echo __("completion_rate"); ?>') {
                            return `<?php echo __("completion_rate"); ?>: ${context.parsed.y}%`;
                        } else {
                            return `<?php echo __("rating"); ?>: ${(context.parsed.y / 20).toFixed(1)}/5`;
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Notification System
document.addEventListener('DOMContentLoaded', function() {
    const notificationSystem = {
        container: document.getElementById('notificationsContainer'),
        badge: document.getElementById('notificationCount'),
        icon: document.querySelector('.notification-icon'),
        dropdownMenu: document.querySelector('.notifications-dropdown'),
        isOpen: false,
        
        updateCount: function(count) {
            const badge = document.getElementById('notificationCount');
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.id = 'notificationCount';
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = count;
                    this.icon.appendChild(newBadge);
                }
                // Update header badge if exists
                const headerBadge = document.querySelector('.notifications-header .badge');
                if (headerBadge) {
                    headerBadge.textContent = count + ' جديد';
                }
            } else {
                if (badge) badge.remove();
                const headerBadge = document.querySelector('.notifications-header .badge');
                if (headerBadge) headerBadge.remove();
            }
        },
        
        toggleDropdown: function() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                // إضافة تأثير الجرس
                const bellIcon = this.icon.querySelector('i');
                bellIcon.classList.add('ringing');
                setTimeout(() => bellIcon.classList.remove('ringing'), 1000);
                
                // تشغيل صوت الجرس عند الفتح
                bellSound.play().catch(e => console.log('Error playing sound:', e));
                this.dropdownMenu.classList.add('show');
                this.refresh(); // Refresh when opening
            } else {
                this.dropdownMenu.classList.remove('show');
            }
        },
        
        closeDropdown: function() {
            this.isOpen = false;
            this.dropdownMenu.classList.remove('show');
        },
        
        markAsRead: function(notificationId) {
            return fetch('ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json());
        },
        
        refresh: function() {
            return fetch('ajax/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (this.container) {
                            this.container.innerHTML = data.notifications_html;
                        }
                        this.updateCount(data.unread_count);
                    }
                    return data;
                });
        },
        
        handleClick: function(event) {
            const notificationItem = event.target.closest('.notification-item');
            if (!notificationItem) return;
            
            const notificationId = notificationItem.dataset.id;
            const link = notificationItem.dataset.link;
            
            if (notificationItem.classList.contains('unread')) {
                this.markAsRead(notificationId)
                    .then(data => {
                        if (data.success) {
                            notificationItem.classList.remove('unread');
                            const badge = document.getElementById('notificationCount');
                            const currentCount = badge ? parseInt(badge.textContent) : 0;
                            this.updateCount(Math.max(0, currentCount - 1));
                            
                            if (link && link !== 'javascript:void(0)') {
                                window.location.href = link;
                            }
                        }
                    })
                    .catch(error => console.error('Error marking notification as read:', error));
            } else if (link && link !== 'javascript:void(0)') {
                window.location.href = link;
            }
        },
        
        init: function() {
            // Add click handler for notifications
            if (this.container) {
                this.container.addEventListener('click', (e) => this.handleClick(e));
            }
            
            // Handle notification icon click
            const notificationToggle = document.querySelector('.notification-icon');
            if (notificationToggle) {
                notificationToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleDropdown();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!notificationToggle.contains(e.target) && 
                        !this.dropdownMenu.contains(e.target)) {
                        this.closeDropdown();
                    }
                });
            }
            
            // Initial load
            this.refresh();
            
            // Auto refresh every 30 seconds
            setInterval(() => {
                if (this.isOpen) { // Only refresh if dropdown is open
                    this.refresh();
                }
            }, 30000);
        }
    };
    
    // Initialize the notification system
    notificationSystem.init();
});
</script>

<?php require_once '../includes/footer.php'; ?> 