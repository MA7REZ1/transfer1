<?php
require_once '../config.php';

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
    
    // Get driver name first
    $stmt = $conn->prepare("SELECT username FROM drivers WHERE id = ?");
    $stmt->execute([$driver_id]);
    $driver_name = $stmt->fetchColumn();
    
    switch ($action) {
        case 'activate':
            $stmt = $conn->prepare("UPDATE drivers SET is_active = 1 WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'success', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تفعيل السائق: " . $driver_name,
                "drivers.php"
            ]);
            
            header('Location: drivers.php');
            exit;
            
        case 'deactivate':
            $stmt = $conn->prepare("UPDATE drivers SET is_active = 0 WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'warning', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تعطيل السائق: " . $driver_name,
                "drivers.php"
            ]);
            
            header('Location: drivers.php');
            exit;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$driver_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'danger', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم حذف السائق: " . $driver_name,
                "drivers.php"
            ]);
            
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
require_once '../includes/header.php';
?>
<style>
.driver-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.driver-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.avatar-placeholder {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-item i {
    width: 20px;
}

.driver-stats .col-6 > div {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 10px;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.action-btn-group {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}

.action-btn {
    width: 35px;
    height: 35px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
    border: none;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.action-btn.whatsapp-btn { background-color: #25D366; color: white; }
.action-btn.edit-btn { background-color: #3498db; color: white; }
.action-btn.deactivate-btn { background-color: #f1c40f; color: white; }
.action-btn.activate-btn { background-color: #2ecc71; color: white; }
.action-btn.delete-btn { background-color: #e74c3c; color: white; }

.action-btn::after {
    content: attr(data-title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

.action-btn:hover::after {
    visibility: visible;
    opacity: 1;
    bottom: calc(100% + 5px);
}

@media (max-width: 768px) {
    .action-btn {
        width: 32px;
        height: 32px;
    }

    .action-btn i {
        font-size: 0.875rem;
    }
}
</style>
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

        <div class="row g-4">
            <?php if (!empty($drivers)): ?>
                <?php foreach ($drivers as $driver): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 driver-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="driver-avatar me-3">
                                        <?php if ($driver['profile_image']): ?>
                                            <img src="..\Drivers\uploads\driver\<?php echo $driver['profile_image']; ?>" alt="Profile" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder">
                                                <i class="fas fa-user-circle fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($driver['username']); ?></h5>
                                        <span class="badge bg-<?php echo $driver['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $driver['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="driver-info mb-3">
                                    <div class="info-item mb-2">
                                        <i class="fas fa-envelope text-muted me-2"></i>
                                        <span class="text-truncate"><?php echo htmlspecialchars($driver['email']); ?></span>
                                    </div>
                                    <div class="info-item mb-2">
                                        <i class="fas fa-phone text-muted me-2"></i>
                                        <span><?php echo htmlspecialchars($driver['phone']); ?></span>
                                    </div>
                                    <div class="info-item mb-2">
                                        <i class="fas fa-car text-muted me-2"></i>
                                        <span><?php echo htmlspecialchars($driver['vehicle_type']); ?></span>
                                    </div>
                                </div>

                                <div class="driver-stats mb-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="p-2 rounded bg-light">
                                                <small class="d-block text-muted">التقييم</small>
                                                <div class="text-warning">
                                                    <?php
                                                    $rating = $driver['rating'];
                                                    echo str_repeat('<i class="fas fa-star"></i>', floor($rating));
                                                    echo fmod($rating, 1) > 0 ? '<i class="fas fa-star-half-alt"></i>' : '';
                                                    echo str_repeat('<i class="far fa-star"></i>', 5 - ceil($rating));
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 rounded bg-light">
                                                <small class="d-block text-muted">عدد الرحلات</small>
                                                <strong><?php echo $driver['total_trips']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="driver-status mb-3">
                                    <?php
                                    $status = $driver['current_status'];
                                    $statusMap = [
                                        'available' => ['ar' => 'السائق متاح', 'class' => 'success'],
                                        'busy' => ['ar' => 'مشغول في توصيل', 'class' => 'warning'],
                                        'offline' => ['ar' => 'غير متصل', 'class' => 'danger']
                                    ];
                                    $displayStatus = $statusMap[$status]['ar'] ?? 'حالة غير معروفة';
                                    $badgeClass = $statusMap[$status]['class'] ?? 'secondary';
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">الحالة الحالية:</span>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($displayStatus); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="action-btn-group">
                                    <button type="button" 
                                           class="action-btn whatsapp-btn"
                                           onclick="openCompanyWhatsApp('<?php echo htmlspecialchars($driver['phone']); ?>')"
                                           data-title="تواصل عبر واتساب">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <a href="driver_form.php?id=<?php echo $driver['id']; ?>" 
                                       class="action-btn edit-btn"
                                       data-title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($driver['is_active']): ?>
                                        <a href="?action=deactivate&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                           class="action-btn deactivate-btn"
                                           data-title="تعطيل"
                                           onclick="return confirm('هل أنت متأكد من تعطيل هذا السائق؟')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                           class="action-btn activate-btn"
                                           data-title="تفعيل"
                                           onclick="return confirm('هل أنت متأكد من تفعيل هذا السائق؟')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $driver['id']; ?>&token=<?php echo $_SESSION['token']; ?>" 
                                       class="action-btn delete-btn"
                                       data-title="حذف"
                                       onclick="return confirm('هل أنت متأكد من حذف هذا السائق؟')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <button type="button" 
                                            class="action-btn info-btn" 
                                            onclick="showDriverDetails(<?php echo $driver['id']; ?>)" 
                                            data-title="عرض التفاصيل"
                                            style="background-color: #17a2b8; color: white;">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">لا يوجد سائقين مسجلين</p>
                    </div>
                </div>
            <?php endif; ?>
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

<div class="modal fade" id="driverDetailsModal" tabindex="-1" aria-labelledby="driverDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverDetailsModalLabel">تفاصيل السائق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="driverDetailsContent">
                    <!-- Driver details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

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

// دالة لفتح محادثة واتساب مع الشركة
function openCompanyWhatsApp(phone) {
    // تنسيق رقم الهاتف (إزالة الصفر الأول إذا وجد وإضافة رمز الدولة)
    let formattedPhone = phone.replace(/^0+/, '');
    if (!formattedPhone.startsWith('966')) {
        formattedPhone = '966' + formattedPhone;
    }
    
    // إنشاء نص الرسالة
    const message = `مرحباً، أود الاستفسار عن خدماتكم`;

    // فتح رابط واتساب
    const whatsappUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}
function showDriverDetails(driverId) {
    fetch(`ajax/get_driver_details.php?id=${driverId}`)
        .then(response => {
            if (!response.ok) throw new Error('فشل الاتصال بالخادم');
            return response.json();
        })
        .then(data => {
            if (!data.success) throw new Error(data.message || 'فشل في جلب البيانات');
            
            const { driver, order_statuses, activity_logs } = data;
            const dateOptions = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            };

            // بناء محتوى تفاصيل السائق
            const driverContent = `
                <div class="driver-header mb-4">
                    <h4 class="fw-bold text-primary">${driver.username}</h4>
                    <div class="d-flex gap-3">
                        <span class="badge bg-secondary">${driver.vehicle_type}</span>
                        <span class="badge ${driver.is_active ? 'bg-success' : 'bg-danger'}">
                            ${driver.is_active ? 'نشط' : 'غير نشط'}
                        </span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3 text-muted">معلومات الاتصال</h5>
                                <p class="mb-2">
                                    <i class="fas fa-envelope me-2"></i>
                                    ${driver.email}
                                </p>
                                <p>
                                    <i class="fas fa-phone me-2"></i>
                                    ${driver.phone}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3 text-muted">إحصائيات الرحلات</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>المكتملة:</span>
                                    <span class="fw-bold text-success">${driver.total_trips}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>الملغاة:</span>
                                    <span class="fw-bold text-danger">${driver.cancelled_orders}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            // بناء حالة الطلبات
            const ordersContent = `
                <div class="mt-4">
                    <h5 class="mb-3 text-muted">حالة الطلبات الحالية</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card bg-warning bg-opacity-10 border-warning">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">قيد الانتظار</h6>
                                    <p class="display-6 text-center">${order_statuses.pending_orders}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info bg-opacity-10 border-info">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">تم القبول</h6>
                                    <p class="display-6 text-center">${order_statuses.accepted_orders}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary bg-opacity-10 border-primary">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">جاري التوصيل</h6>
                                    <p class="display-6 text-center">${order_statuses.in_transit_orders}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            // بناء سجل النشاط
         const activityContent = `
    <div class="mt-4">
        <h5 class="mb-3 text-muted">أحدث الأنشطة</h5>
        <div class="list-group">
            ${activity_logs.map(log => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${log.activity_type || 'نشاط غير معروف'}</h6>
                        <p class="mb-0 text-muted small">${log.activity_details || 'لا توجد تفاصيل إضافية'}</p>
                    </div>
                    <small class="text-muted">
                        ${new Date(log.created_at).toLocaleDateString('ar-EG', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}
                    </small>
                </div>
            `).join('')}
        </div>
    </div>`;

            // دمج المحتوى
            const fullContent = `
                <div class="driver-details-container">
                    ${driverContent}
                    ${ordersContent}
                    ${activityContent}
                </div>`;

            document.getElementById('driverDetailsContent').innerHTML = fullContent;
            new bootstrap.Modal(document.getElementById('driverDetailsModal')).show();
        })
        .catch(error => {
            showToast('error', error.message);
            console.error('Error:', error);
        });
}

// دالة مساعدة لعرض الإشعارات
function showToast(type, message) {
    const toast = new bootstrap.Toast(document.getElementById('liveToast'));
    const toastBody = document.querySelector('#liveToast .toast-body');
    toastBody.textContent = message;
    document.getElementById('liveToast').classList.add(`text-bg-${type}`);
    toast.show();
}
</script>