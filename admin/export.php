<?php
require_once '../config.php';
// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'accounting') {
    header('Location: ../index.php');
    exit;
}
// جلب التواريخ المحددة للتصفية
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// استعلام لجلب كل العمليات مع التصفية حسب التاريخ
$payments_query = "
    SELECT 
        cp.*,
        c.name as company_name,
        COALESCE(a.username, 'مدير النظام') as admin_name
    FROM company_payments cp
    JOIN companies c ON cp.company_id = c.id
    LEFT JOIN employees a ON cp.created_by = a.id
    WHERE 1=1";

if (!empty($start_date) && !empty($end_date)) {
    $payments_query .= " AND cp.payment_date BETWEEN :start_date AND :end_date";
}

$payments_query .= " ORDER BY cp.payment_date DESC";

try {
    $stmt = $conn->prepare($payments_query);
    if (!empty($start_date) && !empty($end_date)) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// تصدير البيانات إلى Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="سجل_العمليات_المالية_' . date('Y-m-d') . '.xls"');

echo "<table border='1'>";
echo "<tr>
        <th>رقم العملية</th>
        <th>الشركة</th>
        <th>نوع العملية</th>
        <th>المبلغ</th>
        <th>طريقة الدفع</th>
        <th>رقم المرجع</th>
        <th>الملاحظات</th>
        <th>التاريخ</th>
        <th>تم بواسطة</th>
      </tr>";

foreach ($payments as $row) {
    $payment_type = $row['payment_type'] === 'outgoing' ? 'دفع للشركة' : 'استلام من الشركة';
    $payment_method = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'check' => 'شيك'
    ][$row['payment_method']] ?? $row['payment_method'];

    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['company_name']}</td>
            <td>{$payment_type}</td>
            <td>{$row['amount']}</td>
            <td>{$payment_method}</td>
            <td>{$row['reference_number']}</td>
            <td>{$row['notes']}</td>
            <td>{$row['payment_date']}</td>
            <td>{$row['admin_name']}</td>
          </tr>";
}

echo "</table>";
?>