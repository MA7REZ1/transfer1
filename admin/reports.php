<?php
session_start();
require_once 'config.php';

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
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التقارير - لوحة تحكم المشرف</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
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
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="content">
    <div class="container-fluid p-4">
        <h1 class="h3 mb-4">لوحة التقارير</h1>
        
        <div class="row">
            <!-- Orders Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-shopping-cart ms-2"></i>تقرير الطلبات</h5>
                        <p class="card-text">عرض إحصائيات واتجاهات الطلبات التفصيلية</p>
                        <a href="order_analysis.php" class="btn btn-primary">عرض التقرير</a>
                    </div>
                </div>
            </div>

            <!-- Driver Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-truck ms-2"></i>تقرير السائقين</h5>
                        <p class="card-text">تحليل أداء السائقين والتوصيلات</p>
                        <a href="driver_analysis.php" class="btn btn-primary">عرض التقرير</a>
                    </div>
                </div>
            </div>

            <!-- Revenue Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-line ms-2"></i>تقرير الإيرادات</h5>
                        <p class="card-text">تتبع الإيرادات والمؤشرات المالية</p>
                        <a href="revenue.php" class="btn btn-primary">عرض التقرير</a>
                    </div>
                </div>
            </div>

            <!-- Complaints Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-exclamation-circle ms-2"></i>تقرير الشكاوى</h5>
                        <p class="card-text">مراقبة شكاوى العملاء وحلولها</p>
                        <a href="complaints.php" class="btn btn-primary">عرض التقرير</a>
                    </div>
                </div>
            </div>

            <!-- Employee Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users ms-2"></i>تقرير الموظفين</h5>
                        <p class="card-text">مراجعة أداء وإحصائيات الموظفين</p>
                        <a href="employees.php" class="btn btn-primary">عرض التقرير</a>
                    </div>
                </div>
            </div>

            <!-- Payment Report Card -->
            <div class="col-md-4 mb-4">
                <div class="card report-card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-money-bill-wave ms-2"></i>تقرير المدفوعات</h5>
                        <p class="card-text">عرض سجل المدفوعات والمعاملات</p>
                        <a href="revenue.php" class="btn btn-primary">عرض التقرير</a>
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