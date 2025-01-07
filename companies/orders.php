<?php
require_once '../config.php';

// Set staff header
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}


$company_id = $_SESSION['company_id'];
$staff_id = $_SESSION['staff_id'];

// Get company information
$stmt = $conn->prepare("SELECT name, logo FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get staff information
$stmt = $conn->prepare("
    SELECT name, email, phone, role, last_login, created_at 
    FROM company_staff 
    WHERE id = ? AND company_id = ?
");
$stmt->execute([$staff_id, $company_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Get requests statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
    FROM requests 
    WHERE company_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$company_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent requests
$stmt = $conn->prepare("
    SELECT r.*, d.username as driver_name, d.phone as driver_phone
    FROM requests r
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.company_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$company_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلبات - <?php echo htmlspecialchars($company['name']); ?></title>
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
        .profile-header {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            padding: 2rem 0;
            margin-bottom: 2rem;
            color: white;
        }
        .stats-cards {
            margin-top: 1.5rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }
        .stat-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        .stat-card .label {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }
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
        .btn-light {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }
        .btn-light:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: rgba(220, 53, 69, 0.9);
            border: none;
        }
        .btn-danger:hover {
            background: rgba(220, 53, 69, 1);
            transform: translateY(-2px);
        }
        .alert-float {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 300px;
            z-index: 9999;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.5s ease-out;
            display: none;
        }

        @keyframes slideDown {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .alert-float.show {
            display: block;
        }

        .alert-float .close-btn {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .alert-float .close-btn:hover {
            opacity: 1;
            transform: translateY(-50%) rotate(90deg);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .highlight-update {
            animation: highlightRow 2s ease-in-out;
        }

        @keyframes highlightRow {
            0% { background-color: #fff; }
            50% { background-color: #e3f2fd; }
            100% { background-color: #fff; }
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .alert-float {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Staff Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><?php echo htmlspecialchars($staff['name']); ?></h2>
                            <div class="text-white-50">
                                <?php echo $staff['role'] === 'order_manager' ? 'مدير طلبات' : 'موظف'; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="mb-1">
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?>
                            </div>
                            <?php if ($staff['phone']): ?>
                            <div class="mb-1">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($staff['phone']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="icon text-primary">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <div class="value"><?php echo number_format($stats['total_requests']); ?></div>
                            <div class="label">إجمالي الطلبات</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon text-warning">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="value"><?php echo number_format($stats['pending_count']); ?></div>
                            <div class="label">قيد الانتظار</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon text-info">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="value"><?php echo number_format($stats['active_count']); ?></div>
                            <div class="label">جاري التوصيل</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="value"><?php echo number_format($stats['delivered_count']); ?></div>
                            <div class="label">تم التوصيل</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                      
                        <button type="button" class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key"></i> تغيير كلمة المرور
                        </button>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
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
                                            <div class="mt-2">
                                                <a href="https://wa.me/<?php echo formatPhoneForWhatsApp($request['driver_phone']); ?>" 
                                                   target="_blank" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="bi bi-whatsapp"></i>
                                                    واتساب
                                                </a>
                                                <a href="tel:<?php echo formatPhoneForWhatsApp($request['driver_phone']); ?>" 
                                                   class="btn btn-primary btn-sm ms-1">
                                                    <i class="bi bi-telephone"></i>
                                                    اتصال
                                                </a>
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
                                        <?php if ($request['status'] === 'cancelled'): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="revertOrder(<?php echo $request['id']; ?>)" title="إرجاع للانتظار">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <!-- Order Details Row -->
                            <tr class="collapse" id="details-<?php echo $request['id']; ?>">
                                <td colspan="8">
                                    <div class="card m-2 border-0">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="mb-3">تفاصيل الطلب</h6>
                                                    <dl class="row">
                                                        <dt class="col-sm-4">موقع الاستلام</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($request['pickup_location']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">موقع التوصيل</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($request['delivery_location']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">عدد القطع</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($request['items_count']); ?> قطعة</dd>
                                                            </dl>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="mb-3">معلومات إضافية</h6>
                                                    <dl class="row">
                                                        <dt class="col-sm-4">طريقة الدفع</dt>
                                                        <dd class="col-sm-8"><?php echo htmlspecialchars($request['payment_method']); ?></dd>
                                                        
                                                        <dt class="col-sm-4">حالة الدفع</dt>
                                                        <dd class="col-sm-8">
                                                            <span class="badge bg-<?php echo $request['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                                        <?php echo $request['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                                                    </span>
                                                                </dd>
                                                                
                                                        <dt class="col-sm-4">ملاحظات</dt>
                                                        <dd class="col-sm-8"><?php echo $request['additional_notes'] ? htmlspecialchars($request['additional_notes']) : 'لا توجد ملاحظات'; ?></dd>
                                                            </dl>
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

    <!-- Include modals -->
    <?php include 'modals/order_modals.php'; ?>

    <!-- Modal تغيير كلمة المرور -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        تغيير كلمة المرور
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm" method="post" action="ajax/change_password.php">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>تنبيه!</strong> يرجى اختيار كلمة مرور قوية وآمنة.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">يجب أن تحتوي على 8 أحرف على الأقل</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check2-circle"></i> تغيير كلمة المرور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- إضافة عنصر التنبيه -->
    <div id="alertFloat" class="alert-float">
        <i class="bi bi-check-circle-fill me-2"></i>
        <span id="alertMessage"></span>
        <button type="button" class="close-btn" onclick="hideAlert()">×</button>
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
                        showAlert('danger', data.message || 'حدث خطأ في جلب بيانات الطلب');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'حدث خطأ في جلب بيانات الطلب');
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
                        showAlert('تم إلغاء الطلب بنجاح');
                        location.reload();
                    } else {
                        showAlert(data.message || 'حدث خطأ أثناء إلغاء الطلب', 'error');
                    }
                })
                .catch(error => {
                    showAlert('حدث خطأ في إرسال البيانات', 'error');
                });
            }
        }

        // تفعيل/تعطيل رؤية كلمة المرور
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
        
        // معالجة نموذج تغيير كلمة المرور
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم تغيير كلمة المرور بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                    this.reset();
                } else {
                    showAlert(data.message || 'حدث خطأ أثناء تغيير كلمة المرور');
                }
            })
            .catch(error => {
                showAlert('حدث خطأ في إرسال البيانات');
            });
        });

        function openWhatsApp(orderData) {
            let phone = orderData.phone.replace(/^0+/, '');
            if (!phone.startsWith('966')) {
                phone = '966' + phone;
            }
            
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

            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
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
            console.log('Opening complaint modal with:', { requestId, driverId });
            
            document.getElementById('complaint_request_id').value = requestId;
            document.getElementById('complaint_driver_id').value = driverId;
            
            // Reset form
            document.getElementById('complaintForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('complaintModal')).show();
            
            // Log the values after setting them
            console.log('Form values after setting:', {
                request_id: document.getElementById('complaint_request_id').value,
                driver_id: document.getElementById('complaint_driver_id').value
            });
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert-float alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // دالة لإخفاء التنبيه
        function hideAlert() {
            const alert = document.getElementById('alertFloat');
            alert.classList.remove('show');
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
                        showAlert('تم إرجاع الطلب إلى حالة الانتظار بنجاح');
                        location.reload();
                    } else {
                        showAlert(data.message || 'حدث خطأ أثناء إرجاع الطلب', 'error');
                    }
                })
                .catch(error => {
                    showAlert('حدث خطأ في إرسال البيانات', 'error');
                });
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
                    // Show success message with animation
                    showAlert('success', 'تم تحديث الطلب بنجاح');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editOrderModal'));
                    modal.hide();
                    
                    // Refresh the specific row instead of full page reload
                    updateOrderRow(data.order);
                } else {
                    // Show error messages
                    if (data.errors && Array.isArray(data.errors)) {
                        data.errors.forEach(error => {
                            showFormError(error);
                        });
                    } else {
                        showAlert('danger', data.message || 'حدث خطأ أثناء تحديث الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                // Hide loading overlay
                hideLoadingOverlay();
            });
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

            // Validate total cost - properly handle zero values
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

        function updateOrderRow(orderData) {
            const row = document.querySelector(`tr[data-order-id="${orderData.id}"]`);
            if (row) {
                // Update each cell with new data
                row.querySelector('.order-number').textContent = orderData.order_number;
                row.querySelector('.customer-info').innerHTML = `
                    <div>${orderData.customer_name}</div>
                    <div class="customer-phone">${orderData.customer_phone}</div>
                `;
                // Update other cells...

                // Add highlight animation
                row.classList.add('highlight-update');
                setTimeout(() => {
                    row.classList.remove('highlight-update');
                }, 2000);
            }
        }

        function clearFormErrors() {
            // Remove all existing error messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        function showFormError(error) {
            // Find the field mentioned in the error message
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

            // Try to find the field ID based on the error message
            let fieldId = null;
            for (const [label, id] of Object.entries(fields)) {
                if (error.includes(label)) {
                    fieldId = id;
                    break;
                }
            }

            if (fieldId) {
                // Add error to specific field
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = error;
                    field.parentNode.appendChild(feedback);
                }
            } else {
                // Show general error if field not found
                showAlert('danger', error);
            }
        }

        // Add form validation before submission
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateOrder(document.getElementById('edit_order_id').value);
        });

        // Initialize form fields when modal opens
        $('#editOrderModal').on('show.bs.modal', function (event) {
            clearFormErrors();
            const button = $(event.relatedTarget);
            const orderId = button.data('order-id');
            
            // Fetch order details and populate form
            fetch(`ajax/get_order.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditForm(data.order);
                    } else {
                        showAlert('danger', data.message);
                        $('#editOrderModal').modal('hide');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'حدث خطأ أثناء تحميل بيانات الطلب');
                    $('#editOrderModal').modal('hide');
                });
        });

        // Clear form when modal closes
        $('#editOrderModal').on('hidden.bs.modal', function () {
            document.getElementById('editOrderForm').reset();
            clearFormErrors();
        });
    </script>
</body>
</html> 