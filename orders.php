<?php
require_once 'config.php';
require_once 'includes/header.php';

// Check permissions
if (!hasPermission('admin') && !hasPermission('super_admin')) {
    header('Location: dashboard.php');
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
        d.username as driver_name
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
                                    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-bs-toggle="dropdown">
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
                                
                                <!-- Assign Driver Modal -->
                                <div class="modal fade" id="assignDriverModal<?php echo $order['id']; ?>" tabindex="-1">
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
                                <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1">
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

<?php require_once 'includes/footer.php'; ?> 