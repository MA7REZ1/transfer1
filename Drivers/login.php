<?php
require_once '../config.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "الرجاء إدخال البريد الإلكتروني وكلمة المرور";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $driver = $stmt->fetch();
            
            if ($driver && password_verify($password, $driver['password'])) {
                session_regenerate_id(true);
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_username'] = $driver['username'];
                
                // Update last login time
                $stmt = $conn->prepare("UPDATE drivers SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$driver['id']]);
                
                // Log successful login
                $stmt = $conn->prepare("INSERT INTO activity_log (driver_id, action, details) VALUES (?, 'login_success', 'Driver logged in successfully')");
                $stmt->execute([$driver['id']]);
                
                header('Location: orders.php');
                exit;
            } else {
                // Log failed login attempt
                $stmt = $conn->prepare("INSERT INTO activity_log (driver_id, action, details) VALUES (NULL, 'login_failed', ?)");
                $stmt->execute(['Failed login attempt for email: ' . $email]);
                
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
    <meta name="description" content="تسجيل دخول السائقين - منصة التوصيل الأولى في المملكة">
    <meta name="keywords" content="سائقين, توصيل, طلبات">
    <title>تسجيل دخول السائقين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #3b82f6;
            --accent-color: #f97316;
            --background-color: #f0f9ff;
            --text-color: #1e293b;
            --border-radius: 16px;
        }

        body {
            background: linear-gradient(135deg, var(--background-color) 0%, #ffffff 100%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            background-attachment: fixed;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
          
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding: 1rem;
        }

        .login-logo::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            border-radius: 2px;
        }

        .login-logo i {
            font-size: 3.5rem;
            background: linear-gradient(45deg, var(--secondary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: inline-block;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .login-logo i:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .login-logo h2 {
            color: var(--text-color);
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .login-logo p {
            color: #64748b;
            margin-top: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-control {
            padding: 0.875rem 3rem 0.875rem 1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background-color: #ffffff;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .input-group:focus-within .input-icon {
            color: var(--secondary-color);
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            color: var(--secondary-color);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            border: none;
            border-radius: 12px;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, rgba(255,255,255,0.13) 0%, rgba(255,255,255,0) 100%);
            transform: rotate(45deg);
            transition: all 0.3s ease;
        }

        .btn-login:hover::after {
            transform: rotate(45deg) translate(50%, 50%);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            background-color: rgba(249, 115, 22, 0.08);
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 3px 6px rgba(249, 115, 22, 0.08);
        }

        .alert::before {
            content: '\f071';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
            }
            
            .login-logo i {
                font-size: 3rem;
            }
            
            .login-logo h2 {
                font-size: 1.5rem;
            }

            .btn-login {
                padding: 0.875rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, var(--secondary-color), var(--accent-color));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, var(--accent-color), var(--secondary-color));
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            justify-content: center;
            align-items: center;
            z-index: 1000;
          
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-truck"></i>
            <h2>تسجيل دخول السائقين</h2>
            <p>مرحباً بك في منصة التوصيل</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo sanitizeInput($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="input-group">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" 
                       class="form-control" 
                       id="email" 
                       name="email" 
                       placeholder="البريد الإلكتروني"
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

            <button type="submit" class="btn btn-login" id="submitBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                تسجيل الدخول
            </button>
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

        // Add loading state and validation
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                return;
            }
            
            document.querySelector('.loading-overlay').classList.add('active');
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحقق...';
            submitBtn.disabled = true;
        });

        // Prevent multiple form submissions
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            if (formSubmitted) {
                event.preventDefault();
                return;
            }
            formSubmitted = true;
        });

        // Add floating label effect
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