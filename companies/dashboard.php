<?php
require_once '../config.php';

if (!isset($_SESSION['company_email'])) {
    header("Location: orders.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// تعريف دالة getDateCondition
function getDateCondition($start_date, $end_date, $column = 'created_at') {
    $condition = "";
    if ($start_date && $end_date) {
        $condition = " AND $column BETWEEN '$start_date' AND '$end_date'";
    } elseif ($start_date) {
        $condition = " AND $column >= '$start_date'";
    } elseif ($end_date) {
        $condition = " AND $column <= '$end_date'";
    }
    return $condition;
}

// جلب تواريخ البداية والنهاية من النموذج
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

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
$stmt = $conn->prepare("SELECT name, logo, delivery_fee FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

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
            (SELECT 
                (SUM(CASE 
                    WHEN status = 'delivered' AND payment_method = 'cash'
                    THEN total_cost
                    ELSE 0 
                END) - SUM(delivery_fee)) - 
                COALESCE((
                    SELECT SUM(amount) 
                    FROM company_payments 
                    WHERE company_id = ? 
                    AND status = 'completed' 
                ), 0)
            FROM requests 
            WHERE company_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 300 DAY)
            AND status = 'delivered'
        ), 0) as amount_owed,
        COALESCE(SUM(CASE 
            WHEN status = 'delivered'
            THEN delivery_fee
            ELSE 0 
        END), 0) as amount_due
    FROM requests 
    WHERE company_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 300 DAY)
");
$stmt->execute([$_SESSION['company_id'], $_SESSION['company_id'], $_SESSION['company_id']]);
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

// Calculate statistics
$stats['completed_orders'] = $stats['delivered_count'];
$stats['total_amount'] = $stats['amount_owed'] + $stats['amount_due'];
$stats['delivery_revenue'] = $stats['amount_due'];
$stats['total_minus_delivery'] = $stats['amount_owed'];

// Get company statistics
$company = [];
    
// استعلام لحساب إجمالي رسوم التوصيل للطلبات الموصلة فقط
$delivery_fees_query = "SELECT 
    r.company_id,
    c.delivery_fee as current_fee,
    COALESCE(SUM(r.delivery_fee), 0) as total_delivery_fees,
    COUNT(*) as total_orders
FROM requests r
JOIN companies c ON r.company_id = c.id 
WHERE r.status = 'delivered' AND r.company_id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
$delivery_fees_query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
$delivery_fees_query .= " GROUP BY r.company_id, c.delivery_fee";
    
$delivery_fees_stmt = $conn->prepare($delivery_fees_query);
$delivery_fees_stmt->execute(['company_id' => $company_id]);
$company_delivery_fees = [];
while ($row = $delivery_fees_stmt->fetch(PDO::FETCH_ASSOC)) {
    $company_delivery_fees[$row['company_id']] = [
        'total' => $row['total_delivery_fees'],
        'per_order' => $row['total_orders'] > 0 ? ($row['total_delivery_fees'] / $row['total_orders']) : 0
    ];
}

$query = "SELECT 
    c.id,
    c.name as company_name,
    COALESCE(c.delivery_fee, 0) as delivery_fee,
    COALESCE(COUNT(DISTINCT CASE WHEN r.status = 'delivered' THEN r.id END), 0) as completed_orders,
    COALESCE(SUM(CASE 
        WHEN r.status = 'delivered' 
        THEN r.total_cost
        ELSE 0 
    END), 0) as total_amount,
    COALESCE(SUM(CASE 
        WHEN r.status = 'delivered' 
        THEN r.total_cost
        ELSE 0 
    END), 0) as company_payable,
    COALESCE((
        SELECT SUM(amount)
        FROM company_payments 
        WHERE company_id = c.id AND status = 'completed' AND payment_type = 'outgoing'
    ), 0) as paid_to_company,  -- مدفوع منا إلى الشركة
    COALESCE((
        SELECT SUM(amount)
        FROM company_payments 
        WHERE company_id = c.id AND status = 'completed' AND payment_type = 'incoming'
    ), 0) as paid_by_company  -- مدفوع من الشركة
