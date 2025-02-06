<?php
require_once '../config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'accounting') {
    header('Location: index.php');
    exit;
}
// التحقق من وجود company_id
if (!isset($_GET['company_id']) || empty($_GET['company_id'])) {
    die("معرف الشركة غير موجود");
}

$company_id = intval($_GET['company_id']);

// جلب بيانات الشركة
$stmt = $conn->prepare("SELECT id, name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    die("الشركة غير موجودة");
}

// جلب التواريخ المحددة للتصفية
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// بناء استعلام SQL مع التصفية حسب التاريخ
$sql = "
    SELECT 
        id,
        amount,
        payment_date,
        payment_method,
        payment_type,
        reference_number,
        notes
    FROM company_payments
    WHERE company_id = ? AND status = 'completed'
";

$params = [$company_id];

if (!empty($start_date) && !empty($end_date)) {
    $sql .= " AND payment_date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

$sql .= " ORDER BY payment_date DESC";

// جلب سجل المدفوعات
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تصنيف المدفوعات
$payments_to_company = [];  // مدفوعات منا إلى الشركة
$payments_from_company = [];  // مدفوعات من الشركة إلينا

foreach ($payments as $payment) {
    if ($payment['payment_type'] === 'outgoing') {
        $payments_to_company[] = $payment;
    } elseif ($payment['payment_type'] === 'incoming') {
        $payments_from_company[] = $payment;
    }
}

// إذا كان طلب تصدير إلى Excel
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="سجل_المدفوعات_' . htmlspecialchars($company['name']) . '_' . date('Y-m-d') . '.xls"');

    echo "<table border='1'>";
    echo "<tr>
            <th>نوع العملية</th>
            <th>المبلغ</th>
            <th>التاريخ</th>
            <th>طريقة الدفع</th>
            <th>رقم المرجع</th>
            <th>ملاحظات</th>
          </tr>";

    foreach ($payments_to_company as $payment) {
        echo "<tr>
                <td>دفع للشركة</td>
                <td>" . number_format($payment['amount'], 2) . " ر.س</td>
                <td>{$payment['payment_date']}</td>
                <td>{$payment['payment_method']}</td>
                <td>{$payment['reference_number']}</td>
                <td>{$payment['notes']}</td>
              </tr>";
    }

    foreach ($payments_from_company as $payment) {
        echo "<tr>
                <td>استلام من الشركة</td>
                <td>" . number_format($payment['amount'], 2) . " ر.س</td>
                <td>{$payment['payment_date']}</td>
                <td>{$payment['payment_method']}</td>
                <td>{$payment['reference_number']}</td>
                <td>{$payment['notes']}</td>
              </tr>";
    }

    echo "</table>";
    exit();
}

// إذا كان طلب طباعة البيانات فقط
if (isset($_GET['print_data'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>طباعة البيانات</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .print-header h2 {
                margin: 0;
                font-size: 24px;
            }
            .print-header p {
                margin: 5px 0;
                font-size: 16px;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }
            .table th, .table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: center;
            }
            .table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
        </style>
    </head>
    <body onload="window.print()">
        <div class="print-header">
            <h2>سجل المدفوعات - <?php echo htmlspecialchars($company['name']); ?></h2>
            <p>الفترة: <?php echo !empty($start_date) && !empty($end_date) ? "من $start_date إلى $end_date" : "جميع التواريخ"; ?></p>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>نوع العملية</th>
                    <th>المبلغ</th>
                    <th>التاريخ</th>
                    <th>طريقة الدفع</th>
                    <th>رقم المرجع</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments_to_company as $payment): ?>
                    <tr>
                        <td>دفع للشركة</td>
                        <td><?php echo number_format($payment['amount'], 2); ?> ر.س</td>
                        <td><?php echo $payment['payment_date']; ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['notes']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php foreach ($payments_from_company as $payment): ?>
                    <tr>
                        <td>استلام من الشركة</td>
                        <td><?php echo number_format($payment['amount'], 2); ?> ر.س</td>
                        <td><?php echo $payment['payment_date']; ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['notes']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل المدفوعات - <?php echo htmlspecialchars($company['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payment-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        .payment-card h5 {
            margin-bottom: 10px;
        }
        .payment-card p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">سجل المدفوعات - <?php echo htmlspecialchars($company['name']); ?></h1>

        <!-- أزرار التصدير والطباعة -->
        <div class="mb-3">
            <a href="?company_id=<?php echo $company_id; ?>&export_excel=true&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> تصدير إلى Excel
            </a>
            <a href="?company_id=<?php echo $company_id; ?>&print_data=true&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-primary">
                <i class="fas fa-print"></i> طباعة البيانات فقط
            </a>
        </div>

        <!-- تصفية حسب التاريخ -->
        <form method="GET" class="mb-4">
            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
            <div class="row">
                <div class="col-md-3">
                    <label for="start_date">من تاريخ:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date">إلى تاريخ:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary mt-4">تصفية</button>
                    <a href="git_payment_history.php?company_id=<?php echo $company_id; ?>" class="btn btn-secondary mt-4">إعادة تعيين</a>
                </div>
            </div>
        </form>

        <!-- عرض المدفوعات بجوار بعضها -->
        <div class="row">
            <!-- مدفوعات منا إلى الشركة -->
            <div class="col-md-6">
                <h3>مدفوعات منا إلى الشركة</h3>
                <?php if (empty($payments_to_company)): ?>
                    <div class="alert alert-info">لا توجد مدفوعات منا إلى الشركة.</div>
                <?php else: ?>
                    <?php foreach ($payments_to_company as $payment): ?>
                        <div class="payment-card">
                            <h5>المبلغ: <?php echo number_format($payment['amount'], 2); ?> ر.س</h5>
                            <p>التاريخ: <?php echo $payment['payment_date']; ?></p>
                            <p>طريقة الدفع: <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                            <p>رقم المرجع: <?php echo htmlspecialchars($payment['reference_number']); ?></p>
                            <p>ملاحظات: <?php echo htmlspecialchars($payment['notes']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- مدفوعات من الشركة إلينا -->
            <div class="col-md-6">
                <h3>مدفوعات من الشركة إلينا</h3>
                <?php if (empty($payments_from_company)): ?>
                    <div class="alert alert-info">لا توجد مدفوعات من الشركة إلينا.</div>
                <?php else: ?>
                    <?php foreach ($payments_from_company as $payment): ?>
                        <div class="payment-card">
                            <h5>المبلغ: <?php echo number_format($payment['amount'], 2); ?> ر.س</h5>
                            <p>التاريخ: <?php echo $payment['payment_date']; ?></p>
                            <p>طريقة الدفع: <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                            <p>رقم المرجع: <?php echo htmlspecialchars($payment['reference_number']); ?></p>
                            <p>ملاحظات: <?php echo htmlspecialchars($payment['notes']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>