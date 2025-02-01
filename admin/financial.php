<?php
require_once '../config.php';


// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: employee-login.php');
    exit;
}

// التحقق من قسم الموظف
$stmt = $conn->prepare("SELECT department FROM employees WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$employee = $stmt->fetch();

if ($employee && $employee['department'] === 'accounting') {
    include '../admin/revenue.php';
} elseif ($employee && $employee['department'] === 'drivers_supervisor') {
    include '../admin/drivers.php';
} else {
    // عرض رسالة خطأ للموظف
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger text-center" role="alert">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <h4 class="alert-heading mb-3">عذراً! لا يمكنك الوصول لهذه الصفحة</h4>
            <p>هذه الصفحة مخصصة فقط لموظفي قسم المحاسبة.</p>
            <hr>
            <a href="dashboard.php" class="btn btn-outline-danger mt-3">
                <i class="fas fa-home me-2"></i>العودة للصفحة الرئيسية
            </a>
        </div>
    </div>
    <?php
    require_once '../includes/footer.php';
    exit;
}