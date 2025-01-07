<?php
require_once 'config.php';
require_once 'driver_auth.php';
require_once 'functions.php';

// Ensure driver is logged in
if (!isDriverLoggedIn()) {
    header('Location: driver_login.php');
    exit;
}

// Get current driver info with enhanced details
$driver = getCurrentDriver();
if (!$driver) {
    logoutDriver();
    header('Location: driver_login.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = validateInput($_POST['status']);
    if (updateDriverStatus($new_status)) {
        $driver['current_status'] = $new_status;
        $_SESSION['success_message'] = 'تم تحديث حالتك بنجاح';
    }
}

// Get dashboard data
$available_requests = getAvailablerequests();
$current_requests = getDriverrequests();
$pending_requests = getDriverrequests('pending');
$completed_requests = getDriverrequests('delivered');
$earnings = getDriverEarnings('month');
$notifications = getDriverNotifications(5, true);

// Get performance metrics
$performance = [
    'acceptance_rate' => calculateAcceptanceRate($_SESSION['driver_id']),
    'completion_rate' => calculateCompletionRate($_SESSION['driver_id']),
    'average_rating' => $driver['average_rating'] ?? 0,
    'total_earnings' => calculateTotalEarnings($_SESSION['driver_id']),
    'total_distance' => calculateTotalDistance($_SESSION['driver_id'])
];
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم السائق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
            background: rgba(13, 110, 253, 0.1);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .stats-card p {
            color: var(--secondary-color);
            margin: 0;
            font-size: 1.1rem;
        }
        
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .notification-card.unread {
            border-right: 4px solid var(--primary-color);
            background: rgba(13, 110, 253, 0.05);
        }
        
        .status-toggle {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .status-toggle .form-check {
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .status-toggle .form-check:hover {
            background: rgba(13, 110, 253, 0.05);
        }
        
        .performance-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            margin-top: 8px;
        }
        
        .rating-stars {
            color: var(--warning-color);
            font-size: 1.2rem;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-car me-2"></i>
                نظام إدارة التوصيل
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="driver_dashboard.php">
                            <i class="fas fa-home me-1"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_orders.php">
                            <i class="fas fa-list me-1"></i> الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_earnings.php">
                            <i class="fas fa-wallet me-1"></i> الأرباح
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_support.php">
                            <i class="fas fa-headset me-1"></i> الدعم
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($driver['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end animate__animated animate__fadeIn">
                            <li>
                                <a class="dropdown-item" href="driver_profile.php">
                                    <i class="fas fa-id-card me-2"></i> الملف الشخصي
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="driver_settings.php">
                                    <i class="fas fa-cog me-2"></i> الإعدادات
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="driver_logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Toggle -->
        <div class="status-toggle mb-4">
            <h5 class="mb-3">حالة التوصيل</h5>
            <form method="POST" id="statusForm" class="row">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status_available" 
                               value="available" <?php echo $driver['current_status'] === 'available' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status_available">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            متاح للطلبات
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status_busy" 
                               value="busy" <?php echo $driver['current_status'] === 'busy' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status_busy">
                            <i class="fas fa-clock text-warning me-2"></i>
                            مشغول
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status_offline" 
                               value="offline" <?php echo $driver['current_status'] === 'offline' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status_offline">
                            <i class="fas fa-power-off text-danger me-2"></i>
                            غير متصل
                        </label>
                    </div>
                </div>
                <input type="hidden" name="update_status" value="1">
            </form>
        </div>

        <!-- Performance Overview -->
        <div class="performance-card mb-4">
            <h5 class="mb-3">نظرة عامة على الأداء</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>معدل القبول</span>
                        <span class="badge bg-primary"><?php echo number_format($performance['acceptance_rate'], 1); ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $performance['acceptance_rate']; ?>%"></div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>معدل الإكمال</span>
                        <span class="badge bg-success"><?php echo number_format($performance['completion_rate'], 1); ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $performance['completion_rate']; ?>%"></div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>التقييم</span>
                        <div class="rating-stars">
                            <?php
                            $rating = round($performance['average_rating']);
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>المسافة الإجمالية</span>
                        <span class="badge bg-info"><?php echo number_format($performance['total_distance'], 1); ?> كم</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3><?php echo count($current_requests); ?></h3>
                    <p>الطلبات الحالية</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?php echo count($completed_requests); ?></h3>
                    <p>الطلبات المكتملة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3><?php echo count($pending_requests); ?></h3>
                    <p>الطلبات المعلقة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3><?php echo formatCurrency($earnings['total']); ?></h3>
                    <p>أرباح الشهر</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Orders Section -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">الطلبات المتاحة</h5>
                            <div class="d-flex align-items-center">
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                    <label class="form-check-label" for="autoRefresh">تحديث تلقائي</label>
                                </div>
                                <button id="refreshOrders" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-sync-alt"></i> تحديث
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_requests)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات متاحة حالياً
                            </div>
                        <?php else: ?>
                            <?php foreach ($available_requests as $request): ?>
                                <div class="order-card animate__animated animate__fadeIn">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            طلب #<?php echo htmlspecialchars($request['order_number']); ?>
                                            <?php if ($request['is_fragile']): ?>
                                                <span class="badge bg-warning">قابل للكسر</span>
                                            <?php endif; ?>
                                        </h6>
                                        <div>
                                            <span class="badge bg-primary"><?php echo formatCurrency($request['total_cost']); ?></span>
                                            <span class="badge bg-<?php echo $request['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                <?php echo $request['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-building text-primary me-2"></i>
                                            <span><?php echo htmlspecialchars($request['company_name']); ?></span>
                                            <a href="tel:<?php echo htmlspecialchars($request['company_phone']); ?>" class="btn btn-sm btn-outline-primary ms-2">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        </div>
                                        
                                        <div class="location-details">
                                            <div class="pickup mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                                    <span><?php echo htmlspecialchars($request['pickup_location']); ?></span>
                                                    <?php if ($request['pickup_location_link']): ?>
                                                        <a href="<?php echo htmlspecialchars($request['pickup_location_link']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-danger ms-2">
                                                            <i class="fas fa-map"></i> خريطة
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="delivery">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-flag-checkered text-success me-2"></i>
                                                    <span><?php echo htmlspecialchars($request['delivery_location']); ?></span>
                                                    <?php if ($request['delivery_location_link']): ?>
                                                        <a href="<?php echo htmlspecialchars($request['delivery_location_link']); ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-success ms-2">
                                                            <i class="fas fa-map"></i> خريطة
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-box me-1"></i>
                                                    عدد القطع: <?php echo $request['items_count']; ?>
                                                </small>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    موعد التسليم: <?php echo date('Y/m/d H:i', strtotime($request['delivery_date'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-history me-1"></i>
                                            <?php echo timeAgo($request['created_at']); ?>
                                        </small>
                                        <form method="POST" action="accept_order.php" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-check me-1"></i> قبول الطلب
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">التنبيهات</h5>
                            <a href="#" class="btn btn-sm btn-link">عرض الكل</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد تنبيهات جديدة
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?> animate__animated animate__fadeIn">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo timeAgo($notification['created_at']); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0 small">
                                        <?php echo htmlspecialchars($notification['message']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Auto-submit status form when radio button changes
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', () => {
                document.getElementById('statusForm').submit();
            });
        });

        // Real-time updates using AJAX
        function checkForUpdates() {
            $.ajax({
                url: 'check_updates.php',
                method: 'GET',
                success: function(response) {
                    if (response.new_orders) {
                        location.reload();
                    }
                }
            });
        }

        // Check for updates every 30 seconds
        setInterval(checkForUpdates, 30000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Animate stats on scroll
        function animateStats() {
            const stats = document.querySelectorAll('.stats-card');
            stats.forEach(stat => {
                const rect = stat.getBoundingClientRect();
                if (rect.top <= window.innerHeight && rect.bottom >= 0) {
                    stat.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }

        window.addEventListener('scroll', animateStats);
        animateStats(); // Initial check

        function refreshOrders() {
            const ordersContainer = document.querySelector('.card-body');
            fetch('check_pending_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.count > 0) {
                        location.reload();
                    }
                });
        }

        // Auto refresh every 30 seconds if enabled
        let refreshInterval;
        const autoRefreshToggle = document.getElementById('autoRefresh');
        const refreshButton = document.getElementById('refreshOrders');

        function toggleAutoRefresh() {
            if (autoRefreshToggle.checked) {
                refreshInterval = setInterval(refreshOrders, 30000);
            } else {
                clearInterval(refreshInterval);
            }
        }

        autoRefreshToggle.addEventListener('change', toggleAutoRefresh);
        refreshButton.addEventListener('click', refreshOrders);

        // Initial setup
        toggleAutoRefresh();
    </script>
</body>
</html>
