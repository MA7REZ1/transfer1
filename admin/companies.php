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
            $saudi_timezone = new DateTimeZone('Asia/Riyadh');
            $date = new DateTime('now', $saudi_timezone);
            $formatted_date = $date->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link, created_at) VALUES (?, ?, ?, 'success', ?, ?)");
            $stmt->execute([ 
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تفعيل شركة: " . $company_name,
                "companies.php",
                $formatted_date
            ]);
            
            header('Location: companies.php');
            exit;
            
        case 'deactivate':
            $stmt = $conn->prepare("UPDATE companies SET is_active = 0 WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $saudi_timezone = new DateTimeZone('Asia/Riyadh');
            $date = new DateTime('now', $saudi_timezone);
            $formatted_date = $date->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link, created_at) VALUES (?, ?, ?, 'warning', ?, ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم تعطيل شركة: " . $company_name,
                "companies.php",
                $formatted_date
            ]);
            
            header('Location: companies.php');
            exit;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
            $stmt->execute([$company_id]);
            
            // Add notification
            $saudi_timezone = new DateTimeZone('Asia/Riyadh');
            $date = new DateTime('now', $saudi_timezone);
            $formatted_date = $date->format('Y-m-d H:i:s');

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, user_type, message, type, link, created_at) VALUES (?, ?, ?, 'danger', ?, ?)");
            $stmt->execute([
                $_SESSION['admin_id'] ?? $_SESSION['employee_id'],
                $_SESSION['admin_role'] ?? 'مدير_عام',
                "تم حذف شركة: " . $company_name,
                "companies.php",
                $formatted_date
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
        <h2 class="h3 mb-0"><?php echo __('companies_management'); ?></h2>
        <a href="company_form.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> <?php echo __('add_new'); ?>
        </a>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="<?php echo __('search'); ?>..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><?php echo __('search'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Companies Grid -->
    <div class="row">
        <?php foreach ($companies as $company): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card company-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="company-logo me-3">
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="../uploads/company_logos/<?php echo htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-building fa-2x text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($company['name']); ?></h5>
                                <span class="badge <?php echo $company['is_active'] ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $company['is_active'] ? __('status_active') : __('status_inactive'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="company-info mb-3">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($company['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($company['phone']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($company['address']); ?></span>
                            </div>
                        </div>

                        <div class="company-stats mb-3">
                            <div class="row text-center">
                                <div class="col">
                                    <h6 class="mb-1"><?php echo __('total_orders'); ?></h6>
                                    <span class="h5"><?php echo number_format($company['total_orders']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="action-btn-group">
                            <?php if (!empty($company['phone'])): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $company['phone']); ?>" 
                                   class="action-btn whatsapp-btn" 
                                   data-title="<?php echo __('whatsapp'); ?>" 
                                   target="_blank">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php endif; ?>

                            <a href="company_form.php?id=<?php echo $company['id']; ?>" 
                               class="action-btn edit-btn" 
                               data-title="<?php echo __('edit'); ?>">
                                <i class="fas fa-edit"></i>
                            </a>

                            <?php if ($company['is_active']): ?>
                                <a href="?action=deactivate&id=<?php echo $company['id']; ?>" 
                                   class="action-btn deactivate-btn" 
                                   data-title="<?php echo __('deactivate'); ?>"
                                   onclick="return confirm('<?php echo __('confirm_deactivate'); ?>')">
                                    <i class="fas fa-ban"></i>
                                </a>
                            <?php else: ?>
                                <a href="?action=activate&id=<?php echo $company['id']; ?>" 
                                   class="action-btn activate-btn" 
                                   data-title="<?php echo __('activate'); ?>"
                                   onclick="return confirm('<?php echo __('confirm_activate'); ?>')">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>

                            <a href="?action=delete&id=<?php echo $company['id']; ?>" 
                               class="action-btn delete-btn" 
                               data-title="<?php echo __('delete'); ?>"
                               onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="<?php echo __('page_navigation'); ?>">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo __('previous'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo __('next'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 