FROM companies c
LEFT JOIN requests r ON c.id = r.company_id
WHERE c.id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
$query .= getDateCondition($start_date, $end_date, 'r.delivery_date');
$query .= " GROUP BY c.id, c.name, c.delivery_fee ORDER BY c.name";

$stmt = $conn->prepare($query);
$stmt->execute(['company_id' => $company_id]);
if ($stmt) {
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($company) {
        $company['completed_orders'] = intval($company['completed_orders']);
        $company['total_amount'] = floatval($company['total_amount']);
        $company['delivery_revenue'] = floatval($company_delivery_fees[$company['id']]['total'] ?? 0);
        $company['company_payable'] = floatval($company['company_payable']);
        $company['paid_amount'] = floatval($company['paid_to_company'] - $company['paid_by_company']);
        $company['remaining'] = $company['company_payable'] - $company['paid_amount'] - $company['delivery_revenue'];
    }
}

// حساب إجمالي المبالغ المتبقية
if (!is_array($company)) {
    $company = []; // تعيين قيمة افتراضية كمصفوفة فارغة
}

// حساب إجمالي المبالغ المتبقية باستخدام عامل الدمج
$company['remaining'] = $company['remaining'] ?? 0;
$total_remaining = $company['remaining'];
// Get monthly revenue data for chart
$monthly_data = [];
$query = "SELECT 
    DATE_FORMAT(delivery_date, '%Y-%m') as month,
    COALESCE(COUNT(*), 0) as total_orders,
    COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
FROM requests 
WHERE status = 'delivered' AND company_id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
$query .= getDateCondition($start_date, $end_date);
$query .= " GROUP BY DATE_FORMAT(delivery_date, '%Y-%m') ORDER BY month";

$stmt = $conn->prepare($query);
$stmt->execute(['company_id' => $company_id]);
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_data[] = [
            'month' => $row['month'],
            'orders' => intval($row['total_orders']),
            'revenue' => floatval($row['total_delivery_fees'])
        ];
    }
}

// Get payment method distribution for chart
$payment_data = [];
$query = "SELECT 
    payment_method,
    COALESCE(COUNT(*), 0) as total_orders,
    COALESCE(SUM(delivery_fee), 0) as total_delivery_fees
FROM requests 
WHERE status = 'delivered' AND company_id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
$query .= getDateCondition($start_date, $end_date);
$query .= " GROUP BY payment_method";

$stmt = $conn->prepare($query);
$stmt->execute(['company_id' => $company_id]);
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payment_data[] = [
            'method' => $row['payment_method'],
            'orders' => intval($row['total_orders']),
            'revenue' => floatval($row['total_delivery_fees'])
        ];
    }
}

// جلب الطلبات مع فلترة حسب التاريخ
$requests = []; // تعيين قيمة افتراضية
$query = "SELECT r.*, d.username as driver_name, d.phone as driver_phone
    FROM requests r
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.company_id = :company_id";

