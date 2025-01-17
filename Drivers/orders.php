<?php
require_once '../config.php';

// التحقق من تسجيل دخول السائق
if (!isset($_SESSION['driver_id']) || empty($_SESSION['driver_id'])) {
    header('Location: login.php');
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];
if ($driver_id <= 0) {
    header('Location: login.php');
    exit;
}

// معالجة قبول الطلب
if (isset($_POST['accept_order']) && isset($_POST['request_id'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $conn->prepare("UPDATE requests SET driver_id = ?, status = 'accepted' WHERE id = ? AND status = 'pending'");
        if ($stmt->execute([$driver_id, $request_id])) {
            // إضافة إشعار للسائق
            $stmt = $conn->prepare("INSERT INTO driver_notifications (driver_id, message, type) VALUES (?, ?, 'order_accepted')");
            $stmt->execute([$driver_id, "تم قبول الطلب رقم " . $request_id . " بنجاح"]);
        }
    } catch (PDOException $e) {
        // يمكن إضافة معالجة الأخطاء هنا
    }
}

// معالجة تحديث حالة الطلب
if (isset($_POST['update_status']) && isset($_POST['request_id']) && isset($_POST['new_status'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = htmlspecialchars($_POST['new_status'], ENT_QUOTES, 'UTF-8');
    
    // التحقق من أن الحالة الجديدة صالحة
    $valid_statuses = ['in_transit', 'delivered'];
    if (in_array($new_status, $valid_statuses)) {
        try {
            $conn->beginTransaction();

            // تحديث حالة الطلب
            $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ? AND driver_id = ?");
            if ($stmt->execute([$new_status, $request_id, $driver_id])) {
                // إضا تم التوصيل، قم بتحديث عدد الطلبات المكتملة للسائق
                if ($new_status === 'delivered') {
                    $stmt = $conn->prepare("UPDATE drivers SET completed_orders = completed_orders + 1 WHERE id = ?");
                    $stmt->execute([$driver_id]);
                }

                // إضافة إشعار للسائق
                $message = $new_status == 'in_transit' ? "تم بدء توصيل الطلب رقم " : "تم تسليم الطلب رقم ";
                $stmt = $conn->prepare("INSERT INTO driver_notifications (driver_id, message, type) VALUES (?, ?, ?)");
                $stmt->execute([$driver_id, $message . $request_id, $new_status]);
            }

            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            // يمكن إضافة معالجة الأخطاء هنا
        }
    }
}

// معالجة إلغاء الطلب
if (isset($_POST['cancel_order']) && isset($_POST['request_id'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // تحديث حالة الطلب إلى معلق وإزالة ارتباط السائق
        $stmt = $conn->prepare("UPDATE requests SET status = 'pending', driver_id = NULL WHERE id = ? AND driver_id = ? AND status IN ('accepted', 'in_transit')");
        if ($stmt->execute([$request_id, $driver_id])) {
            // إضافة إشعار للسائق
            $stmt = $conn->prepare("INSERT INTO driver_notifications (driver_id, message, type) VALUES (?, ?, 'order_cancelled')");
            $stmt->execute([$driver_id, "تم إلغاء الطلب رقم " . $request_id . " وإعادته للقائمة العامة"]);
            
            // إعادة توجيه لتحديث الصفحة
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } catch (PDOException $e) {
        // يمكن إضافة معالجة الأخطاء هنا
    }
}

// جضافة معالجة إلغاء الطلب
if (isset($_POST['cancel_order']) && isset($_POST['request_id'])) {
    $request_id = filter_var($_POST['request_id'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $conn->prepare("UPDATE requests SET status = 'cancelled' WHERE id = ? AND driver_id = ? AND status IN ('accepted', 'in_transit')");
        if ($stmt->execute([$request_id, $driver_id])) {
            // إضافة إشعار للسائق
            $stmt = $conn->prepare("INSERT INTO driver_notifications (driver_id, message, type) VALUES (?, ?, 'order_cancelled')");
            $stmt->execute([$driver_id, "تم إلغاء الطلب رقم " . $request_id]);
        }
    } catch (PDOException $e) {
        // يمكن إضافة معالجة الأخطاء هنا
    }
}

// جلب الطلبات المتاحة
$available_orders = [];
try {
    $stmt = $conn->prepare("
        SELECT r.*, c.name as company_name, c.phone as company_phone 
        FROM requests r 
        JOIN companies c ON r.company_id = c.id 
        WHERE r.status = 'pending' 
        AND r.driver_id IS NULL 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $available_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // يمكن إضافة معالجة الأخطاء هنا
}

// جلب طلبات السائق
$my_orders = [];
try {
    $stmt = $conn->prepare("
        SELECT r.*, c.name as company_name, c.phone as company_phone 
        FROM requests r 
        JOIN companies c ON r.company_id = c.id 
        WHERE r.driver_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$driver_id]);
    $my_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // يمكن إضافة معالجة الأخطاء هنا
}

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلبات - لوحة السائق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .dropdown-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .nav-tabs {
            border: none;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .nav-tabs .nav-link i {
            margin-left: 0.5rem;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .order-card {
            transition: transform 0.2s;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .btn-accept {
            background-color: var(--primary-color);
            color: white;
            border-radius: 20px;
            padding: 8px 20px;
            transition: all 0.3s;
        }

        .btn-accept:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .order-details {
            font-size: 0.9rem;
        }

        .location-link {
            color: var(--primary-color);
            text-decoration: none;
        }

        .location-link:hover {
            text-decoration: underline;
        }

        .status-btn {
            border-radius: 25px;
            padding: 10px 20px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .status-btn i {
            font-size: 1.1em;
        }

        .status-btn:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .status-btn.pending {
            background-color: #fbbf24;
            color: #000;
        }

        .status-btn.accepted {
            background-color: #3b82f6;
            color: white;
        }

        .status-btn.in-transit {
            background-color: #06b6d4;
            color: white;
        }

        .status-btn.delivered {
            background-color: #22c55e;
            color: white;
        }

        .status-btn.cancelled {
            background-color: #ef4444;
            color: white;
        }

        .status-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            background-color: #e5e7eb;
            color: #6b7280;
        }

        .status-progress {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
            padding: 0 10px;
        }

        .status-progress::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e5e7eb;
            z-index: 1;
        }

        .status-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #e5e7eb;
            z-index: 2;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-step.active {
            border-color: var(--primary-color);
            background-color: var(--primary-color);
            color: white;
        }

        .status-step.completed {
            border-color: #22c55e;
            background-color: #22c55e;
            color: white;
        }

        .btn-group-vertical {
            width: 100%;
            gap: 8px;
        }

        .cancel-btn {
            background-color: #fff;
            color: #ef4444;
            border: 2px solid #ef4444;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background-color: #ef4444;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="orders.php">
                <i class="fas fa-truck me-2"></i>
                نظام توصيل الطلبات
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-box"></i>
                            الطلبات المتاحة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php#my-orders">
                            <i class="fas fa-list"></i>
                            طلباتي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user"></i>
                            الملف الشخصي
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            تسجيل الخروج
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Order Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#available" data-bs-toggle="tab">
                    <i class="fas fa-list"></i>
                    الطلبات المتاحة
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#my-orders" data-bs-toggle="tab">
                    <i class="fas fa-check-circle"></i>
                    طلباتي
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Available Orders Tab -->
            <div class="tab-pane fade show active" id="available">
                <div class="row">
                    <?php foreach ($available_orders as $order): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card order-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($order['order_number']) ?></h5>
                                        <span class="status-badge bg-primary"><?= htmlspecialchars($order['order_type']) ?></span>
                                    </div>
                                    
                                    <div class="order-details">
                                        <p class="mb-2">
                                            <i class="fas fa-building me-2"></i>
                                            <?= htmlspecialchars($order['company_name']) ?>
                                            <a href="<?= !empty($order['company_phone']) ? 'https://wa.me/' . formatPhoneForWhatsApp($order['company_phone']) : '#' ?>" 
                                               target="_blank" 
                                               class="btn btn-success btn-sm ms-2 <?= empty($order['company_phone']) ? 'disabled' : '' ?>">
                                                <i class="fab fa-whatsapp"></i>
                                                واتساب الشركة
                                            </a>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-user me-2"></i>
                                            <?= htmlspecialchars($order['customer_name']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-phone me-2"></i>
                                            <?= htmlspecialchars($order['customer_phone']) ?>
                                            <a href="<?= !empty($order['customer_phone']) ? 'https://wa.me/' . formatPhoneForWhatsApp($order['customer_phone']) : '#' ?>" 
                                               target="_blank" 
                                               class="btn btn-success btn-sm ms-2 <?= empty($order['customer_phone']) ? 'disabled' : '' ?>">
                                                <i class="fab fa-whatsapp"></i>
                                                واتساب
                                            </a>
                                            <a href="<?= !empty($order['customer_phone']) ? 'tel:' . formatPhoneForWhatsApp($order['customer_phone']) : '#' ?>" 
                                               class="btn btn-primary btn-sm ms-1 <?= empty($order['customer_phone']) ? 'disabled' : '' ?>">
                                                <i class="fas fa-phone"></i>
                                                اتصال
                                            </a>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            من: 
                                            <?php if ($order['pickup_location_link']): ?>
                                                <a href="<?= htmlspecialchars($order['pickup_location_link']) ?>" target="_blank" class="location-link">
                                                    <?= htmlspecialchars($order['pickup_location']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($order['pickup_location']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            إلى: 
                                            <?php if ($order['delivery_location_link']): ?>
                                                <a href="<?= htmlspecialchars($order['delivery_location_link']) ?>" target="_blank" class="location-link">
                                                    <?= htmlspecialchars($order['delivery_location']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($order['delivery_location']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-box me-2"></i>
                                            عدد القطع: <?= htmlspecialchars($order['items_count']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-money-bill me-2"></i>
                                            رسوم التحصيل: <?= htmlspecialchars($order['total_cost']) ?> ريال
                                        </p>

                                        <div class="status-progress mb-4">
                                            <div class="status-step <?= in_array($order['status'], ['pending', 'accepted', 'in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-clock fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= in_array($order['status'], ['accepted', 'in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-check fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= in_array($order['status'], ['in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-truck fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= $order['status'] == 'delivered' ? 'completed' : '' ?>">
                                                <i class="fas fa-flag-checkered fa-sm"></i>
                                            </div>
                                        </div>

                                        <div class="btn-group-vertical">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="accept_order" class="status-btn accepted">
                                                        <i class="fas fa-check"></i>
                                                        قبول الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'accepted'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="new_status" value="in_transit">
                                                    <button type="submit" name="update_status" class="status-btn in-transit">
                                                        <i class="fas fa-truck"></i>
                                                        بدء التوصيل
                                                    </button>
                                                </form>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="cancel_order" class="status-btn cancel-btn">
                                                        <i class="fas fa-times"></i>
                                                        إلغاء الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'in_transit'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="new_status" value="delivered">
                                                    <button type="submit" name="update_status" class="status-btn delivered">
                                                        <i class="fas fa-check-circle"></i>
                                                        تم التوصيل
                                                    </button>
                                                </form>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="cancel_order" class="status-btn cancel-btn">
                                                        <i class="fas fa-times"></i>
                                                        إلغاء الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'delivered'): ?>
                                                <button type="button" class="status-btn delivered" disabled>
                                                    <i class="fas fa-check-double"></i>
                                                    تم التوصيل
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'cancelled'): ?>
                                                <button type="button" class="status-btn cancelled" disabled>
                                                    <i class="fas fa-times-circle"></i>
                                                    تم الإلغاء
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($available_orders)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات متاحة حالياً
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Orders Tab -->
            <div class="tab-pane fade" id="my-orders">
                <div class="row">
                    <?php foreach ($my_orders as $order): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card order-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($order['order_number']) ?></h5>
                                        <span class="status-badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                            <?= getStatusArabic($order['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="order-details">
                                        <p class="mb-2">
                                            <i class="fas fa-building me-2"></i>
                                            <?= htmlspecialchars($order['company_name']) ?>
                                            <a href="<?= !empty($order['company_phone']) ? 'https://wa.me/' . formatPhoneForWhatsApp($order['company_phone']) : '#' ?>" 
                                               target="_blank" 
                                               class="btn btn-success btn-sm ms-2 <?= empty($order['company_phone']) ? 'disabled' : '' ?>">
                                                <i class="fab fa-whatsapp"></i>
                                                واتساب الشركة
                                            </a>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-user me-2"></i>
                                            <?= htmlspecialchars($order['customer_name']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-phone me-2"></i>
                                            <?= htmlspecialchars($order['customer_phone']) ?>
                                            <a href="<?= !empty($order['customer_phone']) ? 'https://wa.me/' . formatPhoneForWhatsApp($order['customer_phone']) : '#' ?>" 
                                               target="_blank" 
                                               class="btn btn-success btn-sm ms-2 <?= empty($order['customer_phone']) ? 'disabled' : '' ?>">
                                                <i class="fab fa-whatsapp"></i>
                                                واتساب
                                            </a>
                                            <a href="<?= !empty($order['customer_phone']) ? 'tel:' . formatPhoneForWhatsApp($order['customer_phone']) : '#' ?>" 
                                               class="btn btn-primary btn-sm ms-1 <?= empty($order['customer_phone']) ? 'disabled' : '' ?>">
                                                <i class="fas fa-phone"></i>
                                                اتصال
                                            </a>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            من: 
                                            <?php if ($order['pickup_location_link']): ?>
                                                <a href="<?= htmlspecialchars($order['pickup_location_link']) ?>" target="_blank" class="location-link">
                                                    <?= htmlspecialchars($order['pickup_location']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($order['pickup_location']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            إلى: 
                                            <?php if ($order['delivery_location_link']): ?>
                                                <a href="<?= htmlspecialchars($order['delivery_location_link']) ?>" target="_blank" class="location-link">
                                                    <?= htmlspecialchars($order['delivery_location']) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= htmlspecialchars($order['delivery_location']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-box me-2"></i>
                                            عدد القطع: <?= htmlspecialchars($order['items_count']) ?>
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-money-bill me-2"></i>
                                            رسوم التحصيل: <?= htmlspecialchars($order['total_cost']) ?> ريال
                                        </p>

                                        <div class="status-progress mb-4">
                                            <div class="status-step <?= in_array($order['status'], ['pending', 'accepted', 'in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-clock fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= in_array($order['status'], ['accepted', 'in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-check fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= in_array($order['status'], ['in_transit', 'delivered']) ? 'completed' : '' ?>">
                                                <i class="fas fa-truck fa-sm"></i>
                                            </div>
                                            <div class="status-step <?= $order['status'] == 'delivered' ? 'completed' : '' ?>">
                                                <i class="fas fa-flag-checkered fa-sm"></i>
                                            </div>
                                        </div>

                                        <div class="btn-group-vertical">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="accept_order" class="status-btn accepted">
                                                        <i class="fas fa-check"></i>
                                                        قبول الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'accepted'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="new_status" value="in_transit">
                                                    <button type="submit" name="update_status" class="status-btn in-transit">
                                                        <i class="fas fa-truck"></i>
                                                        بدء التوصيل
                                                    </button>
                                                </form>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="cancel_order" class="status-btn cancel-btn">
                                                        <i class="fas fa-times"></i>
                                                        إلغاء الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'in_transit'): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <input type="hidden" name="new_status" value="delivered">
                                                    <button type="submit" name="update_status" class="status-btn delivered">
                                                        <i class="fas fa-check-circle"></i>
                                                        تم التوصيل
                                                    </button>
                                                </form>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="request_id" value="<?= $order['id'] ?>">
                                                    <button type="submit" name="cancel_order" class="status-btn cancel-btn">
                                                        <i class="fas fa-times"></i>
                                                        إلغاء الطلب
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'delivered'): ?>
                                                <button type="button" class="status-btn delivered" disabled>
                                                    <i class="fas fa-check-double"></i>
                                                    تم التوصيل
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($order['status'] == 'cancelled'): ?>
                                                <button type="button" class="status-btn cancelled" disabled>
                                                    <i class="fas fa-times-circle"></i>
                                                    تم الإلغاء
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($my_orders)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                لا توجد طلبات خاصة بك حالياً
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate Bootstrap tabs
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('.nav-tabs a'))
            triggerTabList.forEach(function(triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl)
                triggerEl.addEventListener('click', function(event) {
                    event.preventDefault()
                    tabTrigger.show()
                })
            })
        });
    </script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'accepted':
            return 'primary';
        case 'in_transit':
            return 'info';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

function getStatusArabic($status) {
    switch ($status) {
        case 'pending':
            return 'قيد الانتظار';
        case 'accepted':
            return 'تم القبول';
        case 'in_transit':
            return 'جاري التوصيل';
        case 'delivered':
            return 'تم التوصيل';
        case 'cancelled':
            return 'ملغي';
        default:
            return $status;
    }
}

function formatPhoneForWhatsApp($phone) {
    if (empty($phone)) return '';
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Add Saudi country code if not present
    if (strlen($phone) == 9) {
        $phone = '966' . $phone;
    } else if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        $phone = '966' . substr($phone, 1);
    }
    return $phone;
}
?> 