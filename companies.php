<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Handle company actions (activate/deactivate/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $company_id = $_GET['id'];
    
    switch ($action) {
        case 'activate':
            $stmt = $conn->prepare("UPDATE companies SET is_active = 1 WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'success', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم تفعيل الشركة بنجاح", "companies.php"]);
            
            header('Location: companies.php');
            exit;
            
        case 'deactivate':
            $stmt = $conn->prepare("UPDATE companies SET is_active = 0 WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'warning', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم تعطيل الشركة", "companies.php"]);
            
            header('Location: companies.php');
            exit;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'danger', ?)");
            $stmt->execute([$_SESSION['admin_id'], "تم حذف الشركة", "companies.php"]);
            
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
$query = "SELECT * FROM companies WHERE 1=1";
$countQuery = "SELECT COUNT(*) FROM companies WHERE 1=1";
$params = [];

// Add search conditions if search is provided
if (!empty($search)) {
    $searchCondition = " AND (name LIKE :search1 OR email LIKE :search2 OR phone LIKE :search3)";
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
$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

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
require_once 'includes/header.php';
?>

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
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
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
        </div>
    </div>

    <!-- Companies Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>الشعار</th>
                            <th>اسم الشركة</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>مسؤول الاتصال</th>
                            <th>الحالة</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo !empty($company['logo']) ? 'uploads/companies/' . $company['logo'] : 'assets/img/company-placeholder.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($company['name']); ?>" 
                                         class="rounded-circle" 
                                         width="40" height="40">
                                </td>
                                <td><?php echo htmlspecialchars($company['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($company['email']); ?>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" 
                                            onclick="copyCompanyInfo('<?php echo htmlspecialchars($company['name']); ?>', '<?php echo htmlspecialchars($company['email']); ?>', '<?php echo htmlspecialchars($company['phone']); ?>', '<?php echo htmlspecialchars($company['name']); ?>@123')" 
                                            title="نسخ بيانات الشركة">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($company['phone']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $company['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $company['is_active'] ? 'نشط' : 'معطل'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y/m/d', strtotime($company['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="company_form.php?id=<?php echo $company['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($company['is_active']): ?>
                                            <a href="?action=deactivate&id=<?php echo $company['id']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="تعطيل"
                                               onclick="return confirm('هل أنت متأكد من تعطيل هذه الشركة؟')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&id=<?php echo $company['id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               title="تفعيل"
                                               onclick="return confirm('هل أنت متأكد من تفعيل هذه الشركة؟')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $company['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="حذف"
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الشركة؟')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">لا توجد شركات مسجلة</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
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
</script>

<?php require_once 'includes/footer.php'; ?> 