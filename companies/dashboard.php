<?php
require_once '../config.php';

if (!isset($_SESSION['company_id'])) {
    header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

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
    LIMIT 5
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
    LIMIT 5
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
$active_complaints = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand img {
            height: 40px;
            width: auto;
            margin-left: 10px;
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .order-details-row {
            display: none;
            background: #f8fafc !important;
        }
        .order-details-row.show {
            display: table-row;
        }
        .order-details-content {
            padding: 2rem;
        }
        .invoice-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            overflow: hidden;
            margin: 0.5rem;
        }
        .invoice-section {
            margin-bottom: 0;
        }
        .invoice-section-header {
            padding: 1rem 1.5rem;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
        }
        .invoice-section-header.primary {
            background: linear-gradient(45deg, #4158D0, #C850C0);
        }
        .invoice-section-header.secondary {
            background: linear-gradient(45deg, #0082c8, #0082c8);
        }
        .invoice-section-content {
            padding: 1.5rem;
            background: white;
        }
        .invoice-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .invoice-list dt {
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .invoice-list dd {
            font-size: 1.1rem;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 6px;
            margin-right: 0;
        }
        .invoice-list dd:last-child {
            margin-bottom: 0;
        }
        .payment-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
        }
        .payment-badge.unpaid {
            background: #fff3cd;
            color: #856404;
        }
        .payment-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        @media (max-width: 768px) {
            .order-details-content {
                padding: 1rem;
            }
            .invoice-section-header {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            .invoice-section-content {
                padding: 1rem;
            }
            .invoice-list dt {
                font-size: 0.85rem;
            }
            .invoice-list dd {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
        }
        .btn-group .btn {
            margin: 0 2px;
        }
        .company-name {
            font-weight: bold;
            color: #fff;
            margin-right: 10px;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .badge.bg-warning {
            background-color: #ffeeba !important;
            color: #856404;
        }
        .badge.bg-success {
            background-color: #d4edda !important;
            color: #155724;
        }
        .badge.bg-primary {
            background-color: #cce5ff !important;
            color: #004085;
        }
        .badge.bg-danger {
            background-color: #f8d7da !important;
            color: #721c24;
        }
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
            border-radius: 6px !important;
            margin: 0 2px;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }
        .customer-info {
            line-height: 1.2;
        }
        .customer-phone {
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
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
                </ul>
                <ul class="navbar-nav">
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
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $request['id']; ?>)" title="عرض التفاصيل">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-warning" onclick="editOrder(<?php echo $request['id']; ?>)" title="تعديل الطلب">
                                                <i class="bi bi-pencil"></i>
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
                                        <?php if ($request['driver_id']): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="rateDriver(<?php echo $request['driver_id']; ?>)" title="تقييم السائق">
                                                <i class="bi bi-star"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="submitComplaint(<?php echo $request['id']; ?>)" title="تقديم شكوى">
                                                <i class="bi bi-exclamation-triangle"></i>
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
    </div>

    <!-- Define BASEPATH constant -->
    <?php define('BASEPATH', true); ?>
    
    <!-- Include existing modals -->
    <?php include 'modals/order_modals.php'; ?>

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
                    // Close modal and reload page
                    bootstrap.Modal.getInstance(document.getElementById('newRequestModal')).hide();
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إنشاء الطلب');
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
            // Fetch order details and show edit modal
            fetch('ajax/get_order.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate edit modal with order data
                        const order = data.order;
                        document.getElementById('edit_order_id').value = order.id;
                        document.getElementById('edit_order_type').value = order.order_type;
                        document.getElementById('edit_customer_name').value = order.customer_name;
                        document.getElementById('edit_customer_phone').value = order.customer_phone;
                        document.getElementById('edit_delivery_date').value = order.delivery_date;
                        document.getElementById('edit_pickup_location').value = order.pickup_location;
                        document.getElementById('edit_delivery_location').value = order.delivery_location;
                        document.getElementById('edit_items_count').value = order.items_count;
                        document.getElementById('edit_total_cost').value = order.total_cost;
                        document.getElementById('edit_payment_method').value = order.payment_method;
                        document.getElementById('edit_is_fragile').checked = order.is_fragile == 1;
                        document.getElementById('edit_additional_notes').value = order.additional_notes || '';

                        // Handle invoice image
                        const currentInvoiceDiv = document.getElementById('current_invoice');
                        if (order.invoice_file) {
                            currentInvoiceDiv.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <img src="../uploads/invoices/${order.invoice_file}" alt="صورة الفاتورة الحالية" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="ms-2">
                                        <small class="text-muted d-block">الصورة الحالية</small>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="remove_invoice" id="remove_invoice">
                                            <label class="form-check-label" for="remove_invoice">إزالة الصورة</label>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            currentInvoiceDiv.innerHTML = '<small class="text-muted">لا توجد صورة فاتورة حالية</small>';
                        }

                        new bootstrap.Modal(document.getElementById('editOrderModal')).show();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء جلب بيانات الطلب');
                });
        }

        // Add event listener for edit form submission
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('ajax/update_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload page to show updated data
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث الطلب');
            });
        });

        // دالة لفتح محادثة واتساب
        function openWhatsApp(orderData) {
            // تنسيق رقم الهاتف (إزالة الصفر الأول إذا وجد وإضافة رمز الدولة)
            let phone = orderData.phone.replace(/^0+/, '');
            if (!phone.startsWith('966')) {
                phone = '966' + phone;
            }
            
            // إنشاء نص الرسالة
            const message = `
مرحباً ${orderData.customerName}،
تفاصيل طلبك رقم: ${orderData.orderNumber}

موقع الاستلام: ${orderData.pickupLocation}
موقع التوصيل: ${orderData.deliveryLocation}
تاريخ التوصيل: ${orderData.deliveryDate}
التكلفة: ${orderData.totalCost} ريال
الحالة: ${orderData.status}

شكراً لاختيارك خدماتنا!
            `.trim();

            // فتح رابط واتساب مع الرسالة المجهزة
            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }
    </script>
</body>
</html> 