<?php
require_once 'config.php';

// التحقق من وجود جلسة مفتوحة
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ? AND is_active = 1 AND account_status = 'active'");
            $stmt->execute([$email]);
            $driver = $stmt->fetch();
            
            if ($driver && password_verify($password, $driver['password'])) {
                // تحديث وقت آخر تسجيل دخول
                $stmt = $conn->prepare("UPDATE drivers SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$driver['id']]);
                
                // تسجيل النشاط
                logActivity($driver['id'], 'login', 'تم تسجيل الدخول بنجاح');
                
                // إنشاء الجلسة
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_name'] = $driver['username'];
                $_SESSION['last_activity'] = time();
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'بيانات تسجيل الدخول غير صحيحة';
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء تسجيل الدخول';
            error_log($e->getMessage());
        }
    }
}

// عدد الطلبات المعلقة
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $pendingOrders = $stmt->fetchColumn();
} catch (PDOException $e) {
    $pendingOrders = 0;
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول السائق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 48px;
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .pending-orders {
            background: #e7f3ff;
            color: #0d6efd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            background: #0d6efd;
            border: none;
            color: white;
            font-weight: bold;
        }
        .btn-login:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-car"></i>
                <h2>تسجيل دخول السائق</h2>
                <p class="text-muted">مرحباً بك في نظام إدارة التوصيل</p>
            </div>
            
            <?php if ($pendingOrders > 0): ?>
            <div class="pending-orders">
                <i class="fas fa-bell me-2"></i>
                يوجد <?php echo $pendingOrders; ?> طلبات في انتظار التوصيل
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 