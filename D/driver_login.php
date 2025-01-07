<?php
require_once 'config.php';
require_once 'driver_auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize DriverAuth class
$driverAuth = new DriverAuth($conn);

// إضافة رسائل تصحيح الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اختبار اتصال قاعدة البيانات
try {
    $testConnection = $conn->query('SELECT 1');
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// Redirect if already logged in
if ($driverAuth->isDriverLoggedIn()) {
    header('Location: driver_dashboard.php');
    exit;
}

$error = '';
$success = '';
$pending_orders = 0;

// Get pending orders count
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM requests 
        WHERE status = 'pending' 
        AND driver_id IS NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    $pending_orders = $result['count'];
} catch (PDOException $e) {
    error_log("Error getting pending orders: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "الرجاء إدخال البريد الإلكتروني وكلمة المرور";
    } else {
        try {
            $result = $driverAuth->authenticateDriver($email, $password);
            if ($result['success']) {
                // Set success message in session
                $_SESSION['success_message'] = 'تم تسجيل الدخول بنجاح';
                header('Location: driver_dashboard.php');
                exit;
            } else {
                $error = $result['message'];
                // Log failed attempt
                error_log("Failed login attempt for email: $email");
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "حدث خطأ في تسجيل الدخول. الرجاء المحاولة مرة أخرى";
        }
    }
}

// Clear any existing error messages
if (isset($_SESSION['error_message'])) {
    unset($_SESSION['error_message']);
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
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: inline-block;
            transition: transform 0.3s ease;
        }

        .login-logo i:hover {
            transform: scale(1.1);
        }

        .login-logo h2 {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }

        .orders-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: 500;
        }

        .orders-badge i {
            margin-left: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .form-control {
            padding-right: 2.5rem;
            padding-left: 2.5rem;
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 500;
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-danger::before {
            content: '\f071';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .login-logo i {
                font-size: 2.5rem;
            }
            
            .login-logo h2 {
                font-size: 1.5rem;
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-car"></i>
            <h2>تسجيل دخول السائق</h2>
            <p class="text-muted">مرحباً بك في نظام إدارة التوصيل</p>
            <?php if ($pending_orders > 0): ?>
                <div class="orders-badge pulse">
                    <i class="fas fa-bell"></i>
                    يوجد <?php echo $pending_orders; ?> طلب جديد في انتظار التوصيل
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" autocomplete="off">
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="البريد الإلكتروني"
                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                       required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="كلمة المرور"
                       required>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Auto-refresh pending orders count every minute
        setInterval(function() {
            fetch('check_pending_orders.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.orders-badge');
                    if (data.count > 0) {
                        if (!badge) {
                            location.reload();
                        }
                    } else {
                        if (badge) {
                            badge.remove();
                        }
                    }
                });
        }, 60000);
    </script>
</body>
</html>
