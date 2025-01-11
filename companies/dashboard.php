<?php
require_once '../config.php';

if (!isset($_SESSION['company_email'])) {
      header("Location: orders.php");
    exit();
}
    


$company_id = $_SESSION['company_id'];



// Get unread notifications count
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM company_notifications 
    WHERE company_id = ? AND is_read = 0
");
$stmt->execute([$company_id]);
$unread_notifications = $stmt->fetchColumn();

// Get recent notifications with complaint information
$stmt = $conn->prepare("
    SELECT 
        n.*,
        CASE 
            WHEN n.type = 'complaint_response' THEN c.complaint_number 
            ELSE NULL 
        END as complaint_number,
        CASE 
            WHEN n.type = 'complaint_response' THEN '#'
            ELSE n.link 
        END as link,
        CASE 
            WHEN n.is_read = 0 THEN 0
            ELSE 1
        END as is_read
    FROM company_notifications n
    LEFT JOIN complaints c ON n.reference_id = c.id
    WHERE n.company_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$stmt->execute([$company_id]);
$notifications = $stmt->fetchAll();

// Get company information
$stmt = $conn->prepare("SELECT name, logo FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get delivery fee from settings
$stmt = $conn->query("SELECT value FROM settings WHERE name = 'delivery_fee'");
$delivery_fee = floatval($stmt->fetchColumn() ?: 20);

// Get company statistics
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
            (SELECT SUM(CASE 
                WHEN status = 'delivered' AND payment_method = 'cash'
                THEN total_cost
                ELSE 0 
            END) - SUM(delivery_fee)
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
$stmt->execute([$_SESSION['company_id'], $_SESSION['company_id']]);
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
        'amount_due' => 0
    ];
}

// Get recent requests with driver information
$stmt = $conn->prepare("
    SELECT r.*, d.username as driver_name, d.phone as driver_phone
    FROM requests r
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.company_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['company_id']]);
$requests = $stmt->fetchAll();

// Get requests by status
$stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM requests
    WHERE company_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
");
$stmt->execute([$_SESSION['company_id']]);
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get monthly requests data
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_requests,
        SUM(total_cost) as total_revenue
    FROM requests
    WHERE company_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute([$_SESSION['company_id']]);
$monthly_data = $stmt->fetchAll();

// Get top performing drivers for this company
$stmt = $conn->prepare("
    SELECT 
        d.username,
        COUNT(r.id) as total_deliveries,
        AVG(dr.rating) as avg_rating,
        SUM(r.total_cost) as total_revenue
    FROM drivers d
    JOIN requests r ON d.id = r.driver_id
    LEFT JOIN driver_ratings dr ON r.id = dr.request_id
    WHERE r.company_id = ?
    AND r.status = 'delivered'
    AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY d.id, d.username
    ORDER BY total_deliveries DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['company_id']]);
$top_drivers = $stmt->fetchAll();

// Get delivery performance metrics
$stmt = $conn->prepare("
    SELECT 
        AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_delivery_time,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) * 100.0 / COUNT(*) as completion_rate,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) * 100.0 / COUNT(*) as cancellation_rate
    FROM requests
    WHERE company_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$_SESSION['company_id']]);
$performance_metrics = $stmt->fetch();

// Get customer satisfaction metrics
$stmt = $conn->prepare("
    SELECT 
        AVG(dr.rating) as avg_rating,
        COUNT(*) as total_ratings,
        SUM(CASE WHEN dr.rating >= 4 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as satisfaction_rate
    FROM requests r
    JOIN driver_ratings dr ON r.id = dr.request_id
    WHERE r.company_id = ?
    AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$_SESSION['company_id']]);
$satisfaction_metrics = $stmt->fetch();

// Get active complaints
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM complaints 
    WHERE company_id = ? 
    AND status IN ('new', 'in_progress')
");
$stmt->execute([$_SESSION['company_id']]);
$ب = $stmt->fetchColumn();

