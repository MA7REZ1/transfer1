<?php
session_start();
require_once '../config.php';

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

// Handle order status updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accept_order'])) {
        $order_id = $_POST['order_id'];
        $stmt = $conn->prepare("UPDATE driver_orders SET status = 'accepted', driver_id = :driver_id WHERE id = :order_id");
        $stmt->execute(['driver_id' => $driver_id, 'order_id' => $order_id]);
    } elseif (isset($_POST['start_delivery'])) {
        $order_id = $_POST['order_id'];
        $stmt = $conn->prepare("UPDATE driver_orders SET status = 'in_transit' WHERE id = :order_id AND driver_id = :driver_id");
        $stmt->execute(['order_id' => $order_id, 'driver_id' => $driver_id]);
    } elseif (isset($_POST['complete_delivery'])) {
        $order_id = $_POST['order_id'];
        $stmt = $conn->prepare("UPDATE driver_orders SET status = 'delivered' WHERE id = :order_id AND driver_id = :driver_id");
        $stmt->execute(['order_id' => $order_id, 'driver_id' => $driver_id]);
        
        // Update driver stats
        $stmt = $conn->prepare("UPDATE drivers SET total_trips = total_trips + 1 WHERE id = :driver_id");
        $stmt->execute(['driver_id' => $driver_id]);
    }
}

// Get driver's current orders
$stmt = $conn->prepare("SELECT do.*, o.order_number, o.customer_name, o.customer_phone, 
                c.name as company_name, c.phone as company_phone
                FROM driver_orders do
                JOIN orders o ON do.order_id = o.id
                JOIN companies c ON do.company_id = c.id
                WHERE do.driver_id = :driver_id
                AND do.status != 'delivered'
                ORDER BY do.created_at DESC");
$stmt->execute(['driver_id' => $driver_id]);
$orders_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get new order notifications
$stmt = $conn->prepare("SELECT dn.*, o.order_number, c.name as company_name
                      FROM driver_notifications dn
                      JOIN orders o ON dn.order_id = o.id
                      JOIN companies c ON o.company_id = c.id
                      WHERE dn.driver_id = :driver_id
                      AND dn.is_read = 0
                      ORDER BY dn.created_at DESC");
$stmt->execute(['driver_id' => $driver_id]);
$notifications_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get driver stats
$stmt = $conn->prepare("SELECT * FROM drivers WHERE id = :driver_id");
$stmt->execute(['driver_id' => $driver_id]);
$driver_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم السائق</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .order-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }
        .pending-order {
            background-color: #fff3cd;
        }
        .accepted-order {
            background-color: #d1e7dd;
        }
        .in-transit-order {
            background-color: #cfe2ff;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            padding: 5px 8px;
            border-radius: 50%;
            background-color: red;
            color: white;
            font-size: 12px;
        }
        .stats-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">لوحة تحكم السائق</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="modal" data-bs-target="#notificationsModal">
                            <i class="bi bi-bell"></i>
                            <?php if(count($notifications_result) > 0): ?>
                                <span class="notification-badge"><?php echo count($notifications_result); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">تسجيل الخروج</a>
                    </li>
                </ul>
                <span class="navbar-text">
                    مرحباً <?php echo $_SESSION['driver_username']; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Driver Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h5>التقييم</h5>
                    <p class="h3"><?php echo number_format($driver_stats['rating'], 1); ?> / 5</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h5>عدد الرحلات</h5>
                    <p class="h3"><?php echo $driver_stats['total_trips']; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h5>الحالة</h5>
                    <p class="h3"><?php echo $driver_stats['is_active'] == 1 ? 'نشط' : 'غير نشط'; ?></p>
                </div>
            </div>
        </div>

        <h2 class="mb-4">الطلبات الحالية</h2>
        
        <?php foreach($orders_result as $order): ?>
            <div class="order-card <?php echo $order['status'] == 'pending' ? 'pending-order' : ($order['status'] == 'accepted' ? 'accepted-order' : 'in-transit-order'); ?>">
                <div class="row">
                    <div class="col-md-8">
                        <h5>طلب رقم: <?php echo $order['order_number']; ?></h5>
                        <p><strong>الشركة:</strong> <?php echo $order['company_name']; ?> - <?php echo $order['company_phone']; ?></p>
                        <p><strong>العميل:</strong> <?php echo $order['customer_name']; ?> - <?php echo $order['customer_phone']; ?></p>
                        <p><strong>موقع الاستلام:</strong> <?php echo $order['pickup_location']; ?></p>
                        <p><strong>موقع التسليم:</strong> <?php echo $order['delivery_location']; ?></p>
                        <p><strong>الوقت:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <form method="POST" action="">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <?php if($order['status'] == 'pending'): ?>
                                <button type="submit" name="accept_order" class="btn btn-success mb-2">قبول الطلب</button>
                            <?php elseif($order['status'] == 'accepted'): ?>
                                <button type="submit" name="start_delivery" class="btn btn-primary mb-2">بدء التوصيل</button>
                            <?php elseif($order['status'] == 'in_transit'): ?>
                                <button type="submit" name="complete_delivery" class="btn btn-info mb-2">إتمام التوصيل</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Notifications Modal -->
    <div class="modal fade" id="notificationsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">الإشعارات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php 
                    foreach($notifications_result as $notification): 
                    ?>
                        <div class="alert alert-info">
                            <strong>طلب جديد #<?php echo $notification['order_number']; ?></strong>
                            <p><?php echo $notification['message']; ?></p>
                            <small>من <?php echo $notification['company_name']; ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Check for new notifications every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
