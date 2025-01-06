<?php
require_once '../config.php';

// Set staff header
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['company_id'])) {
    header("Location: staff_login.php");
    exit();
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

        function editOrder(orderId) {
            // Implementation of edit order
            console.log('Edit order:', orderId);
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

        // دالة لعرض التنبيه
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alertFloat');
            const messageElement = document.getElementById('alertMessage');
            
            alert.style.background = type === 'success' 
                ? 'linear-gradient(45deg, #4CAF50, #45a049)'
                : 'linear-gradient(45deg, #f44336, #d32f2f)';
            
            messageElement.textContent = message;
            alert.classList.add('show');
            
            setTimeout(() => {
                hideAlert();
            }, 3000);
        }

        // دالة لإخفاء التنبيه
        function hideAlert() {
            const alert = document.getElementById('alertFloat');
            alert.classList.remove('show');
        }
    </script>
</body>
</html> 