// Add this after the ب query
$stmt = $conn->prepare("
    SELECT 
        cr.*, c.complaint_number, c.subject,
        a.username as admin_name
    FROM complaint_responses cr
    JOIN complaints c ON cr.complaint_id = c.id
    JOIN admins a ON cr.admin_id = a.id
    WHERE c.company_id = ?
    AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY cr.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['company_id']]);
$complaint_responses = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html dir="rtl" lang="ar">

<body>
    <?php include '../includes/comHeader.php'; ?>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="profile.php">
                <?php if (!empty($company['logo'])): ?>
                    <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="rounded">
                <?php else: ?>
                    <i class="bi bi-building"></i>
                <?php endif; ?>
                <span class="company-name"><?php echo htmlspecialchars($company['name']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#requests"><i class="bi bi-list-check"></i> الطلبات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#statistics"><i class="bi bi-graph-up"></i> الإحصائيات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="staff.php"><i class="bi bi-people"></i> إدارة الموظفين</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="complaints.php">
                            <i class="bi bi-exclamation-circle"></i> الشكاوى
                            <?php if ($ب > 0): ?>
                                <span class="badge bg-danger"><?php echo $ب; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                        <h6 class="dropdown-header">الإشعارات</h6>
                        <?php if (empty($notifications)): ?>
                            <div class="dropdown-item text-muted">لا توجد إشعارات</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                   onclick="handleNotificationClick(<?php echo $notification['id']; ?>, '<?php echo htmlspecialchars($notification['link']); ?>', event)"
                                   data-notification-id="<?php echo $notification['id']; ?>"
                                   data-type="<?php echo htmlspecialchars($notification['type']); ?>"
                                   <?php if ($notification['type'] === 'complaint_response' && $notification['complaint_number']): ?>
                                   data-complaint-number="<?php echo htmlspecialchars($notification['complaint_number']); ?>"
                                   <?php endif; ?>>
                                    <div class="notification-content">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="#" onclick="markAllNotificationsAsRead(event)">
                                تعليم الكل كمقروء
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-gradient">
                    <div class="card-body" style="background: linear-gradient(45deg, #4158D0, #C850C0);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">الطلبات النشطة</h6>
                                <h3 class="mb-0 text-white"><?php echo $stats['active_count']; ?></h3>
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
                    <div class="card-body" style="background: linear-gradient(45deg, #FF8008, #FFC837);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">قيد الانتظار</h6>
                                <h3 class="mb-0 text-white"><?php echo $stats['pending_count']; ?></h3>
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
                    <div class="card-body" style="background: linear-gradient(45deg, #11998e, #38ef7d);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">تم التوصيل</h6>
                                <h3 class="mb-0 text-white"><?php echo $stats['delivered_count']; ?></h3>
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
                    <div class="card-body" style="background: linear-gradient(45deg, #0082c8, #0082c8);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">سعر التوصيل</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($delivery_fee, 2); ?> ريال</h3>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Status Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #FF416C, #FF4B2B);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">المبلغ المستحق عليه (نقدي)</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['cash_in_hand'], 2); ?> ريال</h3>
                                <small class="text-white">المبلغ بعد خصم التوصيل: <?php echo number_format($stats['amount_owed'], 2); ?> ريال</small>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card">
                    <div class="card-body" style="background: linear-gradient(45deg, #11998e, #38ef7d);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-white mb-2">المبلغ المستحق له</h6>
                                <h3 class="mb-0 text-white"><?php echo number_format($stats['amount_due'], 2); ?> ريال</h3>
                                <small class="text-white">إجمالي رسوم التوصيل</small>
                            </div>
                            <div class="stat-icon text-white">
                                <i class="bi bi-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> الطلبات</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                    <i class="bi bi-plus-lg"></i> طلب جديد
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>نوع الطلب</th>
                                <th>التاريخ</th>
                                <th>التكلفة</th>
                                <th>الحالة</th>
                                <th>السائق</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><span class="order-number"><?php echo $request['order_number']; ?></span></td>
                                <td>
                                    <div class="customer-info">
                                        <div><?php echo $request['customer_name']; ?></div>
                                        <div class="customer-phone"><?php echo $request['customer_phone']; ?></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo $request['order_type']; ?></span></td>
                                <td><?php echo date('Y-m-d', strtotime($request['delivery_date'])); ?></td>
                                <td><strong><?php echo number_format($request['total_cost'], 2); ?> ريال</strong></td>
                                <td>
                                    <?php 
                                    $status_class = match($request['status']) {
                                        'pending' => 'warning',
                                        'accepted' => 'info',
                                        'in_transit' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                    $status_text = match($request['status']) {
                                        'pending' => 'قيد الانتظار',
                                        'accepted' => 'تم القبول',
                                        'in_transit' => 'جاري التوصيل',
                                        'delivered' => 'تم التوصيل',
                                        'cancelled' => 'ملغي',
                                        default => 'غير معروف'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($request['driver_id']): ?>
                                        <div class="customer-info">
                                            <div><?php echo $request['driver_name']; ?></div>
                                            <div class="customer-phone"><?php echo $request['driver_phone']; ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">لم يتم التعيين</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#details-<?php echo $request['id']; ?>" title="عرض التفاصيل">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editOrder(<?php echo $request['id']; ?>)" title="تعديل الطلب">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="cancelOrder(<?php echo $request['id']; ?>)" title="إلغاء الطلب">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array($request['status'], ['accepted', 'in_transit', 'delivered']) && $request['driver_id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="openComplaint(<?php echo $request['id']; ?>, <?php echo $request['driver_id']; ?>)" 
                                                    title="تقديم شكوى">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($request['status'] === 'delivered' && $request['driver_id']): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="openRating(<?php echo $request['id']; ?>, <?php echo $request['driver_id']; ?>)" 
                                                    title="تقييم السائق">
                                                <i class="bi bi-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-success" onclick="openWhatsApp(<?php 
                                            echo htmlspecialchars(json_encode([
                                                'phone' => $request['customer_phone'],
                                                'orderNumber' => $request['order_number'],
                                                'customerName' => $request['customer_name'],
                                                'pickupLocation' => $request['pickup_location'],
                                                'deliveryLocation' => $request['delivery_location'],
                                                'deliveryDate' => date('Y-m-d', strtotime($request['delivery_date'])),
                                                'totalCost' => $request['total_cost'],
                                                'status' => $status_text
                                            ])); 
                                        ?>)" title="فتح محادثة واتساب">
                                            <i class="bi bi-whatsapp"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" onclick="trackOrder('<?php echo $request['order_number']; ?>')" title="تتبع الطلب">
                                            <i class="bi bi-search"></i>
                                        </button>
                                        <?php if ($request['status'] === 'cancelled'): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="revertOrder(<?php echo $request['id']; ?>)" title="إرجاع للانتظار">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Order Details Row -->
                            <tr class="order-details-row" id="details-<?php echo $request['id']; ?>">
                                <td colspan="8">
                                    <div class="order-details-content">
                                        <div class="invoice-container">
                                            <div class="row g-0">
                                                <div class="col-md-6">
                                                    <div class="invoice-section">
                                                        <h6 class="invoice-section-header primary">تفاصيل الطلب</h6>
                                                        <div class="invoice-section-content">
                                                            <dl class="invoice-list">
                                                                <dt>موقع الاستلام</dt>
                                                                <dd><?php echo htmlspecialchars($request['pickup_location']); ?></dd>
                                                                
                                                                <dt>موقع التوصيل</dt>
                                                                <dd><?php echo htmlspecialchars($request['delivery_location']); ?></dd>
                                                                
                                                                <dt>عدد القطع</dt>
                                                                <dd><?php echo htmlspecialchars($request['items_count']); ?> قطعة</dd>
                                                            </dl>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="invoice-section">
                                                        <h6 class="invoice-section-header secondary">معلومات اضافية</h6>
                                                        <div class="invoice-section-content">
                                                            <dl class="invoice-list">
                                                                <dt>طريقة الدفع</dt>
                                                                <dd><?php echo htmlspecialchars($request['payment_method']); ?></dd>
                                                                
                                                                <dt>حالة الدفع</dt>
                                                                <dd>
                                                                    <span class="payment-badge <?php echo $request['payment_status'] === 'paid' ? 'paid' : 'unpaid'; ?>">
                                                                        <?php echo $request['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                                                    </span>
                                                                </dd>
                                                                
                                                                <dt>ملاحظات</dt>
                                                                <dd><?php echo $request['additional_notes'] ? htmlspecialchars($request['additional_notes']) : 'لا توجد ملاحظات'; ?></dd>
                                                            </dl>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Complaint Responses Section -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="bi bi-chat-dots"></i> الردود على الشكاوى</h5>
            </div>
            <div class="card-body">
                <?php if (empty($complaint_responses)): ?>
                    <p class="text-muted text-center">لا توجد ردود على الشكاوى حتى الآن</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($complaint_responses as $response): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1">
                                        شكوى رقم: <?php echo htmlspecialchars($response['complaint_number']); ?>
                                        <small class="text-muted">(<?php echo htmlspecialchars($response['subject']); ?>)</small>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($response['response'])); ?></p>
                                <small class="text-muted">
                                    رد من: <?php echo htmlspecialchars($response['admin_name']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Include existing modals -->
    <?php include 'modals/order_modals.php'; ?>
    <?php include 'modals/complaint_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function submitNewOrder() {
            const form = document.getElementById('newOrderForm');
            const formData = new FormData(form);
            
            fetch('ajax/create_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('نجاح', 'تم إنشاء الطلب بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('newRequestModal')).hide();
                    location.reload();
                } else {
                    showAlert('خطأ', data.message || 'حدث خطأ أثناء إنشاء الطلب');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('خطأ', 'حدث خطأ أثناء إنشاء الطلب');
            });
        }

        function viewOrderDetails(orderId) {
            const detailsRow = document.getElementById('details-' + orderId);
            if (detailsRow.classList.contains('show')) {
                detailsRow.classList.remove('show');
            } else {
                // Hide all other detail rows first
                document.querySelectorAll('.order-details-row.show').forEach(row => {
                    row.classList.remove('show');
                });
                // Show the clicked row
                detailsRow.classList.add('show');
            }
        }

        function editOrder(orderId) {
            // Fetch order details
            fetch('ajax/get_order.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill the edit modal with order data
                        populateEditForm(data.order);
                        // Show the edit modal
                        new bootstrap.Modal(document.getElementById('editOrderModal')).show();
                    } else {
                        showAlert('خطأ', data.message || 'حدث خطأ في جلب بيانات الطلب');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('خطأ', 'حدث خطأ في جلب بيانات الطلب');
                });
        }

        function populateEditForm(order) {
            // Set the order ID
            document.getElementById('edit_order_id').value = order.id;
            
            // Set order type
            document.getElementById('edit_order_type').value = order.order_type;
            
            // Set customer details
            document.getElementById('edit_customer_name').value = order.customer_name;
            document.getElementById('edit_customer_phone').value = order.customer_phone;
            
            // Set delivery date and time
            const deliveryDateTime = new Date(order.delivery_date);
            document.getElementById('edit_delivery_date').value = deliveryDateTime.toISOString().split('T')[0];
            document.getElementById('edit_delivery_time').value = deliveryDateTime.toTimeString().slice(0,5);
            
            // Set locations
            document.getElementById('edit_pickup_location').value = order.pickup_location;
            if (order.pickup_location_link) {
                document.getElementById('edit_pickup_location_link').value = order.pickup_location_link;
            }
            
            document.getElementById('edit_delivery_location').value = order.delivery_location;
            if (order.delivery_location_link) {
                document.getElementById('edit_delivery_location_link').value = order.delivery_location_link;
            }
            
            // Set other details
            document.getElementById('edit_items_count').value = order.items_count;
            document.getElementById('edit_total_cost').value = order.total_cost;
            document.getElementById('edit_payment_method').value = order.payment_method;
            
            // Set fragile checkbox
            document.getElementById('edit_is_fragile').checked = order.is_fragile == 1;
            
            // Set additional notes
            if (order.additional_notes) {
                document.getElementById('edit_additional_notes').value = order.additional_notes;
            }
        }

        function updateOrder(orderId) {
            // Clear previous errors
            clearFormErrors();
            
            const form = document.getElementById('editOrderForm');
            const formData = new FormData(form);
            
            // Validate form before submission
            if (!validateOrderForm()) {
                return false;
            }

            // Show confirmation dialog
            if (!confirm('هل أنت متأكد من تحديث بيانات الطلب؟')) {
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري التحديث...';
            submitBtn.disabled = true;

            // Show loading overlay
            showLoadingOverlay();

            fetch('ajax/update_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('نجاح', 'تم تحديث الطلب بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('editOrderModal')).hide();
                    location.reload();
                } else {
                    if (data.errors && Array.isArray(data.errors)) {
                        data.errors.forEach(error => {
                            showFormError(error);
                        });
                    } else {
                        showAlert('خطأ', data.message || 'حدث خطأ أثناء تحديث الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('خطأ', 'حدث خطأ في الاتصال بالخادم');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                // Hide loading overlay
                hideLoadingOverlay();
            });
        }

        function cancelOrder(orderId) {
            if (confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) {
                fetch('ajax/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('نجاح', 'تم إلغاء الطلب بنجاح');
                        location.reload();
                    } else {
                        showAlert('خطأ', data.message || 'حدث خطأ أثناء إلغاء الطلب');
                    }
                })
                .catch(error => {
                    showAlert('خطأ', 'حدث خطأ في إرسال البيانات');
                });
            }
        }

        function revertOrder(orderId) {
            if (confirm('هل أنت متأكد من إرجاع هذا الطلب إلى حالة الانتظار؟')) {
                fetch('ajax/revert_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('نجاح', 'تم إرجاع الطلب إلى حالة الانتظار بنجاح');
                        location.reload();
                    } else {
                        showAlert('خطأ', data.message || 'حدث خطأ أثناء إرجاع الطلب');
                    }
                })
                .catch(error => {
                    showAlert('خطأ', 'حدث خطأ في إرسال البيانات');
                });
            }
        }

        function openRating(requestId, driverId) {
            document.getElementById('rate_request_id').value = requestId;
            document.getElementById('rate_driver_id').value = driverId;
            
            // Reset form
            document.getElementById('rateDriverForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('rateDriverModal')).show();
        }

        function openComplaint(requestId, driverId) {
            document.getElementById('complaint_request_id').value = requestId;
            document.getElementById('complaint_driver_id').value = driverId;
            
            // Reset form
            document.getElementById('complaintForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('complaintModal')).show();
        }

        function openWhatsApp(orderData) {
            let phone = orderData.phone.replace(/^0+/, '');
            if (!phone.startsWith('966')) {
                phone = '966' + phone;
            }
            
            // Get the current domain
            const domain = window.location.protocol + '//' + window.location.host;
            const trackingUrl = domain + '/track_order.php?order_number=' + orderData.orderNumber;
            
            const message = `
مرحباً ${orderData.customerName}،
تفاصيل طلبك رقم: ${orderData.orderNumber}

موقع الاستلام: ${orderData.pickupLocation}
موقع التوصيل: ${orderData.deliveryLocation}
تاريخ التوصيل: ${orderData.deliveryDate}
التكلفة: ${orderData.totalCost} ريال
الحالة: ${orderData.status}

يمكنك تتبع طلبك من خلال الرابط التالي:
${trackingUrl}

شكراً لاختيارك خدماتنا!
            `.trim();

            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        function validateOrderForm() {
            const form = document.getElementById('editOrderForm');
            let isValid = true;

            // Validate customer name
            const customerName = form.querySelector('[name="customer_name"]');
            if (!customerName.value.trim()) {
                showFormError('اسم العميل مطلوب');
                isValid = false;
            }

            // Validate phone number
            const phoneNumber = form.querySelector('[name="customer_phone"]');
            if (!phoneNumber.value.match(/^[0-9]{10}$/)) {
                showFormError('رقم الهاتف يجب أن يتكون من 10 أرقام');
                isValid = false;
            }

            // Validate delivery date and time
            const deliveryDate = form.querySelector('[name="delivery_date"]');
            const deliveryTime = form.querySelector('[name="delivery_time"]');
            if (deliveryDate.value && deliveryTime.value) {
                const deliveryDateTime = new Date(deliveryDate.value + ' ' + deliveryTime.value);
                if (deliveryDateTime < new Date()) {
                    showFormError('لا يمكن تحديد تاريخ ووقت توصيل في الماضي');
                    isValid = false;
                }
            }

            // Validate items count
            const itemsCount = form.querySelector('[name="items_count"]');
            if (!itemsCount.value || itemsCount.value < 1) {
                showFormError('عدد القطع يجب أن يكون رقماً موجباً');
                isValid = false;
            }

            // Validate total cost
            const totalCost = form.querySelector('[name="total_cost"]');
            const costValue = totalCost.value.trim();
            if (costValue === '') {
                showFormError('التكلفة الإجمالية مطلوبة');
                isValid = false;
            } else if (isNaN(costValue) || parseFloat(costValue) < 0) {
                showFormError('التكلفة الإجمالية يجب أن تكون رقماً صفر أو أكبر');
                isValid = false;
            }

            return isValid;
        }

        function showLoadingOverlay() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoadingOverlay() {
            const overlay = document.querySelector('.loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function showAlert(type, message) {
            // Remove any existing alerts first
            const existingAlerts = document.querySelectorAll('.custom-alert');
            existingAlerts.forEach(alert => alert.remove());

            // Map Arabic type to CSS class
            const typeClass = {
                'نجاح': 'success',
                'خطأ': 'danger',
                'تحذير': 'warning',
                'معلومات': 'info',
                // Keep English types for backward compatibility
                'success': 'success',
                'danger': 'danger',
                'warning': 'warning',
                'info': 'info'
            }[type] || 'info';

            // Create alert container
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert alert-${typeClass}`;
            
            // Create icon based on alert type
            const icon = document.createElement('i');
            switch(typeClass) {
                case 'success':
                    icon.className = 'bi bi-check-circle-fill';
                    break;
                case 'danger':
                    icon.className = 'bi bi-x-circle-fill';
                    break;
                case 'warning':
                    icon.className = 'bi bi-exclamation-triangle-fill';
                    break;
                case 'info':
                    icon.className = 'bi bi-info-circle-fill';
                    break;
            }
            
            // Create message container
            const messageSpan = document.createElement('span');
            messageSpan.className = 'alert-message';
            messageSpan.textContent = message;
            
            // Create close button
            const closeButton = document.createElement('button');
            closeButton.className = 'alert-close';
            closeButton.innerHTML = '×';
            closeButton.onclick = function() {
                alertDiv.classList.add('fade-out');
                setTimeout(() => alertDiv.remove(), 300);
            };
            
            // Assemble alert
            alertDiv.appendChild(icon);
            alertDiv.appendChild(messageSpan);
            alertDiv.appendChild(closeButton);
            
            // Add to document
            document.body.appendChild(alertDiv);
            
            // Trigger entrance animation
            setTimeout(() => alertDiv.classList.add('show'), 100);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv && document.body.contains(alertDiv)) {
                    alertDiv.classList.add('fade-out');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }

        function clearFormErrors() {
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        function showFormError(error) {
            const fields = {
                'نوع الطلب': 'edit_order_type',
                'اسم العميل': 'edit_customer_name',
                'رقم هاتف العميل': 'edit_customer_phone',
                'تاريخ التوصيل': 'edit_delivery_date',
                'وقت التوصيل': 'edit_delivery_time',
                'موقع الاستلام': 'edit_pickup_location',
                'موقع التوصيل': 'edit_delivery_location',
                'عدد القطع': 'edit_items_count',
                'التكلفة الإجمالية': 'edit_total_cost',
                'طريقة الدفع': 'edit_payment_method'
            };

            let fieldId = null;
            for (const [label, id] of Object.entries(fields)) {
                if (error.includes(label)) {
                    fieldId = id;
                    break;
                }
            }

            if (fieldId) {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = error;
                    field.parentNode.appendChild(feedback);
                }
            } else {
                showAlert('خطأ', error);
            }
        }

        // Add form validation before submission
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateOrder(document.getElementById('edit_order_id').value);
        });

        // Initialize form fields when modal opens
        document.getElementById('editOrderModal').addEventListener('show.bs.modal', function () {
            clearFormErrors();
        });

        // Clear form when modal closes
        document.getElementById('editOrderModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('editOrderForm').reset();
            clearFormErrors();
        });

        // Auto refresh functionality
        const autoRefreshInterval = setInterval(function() {
            location.reload();
        }, 60000); // Refresh every minute

        // Add CSS styles at the end of the file
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            .custom-alert {
                position: fixed;
                top: 30px;
                left: 50%;
                transform: translate(-50%, -150%);
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 350px;
                max-width: 450px;
                z-index: 9999;
                direction: rtl;
                transition: transform 0.3s ease;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .custom-alert.show {
                transform: translate(-50%, 0);
            }

            .custom-alert.fade-out {
                transform: translate(-50%, -150%);
            }

            .custom-alert i {
                font-size: 1.4rem;
                color: #fff;
            }

            .custom-alert .alert-message {
                flex-grow: 1;
                font-size: 0.95rem;
                font-weight: 500;
                color: #fff;
            }

            .custom-alert .alert-close {
                background: none;
                border: none;
                color: rgba(255, 255, 255, 0.8);
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                line-height: 1;
                transition: all 0.2s;
                margin-right: 5px;
            }

            .custom-alert .alert-close:hover {
                color: #fff;
                transform: scale(1.1);
            }

            .alert-success {
                background: #10B981;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
            }

            .alert-danger {
                background: #EF4444;
                box-shadow: 0 4px 15px rgba(239, 68, 68, 0.2);
            }

            .alert-warning {
                background: #F59E0B;
                box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2);
            }

            .alert-info {
                background: #3B82F6;
                box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
            }

            @media (max-width: 576px) {
                .custom-alert {
                    min-width: 90%;
                    margin: 0 20px;
                    padding: 12px 20px;
                }
            }
        `;
        document.head.appendChild(styleSheet);

        function trackOrder(orderNumber) {
            // Get the current domain
            const domain = window.location.protocol + '//' + window.location.host;
            const trackingUrl = domain + '/track_order.php?order_number=' + orderNumber;
            
            // Open tracking page in new tab
            window.open(trackingUrl, '_blank');
        }

        // Function to fetch complaint responses live
        function fetchComplaintResponses() {
            fetch('ajax/get_complaint_responses.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const responsesContainer = document.querySelector('.list-group');
                        if (responsesContainer) {
                            if (data.responses.length === 0) {
                                responsesContainer.innerHTML = '<p class="text-muted text-center">لا توجد ردود على الشكاوى حتى الآن</p>';
                            } else {
                                responsesContainer.innerHTML = data.responses.map(response => `
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1">
                                                شكوى رقم: ${response.complaint_number}
                                                <small class="text-muted">(${response.subject})</small>
                                            </h6>
                                            <small class="text-muted">
                                                ${new Date(response.created_at).toLocaleString('ar-SA')}
                                            </small>
                                        </div>
                                        <p class="mb-1">${response.response}</p>
                                        <small class="text-muted">
                                            رد من: ${response.admin_name}
                                        </small>
                                    </div>
                                `).join('');
                            }
                        }
                    }
                })
                .catch(error => console.error('Error fetching responses:', error));
        }

        // Function to update notification count
        function updateNotificationCount(change) {
            // Update dropdown badge (notifications)
            const dropdownBadge = document.querySelector('#notificationsDropdown .badge.bg-danger');
            if (dropdownBadge) {
                const currentCount = parseInt(dropdownBadge.textContent || '0');
                const newCount = Math.max(0, currentCount + change);
                if (newCount <= 0) {
                    dropdownBadge.remove();
                } else {
                    dropdownBadge.textContent = newCount;
                }
            }

            // Update complaints badge (in the main menu)
            const complaintsBadge = document.querySelector('a[href="complaints.php"] .badge.bg-danger');
            if (complaintsBadge) {
                const currentCount = parseInt(complaintsBadge.textContent || '0');
                const newCount = Math.max(0, currentCount + change);
                if (newCount <= 0) {
                    complaintsBadge.remove();
                } else {
                    complaintsBadge.textContent = newCount;
                }
            }
        }

        // Function to handle notification click
        function handleNotificationClick(notificationId, link, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const notificationElement = event.currentTarget;
            if (!notificationElement) return;

            const notificationType = notificationElement.getAttribute('data-type');
            const complaintNumber = notificationElement.getAttribute('data-complaint-number');
            
            // Only proceed if notification is unread (has bg-light class)
            if (notificationElement.classList.contains('bg-light')) {
                // Mark notification as read
                fetch('ajax/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notification_id: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        notificationElement.classList.remove('bg-light');
                        
                        // Update notification count based on server response
                        const dropdownBadge = document.querySelector('#notificationsDropdown .badge.bg-danger');
                        if (dropdownBadge) {
                            if (data.unread_count <= 0) {
                                dropdownBadge.remove();
                            } else {
                                dropdownBadge.textContent = data.unread_count;
                            }
                        }
                        
                        // Navigate after successful update
                        setTimeout(() => {
                            navigateToDestination(notificationType, complaintNumber, link);
                        }, 100);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    navigateToDestination(notificationType, complaintNumber, link);
                });
            } else {
                navigateToDestination(notificationType, complaintNumber, link);
            }
        }

        // Helper function for navigation
        function navigateToDestination(notificationType, complaintNumber, link) {
            if (notificationType === 'complaint_response' && complaintNumber) {
                window.location.href = `complaints.php?id=${complaintNumber}`;
            } else if (link && link !== '#') {
                window.location.href = link;
            }
        }

        function markAllNotificationsAsRead(event) {
            event.preventDefault();
            
            // Count unread notifications before marking them as read
            const unreadCount = document.querySelectorAll('.dropdown-item.bg-light').length;
            
            if (unreadCount > 0) {
                fetch('ajax/mark_all_notifications_read.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.querySelectorAll('.dropdown-item').forEach(item => {
                            item.classList.remove('bg-light');
                        });
                        
                        // Update notification count
                        updateNotificationCount(-unreadCount);
                    }
                });
            }
        }

        // Add event listeners when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to all notification items
            document.querySelectorAll('.dropdown-item[data-notification-id]').forEach(item => {
                item.addEventListener('click', function(e) {
                    const notificationId = this.dataset.notificationId;
                    const link = this.getAttribute('href') || '#';
                    handleNotificationClick(notificationId, link, e);
                });
            });

            // Add click handler to "Mark all as read" button
            const markAllReadBtn = document.querySelector('#markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', markAllNotificationsAsRead);
            }

            // Log initial badge elements to verify selectors
            console.log('Notifications Badge:', document.querySelector('#notificationsDropdown .badge.bg-danger'));
            console.log('Complaints Badge:', document.querySelector('a[href="complaints.php"] .badge.bg-danger'));
        });

        // Prevent dropdown from closing when clicking inside
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownMenu = document.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.addEventListener('click', function(e) {
                    if (e.target.closest('.notification-content') || e.target.closest('.reply-form')) {
                        e.stopPropagation();
                    }
                });
            }
        });
    </script>
</body>
</html>
