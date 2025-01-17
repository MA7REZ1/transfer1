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
            $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_department'] = $admin['department'];
                
                $stmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
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
    <meta name="description" content="لوحة تحكم الإدارة">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f8fafc;
            --text-color: #1e293b;
            --border-radius: 16px;
            --box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
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
            max-width: 440px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .login-logo i:hover {
            transform: scale(1.15) rotate(5deg);
        }

        .login-logo h2 {
            color: var(--text-color);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            margin-bottom: 0.5rem;
        }

        .login-logo p {
            color: #64748b;
            font-size: 1rem;
            margin: 0;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .input-group .form-control {
            height: 3.25rem;
            padding: 1rem 3rem;
            font-size: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .input-group .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }

        .input-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: color 0.3s ease;
            font-size: 1.1rem;
        }

        .input-group:focus-within .input-icon {
            color: var(--primary-color);
        }

        .password-toggle {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .btn-login {
            width: 100%;
            height: 3.25rem;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border-right: 4px solid #dc2626;
        }

        .employee-login-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: #64748b;
            text-decoration: none;
            font-size: 0.95rem;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .employee-login-link:hover {
            background-color: #f1f5f9;
            color: var(--primary-color);
        }

        .employee-login-link i {
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
            
            .login-logo i {
                font-size: 3rem;
            }
            
            .login-logo h2 {
                font-size: 1.75rem;
            }

            .input-group .form-control {
                height: 3rem;
                font-size: 0.95rem;
            }

            .btn-login {
                height: 3rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-user-shield"></i>
            <h2>تسجيل الدخول</h2>
            <p class="text-muted">مرحباً بك في لوحة التحكم</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo sanitizeInput($error); ?>
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
                       required
                       autocomplete="off">
            </div>

            <div class="input-group">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="كلمة المرور"
                       required
                       autocomplete="new-password">
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-login" id="submitBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </button>

            <a href="employee-login.php" class="employee-login-link">
                <i class="fas fa-users"></i>
                تسجيل دخول الموظفين
            </a>
        </form>
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

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalContent = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحقق...';
            submitBtn.disabled = true;

            // Store original content to restore if there's an error
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            }, 10000); // Timeout after 10 seconds
        });

        // Add input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Check initial state
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });
    </script>
</body>
</html> 