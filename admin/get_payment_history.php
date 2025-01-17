<?php
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    die("غير مصرح لك");
}

// التحقق من وجود معرف الشركة
if (!isset($_GET['company_id'])) {
    die("معرف الشركة مطلوب");
}

$company_id = intval($_GET['company_id']);

// جلب بيانات الشركة
$stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    die("الشركة غير موجودة");
}

// جلب سجل المدفوعات مع اسم المدير
$stmt = $conn->prepare("
    SELECT 
        cp.*,
        a.username as admin_username,
        a.username as admin_name
    FROM company_payments cp
    LEFT JOIN admins a ON cp.created_by = a.id
    WHERE cp.company_id = ?
    ORDER BY cp.payment_date DESC
");
$stmt->execute([$company_id]);
$payments = $stmt->fetchAll();

// حساب الإجماليات
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total_paid
    FROM company_payments 
    WHERE company_id = ? AND status = 'completed'
");
$stmt->execute([$company_id]);
$totals = $stmt->fetch();
?>

<div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>المبلغ</th>
                <th>طريقة الدفع</th>
                <th>رقم المرجع</th>
                <th>الحالة</th>
                <th>تم التسجيل بواسطة</th>
                <th>ملاحظات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?php echo $payment['id']; ?></td>
                <td><?php echo date('Y-m-d H:i', strtotime($payment['payment_date'])); ?></td>
                <td class="text-success"><?php echo number_format($payment['amount'], 2); ?> ر.س</td>
                <td>
                    <?php 
                    $methods = [
                        'cash' => 'نقدي',
                        'bank_transfer' => 'تحويل بنكي',
                        'check' => 'شيك'
                    ];
                    echo $methods[$payment['payment_method']] ?? $payment['payment_method'];
                    ?>
                </td>
                <td><?php echo $payment['reference_number'] ?: '-'; ?></td>
                <td>
                    <?php 
                    $status_classes = [
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger'
                    ];
                    $status_labels = [
                        'completed' => 'مكتمل',
                        'pending' => 'معلق',
                        'cancelled' => 'ملغي'
                    ];
                    $status_class = $status_classes[$payment['status']] ?? 'secondary';
                    $status_label = $status_labels[$payment['status']] ?? $payment['status'];
                    ?>
                    <span class="badge bg-<?php echo $status_class; ?>">
                        <?php echo $status_label; ?>
                    </span>
                </td>
                <td>
                    <span class="text-primary">
                        <i class="fas fa-user me-1"></i>
                        <?php 
                        $admin_name = $payment['admin_name'] ?? $payment['admin_username'] ?? 'مدير النظام';
                        echo htmlspecialchars($admin_name);
                        ?>
                    </span>
                </td>
                <td><?php echo $payment['notes'] ?: '-'; ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    لا يوجد مدفوعات مسجلة
                </td>
            </tr>
            <?php else: ?>
            <tr class="table-light">
                <td colspan="2" class="fw-bold">الإجمالي</td>
                <td colspan="6" class="text-success fw-bold">
                    <?php echo number_format($totals['total_paid'], 2); ?> ر.س
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div> 