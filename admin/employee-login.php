<?php
require_once '../config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "الرجاء إدخال البريد الإلكتروني وكلمة المرور";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $employee = $stmt->fetch();
            
            if ($employee && password_verify($password, $employee['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $employee['id'];
                $_SESSION['admin_role'] = $employee['role'];
                $_SESSION['admin_username'] = $employee['username'];
                
                // جلب صلاحيات الموظف
                $stmt = $conn->prepare("SELECT * FROM employee_permissions WHERE employee_id = ?");
                $stmt->execute([$employee['id']]);
                $permissions = $stmt->fetch();
                $_SESSION['permissions'] = $permissions;
                
                // تحديث آخر تسجيل دخول
                $stmt = $conn->prepare("UPDATE employees SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$employee['id']]);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "بيانات الدخول غير صحيحة";
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ في النظام";
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الموظفين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #1d4ed8;
            --background-color: #f0f9ff;
            --text-color: #1e293b;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .employee-login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .admin-btn {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .admin-btn:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .admin-btn i {
            font-size: 1.1rem;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }

        .login-header h1 {
            color: var(--text-color);
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #64748b;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        .form-control {
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-login {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            width: 100%;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
        }

        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.875rem;
            margin: 0 0.5rem;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }

        .admin-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: #e2e8f0;
            color: var(--text-color);
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .admin-link:hover {
            background-color: #cbd5e1;
            color: var(--primary-color);
        }

        @media (max-width: 480px) {
            .employee-login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="employee-login-container">
        <div class="login-header">
            <a href="index.php" class="admin-btn">
                <i class="fas fa-users-cog"></i>
                إدارة الموظفين
            </a>
            <i class="fas fa-user-tie fa-3x mb-3" style="color: var(--primary-color);"></i>
            <h1>تسجيل دخول الموظفين</h1>
            <p>أهلاً بك في نظام إدارة الموظفين</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo sanitizeInput($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="employeeLoginForm">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input 
                    type="email" 
                    class="form-control" 
                    name="email" 
                    placeholder="البريد الإلكتروني"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input 
                    type="password" 
                    class="form-control" 
                    name="password" 
                    id="password"
                    placeholder="كلمة المرور"
                    required
                >
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-login" id="submitBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </button>
        </form>

        <div class="footer-links">
            <a href="forgot-password.php">نسيت كلمة المرور؟</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('employeeLoginForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحقق...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html> 