<?php
require_once 'config.php';
require_once 'driver_auth.php';

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

// Get current orders
$current_orders = getDriverOrders('in_transit');
$pending_orders = getDriverOrders('pending');
$completed_orders = getDriverOrders('delivered');

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
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }

        .navbar {
            background: white;
            box-shadow: var(--box-shadow);
        }

        .stats-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            width: 48px;
            height: 48px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-card .value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stats-card .label {
            color: #64748b;
            font-size: 0.875rem;
        }

        .order-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
        }

        .order-card .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-card .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-card .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .order-card .order-status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .order-card .order-status.in-transit {
            background: #dbeafe;
            color: #1e40af;
        }

        .order-card .order-status.delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .notification-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
        }

        .notification-card.unread {
            border-right: 4px solid var(--primary-color);
        }

        .status-toggle {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .status-toggle .form-check {
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-toggle .form-check:hover {
            background: #f8fafc;
        }

        .status-toggle .form-check-input {
            margin-left: 1rem;
        }

        .status-toggle .form-check.active {
            background: #dbeafe;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
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
                            <i class="fas fa-home me-1"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_orders.php">
                            <i class="fas fa-box me-1"></i>
                            الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_earnings.php">
                            <i class="fas fa-dollar-sign me-1"></i>
                            الأرباح
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <img src="<?php echo $driver['profile_image'] ? 'uploads/driver/' . $driver['profile_image'] : 'assets/img/default-avatar.png'; ?>" 
                                 class="rounded-circle me-2" 
                                 width="32" height="32" 
                                 alt="Profile">
                            <?php echo htmlspecialchars($driver['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="driver_profile.php">
                                    <i class="fas fa-user me-2"></i>
                                    الملف الشخصي
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="driver_logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Status Toggle -->
        <div class="status-toggle">
            <form method="POST" id="statusForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check <?php echo $driver['current_status'] === 'available' ? 'active' : ''; ?>">
                            <input class="form-check-input" type="radio" name="status" id="status_available" 
                                   value="available" <?php echo $driver['current_status'] === 'available' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_available">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                متاح للطلبات
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check <?php echo $driver['current_status'] === 'busy' ? 'active' : ''; ?>">
                            <input class="form-check-input" type="radio" name="status" id="status_busy" 
                                   value="busy" <?php echo $driver['current_status'] === 'busy' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status_busy">
                                <i class="fas fa-clock text-warning me-2"></i>
                                مشغول
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check <?php echo $driver['current_status'] === 'offline' ? 'active' : ''; ?>">
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon" style="background: var(--primary-color);">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="value"><?php echo count($current_orders); ?></div>
                    <div class="label">الطلبات الحالية</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon" style="background: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="value"><?php echo count($completed_orders); ?></div>
                    <div class="label">الطلبات المكتملة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon" style="background: var(--warning-color);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="value"><?php echo count($pending_orders); ?></div>
                    <div class="label">الطلبات المعلقة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="icon" style="background: var(--secondary-color);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="value"><?php echo number_format($earnings['total'], 2); ?></div>
                    <div class="label">أرباح الشهر</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Current Orders -->
            <div class="col-md-8">
                <h4 class="mb-3">الطلبات الحالية</h4>
                <?php if (empty($current_orders)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد طلبات حالية
                    </div>
                <?php else: ?>
                    <?php foreach ($current_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-number">#<?php echo $order['order_number']; ?></span>
                                <span class="order-status in-transit">قيد التوصيل</span>
                            </div>
                            <div class="mb-3">
                                <strong>العميل:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <strong>الهاتف:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                <strong>العنوان:</strong> <?php echo htmlspecialchars($order['delivery_location']); ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>المبلغ:</strong> <?php echo number_format($order['total_cost'], 2); ?> ريال
                                </div>
                                <div>
                                    <a href="driver_order.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        تفاصيل الطلب
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Notifications -->
            <div class="col-md-4">
                <h4 class="mb-3">الإشعارات</h4>
                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد إشعارات جديدة
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <i class="fas fa-bell text-primary me-2"></i>
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge bg-primary">جديد</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo date('Y/m/d H:i', strtotime($notification['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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

        // Update active status in form-check divs
        document.querySelectorAll('input[name="status"]').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.form-check').forEach(div => {
                    div.classList.remove('active');
                });
                this.closest('.form-check').classList.add('active');
            });
        });
    </script>
</body>
</html>
