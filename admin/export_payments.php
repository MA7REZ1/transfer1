<?php
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// استعلام لجلب المبالغ المتبقية لكل شركة
$balances_query = "
    SELECT 
        c.id,
        c.name as company_name,
        (
            SELECT COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_cost ELSE 0 END) - SUM(delivery_fee), 0)
            FROM requests
            WHERE company_id = c.id AND status = 'delivered'
        ) as total_payable,
        (
            SELECT COALESCE(SUM(CASE 
                WHEN payment_type = 'outgoing' THEN amount 
                WHEN payment_type = 'incoming' THEN -amount 
            END), 0)
            FROM company_payments
            WHERE company_id = c.id AND status = 'completed'
        ) as total_paid
    FROM companies c
    HAVING total_payable > 0 OR total_paid > 0
    ORDER BY total_payable - total_paid DESC";

try {
    $balances_stmt = $conn->query($balances_query);
    $companies_balances = $balances_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// التحقق من طلب التصدير
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=payments_' . date('Y-m-d') . '.xls');
}

// استعلام لجلب كل العمليات
$query = "
    SELECT 
        cp.*,
        c.name as company_name,
        COALESCE(a.username, 'مدير النظام') as admin_name,
        (
            SELECT COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total_cost ELSE 0 END) - SUM(delivery_fee), 0)
            FROM requests
            WHERE company_id = c.id AND status = 'delivered'
        ) as total_payable,
        (
            SELECT COALESCE(SUM(CASE 
                WHEN payment_type = 'outgoing' THEN amount 
                WHEN payment_type = 'incoming' THEN -amount 
            END), 0)
            FROM company_payments
            WHERE company_id = c.id AND status = 'completed'
            AND id <= cp.id
        ) as running_balance
    FROM company_payments cp
    JOIN companies c ON cp.company_id = c.id
    LEFT JOIN employees a ON cp.created_by = a.id
    ORDER BY cp.payment_date DESC";

try {
    $stmt = $conn->query($query);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// إذا لم يكن طلب تصدير، نعرض الصفحة العادية
if (!isset($_GET['export'])) {
    include '../includes/header.php';
    ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">سجل العمليات المالية</h2>
            <a href="?export=1" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i>
                تصدير إلى Excel
            </a>
        </div>

        <!-- عرض المبالغ المتبقية -->
        <div class="row mb-4">
            <?php foreach ($companies_balances as $balance): 
                $remaining = $balance['total_payable'] - $balance['total_paid'];
                if ($remaining != 0):
            ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($balance['company_name']); ?></h5>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>المبلغ المستحق:</span>
                                <span class="text-primary"><?php echo number_format($balance['total_payable'], 2); ?> ر.س</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>المبلغ المدفوع:</span>
                                <span class="text-success"><?php echo number_format($balance['total_paid'], 2); ?> ر.س</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>المتبقي:</span>
                                <span class="fw-bold <?php echo $remaining > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo number_format($remaining, 2); ?> ر.س
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if (empty($payments)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    لا توجد عمليات مالية مسجلة حتى الآن
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
    <?php endif;
}

if (!empty($payments)) {
// عرض الجدول
echo '<table' . (!isset($_GET['export']) ? ' class="table table-hover"' : ' border="1"') . '>
<tr>
    <th>رقم العملية</th>
    <th>الشركة</th>
    <th>نوع العملية</th>
    <th>المبلغ</th>
    <th>طريقة الدفع</th>
    <th>رقم المرجع</th>
    <th>الملاحظات</th>
    <th>التاريخ</th>
    <th>تم بواسطة</th>
        <th>المبلغ المستحق</th>
        <th>الرصيد المتراكم</th>
</tr>';

    foreach ($payments as $row) {
    $payment_type = $row['payment_type'] === 'outgoing' ? 'دفع للشركة' : 'استلام من الشركة';
    $payment_method = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك'
    ][$row['payment_method']] ?? $row['payment_method'];
    
    echo '<tr>
        <td>' . $row['id'] . '</td>
            <td>' . htmlspecialchars($row['company_name']) . '</td>
        <td>' . $payment_type . '</td>
        <td>' . number_format($row['amount'], 2) . '</td>
        <td>' . $payment_method . '</td>
            <td>' . htmlspecialchars($row['reference_number'] ?: '-') . '</td>
            <td>' . htmlspecialchars($row['notes'] ?: '-') . '</td>
        <td>' . date('Y-m-d H:i', strtotime($row['payment_date'])) . '</td>
            <td>' . htmlspecialchars($row['admin_name']) . '</td>
            <td>' . number_format($row['total_payable'], 2) . '</td>
            <td>' . number_format($row['running_balance'], 2) . '</td>
    </tr>';
}

echo '</table>';
}

// إذا لم يكن طلب تصدير، نغلق العناصر المتبقية
if (!isset($_GET['export']) && !empty($payments)) {
    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
}
?>
