<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['company_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "الرجاء إدخال بريد إلكتروني صحيح";
        } else if (strlen($password) < 6) {
            $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
        } else {
                $sql = "SELECT * FROM companies WHERE email = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute([$email])) {
                    $company = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($company && password_verify($password, $company['password'])) {
                        $_SESSION['company_id'] = $company['id'];
                        $_SESSION['company_name'] = $company['name'];
                        
                        if (isset($_POST['remember_me']) && $_POST['remember_me'] == 1) {
                            $token = bin2hex(random_bytes(32));
                            $expires = time() + (30 * 24 * 60 * 60); // 30 days
                            
                            $sql = "UPDATE companies SET remember_token = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$token, $company['id']]);
                            
                            setcookie('remember_token', $token, $expires, '/', '', true, true);
                        }
                        
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "بيانات الدخول غير صحيحة";
                    }
                } else {
                    $error = "حدث خطأ في النظام";
            }
        }
    } else {
        $error = "الرجاء إدخال البريد الإلكتروني وكلمة المرور";
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="تسجيل دخول الشركات - منصة التوظيف الأولى في المملكة">
    <meta name="keywords" content="توظيف, شركات, وظائف">
    <title>تسجيل دخول الشركات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --background-color: #f8f9fa;
        }
        body { 
            background: linear-gradient(135deg, var(--background-color) 0%, #ffffff 100%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 480px;
            margin: 30px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
        }
        .company-logo {
            text-align: center;
            margin-bottom: 35px;
            padding: 20px;
        }
        .company-logo img { 
            max-width: 200px;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1));
        }
        .company-logo img:hover {
            transform: scale(1.05);
        }
        .form-control {
            padding: 14px;
            border-radius: 10px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            border-color: var(--secondary-color);
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group-text {
            background-color: transparent;
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            color: var(--primary-color);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
            transition: all 0.3s ease;
        }
        .btn-primary:hover::after {
            transform: rotate(45deg) translate(50%, 50%);
        }
        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert i {
            font-size: 1.2rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .additional-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid #eee;
        }
        .additional-links a {
            color: var(--secondary-color);
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        .additional-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            transition: width 0.3s ease;
        }
        .additional-links a:hover::after {
            width: 100%;
        }
        .form-check {
            padding: 0.5rem 0;
        }
        .form-check-input {
            margin-left: 0.5rem;
            cursor: pointer;
        }
        .form-check-label {
            cursor: pointer;
            user-select: none;
        }
        .password-toggle {
            cursor: pointer;
            padding: 14px;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        .password-toggle:hover {
            color: var(--secondary-color);
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        @media (max-width: 576px) {
            .login-container {
                margin: 15px;
                padding: 25px;
            }
            .additional-links a {
                display: block;
                margin: 10px 0;
            }
        }
        .loading {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .loading.active {
            display: flex;
        }
        .spinner {
            width: 40px;
            height: 40px;
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
    <div class="container">
        <div class="login-container">
            <div class="loading">
                <div class="spinner"></div>
            </div>
            <div class="company-logo">
                <img src="../assets/img/logo.png" alt="Logo" class="img-fluid">
            </div>
            <h2 class="text-center mb-4">تسجيل دخول الشركات</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="" class="needs-validation" novalidate id="loginForm">
                <div class="mb-4">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" required 
                               pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                               title="الرجاء إدخال بريد إلكتروني صحيح">
                        <div class="invalid-feedback">
                            الرجاء إدخال بريد إلكتروني صحيح
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="6" 
                               title="كلمة المرور يجب أن تكون 6 أحرف على الأقل">
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                        <div class="invalid-feedback">
                            كلمة المرور يجب أن تكون 6 أحرف على الأقل
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" value="1">
                        <label class="form-check-label" for="remember_me">
                            تذكرني
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    تسجيل الدخول
                </button>
                
                <div class="additional-links">
                    <a href="forgot-password.php">
                        <i class="fas fa-key me-1"></i>
                        نسيت كلمة المرور؟
                    </a>
                    <span class="mx-2">|</span>
                    <a href="register.php">
                        <i class="fas fa-user-plus me-1"></i>
                        تسجيل شركة جديدة
                    </a>
                </div>
            </form>
            
            <div class="login-footer">
                <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                document.querySelector('.loading').classList.add('active');
                document.getElementById('submitBtn').disabled = true;
            }
            this.classList.add('was-validated');
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

        // Add password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            // You can add password strength logic here if needed
        });

        // Disable autocomplete for security
        document.getElementById('password').setAttribute('autocomplete', 'new-password');

        // Auto refresh CSRF token every 30 minutes
        setInterval(function() {
            fetch('refresh_csrf.php')
                .then(response => response.json())
                .then(data => {
                    if (data.csrf_token) {
                        document.querySelector('input[name="csrf_token"]').value = data.csrf_token;
                    }
                })
                .catch(error => console.error('Error refreshing CSRF token:', error));
        }, 1800000); // 30 minutes
    </script>
</body>
</html>
