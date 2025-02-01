<?php
require_once '../config.php';
require_once '../includes/header.php';
// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'drivers_supervisor') {
    header('Location: ../index.php');
    exit;
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
                              <button type="button" class="btn btn-sm btn-info" 
    onclick="showOrderDetails(
        <?php echo $order['id']; ?>,
        '<?php echo $order['order_number']; ?>',
        '<?php echo $order['company_name']; ?>',
        '<?php echo $order['customer_name']; ?>',
        '<?php echo $order['customer_phone']; ?>',
        '<?php echo $order['order_type']; ?>',
        '<?php echo $order['delivery_date']; ?>',
        '<?php echo $order['total_cost']; ?>',
        '<?php echo $order['payment_status']; ?>',
        '<?php echo $order['status']; ?>',
        '<?php echo $order['driver_name'] ?? ''; ?>',
        '<?php echo $order['driver_phone'] ?? ''; ?>',
        '<?php echo $order['created_at']; ?>'
    )" 
    title="عرض التفاصيل">
    <i class="fas fa-eye"></i>
</button>
                                    
                                    <!-- طباعة الفاتورة -->
                                    <button type="button" class="btn btn-sm btn-primary" onclick="printInvoice(<?php echo $order['id']; ?>, '<?php echo $order['order_number']; ?>', '<?php echo $order['customer_name']; ?>', '<?php echo $order['customer_phone']; ?>', '<?php echo $order['total_cost']; ?>', '<?php echo $order['payment_status']; ?>', '<?php echo $order['delivery_date']; ?>')" title="طباعة الفاتورة">
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

<style>
    .sidebar {
        background-color: #1e1e1e; /* Dark background for sidebar */
        color: #ffffff; /* Light text color */
        height: 100vh; /* Full height */
        position: fixed; /* Fixed position */
        top: 0;
        right: 0; /* Adjust if your layout is LTR */
        width: 250px; /* Set width */
        z-index: 1000; /* Ensure it is on top */
    }

    .sidebar a {
        color: #ffffff; /* Light text color for links */
    }

    .sidebar a:hover {
        background-color: #333333; /* Slightly lighter on hover */
    }
</style>

<!-- View Order Details Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    تفاصيل الطلب #<span id="orderNumberSpan"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<style>
.modal-backdrop {
    --bs-backdrop-zindex: 1050;
    --bs-backdrop-bg: #000;
    --bs-backdrop-opacity: 0.5;
    position: fixed;
    top: 0;
    left: 0;
    z-index: var(--bs-backdrop-zindex);
    width: 100vw;
    height: 100vh;
    background-color: var(--bs-backdrop-bg);
}

.modal {
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-dialog {
    margin: 30px auto;
    max-width: 800px;
}

.modal-content {
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-radius: 8px 8px 0 0;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    border-radius: 0 0 8px 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تعريف المتغيرات العامة
    let currentModal = null;
    const modalOptions = {
        backdrop: 'static',
        keyboard: false,
        focus: true
    };

    // تهيئة المودال
    const modalEl = document.getElementById('viewOrderModal');
    if (modalEl) {
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
    }
});

