<?php
require_once '../config.php';
require_once '../includes/header.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// التحقق من نوع المستخدم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'accounting') {
    header('Location: index.php');
    exit;
}

// معالجة تحديث الأرباح والملاحظات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_driver'])) {
    $driver_id = $_POST['driver_id'];
    $total_earnings = floatval($_POST['total_earnings']);
    $notes = $_POST['notes'];
    
    $stmt = $conn->prepare("UPDATE drivers SET total_earnings = ?, notes = ? WHERE id = ?");
    if ($stmt->execute([$total_earnings, $notes, $driver_id])) {
        $success_msg = "تم تحديث بيانات السائق بنجاح";
    } else {
        $error_msg = "حدث خطأ أثناء تحديث البيانات";
    }
}

// البحث عن السائقين
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = " WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// جلب قائمة السائقين
$query = "SELECT * FROM drivers" . $search_condition . " ORDER BY total_earnings DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحصيل من السواق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .search-box {
            margin-bottom: 20px;
        }
        .driver-card {
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .driver-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .earnings-input {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <!-- Search Box -->
                <div class="card search-box">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="ابحث عن سائق (الاسم، البريد الإلكتروني، رقم الهاتف)"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> بحث
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="driver_earnings_settings.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> مسح البحث
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <!-- Drivers List -->
                <?php foreach ($drivers as $driver): ?>
                    <div class="card driver-card">
                        <div class="card-body">
                            <form method="POST" class="row align-items-center">
                                <input type="hidden" name="driver_id" value="<?php echo $driver['id']; ?>">
                                
                                <div class="col-md-3">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($driver['username']); ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($driver['email']); ?><br>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($driver['phone']); ?>
                                    </small>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">إجمالي الطلبات</label>
                                    <div class="fw-bold"><?php echo $driver['total_trips']; ?></div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="form-label">التقييم</label>
                                    <div class="text-warning">
                                        <?php echo number_format($driver['rating'], 1); ?> <i class="fas fa-star"></i>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="total_earnings_<?php echo $driver['id']; ?>" class="form-label">الأرباح</label>
                                    <input type="number" 
                                           class="form-control earnings-input" 
                                           id="total_earnings_<?php echo $driver['id']; ?>"
                                           name="total_earnings"
                                           value="<?php echo $driver['total_earnings']; ?>"
                                           step="0.01">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="notes_<?php echo $driver['id']; ?>" class="form-label">ملاحظات</label>
                                    <textarea class="form-control" 
                                              id="notes_<?php echo $driver['id']; ?>"
                                              name="notes"
                                              rows="2"><?php echo htmlspecialchars($driver['notes'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-md-1">
                                    <button type="submit" 
                                            name="update_driver" 
                                            class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($drivers)): ?>
                    <div class="alert alert-info" role="alert">
                        لا يوجد سائقين متطابقين مع البحث
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 