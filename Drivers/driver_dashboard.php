<?php
require_once 'config.php';
require_once 'driver_auth.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if driver is logged in
if (!isDriverLoggedIn()) {
    header('Location: driver_login.php');
    exit;
}

// Get current driver info
$driver = getCurrentDriver();
if (!$driver) {
    logoutDriver();
    header('Location: driver_login.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    if (updateDriverStatus($new_status)) {
        $driver['current_status'] = $new_status;
    }
}

// Get requests
$available_requests = getAvailablerequests();
$current_requests = getDriverrequests();
$pending_requests = getDriverrequests('pending');
$completed_requests = getDriverrequests('delivered');

// Get earnings
$earnings = getDriverEarnings('month');

// Get notifications
$notifications = getDriverNotifications(5, true);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم السائق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .notification-card.unread {
            border-right: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
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
                        <a class="nav-link active" href="driver_dashboard.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_orders.php">الطلبات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_earnings.php">الأرباح</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($driver['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="driver_profile.php">الملف الشخصي</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="driver_logout.php">تسجيل الخروج</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Toggle -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" id="statusForm">
                    <div class="row">
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
                    </div>
                    <input type="hidden" name="update_status" value="1">
                </form>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon"><i class="fas fa-box"></i></div>
                    <h3><?php echo count($current_requests); ?></h3>
                    <p>الطلبات الحالية</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <h3><?php echo count($completed_requests); ?></h3>
                    <p>الطلبات المكتملة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon"><i class="fas fa-clock"></i></div>
                    <h3><?php echo count($pending_requests); ?></h3>
                    <p>الطلبات المعلقة</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    <h3><?php echo formatCurrency($earnings['total']); ?></h3>
                    <p>أرباح الشهر</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Orders Section -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">الطلبات المتاحة</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_requests)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات متاحة حالياً
                            </div>
                        <?php else: ?>
                            <?php foreach ($available_requests as $request): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <h6 class="mb-0">طلب #<?php echo $request['order_number']; ?></h6>
                                        <span class="badge bg-primary">متاح للقبول</span>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>العميل:</strong> <?php echo htmlspecialchars($request['customer_name']); ?></p>
                                            <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($request['customer_phone']); ?></p>
                                            <p><strong>موقع الاستلام:</strong> <?php echo htmlspecialchars($request['pickup_location']); ?></p>
                                            <p><strong>موقع التوصيل:</strong> <?php echo htmlspecialchars($request['delivery_location']); ?></p>
                                            <?php if ($request['additional_notes']): ?>
                                                <p><strong>ملاحظات:</strong> <?php echo htmlspecialchars($request['additional_notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>نوع الطلب:</strong> <?php echo $request['order_type'] === 'delivery' ? 'توصيل' : 'نقل'; ?></p>
                                            <p><strong>عدد القطع:</strong> <?php echo $request['items_count']; ?></p>
                                            <p><strong>المبلغ:</strong> <?php echo formatCurrency($request['total_cost']); ?></p>
                                            <p><strong>طريقة الدفع:</strong> <?php echo getArabicPaymentMethod($request['payment_method']); ?></p>
                                            <?php if ($request['is_fragile']): ?>
                                                <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> شحنة قابلة للكسر</p>
                                            <?php endif; ?>
                                            <form method="POST" action="accept_order.php">
                                                <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check me-1"></i>
                                                    قبول الطلب
                                                </button>
                                                <?php if ($request['pickup_location_link']): ?>
                                                    <a href="<?php echo htmlspecialchars($request['pickup_location_link']); ?>" 
                                                       target="_blank" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        موقع الاستلام
                                                    </a>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">الإلبات الحالية</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($current_requests)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد إلبات حالية
                            </div>
                        <?php else: ?>
                            <?php foreach ($current_requests as $request): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <h6 class="mb-0">طلب #<?php echo $request['order_number']; ?></h6>
                                        <span class="badge bg-<?php echo getStatusBadgeClass($request['status']); ?>">
                                            <?php echo getArabicStatus($request['status']); ?>
                                        </span>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>العميل:</strong> <?php echo htmlspecialchars($request['customer_name']); ?></p>
                                            <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($request['customer_phone'] ?? 'غير متوفر'); ?></p>
                                            <p><strong>العنوان:</strong> <?php echo htmlspecialchars($request['delivery_address'] ?? 'غير متوفر'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>المبلغ:</strong> <?php echo formatCurrency($request['total_cost']); ?></p>
                                            <div class="btn-group">
                                                <a href="driver_order.php?id=<?php echo $request['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye me-1"></i>
                                                    تفاصيل الطلب
                                                </a>
                                                <?php if ($request['status'] === 'accepted'): ?>
                                                    <form method="POST" action="update_order_status.php" style="display: inline;">
                                                        <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="status" value="in_transit">
                                                        <button type="submit" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-truck me-1"></i>
                                                            في الطريق
                                                        </button>
                                                    </form>
                                                <?php elseif ($request['status'] === 'in_transit'): ?>
                                                    <form method="POST" action="update_order_status.php" style="display: inline;">
                                                        <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            تم التوصيل
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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
                    <div class="card-header">
                        <h5 class="mb-0">الإشعارات</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notifications)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد إشعارات جديدة
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <?php echo formatArabicDate($notification['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit status form when changed
        document.querySelectorAll('input[name="status"]').forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('statusForm').submit();
            });
        });
    </script>
</body>
</html>
<?php
// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'accepted':
            return 'info';
        case 'in_transit':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
