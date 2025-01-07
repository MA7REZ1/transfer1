<?php
require_once 'config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// جلب معلومات السائق
try {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$_SESSION['driver_id']]);
    $driver = $stmt->fetch();
    
    if (!$driver) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    die("خطأ في جلب معلومات السائق");
}

// جلب الطلبات المتاحة
try {
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE status = 'pending' 
        AND driver_id IS NULL 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $availableOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $availableOrders = [];
    error_log($e->getMessage());
}

// جلب الطلبات الحالية للسائق
try {
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE driver_id = ? 
        AND status IN ('accepted', 'picked_up') 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['driver_id']]);
    $currentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $currentOrders = [];
    error_log($e->getMessage());
}

// معالجة قبول الطلب
if (isset($_POST['accept_order'])) {
    $orderId = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $conn->beginTransaction();
        
        // التحقق من أن الطلب ما زال متاحاً
        $stmt = $conn->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND status = 'pending' AND driver_id IS NULL
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if ($order) {
            // تحديث حالة الطلب
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'accepted', 
                    driver_id = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['driver_id'], $orderId]);
            
            // تسجيل النشاط
            logActivity($_SESSION['driver_id'], 'accept_order', 'تم قبول الطلب رقم: ' . $order['order_number']);
            
            $conn->commit();
            header('Location: dashboard.php');
            exit;
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log($e->getMessage());
    }
}
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
        .navbar {
            background: linear-gradient(to right, #0d6efd, #0a58ca);
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .order-card {
            transition: transform 0.2s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 15px;
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
                        <a class="nav-link active" href="dashboard.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">طلباتي</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($driver['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-id-card me-2"></i>
                                    الملف الشخصي
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
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

    <div class="container">
        <!-- الطلبات الحالية -->
        <?php if (!empty($currentOrders)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    الطلبات الحالية
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($currentOrders as $order): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card order-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0">
                                        طلب #<?php echo htmlspecialchars($order['order_number']); ?>
                                    </h6>
                                    <span class="badge bg-primary status-badge">
                                        <?php echo getOrderStatus($order['status']); ?>
                                    </span>
                                </div>
                                <p class="card-text">
                                    <i class="fas fa-user me-2"></i>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-phone me-2"></i>
                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($order['pickup_location']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-flag-checkered me-2"></i>
                                    <?php echo htmlspecialchars($order['delivery_location']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-primary fw-bold">
                                        <?php echo formatCurrency($order['delivery_fee']); ?>
                                    </span>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                        تفاصيل الطلب
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- الطلبات المتاحة -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    الطلبات المتاحة
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($availableOrders)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    لا توجد طلبات متاحة حالياً
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($availableOrders as $order): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card order-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0">
                                        طلب #<?php echo htmlspecialchars($order['order_number']); ?>
                                    </h6>
                                    <span class="badge bg-success status-badge">متاح</span>
                                </div>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($order['pickup_location']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-flag-checkered me-2"></i>
                                    <?php echo htmlspecialchars($order['delivery_location']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-success fw-bold">
                                        <?php echo formatCurrency($order['delivery_fee']); ?>
                                    </span>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" name="accept_order" class="btn btn-success btn-sm">
                                            <i class="fas fa-check me-1"></i>
                                            قبول الطلب
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحديث الصفحة كل دقيقة للحصول على الطلبات الجديدة
        setInterval(function() {
            location.reload();
        }, 60000);
    </script>
</body>
</html> 