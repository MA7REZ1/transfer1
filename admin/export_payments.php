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
// جلب التواريخ المحددة للتصفية
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// جلب قيمة البحث (اسم الشركة أو رقم المرجع)
$search = $_GET['search'] ?? '';

// استعلام لجلب كل العمليات مع التصفية حسب التاريخ والبحث
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

if (!empty($search)) {
    $payments_query .= " AND (c.name LIKE :search OR cp.reference_number LIKE :search)";
}

$payments_query .= " ORDER BY cp.payment_date DESC";

try {
    $stmt = $conn->prepare($payments_query);
    if (!empty($start_date) && !empty($end_date)) {
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
    }
    if (!empty($search)) {
        $search_term = "%$search%";
        $stmt->bindParam(':search', $search_term);
    }
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// إذا كان طلب تصدير إلى Excel
if (isset($_GET['export_excel'])) {
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
    exit();
}

// إذا كان طلب طباعة، نعرض البيانات فقط
if (isset($_GET['print'])) {
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
            <h2>سجل العمليات المالية</h2>
            <p>تاريخ التقرير: <?php echo date('Y-m-d'); ?></p>
            <?php if (!empty($start_date) && !empty($end_date)): ?>
            <p>الفترة: من <?php echo $start_date; ?> إلى <?php echo $end_date; ?></p>
            <?php endif; ?>
            <?php if (!empty($search)): ?>
            <p>نتائج البحث عن: <?php echo htmlspecialchars($search); ?></p>
            <?php endif; ?>
        </div>
        <table class="table">
            <thead>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $row): 
                    $payment_type = $row['payment_type'] === 'outgoing' ? 'دفع للشركة' : 'استلام من الشركة';
                    $payment_method = [
                        'cash' => 'نقدي',
                        'bank_transfer' => 'تحويل بنكي',
                        'check' => 'شيك'
                    ][$row['payment_method']] ?? $row['payment_method'];
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                    <td><?php echo $payment_type; ?></td>
                    <td><?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo $payment_method; ?></td>
                    <td><?php echo htmlspecialchars($row['reference_number'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['notes'] ?: '-'); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['payment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}

// إذا لم يكن طلب تصدير أو طباعة، نعرض الصفحة العادية
include '../includes/header.php';
?>
<style>
    @media print {
        .no-print, header, footer, .card-header, .card-footer, .btn, .form-group, .alert {
            display: none;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
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

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .print-only {
            display: block;
        }

        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .print-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .print-header p {
            margin: 5px 0;
            font-size: 14px;
        }

        /* إخفاء الجزء الذي يحتوي على "نظام إدارة النقل" */
        .system-title {
            display: none;
        }
    }
</style>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h2 class="h3 mb-0">سجل العمليات المالية</h2>
        <div>
         
            <a href="?print=true&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-success">
                <i class="fas fa-print me-1"></i>
                طباعة البيانات فقط
            </a>
            <a href="?export_excel=true&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&search=<?php echo urlencode($search); ?>" class="btn btn-warning">
                <i class="fas fa-file-excel me-1"></i>
                تصدير إلى Excel
            </a>
        </div>
    </div>

    <!-- تصفية حسب التاريخ والبحث -->
    <form method="GET" class="mb-4 no-print">
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
                <label for="search">بحث (شركة أو رقم المرجع):</label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary mt-4">تصفية</button>
                <a href="?" class="btn btn-secondary mt-4">إعادة تعيين</a>
            </div>
        </div>
    </form>

    <!-- عرض البيانات -->
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
                    <thead>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $row): 
                            $payment_type = $row['payment_type'] === 'outgoing' ? 'دفع للشركة' : 'استلام من الشركة';
                            $payment_method = [
                                'cash' => 'نقدي',
                                'bank_transfer' => 'تحويل بنكي',
                                'check' => 'شيك'
                            ][$row['payment_method']] ?? $row['payment_method'];
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                            <td><?php echo $payment_type; ?></td>
                            <td><?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo $payment_method; ?></td>
                            <td><?php echo htmlspecialchars($row['reference_number'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['notes'] ?: '-'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['payment_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['admin_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
include '../includes/footer.php';
?>