// إضافة شرط التاريخ إذا تم تحديده
$query .= getDateCondition($start_date, $end_date, 'r.created_at');
$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute(['company_id' => $company_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// عرض الحالة
if ($company) {
    $remaining = $company['remaining'];
    $status = '';
    $status_color = '';
    if ($remaining > 0) {
        $status = 'مستحق لنا';
        $status_color = 'text-white';
    } elseif ($remaining < 0) {
        $status = 'مستحق علينا ⚠️';
        $status_color = 'text-danger';
    } else {
        $status = 'لا يوجد مستحقات';
        $status_color = 'text-success';
    }
} else {
    $status = 'لا يوجد بيانات';
    $status_color = 'text-secondary';
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- إضافة Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places"></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
        .location-link {
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include '../includes/comHeader.php'; ?>
   
    <div class="container mt-4">
        <!-- تصفية حسب التاريخ -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-filter me-1"></i>
                    تصفية حسب التاريخ
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">تاريخ البداية</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">تاريخ النهاية</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>
                            تطبيق التصفية
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary ms-2">إعادة تعيين</a>
                    </div>
                </form>
            </div>
        </div>
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
                                <h3 class="mb-0 text-white"><?php echo htmlspecialchars($company['delivery_fee'] ?? '0'); ?> ريال</h3>
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
                     <h5 class="card-title <?php echo $status_color; ?>">
                    <?php echo $status; ?>
            </h5>
             <h3 class="mb-0 text-white"><?php echo number_format($total_remaining, 2); ?> ر.س</h3>
             <small class="text-white">
                    إجمالي المبالغ المتبقية: <?php echo number_format($total_remaining, 2); ?> ر.س
</small>    
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
                <div class="d-flex gap-2 align-items-center">
                    <div class="input-group search-container">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="orderSearch" class="form-control" placeholder="ابحث عن طلب (رقم الطلب، اسم العميل، رقم الهاتف)">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                        <i class="bi bi-plus-lg"></i> طلب جديد
                    </button>
                </div>
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
                                            <div class="mt-1">
                                                <a href="tel:<?php echo $request['driver_phone']; ?>" class="btn btn-sm btn-info" title="اتصال بالسائق">
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-success" onclick="openDriverWhatsApp('<?php echo $request['driver_phone']; ?>', '<?php echo $request['driver_name']; ?>', '<?php echo $request['order_number']; ?>')" title="واتساب السائق">
                                                    <i class="bi bi-whatsapp"></i>
                                                </button>
                                            </div>
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
                                                                <dd>
                                                                    <?php echo htmlspecialchars($request['pickup_location']); ?>
                                                                    <?php if ($request['pickup_location_link']): ?>
                                                                        <br>
                                                                        <a class="location-link" onclick="showMap('<?php echo htmlspecialchars($request['pickup_location_link']); ?>', '<?php echo htmlspecialchars($request['pickup_location']); ?>')">
                                                                            <i class="fas fa-map-marker-alt"></i> عرض على الخريطة
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </dd>
                                                                
                                                                <dt>موقع التوصيل</dt>
                                                                <dd>
                                                                    <?php echo htmlspecialchars($request['delivery_location']); ?>
                                                                    <?php if ($request['delivery_location_link']): ?>
                                                                        <br>
                                                                        <a class="location-link" onclick="showMap('<?php echo htmlspecialchars($request['delivery_location_link']); ?>', '<?php echo htmlspecialchars($request['delivery_location']); ?>')">
                                                                            <i class="fas fa-map-marker-alt"></i> عرض على الخريطة
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </dd>
                                                                
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

    <!-- إضافة Modal للخريطة -->
    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">عرض الموقع على الخريطة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>

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

        function trackOrder(orderNumber) {
            // استخدام المسار النسبي للوصول إلى ملف التتبع
            const trackingUrl = '../track_order.php?order_number=' + orderNumber;
            
            // فتح صفحة التتبع في نافذة جديدة
            window.open(trackingUrl, '_blank');
        }

        function openWhatsApp(orderData) {
            let phone = orderData.phone.replace(/^0+/, '');
            if (!phone.startsWith('966')) {
                phone = '966' + phone;
            }
            
            // Get the current domain
            const domain = window.location.protocol + '//' + window.location.host;
            const trackingUrl = domain + '/track_order.php?order_number=' + orderData.orderNumber;
            
            const message = `مرحباً ${orderData.customerName}،
تفاصيل طلبك رقم: ${orderData.orderNumber}

موقع الاستلام: ${orderData.pickupLocation}
موقع التوصيل: ${orderData.deliveryLocation}
تاريخ التوصيل: ${orderData.deliveryDate}
التكلفة: ${orderData.totalCost} ريال
الحالة: ${orderData.status}

يمكنك تتبع طلبك من خلال الرابط التالي:
${trackingUrl}

شكراً لاختيارك خدماتنا!`.trim();

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

            .search-container {
                min-width: 300px;
                position: relative;
            }

            .search-container .input-group-text {
                background-color: white;
                border-left: 0;
            }

            .search-container .form-control {
                border-right: 0;
                border-left: 0;
            }

            .search-container .btn {
                border-right: 1px solid #dee2e6;
            }

            .no-results {
                text-align: center;
                padding: 20px;
                color: #6c757d;
            }

            @media (max-width: 768px) {
                .card-header {
                    flex-direction: column;
                    gap: 10px;
                }
                .search-container {
                    min-width: 100%;
                }
                .d-flex.gap-2 {
                    width: 100%;
                    flex-direction: column;
                }
                .btn-primary {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(styleSheet);

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

        function openDriverWhatsApp(phone, driverName, orderNumber) {
            let formattedPhone = phone.replace(/^0+/, '');
            if (!formattedPhone.startsWith('966')) {
                formattedPhone = '966' + formattedPhone;
            }
            
            const message = `
مرحباً ${driverName}،
بخصوص الطلب رقم: ${orderNumber}

كيف حال الطلب؟ هل هناك أي تحديثات؟
            `.trim();

            const whatsappUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        // تحسين وظيفة البحث
        function performSearch() {
            const searchInput = document.getElementById('orderSearch');
            const searchValue = searchInput.value.toLowerCase().trim();
            const tableBody = document.querySelector('table tbody');
            const rows = tableBody.querySelectorAll('tr:not(.order-details-row)');
            let hasResults = false;

            // إزالة رسالة "لا توجد نتائج" إذا كانت موجودة
            const existingNoResults = document.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            rows.forEach(row => {
                const orderNumber = row.querySelector('.order-number')?.textContent.toLowerCase() || '';
                const customerInfo = row.querySelector('.customer-info')?.textContent.toLowerCase() || '';
                const customerPhone = row.querySelector('.customer-phone')?.textContent.toLowerCase() || '';
                
                const matchesSearch = !searchValue || 
                                    orderNumber.includes(searchValue) || 
                                    customerInfo.includes(searchValue) || 
                                    customerPhone.includes(searchValue);
                
                row.style.display = matchesSearch ? '' : 'none';
                
                if (matchesSearch) {
                    hasResults = true;
                }

                // إخفاء/إظهار صف التفاصيل المرتبط
                const detailsRow = row.nextElementSibling;
                if (detailsRow && detailsRow.classList.contains('order-details-row')) {
                    detailsRow.style.display = matchesSearch ? 'none' : 'none'; // يبدأ مخفياً دائماً
                }
            });

            // إظهار رسالة "لا توجد نتائج" إذا لم يتم العثور على نتائج
            if (!hasResults && searchValue) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results';
                noResultsRow.innerHTML = `
                    <td colspan="8" class="text-center py-4">
                        <i class="bi bi-search" style="font-size: 2rem; color: #6c757d;"></i>
                        <p class="mb-0 mt-2">لا توجد نتائج تطابق بحثك</p>
                    </td>
                `;
                tableBody.appendChild(noResultsRow);
            }
        }

        // دالة لمسح البحث
        function clearSearch() {
            const searchInput = document.getElementById('orderSearch');
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }

        // إضافة مستمعي الأحداث
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('orderSearch');
            
            // البحث عند الكتابة
            searchInput.addEventListener('input', performSearch);
            
            // البحث عند الضغط على Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        });

        let map;
        let marker;

        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: { lat: 24.7136, lng: 46.6753 }, // الرياض كمركز افتراضي
            });
            marker = new google.maps.Marker({
                map: map,
                draggable: false
            });
        }

        function showMap(locationLink, locationName) {
            // استخراج الإحداثيات من رابط Google Maps
            let lat, lng;
            
            if (locationLink.includes('@')) {
                const matches = locationLink.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                if (matches) {
                    lat = parseFloat(matches[1]);
                    lng = parseFloat(matches[2]);
                }
            } else if (locationLink.includes('?q=')) {
                const matches = locationLink.match(/q=(-?\d+\.\d+),(-?\d+\.\d+)/);
                if (matches) {
                    lat = parseFloat(matches[1]);
                    lng = parseFloat(matches[2]);
                }
            }

            if (lat && lng) {
                const position = { lat, lng };
                
                // تحديث الخريطة
                map.setCenter(position);
                marker.setPosition(position);
                
                // إضافة عنوان للماركر
                const infoWindow = new google.maps.InfoWindow({
                    content: locationName,
                    position: position
                });
                marker.addListener('click', () => {
                    infoWindow.open(map);
                });
            }
        }
    </script>
</body>
</html>
