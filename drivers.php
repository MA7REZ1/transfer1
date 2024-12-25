<?php
require_once 'config.php';

// Check if user is logged in and has permission
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// Handle driver actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    // Verify CSRF token
    if (!isset($_GET['token']) || $_GET['token'] !== $_SESSION['token']) {
        $_SESSION['error'] = 'عملية غير مصرح بها';
        header('Location: drivers.php');
        exit;
    }
    
    $action = $_GET['action'];
    $driver_id = $_GET['id'];
    
    switch ($action) {
        case 'activate':
            $stmt = $conn->prepare("UPDATE drivers SET is_active = 1 WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'success', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم تفعيل السائق بنجاح", "drivers.php"]);
            
            header('Location: drivers.php');
            exit;
            
        case 'deactivate':
            $stmt = $conn->prepare("UPDATE drivers SET is_active = 0 WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'warning', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم تعطيل السائق", "drivers.php"]);
            
            header('Location: drivers.php');
            exit;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'danger', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم حذف السائق", "drivers.php"]);
            
            header('Location: drivers.php');
            exit;
    }
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Prepare the base query
$query = "SELECT d.*,
    COALESCE(AVG(dr.rating), 0) as rating,
    COUNT(DISTINCT r.id) as total_trips
    FROM drivers d
    LEFT JOIN driver_ratings dr ON d.id = dr.driver_id
    LEFT JOIN requests r ON d.id = r.driver_id AND r.status = 'delivered'
    WHERE 1=1";
$countQuery = "SELECT COUNT(DISTINCT d.id) FROM drivers d WHERE 1=1";
$params = [];

// Add search conditions if search is provided
if (!empty($search)) {
    $searchCondition = " AND (d.username LIKE :search1 OR d.email LIKE :search2 OR d.phone LIKE :search3)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $params = [
        ':search1' => "%$search%",
        ':search2' => "%$search%",
        ':search3' => "%$search%"
    ];
}

$query .= " GROUP BY d.id ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";

// Get total records for pagination
$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Get drivers
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$drivers = $stmt->fetchAll();

// Include header after all possible redirects
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">إدارة السائقين</h1>
    <a href="driver_form.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> إضافة سائق جديد
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="البحث عن سائق..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>الصورة</th>
                        <th>اسم السائق</th>
                        <th>البريد الإلكتروني</th>
                        <th>الهاتف</th>
                        <th>التقييم</th>
                        <th>عدد الرحلات</th>
                        <th>نوع المركب</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drivers as $driver): ?>
                        <tr>
                            <td>
                                <?php if ($driver['profile_image']): ?>
                                    <img src="uploads/profiles/<?php echo $driver['profile_image']; ?>" alt="Profile" class="img-thumbnail" style="width: 50px;">
                                <?php else: ?>
                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($driver['username']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($driver['email']); ?>
                                <button class="btn btn-sm btn-outline-secondary ms-2" 
                                        onclick="copyDriverInfo('<?php echo htmlspecialchars($driver['username']); ?>', '<?php echo htmlspecialchars($driver['email']); ?>', '<?php echo htmlspecialchars($driver['phone']); ?>', '<?php echo htmlspecialchars($driver['username']); ?>@123')" 
                                        title="نسخ بيانات السائق">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                            <td>
                                <div class="text-warning">
                                    <?php
                                    $rating = $driver['rating'];
                                    echo str_repeat('<i class="fas fa-star"></i>', floor($rating));
                                    echo fmod($rating, 1) > 0 ? '<i class="fas fa-star-half-alt"></i>' : '';
                                    echo str_repeat('<i class="far fa-star"></i>', 5 - ceil($rating));
                                    ?>
                                </div>
                            </td>
                            <td><?php echo $driver['total_trips']; ?></td>
                            <td><?php echo htmlspecialchars($driver['vehicle_type']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $driver['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $driver['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y/m/d', strtotime($driver['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="driver_form.php?id=<?php echo $driver['id']; ?>" 
                                       class="btn btn-sm btn-info" 
                                       title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($driver['is_active']): ?>
                                        <a href="?action=deactivate&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           title="تعطيل"
                                           onclick="return confirm('هل أنت متأكد من تعطيل هذا السائق؟')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                           class="btn btn-sm btn-success" 
                                           title="تفعيل"
                                           onclick="return confirm('هل أنت متأكد من تفعيل هذا السائق؟')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="حذف"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا السائق؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($drivers)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">لا يوجد سائقين مسجلين</p>
                            </td>
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
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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

<!-- Add JavaScript for copy functionality -->
<script>
function copyDriverInfo(name, email, phone, password) {
    const text = `اسم السائق: ${name}\nالبريد الإلكتروني: ${email}\nرقم الهاتف: ${phone}\nكلمة المرور: ${password}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('تم نسخ بيانات السائق بنجاح');
    }).catch(err => {
        console.error('فشل في نسخ البيانات:', err);
    });
}
</script> 