function showOrderDetails(
    orderId, 
    orderNumber, 
    companyName, 
    customerName, 
    customerPhone, 
    orderType, 
    deliveryDate, 
    totalCost, 
    paymentStatus, 
    orderStatus,
    driverName,
    driverPhone,
    createdAt
) {
    // إنشاء عناصر الحالة بناءً على orderStatus
    let statusText, statusClass;
    switch(orderStatus) {
        case 'pending':
            statusText = 'قيد الانتظار';
            statusClass = 'warning';
            break;
        case 'accepted':
            statusText = 'مقبول';
            statusClass = 'info';
            break;
        case 'in_transit':
            statusText = 'قيد التوصيل';
            statusClass = 'primary';
            break;
        case 'delivered':
            statusText = 'تم التوصيل';
            statusClass = 'success';
            break;
        case 'cancelled':
            statusText = 'ملغي';
            statusClass = 'danger';
            break;
        default:
            statusText = 'غير معروف';
            statusClass = 'secondary';
    }

    const detailsHtml = `
        <div class="row">
            <!-- معلومات الطلب -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-shopping-cart text-primary me-2"></i>
                            <h6 class="mb-0">معلومات الطلب</h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong>رقم الطلب:</strong> ${orderNumber}</p>
                            <p class="mb-1"><strong>نوع الطلب:</strong> ${orderType}</p>
                            <p class="mb-1"><strong>تاريخ الإنشاء:</strong> ${new Date(createdAt).toLocaleString()}</p>
                            <p class="mb-1"><strong>تاريخ التوصيل:</strong> ${new Date(deliveryDate).toLocaleString()}</p>
                            <p class="mb-1">
                                <strong>الحالة:</strong> 
                                <span class="badge bg-${statusClass}">${statusText}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات الشركة -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-building text-primary me-2"></i>
                            <h6 class="mb-0">معلومات الشركة</h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong>الشركة:</strong> ${companyName}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات العميل -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user text-primary me-2"></i>
                            <h6 class="mb-0">معلومات العميل</h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong>اسم العميل:</strong> ${customerName}</p>
                            <p class="mb-1">
                                <strong>رقم الهاتف:</strong>
                                <a href="tel:${customerPhone}" class="text-decoration-none">
                                    ${customerPhone}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات السائق -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-truck text-primary me-2"></i>
                            <h6 class="mb-0">معلومات السائق</h6>
                        </div>
                        <div class="ms-4">
                            ${driverName ? `
                                <p class="mb-1"><strong>اسم السائق:</strong> ${driverName}</p>
                                <p class="mb-1">
                                    <strong>رقم الهاتف:</strong>
                                    <a href="tel:${driverPhone}" class="text-decoration-none me-2">
                                        ${driverPhone}
                                    </a>
                                    ${driverPhone ? `
                                    <a href="https://wa.me/${driverPhone}" class="btn btn-sm btn-success">
                                        <i class="fab fa-whatsapp"></i> واتساب
                                    </a>
                                    ` : ''}
                                </p>
                            ` : `
                                <p class="text-muted">لم يتم تعيين سائق بعد</p>
                            `}
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات الدفع -->
            <div class="col-12">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-money-bill text-primary me-2"></i>
                            <h6 class="mb-0">معلومات الدفع</h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong>التكلفة الإجمالية:</strong> ${parseFloat(totalCost).toFixed(2)} ريال</p>
                            <p class="mb-1">
                                <strong>حالة الدفع:</strong>
                                <span class="badge bg-${paymentStatus === 'paid' ? 'success' : 'warning'}">
                                    ${paymentStatus === 'paid' ? 'مدفوع' : 'غير مدفوع'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="row mt-4">
            <div class="col-12">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    إغلاق
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = detailsHtml;
    document.getElementById('orderNumberSpan').textContent = orderNumber;
    
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'), {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();
}

// دالة لطباعة الفاتورة
function printInvoice(orderId, orderNumber, customerName, customerPhone, totalCost, paymentStatus, deliveryDate) {
    // Create invoice content
    const invoiceContent = `
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2>فاتورة طلب</h2>
                <p>رقم الفاتورة: #${orderNumber}</p>
            </div>
            
            <!-- معلومات الطلب -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات الطلب</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>رقم الطلب:</strong> ${orderNumber}</p>
                        <p class="mb-1"><strong>نوع الطلب:</strong> transport</p>
                        <p class="mb-1"><strong>تاريخ الإنشاء:</strong> ${deliveryDate}</p>
                        <p class="mb-1"><strong>تاريخ التوصيل:</strong> ${deliveryDate}</p>
                    </div>
                </div>
            </div>

            <!-- معلومات العميل -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات العميل</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>اسم العميل:</strong> ${customerName}</p>
                        <p class="mb-1"><strong>رقم الهاتف:</strong> ${customerPhone}</p>
                    </div>
                </div>
            </div>

            <!-- معلومات الدفع -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">معلومات الدفع</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>التكلفة الإجمالية:</strong> ${totalCost} ريال</p>
                        <p class="mb-1">
                            <strong>حالة الدفع:</strong>
                            <span class="badge bg-${paymentStatus === 'paid' ? 'success' : 'warning'}">
                                ${paymentStatus === 'paid' ? 'مدفوع' : 'غير مدفوع'}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Create a new window
    const printWindow = window.open('', '_blank');
    
    // Write the HTML content
    printWindow.document.write(`
        <!DOCTYPE html>
        <html dir="rtl" lang="ar">
        <head>
            <meta charset="UTF-8">
            <title>فاتورة #${orderNumber}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    padding: 20px;
                }
                .invoice-content {
                    max-width: 800px;
                    margin: 0 auto;
                }
                @media print {
                    .no-print {
                        display: none !important;
                    }
                    .card {
                        border: 1px solid #dee2e6 !important;
                    }
                    .card-header {
                        background-color: #f8f9fa !important;
                        border-bottom: 1px solid #dee2e6 !important;
                    }
                    .badge {
                        border: 1px solid #000 !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="invoice-content">
                ${invoiceContent}
            </div>
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> طباعة
                </button>
            </div>
        </body>
        </html>
    `);
    
    // Close the document
    printWindow.document.close();
}

// دالة لتحديث حالة الطلب
function updateOrderStatus(orderId, status) {
    // ... الكود الخاص بتحديث الحالة
}

// دالة لتعيين السائق
function assignDriver(orderId, driverId) {
    // ... الكود الخاص بتعيين السائق
}
</script>

<?php require_once '../includes/footer.php'; ?> 