<?php
require_once '../config.php';
require_once '../includes/header.php';

// Check permissions
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}


// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    
    // Add notification
    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['admin_id'], "تم تحديث حالة الطلب رقم: " . $order_id, "info", "orders.php"]);
    
    header('Location: orders.php');
    exit;
}

// Handle order deletion
if (isset($_POST['delete']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    
    // Get request number for notification
    $stmt = $conn->prepare("SELECT request_number FROM requests WHERE id = ?");
    $stmt->execute([$order_id]);
    $request_number = $stmt->fetchColumn();
    
    // Delete request
    $stmt = $conn->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->execute([$order_id]);
    
    // Add notification
    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['admin_id'], "تم حذف الطلب رقم: " . $request_number, "warning", "orders.php"]);
    
    header('Location: orders.php');
    exit;
}

// Get orders list with search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];

if ($search) {
    $where[] = "(r.id LIKE :search OR c.name LIKE :search OR r.customer_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter) {
    $where[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

// تحديث استعلام العد
$stmt = $conn->prepare("SELECT COUNT(*) FROM requests r 
    LEFT JOIN companies c ON r.company_id = c.id" . $where_clause);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// تحديث استعلام جلب البيانات
$stmt = $conn->prepare("
    SELECT 
        r.id,
        r.order_number,
        c.name as company_name,
        r.customer_name,
        r.customer_phone,
        r.order_type,
        r.delivery_date,
        r.total_cost,
        r.payment_status,
        r.status,
        r.driver_id,
        r.created_at,
        r.updated_at,
        d.username as driver_name,
        d.phone as driver_phone
    FROM requests r 
    LEFT JOIN companies c ON r.company_id = c.id
    LEFT JOIN drivers d ON r.driver_id = d.id" . 
    $where_clause . 
    " ORDER BY r.created_at DESC LIMIT :offset, :per_page");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

// Get available drivers for assignment
$stmt = $conn->prepare("SELECT id, username FROM drivers WHERE is_active = 1 ORDER BY username");
$stmt->execute();
$drivers = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">إدارة الطلبات</h1>
    <!-- <a href="order_form.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> إضافة طلب جديد
    </a> -->
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="البحث في الطلبات..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="">جميع الحالات</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                    <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>مقبول</option>
                    <option value="in_transit" <?php echo $status_filter === 'in_transit' ? 'selected' : ''; ?>>قيد التوصيل</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>تم التوصيل</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                </select>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>الشركة</th>
                        <th>العميل</th>
                        <th>نوع الطلب</th>
                        <th>تاريخ التوصيل</th>
                        <th>وقت الطلب</th>
                        <th>التكلفة</th>
                        <th>حالة الدفع</th>
                        <th>السائق</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_type']); ?></td>
                            <td><?php echo date('Y/m/d H:i', strtotime($order['delivery_date'])); ?></td>
                            <td><?php echo date('Y/m/d H:i:s', strtotime($order['created_at'])); ?></td>
                            <td><?php echo number_format($order['total_cost'], 2); ?> ريال</td>
                            <td>
                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($order['driver_id']): ?>
                                    <?php echo htmlspecialchars($order['driver_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">لم يتم التعيين</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($order['status']) {
                                    case 'pending':
                                        $status_class = 'warning';
                                        $status_text = 'قيد الانتظار';
                                        break;
                                    case 'accepted':
                                        $status_class = 'info';
                                        $status_text = 'مقبول';
                                        break;
                                    case 'in_transit':
                                        $status_class = 'primary';
                                        $status_text = 'قيد التوصيل';
                                        break;
                                    case 'delivered':
                                        $status_class = 'success';
                                        $status_text = 'تم التوصيل';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'danger';
                                        $status_text = 'ملغي';
                                        break;
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <!-- عرض التفاصيل -->
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewOrderModal<?php echo $order['id']; ?>" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- طباعة الفاتورة -->
                                    <button type="button" class="btn btn-sm btn-primary" onclick="printInvoice(<?php echo $order['id']; ?>)" title="طباعة الفاتورة">
                                        <i class="fas fa-print"></i>
                                    </button>

                                    <!-- تتبع الطلب -->
                                    <a href="../track_order.php?order_number=<?php echo $order['order_number']; ?>" class="btn btn-sm btn-success" target="_blank" title="تتبع الطلب">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </a>

                                    <!-- القائمة المنسدلة للإجراءات الأخرى -->
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="order_form.php?id=<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit me-1"></i> تعديل
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignDriverModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-user-tie me-1"></i> تعيين سائق
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-sync-alt me-1"></i> تحديث الحالة
                                            </a>
                                        </li>
                                        <?php if (hasPermission('super_admin')): ?>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف الطلب؟');">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <button type="submit" name="delete" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-1"></i> حذف
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>

                                <!-- View Order Details Modal -->
                                <div class="modal fade" id="viewOrderModal<?php echo $order['id']; ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-modal="true" role="dialog">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    تفاصيل الطلب #<?php echo $order['order_number']; ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-4">
                                                    <!-- Order Information -->
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>معلومات الطلب</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <ul class="list-unstyled mb-0">
                                                                    <li class="mb-2">
                                                                        <strong>رقم الطلب:</strong> <?php echo $order['order_number']; ?>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>نوع الطلب:</strong> <?php echo $order['order_type']; ?>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>تاريخ الإنشاء:</strong> <?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>تاريخ التوصيل:</strong> <?php echo date('Y/m/d H:i', strtotime($order['delivery_date'])); ?>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>الحالة:</strong> 
                                                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Customer Information -->
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0"><i class="fas fa-user me-2"></i>معلومات العميل</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <ul class="list-unstyled mb-0">
                                                                    <li class="mb-2">
                                                                        <strong>اسم العميل:</strong> <?php echo $order['customer_name']; ?>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>رقم الهاتف:</strong> 
                                                                        <a href="tel:<?php echo $order['customer_phone']; ?>" class="text-decoration-none">
                                                                            <?php echo $order['customer_phone']; ?>
                                                                            <i class="fas fa-phone-alt ms-1"></i>
                                                                        </a>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>الشركة:</strong> <?php echo $order['company_name']; ?>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Payment Information -->
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0"><i class="fas fa-money-bill me-2"></i>معلومات الدفع</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <ul class="list-unstyled mb-0">
                                                                    <li class="mb-2">
                                                                        <strong>التكلفة الإجمالية:</strong> 
                                                                        <span class="text-primary"><?php echo number_format($order['total_cost'], 2); ?> ريال</span>
                                                                    </li>
                                                                    <li class="mb-2">
                                                                        <strong>حالة الدفع:</strong>
                                                                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                                            <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                                                        </span>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Driver Information -->
                                                    <div class="col-md-6">
                                                        <div class="card h-100">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0"><i class="fas fa-truck me-2"></i>معلومات السائق</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <?php if ($order['driver_id']): ?>
                                                                    <ul class="list-unstyled mb-0">
                                                                        <li class="mb-2">
                                                                            <strong>اسم السائق:</strong> <?php echo $order['driver_name']; ?>
                                                                        </li>
                                                                        <li class="mb-2">
                                                                            <strong>رقم الهاتف:</strong>
                                                                            <?php if (!empty($order['driver_phone'])): ?>
                                                                                <?php
                                                                                // معالجة رقم الهاتف للواتساب
                                                                                $driver_phone = $order['driver_phone'];
                                                                                $whatsapp_number = preg_replace('/[^0-9]/', '', $driver_phone);
                                                                                if (!str_starts_with($whatsapp_number, '966')) {
                                                                                    $whatsapp_number = '966' . ltrim($whatsapp_number, '0');
                                                                                }
                                                                                ?>
                                                                                <a href="tel:<?php echo $driver_phone; ?>" class="text-decoration-none me-2">
                                                                                    <?php echo $driver_phone; ?>
                                                                                    <i class="fas fa-phone-alt ms-1"></i>
                                                                                </a>
                                                                                <a href="https://wa.me/<?php echo $whatsapp_number; ?>" class="btn btn-sm btn-success" target="_blank">
                                                                                    <i class="fab fa-whatsapp"></i> واتساب
                                                                                </a>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">لا يوجد رقم هاتف</span>
                                                                            <?php endif; ?>
                                                                        </li>
                                                                    </ul>
                                                                <?php else: ?>
                                                                    <p class="text-muted mb-0">لم يتم تعيين سائق بعد</p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                                <?php if ($order['driver_id'] && !empty($order['driver_phone'])): ?>
                                                    <?php
                                                    // معالجة رقم الهاتف للواتساب
                                                    $whatsapp_number = preg_replace('/[^0-9]/', '', $order['driver_phone']);
                                                    if (!str_starts_with($whatsapp_number, '966')) {
                                                        $whatsapp_number = '966' . ltrim($whatsapp_number, '0');
                                                    }
                                                    ?>
                                                    <a href="https://wa.me/<?php echo $whatsapp_number; ?>" class="btn btn-success" target="_blank">
                                                        <i class="fab fa-whatsapp me-1"></i> تواصل عبر واتساب
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary" onclick="printInvoice(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-print me-1"></i> طباعة الفاتورة
                                                </button>
                                            </div>

                                            <!-- Invoice Print Section (Hidden by default) -->
                                            <div id="invoice-print-<?php echo $order['id']; ?>" class="d-none">
                                                <div class="invoice-print-content p-4">
                                                    <!-- Invoice Header -->
                                                    <div class="text-center mb-4">
                                                        <h2 class="mb-1">فاتورة طلب توصيل</h2>
                                                        <p class="mb-1">رقم الفاتورة: #<?php echo $order['order_number']; ?></p>
                                                        <p class="text-muted"><?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?></p>
                                                    </div>

                                                    <div class="row mb-4">
                                                        <!-- Company Details -->
                                                        <div class="col-6">
                                                            <h5 class="mb-2">تفاصيل الشركة</h5>
                                                            <p class="mb-1"><strong>الشركة:</strong> <?php echo $order['company_name']; ?></p>
                                                        </div>
                                                        <!-- Customer Details -->
                                                        <div class="col-6 text-end">
                                                            <h5 class="mb-2">تفاصيل العميل</h5>
                                                            <p class="mb-1"><strong>الاسم:</strong> <?php echo $order['customer_name']; ?></p>
                                                            <p class="mb-1"><strong>الهاتف:</strong> <?php echo $order['customer_phone']; ?></p>
                                                        </div>
                                                    </div>

                                                    <!-- Order Details -->
                                                    <div class="row mb-4">
                                                        <div class="col-12">
                                                            <table class="table table-bordered">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>تفاصيل الطلب</th>
                                                                        <th>المعلومات</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>نوع الطلب</td>
                                                                        <td><?php echo $order['order_type']; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>تاريخ التوصيل</td>
                                                                        <td><?php echo date('Y/m/d H:i', strtotime($order['delivery_date'])); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>حالة الطلب</td>
                                                                        <td><?php echo $status_text; ?></td>
                                                                    </tr>
                                                                    <?php if ($order['driver_id']): ?>
                                                                    <tr>
                                                                        <td>السائق</td>
                                                                        <td><?php echo $order['driver_name']; ?></td>
                                                                    </tr>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Payment Details -->
                                                    <div class="row mb-4">
                                                        <div class="col-12">
                                                            <table class="table table-bordered">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th>تفاصيل الدفع</th>
                                                                        <th>المبلغ</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>التكلفة الإجمالية</td>
                                                                        <td><?php echo number_format($order['total_cost'], 2); ?> ريال</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>حالة الدفع</td>
                                                                        <td>
                                                                            <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                                                <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Footer -->
                                                    <div class="text-center mt-4">
                                                        <p class="mb-1">شكراً لاستخدامكم خدماتنا</p>
                                                        <small class="text-muted">هذه الفاتورة تم إنشاؤها بواسطة النظام</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // تعريف المتغيرات العامة
                                    let currentModal = null;
                                    const modalOptions = {
                                        backdrop: 'static',
                                        keyboard: false,
                                        focus: true
                                    };

                                    // تهيئة جميع المودالات
                                    document.querySelectorAll('[id^="viewOrderModal"]').forEach(modalEl => {
                                        // إنشاء كائن المودال
                                        const modal = new bootstrap.Modal(modalEl, modalOptions);
                                        
                                        // إضافة مستمع لحدث فتح المودال
                                        modalEl.addEventListener('show.bs.modal', function() {
                                            currentModal = modal;
                                            // منع التفاعل مع العناصر خلف المودال
                                            document.body.style.pointerEvents = 'none';
                                            modalEl.style.pointerEvents = 'auto';
                                        });

                                        // إضافة مستمع لحدث إغلاق المودال
                                        modalEl.addEventListener('hidden.bs.modal', function() {
                                            currentModal = null;
                                            // إعادة التفاعل مع العناصر
                                            document.body.style.pointerEvents = 'auto';
                                        });

                                        // منع إغلاق المودال عند النقر خارجه
                                        modalEl.addEventListener('click', function(e) {
                                            if (e.target === modalEl) {
                                                e.stopPropagation();
                                                return false;
                                            }
                                        });

                                        // منع إغلاق المودال بزر ESC
                                        modalEl.addEventListener('keydown', function(e) {
                                            if (e.key === 'Escape') {
                                                e.preventDefault();
                                                return false;
                                            }
                                        });

                                        // إضافة مستمع حدث لزر الإغلاق فقط
                                        const closeBtn = modalEl.querySelector('.btn-close');
                                        if (closeBtn) {
                                            closeBtn.addEventListener('click', function() {
                                                if (currentModal) {
                                                    currentModal.hide();
                                                }
                                            });
                                        }
                                    });

                                    // منع التفاعل مع العناصر خلف المودال عند فتحه
                                    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
                                        button.addEventListener('click', function(e) {
                                            e.stopPropagation();
                                        });
                                    });
                                });

                                function printInvoice(orderId) {
                                    // Get the invoice content
                                    const invoiceContent = document.getElementById(`invoice-print-${orderId}`).innerHTML;
                                    
                                    // Create a new window
                                    const printWindow = window.open('', '_blank');
                                    
                                    // Write the HTML content
                                    printWindow.document.write(`
                                        <!DOCTYPE html>
                                        <html dir="rtl" lang="ar">
                                        <head>
                                            <meta charset="UTF-8">
                                            <title>فاتورة #${orderId}</title>
                                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
                                            <style>
                                                body {
                                                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                                                    padding: 20px;
                                                }
                                                .invoice-print-content {
                                                    max-width: 800px;
                                                    margin: 0 auto;
                                                }
                                                @media print {
                                                    .no-print {
                                                        display: none !important;
                                                    }
                                                    .table {
                                                        border-color: #dee2e6 !important;
                                                    }
                                                    .table-bordered {
                                                        border: 1px solid #dee2e6 !important;
                                                    }
                                                    .badge {
                                                        border: 1px solid #000;
                                                    }
                                                }
                                            </style>
                                        </head>
                                        <body>
                                            ${invoiceContent}
                                            <div class="text-center mt-4 no-print">
                                                <button onclick="window.print()" class="btn btn-primary">
                                                    <i class="fas fa-print me-1"></i> طباعة
                                                </button>
                                            </div>
                                        </body>
                                        </html>
                                    `);
                                    
                                    // Close the document
                                    printWindow.document.close();
                                }
                                </script>

                                <style>
                                /* تثبيت السايدبار بشكل كامل */
                                .main-sidebar {
                                    position: fixed !important;
                                    right: 0 !important;
                                    top: 0 !important;
                                    bottom: 0 !important;
                                    width: 250px !important;
                                    z-index: 1040 !important;
                                }

                                /* منع تغيير حجم السايدبار */
                                .sidebar-mini .main-sidebar,
                                .sidebar-mini.sidebar-collapse .main-sidebar,
                                .sidebar-mini.sidebar-collapse .main-sidebar:hover,
                                .sidebar-mini.sidebar-collapse .main-sidebar .nav-sidebar.nav-child-indent .nav-treeview,
                                .sidebar-mini.sidebar-collapse .main-sidebar:hover .nav-sidebar.nav-child-indent .nav-treeview {
                                    width: 250px !important;
                                    transform: none !important;
                                    transition: none !important;
                                }

                                /* إظهار النص دائماً */
                                .sidebar-mini.sidebar-collapse .main-sidebar .nav-sidebar > .nav-item > .nav-link > span,
                                .nav-sidebar > .nav-item .nav-icon,
                                .nav-sidebar > .nav-item > .nav-link > span {
                                    display: inline-block !important;
                                    visibility: visible !important;
                                    opacity: 1 !important;
                                    transform: none !important;
                                    transition: none !important;
                                    margin-left: 0 !important;
                                }

                                /* تثبيت المحتوى */
                                .content-wrapper {
                                    margin-right: 250px !important;
                                    width: calc(100% - 250px) !important;
                                    transition: none !important;
                                }

                                /* منع التمرير الأفقي */
                                body {
                                    overflow-x: hidden !important;
                                }

                                /* تحسين مظهر المودال */
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.5);
                                }
                                .modal-backdrop {
                                    display: none !important;
                                }
                                .modal-dialog {
                                    margin: 30px auto !important;
                                }
                                </style>

                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // إلغاء خاصية طي السايدبار
                                    const body = document.querySelector('body');
                                    body.classList.remove('sidebar-collapse');
                                    body.classList.add('sidebar-open');
                                    
                                    // منع إضافة كلاس sidebar-collapse
                                    const observer = new MutationObserver(function(mutations) {
                                        mutations.forEach(function(mutation) {
                                            if (mutation.target.classList.contains('sidebar-collapse')) {
                                                mutation.target.classList.remove('sidebar-collapse');
                                            }
                                        });
                                    });
                                    
                                    observer.observe(body, {
                                        attributes: true,
                                        attributeFilter: ['class']
                                    });
                                });
                                </script>

                                <!-- Assign Driver Modal -->
                                <div class="modal fade" id="assignDriverModal<?php echo $order['id']; ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تعيين سائق للطلب #<?php echo $order['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">اختر السائق</label>
                                                        <select class="form-select" name="driver_id" required>
                                                            <option value="">-- اختر السائق --</option>
                                                            <?php foreach ($drivers as $driver): ?>
                                                                <option value="<?php echo $driver['id']; ?>" <?php echo $order['driver_id'] == $driver['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($driver['username']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" name="assign_driver" class="btn btn-primary">تعيين</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تحديث حالة الطلب #<?php echo $order['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">اختر الحالة الجديدة</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                                            <option value="accepted" <?php echo $order['status'] === 'accepted' ? 'selected' : ''; ?>>مقبول</option>
                                                            <option value="in_transit" <?php echo $order['status'] === 'in_transit' ? 'selected' : ''; ?>>قيد التوصيل</option>
                                                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>تم التوصيل</option>
                                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" name="update_status" class="btn btn-primary">تحديث</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="10" class="text-center">لا توجد طلبات</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 