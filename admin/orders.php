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
    header('Location: index.php');
    exit;
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    
    // Add notification
    $saudi_timezone = new DateTimeZone('Asia/Riyadh');
    $date = new DateTime('now', $saudi_timezone);
    $formatted_date = $date->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['admin_id'], sprintf(__('order_status_updated'), $order_id), "info", "orders.php", $formatted_date]);
    
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
    $saudi_timezone = new DateTimeZone('Asia/Riyadh');
    $date = new DateTime('now', $saudi_timezone);
    $formatted_date = $date->format('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['admin_id'], sprintf(__('order_deleted'), $request_number), "warning", "orders.php", $formatted_date]);
    
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
    $where[] = "(r.id LIKE :search OR c.name LIKE :search OR r.customer_name LIKE :search OR r.order_number LIKE :search)";
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
    <h1 class="h3 mb-0"><?php echo __('orders_management'); ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="<?php echo __('search_orders'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value=""><?php echo __('all_statuses'); ?></option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>><?php echo __('status_pending'); ?></option>
                    <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>><?php echo __('status_accepted'); ?></option>
                    <option value="in_transit" <?php echo $status_filter === 'in_transit' ? 'selected' : ''; ?>><?php echo __('status_in_transit'); ?></option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>><?php echo __('status_delivered'); ?></option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>><?php echo __('status_cancelled'); ?></option>
                </select>
            </div>
        </form>

        <div class="row g-3">
            <?php foreach ($orders as $order): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo __('order_number'); ?> #<?php echo htmlspecialchars($order['id']); ?></h6>
                            <span class="badge bg-<?php 
                                switch ($order['status']) {
                                    case 'pending': echo 'warning'; break;
                                    case 'accepted': echo 'info'; break;
                                    case 'in_transit': echo 'primary'; break;
                                    case 'delivered': echo 'success'; break;
                                    case 'cancelled': echo 'danger'; break;
                                    default: echo 'secondary';
                                }
                            ?>">
                                <?php echo __('status_' . $order['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-building text-primary me-2"></i>
                                    <strong><?php echo __('company_name'); ?>:</strong>
                                    <span class="ms-2"><?php echo htmlspecialchars($order['company_name']); ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user text-primary me-2"></i>
                                    <strong><?php echo __('customer_name'); ?>:</strong>
                                    <span class="ms-2"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-box text-primary me-2"></i>
                                    <strong><?php echo __('order_type'); ?>:</strong>
                                    <span class="ms-2"><?php echo htmlspecialchars($order['order_type']); ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <strong><?php echo __('delivery_date'); ?>:</strong>
                                    <span class="ms-2"><?php echo date('Y/m/d H:i', strtotime($order['delivery_date'])); ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-money-bill text-primary me-2"></i>
                                    <strong><?php echo __('total_cost'); ?>:</strong>
                                    <span class="ms-2"><?php echo number_format($order['total_cost'], 2); ?> <?php echo __('currency_sar'); ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <!-- <i class="fas fa-credit-card text-primary me-2"></i>
                                    <strong><?php echo __('payment_status'); ?>:</strong> -->
                                    <!-- <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?> ms-2">
                                        <?php echo $order['payment_status'] === 'paid' ? __('paid') : __('unpaid'); ?>
                                    </span> -->
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <strong><?php echo __('driver_name'); ?>:</strong>
                                <span class="ms-2">
                                    <?php if ($order['driver_id']): ?>
                                        <?php echo htmlspecialchars($order['driver_name']); ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo __('not_assigned'); ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('Y/m/d H:i', strtotime($order['created_at'])); ?>
                                    </small>
                                    <!-- <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                        <i class="fas fa-<?php echo $order['payment_status'] === 'paid' ? 'check-circle' : 'clock'; ?> me-1"></i>
                                        <?php echo $order['payment_status'] === 'paid' ? __('paid') : __('unpaid'); ?>
                                    </span> -->
                                </div>
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="btn-group flex-grow-1">
                                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" 
                                            onclick="showOrderDetails(
                                                '<?php echo $order['id']; ?>',
                                                '<?php echo $order['order_number']; ?>',
                                                '<?php echo htmlspecialchars($order['company_name']); ?>',
                                                '<?php echo htmlspecialchars($order['customer_name']); ?>',
                                                '<?php echo htmlspecialchars($order['customer_phone']); ?>',
                                                '<?php echo htmlspecialchars($order['order_type']); ?>',
                                                '<?php echo $order['delivery_date']; ?>',
                                                '<?php echo $order['total_cost']; ?>',
                                                '<?php echo $order['payment_status']; ?>',
                                                '<?php echo $order['status']; ?>',
                                                '<?php echo htmlspecialchars($order['driver_name'] ?? ''); ?>',
                                                '<?php echo htmlspecialchars($order['driver_phone'] ?? ''); ?>',
                                                '<?php echo $order['created_at']; ?>'
                                            )">
                                            <i class="fas fa-eye me-1"></i>
                                            <span class="d-none d-sm-inline"><?php echo __('view_details'); ?></span>
                                        </button>
                                        
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="printInvoice(<?php echo $order['id']; ?>)" 
                                            title="<?php echo __('print_invoice'); ?>">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        
                                        <a href="../track_order.php?order_number=<?php echo $order['order_number']; ?>" 
                                            class="btn btn-outline-primary btn-sm"
                                            target="_blank" 
                                            title="<?php echo __('track_order'); ?>">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </a>
                                    </div>
                                    
                                    <div class="dropdown">
                                     
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item text-primary" href="order_form.php?id=<?php echo $order['id']; ?>">
                                                    <i class="fas fa-edit me-2"></i> <?php echo __('edit_order'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-info" href="#" data-bs-toggle="modal" data-bs-target="#assignDriverModal<?php echo $order['id']; ?>">
                                                    <i class="fas fa-user-tie me-2"></i> <?php echo __('assign_driver'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-success" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $order['id']; ?>">
                                                    <i class="fas fa-sync-alt me-2"></i> <?php echo __('update_status'); ?>
                                                </a>
                                            </li>
                                            <?php if (hasPermission('super_admin')): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" class="d-inline w-100" onsubmit="return confirm('<?php echo __('confirm_delete_order'); ?>');">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <button type="submit" name="delete" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i> <?php echo __('delete_order'); ?>
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo __('no_orders'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav aria-label="<?php echo __('page_navigation'); ?>" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <?php echo __('previous'); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>">
                                <?php echo __('next'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- View Order Details Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo __('order_details'); ?> #<span id="orderNumberSpan"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
       @media (max-width: 768px) {
       .modal-backdrop {
           opacity: 1 !important;
           background-color: rgba(0, 0, 0, 0.9) !important;
       }
       .modal-content {
           background-color: #fff !important;
           box-shadow: none !important;
       }
       .modal-dialog {
           margin: 0.5rem !important;
       }
       .modal {
           background-color: rgba(0, 0, 0, 0.9) !important;
       }
   }
      .modal-content {
       border: none !important;
       border-radius: 15px !important;
   }
   .modal-header {
       background: var(--bs-primary) !important;
       color: white !important;
       border-bottom: none !important;
   }
   .modal-footer {
       background-color: #f8f9fa !important;
       border-top: 1px solid #dee2e6 !important;
       border-radius: 0 0 15px 15px !important;
   }
.hover-shadow {
    transition: all 0.3s ease-in-out;
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-3px);
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    background: #fff;
}

.card-header {
    background: linear-gradient(45deg, #2b5876, #4e4376);
    color: white;
    border-bottom: none;
    padding: 1rem;
}

.card-header h6 {
    font-weight: 600;
    margin: 0;
}

.card-body {
    padding: 1.25rem;
}

.card-body .info-item {
    padding: 0.5rem;
    border-radius: 10px;
    transition: all 0.2s ease;
    margin-bottom: 0.5rem;
}

.card-body .info-item:hover {
    background-color: #f8f9fa;
}

.card-body .info-item i {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    margin-right: 0.75rem;
}

.card-body strong {
    color: #495057;
    font-weight: 600;
    min-width: 100px;
    display: inline-block;
}

.card-footer {
    background: #f8f9fa;
    border-top: 1px solid rgba(0,0,0,.05);
    padding: 1rem;
}

.btn-group {
    display: inline-flex;
    gap: 0.5rem;
}

.btn-group .btn {
    border-radius: 10px !important;
    padding: 0.5rem 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: hidden;
}

.btn-group .btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.3s ease-out, height 0.3s ease-out;
}

.btn-group .btn:hover::after {
    width: 200%;
    height: 200%;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
    border-radius: 8px;
    font-size: 0.85rem;
}

.badge i {
    margin-right: 0.4rem;
}

.dropdown-menu {
    padding: 0.5rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 0.5rem 2rem rgba(0,0,0,0.15);
}

.dropdown-item {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-bottom: 0.25rem;
}

.dropdown-item:last-child {
    margin-bottom: 0;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(-5px);
}

.dropdown-item i {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-right: 0.75rem;
    font-size: 0.9rem;
}

.text-primary i {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.text-info i {
    background: rgba(13, 202, 240, 0.1);
    color: #0dcaf0;
}

.text-success i {
    background: rgba(25, 135, 84, 0.1);
    color: #198754;
}

.text-danger i {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

@media (max-width: 768px) {
    .card {
        margin-bottom: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.4rem 0.8rem;
    }
    
    .card-body strong {
        min-width: 80px;
    }
}

@media (max-width: 576px) {
    .btn-group .btn {
        padding: 0.3rem 0.6rem;
    }
    
    .btn-group .btn i {
        margin-right: 0 !important;
    }
    
    .card-body .info-item {
        padding: 0.4rem;
    }
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
            statusText = '<?php echo __('status_pending'); ?>';
            statusClass = 'warning';
            break;
        case 'accepted':
            statusText = '<?php echo __('status_accepted'); ?>';
            statusClass = 'info';
            break;
        case 'in_transit':
            statusText = '<?php echo __('status_in_transit'); ?>';
            statusClass = 'primary';
            break;
        case 'delivered':
            statusText = '<?php echo __('status_delivered'); ?>';
            statusClass = 'success';
            break;
        case 'cancelled':
            statusText = '<?php echo __('status_cancelled'); ?>';
            statusClass = 'danger';
            break;
        default:
            statusText = '<?php echo __('status_unknown'); ?>';
            statusClass = 'secondary';
    }

    const detailsHtml = `
        <div class="row">
            <!-- Order Information -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-shopping-cart text-primary me-2"></i>
                            <h6 class="mb-0"><?php echo __('order_details'); ?></h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong><?php echo __('order_number'); ?>:</strong> ${orderNumber}</p>
                            <p class="mb-1"><strong><?php echo __('order_type'); ?>:</strong> ${orderType}</p>
                            <p class="mb-1"><strong><?php echo __('created_at'); ?>:</strong> ${new Date(createdAt).toLocaleString()}</p>
                            <p class="mb-1"><strong><?php echo __('delivery_date'); ?>:</strong> ${new Date(deliveryDate).toLocaleString()}</p>
                            <p class="mb-1">
                                <strong><?php echo __('status'); ?>:</strong> 
                                <span class="badge bg-${statusClass}">${statusText}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-building text-primary me-2"></i>
                            <h6 class="mb-0"><?php echo __('company_info'); ?></h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong><?php echo __('company_name'); ?>:</strong> ${companyName}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user text-primary me-2"></i>
                            <h6 class="mb-0"><?php echo __('customer_info'); ?></h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong><?php echo __('customer_name'); ?>:</strong> ${customerName}</p>
                            <p class="mb-1">
                                <strong><?php echo __('customer_phone'); ?>:</strong>
                                <a href="tel:${customerPhone}" class="text-decoration-none">
                                    ${customerPhone}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Information -->
            <div class="col-12 mb-3">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-truck text-primary me-2"></i>
                            <h6 class="mb-0"><?php echo __('driver_info'); ?></h6>
                        </div>
                        <div class="ms-4">
                            ${driverName ? `
                                <p class="mb-1"><strong><?php echo __('driver_name'); ?>:</strong> ${driverName}</p>
                                <p class="mb-1">
                                    <strong><?php echo __('driver_phone'); ?>:</strong>
                                    <a href="tel:${driverPhone}" class="text-decoration-none me-2">
                                        ${driverPhone}
                                    </a>
                                    ${driverPhone ? `
                                    <a href="https://wa.me/${driverPhone}" class="btn btn-sm btn-success">
                                        <i class="fab fa-whatsapp"></i> <?php echo __('contact_via_whatsapp'); ?>
                                    </a>
                                    ` : ''}
                                </p>
                            ` : `
                                <p class="text-muted"><?php echo __('not_assigned'); ?></p>
                            `}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="col-12">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-money-bill text-primary me-2"></i>
                            <h6 class="mb-0"><?php echo __('payment_info'); ?></h6>
                        </div>
                        <div class="ms-4">
                            <p class="mb-1"><strong><?php echo __('total_cost'); ?>:</strong> ${parseFloat(totalCost).toFixed(2)} <?php echo __('currency_sar'); ?></p>
                            <p class="mb-1">
                                <strong><?php echo __('payment_status'); ?>:</strong>
                                <span class="badge bg-${paymentStatus === 'paid' ? 'success' : 'warning'}">
                                    ${paymentStatus === 'paid' ? '<?php echo __('paid'); ?>' : '<?php echo __('unpaid'); ?>'}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = detailsHtml;
    document.getElementById('orderNumberSpan').textContent = orderNumber;
    
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    modal.show();
}

// دالة لطباعة الفاتورة
function printInvoice(orderId) {
    // فتح نافذة جديدة للطباعة
    const printWindow = window.open(`print_invoice.php?order_id=${orderId}`, '_blank', 'width=800,height=600');
    
    // التأكد من فتح النافذة بنجاح
    if (printWindow) {
        // الانتظار حتى تحميل المحتوى
        printWindow.onload = function() {
            // طباعة المحتوى تلقائياً
            printWindow.print();
        };
    } else {
        showAlert('يرجى السماح بالنوافذ المنبثقة للطباعة', 'warning');
    }
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