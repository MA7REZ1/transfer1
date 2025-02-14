<?php

require_once '../config.php';

// Get current language direction
$dir = $_SESSION['lang'] === 'ar' ? 'rtl' : 'ltr';
$lang = $_SESSION['lang'];

// Include language file
require_once '../includes/languages.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام') {
    header('Location: index.php');
    exit;
}
// Get admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = :admin_id");
$stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('reports_dashboard'); ?> - <?php echo __('admin_panel'); ?></title>
    <!-- Bootstrap CSS -->
    <?php if ($dir === 'rtl'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        /* تعديل الهوامش حسب اتجاه اللغة */
        .card-title i {
            margin-<?php echo $dir === 'rtl' ? 'left' : 'right'; ?>: 0.5rem;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>


<main class="content">
    <div class="container-fluid p-4">
        <h1 class="h3 mb-4"><?php echo __('reports_dashboard'); ?></h1>
        
        <div class="row">
            <!-- Orders Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-shopping-cart"></i><?php echo __('orders_report'); ?></h5>
                        <p class="card-text"><?php echo __('orders_report_desc'); ?></p>
                        <a href="order_analysis.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>

            <!-- Driver Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-truck"></i><?php echo __('drivers_report'); ?></h5>
                        <p class="card-text"><?php echo __('drivers_report_desc'); ?></p>
                        <a href="driver_analysis.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>

            <!-- Revenue Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-line"></i><?php echo __('revenue_report'); ?></h5>
                        <p class="card-text"><?php echo __('revenue_report_desc'); ?></p>
                        <a href="revenue.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>

            <!-- Complaints Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-exclamation-circle"></i><?php echo __('complaints_report'); ?></h5>
                        <p class="card-text"><?php echo __('complaints_report_desc'); ?></p>
                        <a href="complaints.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>

            <!-- Employee Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users"></i><?php echo __('employees_report'); ?></h5>
                        <p class="card-text"><?php echo __('employees_report_desc'); ?></p>
                        <a href="employees.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>

            <!-- Payment Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-money-bill-wave"></i><?php echo __('payments_report'); ?></h5>
                        <p class="card-text"><?php echo __('payments_report_desc'); ?></p>
                        <a href="revenue.php" class="btn btn-primary"><?php echo __('view_report'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Add any JavaScript functionality here
});
</script>

</body>
</html> 