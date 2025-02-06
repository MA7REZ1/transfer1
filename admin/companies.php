<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام') {
    header('Location: index.php');
    exit;
}

// Handle company actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $company_id = $_GET['id'];
    
    // Get company name first
    $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company_name = $stmt->fetchColumn();
    
    switch ($action) {
        case 'activate':
            $stmt = $conn->prepare("UPDATE companies SET is_active = 1 WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'success', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تفعيل شركة: " . $company_name,
                "companies.php"
            ]);
            
            header('Location: companies.php');
            exit;
            
        case 'deactivate':
            $stmt = $conn->prepare("UPDATE companies SET is_active = 0 WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'warning', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تعطيل شركة: " . $company_name,
                "companies.php"
            ]);
            
            header('Location: companies.php');
            exit;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link) VALUES (?, ?, ?, 'danger', ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم حذف شركة: " . $company_name,
                "companies.php"
            ]);
            
            header('Location: companies.php');
            exit;
    }
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Prepare the base query
$query = "SELECT c.*, 
    COUNT(DISTINCT r.id) as total_orders
    FROM companies c
    LEFT JOIN requests r ON c.id = r.company_id
    WHERE 1=1";
$countQuery = "SELECT COUNT(DISTINCT c.id) FROM companies c WHERE 1=1";
$params = [];

// Add search conditions if search is provided
if (!empty($search)) {
    $searchCondition = " AND (c.name LIKE :search1 OR c.email LIKE :search2 OR c.phone LIKE :search3)";
    $query .= $searchCondition;
    $countQuery .= $searchCondition;
    $params = [
        ':search1' => "%$search%",
        ':search2' => "%$search%",
        ':search3' => "%$search%"
    ];
}

// Get total records for pagination
$stmt = $conn->prepare($countQuery);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Add pagination to the main query
$query .= " GROUP BY c.id ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

// Get companies
$stmt = $conn->prepare($query);

// Bind all parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$companies = $stmt->fetchAll();

// Include header
require_once '../includes/header.php';
?>

<style>
.company-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.company-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.company-logo {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
}

.company-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.5rem;
}

.info-item i {
    width: 20px;
    color: #6c757d;
}

.company-stats {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
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


<!-- Main Content -->
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
  

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">إدارة الشركات</h2>
        <a href="company_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>إضافة شركة جديدة
        </a>
    </div>

    <!-- Search Form -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="البحث عن شركة..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
            </form>

            <div class="row g-4">
                <?php if (!empty($companies)): ?>
                    <?php foreach ($companies as $company): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 company-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="company-logo me-3">
                                            <?php if (!empty($company['logo'])): ?>
                                                <img src="/proo/uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($company['name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-building fa-3x text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($company['name']); ?></h5>
                                            <span class="badge bg-<?php echo $company['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $company['is_active'] ? 'نشط' : 'معطل'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="company-info mb-3">
                                        <div class="info-item">
                                            <i class="fas fa-envelope"></i>
                                            <span class="text-truncate"><?php echo htmlspecialchars($company['email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($company['phone']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-user"></i>
                                            <span><?php echo htmlspecialchars($company['contact_person']); ?></span>
                                        </div>
                                    </div>

                                    <div class="company-stats">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="d-block text-muted mb-1">تاريخ التسجيل</small>
                                                <strong><?php echo date('Y/m/d', strtotime($company['created_at'])); ?></strong>
                                            </div>
                                            <div class="col-6">
                                                <small class="d-block text-muted mb-1">عدد الطلبات</small>
                                                <strong><?php echo number_format($company['total_orders']); ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="action-btn-group">
                                        <button type="button" 
                                               class="action-btn whatsapp-btn"
                                               onclick="openCompanyWhatsApp('<?php echo htmlspecialchars($company['phone']); ?>')"
                                               data-title="تواصل عبر واتساب">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                        <a href="company_form.php?id=<?php echo $company['id']; ?>" 
                                           class="action-btn edit-btn"
                                           data-title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($company['is_active']): ?>
                                            <a href="?action=deactivate&id=<?php echo $company['id']; ?>" 
                                               class="action-btn deactivate-btn"
                                               data-title="تعطيل"
                                               onclick="return confirm('هل أنت متأكد من تعطيل هذه الشركة؟')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $company['id']; ?>" 
                                               class="action-btn activate-btn"
                                               data-title="تفعيل"
                                               onclick="return confirm('هل أنت متأكد من تفعيل هذه الشركة؟')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $company['id']; ?>" 
                                           class="action-btn delete-btn"
                                           data-title="حذف"
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الشركة؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <button class="action-btn" 
                                                onclick="copyCompanyInfo('<?php echo htmlspecialchars($company['name']); ?>', '<?php echo htmlspecialchars($company['email']); ?>', '<?php echo htmlspecialchars($company['phone']); ?>', '<?php echo htmlspecialchars($company['name']); ?>@123')" 
                                                data-title="نسخ بيانات الشركة"
                                                style="background-color: #6c757d; color: white;">
                                            <i class="fas fa-copy"></i>
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
                            <p class="text-muted">لا توجد شركات مسجلة</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add JavaScript for copy functionality -->
<script>
function copyCompanyInfo(name, email, phone, password) {
    const text = `اسم الشركة: ${name}\nالبريد الإلكتروني: ${email}\nرقم الهاتف: ${phone}\nكلمة المرور: ${password}`;
    navigator.clipboard.writeText(text).then(() => {
        alert('تم نسخ بيانات الشركة بنجاح');
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
</script>

<?php require_once '../includes/footer.php'; ?> 