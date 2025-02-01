<?php
require '../config.php'; // تأكد من اتصال قاعدة البيانات

// جلب تواريخ البداية والنهاية من النموذج
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// جلب عمليات الدفع من وإلى الشركة مع التصفية حسب التاريخ
$payments_query = "SELECT 
    id,
    payment_type,
    amount,
    payment_method,
    reference_number,
    notes,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as payment_date
FROM company_payments
WHERE company_id = :company_id";

if ($start_date && $end_date) {
    $payments_query .= " AND DATE(created_at) BETWEEN :start_date AND :end_date";
} elseif ($start_date) {
    $payments_query .= " AND DATE(created_at) >= :start_date";
} elseif ($end_date) {
    $payments_query .= " AND DATE(created_at) <= :end_date";
}

$payments_query .= " ORDER BY created_at DESC";

$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bindValue(':company_id', $_SESSION['company_id']);

if ($start_date && $end_date) {
    $payments_stmt->bindValue(':start_date', $start_date);
    $payments_stmt->bindValue(':end_date', $end_date);
} elseif ($start_date) {
    $payments_stmt->bindValue(':start_date', $start_date);
} elseif ($end_date) {
    $payments_stmt->bindValue(':end_date', $end_date);
}

$payments_stmt->execute();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

// إعداد ملف CSV للتنزيل
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment;filename="عمليات_الدفع.csv"');

$output = fopen('php://output', 'w');

// كتابة عناوين الأعمدة
fputcsv($output, [
    'نوع الدفعة',
    'المبلغ',
    'طريقة الدفع',
    'رقم المرجع',
    'التاريخ',
    'ملاحظات'
], ';');

// كتابة البيانات
foreach ($payments as $payment) {
    fputcsv($output, [
        $payment['payment_type'] === 'incoming' ? 'وارد (إلينا)' : 'صادر (مننا)',
        $payment['amount'],
        $payment['payment_method'],
        $payment['reference_number'] ?? 'لا يوجد',
        $payment['payment_date'],
        $payment['notes'] ?? 'لا يوجد'
    ], ';');
}

fclose($output);
exit;