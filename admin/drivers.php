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
require_once '../includes/header.php';
?>
<style>
.action-btn-group {
    display: flex;
    gap: 5px;
    justify-content: flex-start;
    align-items: center;
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

.action-btn i {
    font-size: 1rem;
}

.action-btn.edit-btn {
    background-color: #3498db;
    color: white;
}

.action-btn.edit-btn:hover {
    background-color: #2980b9;
}

.action-btn.whatsapp-btn {
    background-color: #25D366;
    color: white;
}

.action-btn.whatsapp-btn:hover {
    background-color: #128C7E;
}

.action-btn.deactivate-btn {
    background-color: #f1c40f;
    color: white;
}

.action-btn.deactivate-btn:hover {
    background-color: #f39c12;
}

.action-btn.activate-btn {
    background-color: #2ecc71;
    color: white;
}

.action-btn.activate-btn:hover {
    background-color: #27ae60;
}

.action-btn.delete-btn {
    background-color: #e74c3c;
    color: white;
}

.action-btn.delete-btn:hover {
    background-color: #c0392b;
}

/* Tooltip styles */
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
/* تنسيق عام للمودال */
#driverDetailsModal .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

#driverDetailsModal .modal-header {
    background: #f8f9fa;
    border-bottom: 2px solid #eee;
    padding: 1rem 2rem;
}

#driverDetailsModal .modal-title {
    font-weight: 700;
    color: #2c3e50;
}

/* تنسيق المعلومات الرئيسية */
#driverDetailsContent h4 {
    color: #34495e;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
    border-bottom: 2px solid #3498db;
    padding-bottom: 0.5rem;
    display: inline-block;
}

#driverDetailsContent p {
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
    color: #4a5568;
}

#driverDetailsContent p strong {
    color: #2d3748;
    min-width: 140px;
    display: inline-block;
}

/* تنسيق إحصائيات الرحلات */
#driverDetailsContent p strong + span {
    font-weight: bold;
    padding: 0.3rem 0.8rem;
    border-radius: 8px;
}

#driverDetailsContent p:has(strong:contains("المكتملة")) span {
    background: #e8f5e9;
    color: #2e7d32;
}

#driverDetailsContent p:has(strong:contains("الملغاة")) span {
    background: #ffebee;
    color: #c62828;
}

/* تنسيق حالة الطلبات */
#driverDetailsContent h5 {
    color: #2c3e50;
    margin: 2rem 0 1rem;
    font-size: 1.4rem;
    position: relative;
    padding-left: 1.5rem;
}

#driverDetailsContent h5::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 70%;
    background: #3498db;
    border-radius: 4px;
}

#driverDetailsContent ul {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

#driverDetailsContent ul li {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    border-left: 4px solid;
    font-weight: 500;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#driverDetailsContent ul li:nth-child(1) {
    border-color: #f1c40f;
    background: #fffbe6;
}

#driverDetailsContent ul li:nth-child(2) {
    border-color: #3498db;
    background: #e3f2fd;
}

#driverDetailsContent ul li:nth-child(3) {
    border-color: #2ecc71;
    background: #e8f5e9;
}

/* تنسيق سجل النشاط */
#driverDetailsContent .activity-log {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 1rem;
}

#driverDetailsContent .activity-log ul {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

#driverDetailsContent .activity-log li {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    transition: transform 0.2s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

#driverDetailsContent .activity-log li:hover {
    transform: translateX(5px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

#driverDetailsContent .activity-log small {
    color: #718096;
    font-size: 0.9rem;
    min-width: 150px;
    text-align: left;
}

/* تنسيق التواريخ */
#driverDetailsContent time {
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #4a5568;
}

/* تأثيرات التحويم */
#driverDetailsContent ul li,
#driverDetailsContent .activity-log li {
    transition: all 0.3s ease;
}

/* تنسيق للهواتف المحمولة */
@media (max-width: 768px) {
    #driverDetailsContent ul {
        grid-template-columns: 1fr;
    }
    
    #driverDetailsContent .activity-log li {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    #driverDetailsContent h4 {
        font-size: 1.5rem;
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
                        <th> الحالة</th>
                       
 <th> ماذا يفعل الان</th>
  <th> مبلغ تم تحصيله</th>
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
                <td>
    <?php
    // تعريف الحالات الممكنة وتعريبهن
    $status = $driver['current_status'];
    $statusMap = [
        'available' => [
            'ar' => 'السائق متاح',
            'class' => 'success'
        ],
        'busy' => [
            'ar' => ' مشغول في توصيل',
            'class' => 'warning'
        ],
        'offline' => [
            'ar' => 'غير متصل',
            'class' => 'danger'
        ]
    ];
    
    // تحديد القيم الافتراضية للحالة غير المعروفة
    $displayStatus = $statusMap[$status]['ar'] ?? 'حالة غير معروفة';
    $badgeClass = $statusMap[$status]['class'] ?? 'secondary';
    ?>
    
    <span class="badge bg-<?php echo $badgeClass; ?>">
        <?php echo htmlspecialchars($displayStatus); ?>
    </span>
</td>
            <td>
                <?php echo number_format($driver['total_earnings'], 2) . ' ر.س'; ?>
            </td>
           
                            <td><?php echo date('Y/m/d', strtotime($driver['created_at'])); ?></td>
                            <td>
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
                                    <button type="button" class="action-btn info-btn" onclick="showDriverDetails(<?php echo $driver['id']; ?>)" data-title="عرض التفاصيل">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
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