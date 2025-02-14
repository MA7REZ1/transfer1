<?php
require_once '../config.php';

// Initialize variables
$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        // Get staff member
        $stmt = $conn->prepare("
            SELECT s.*, c.name as company_name 
            FROM company_staff s
            JOIN companies c ON s.company_id = c.id
            WHERE s.email = ? AND s.is_active = 1
        ");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();
        
        if ($staff && password_verify($password, $staff['password'])) {
            // Update last login
            $stmt = $conn->prepare("UPDATE company_staff SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$staff['id']]);
            
            // Set session variables
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['staff_role'] = $staff['role'];
            $_SESSION['company_id'] = $staff['company_id'];
            $_SESSION['company_name'] = $staff['company_name'];
            
            // Redirect to appropriate page
            header("Location: orders.php");
            exit();
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="تسجيل دخول الموظفين - منصة التوظيف الأولى في المملكة">
    <meta name="keywords" content="توظيف, موظفين, وظائف">
 <title>تسجيل دخول الموظفين</title>
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
            --background-color: #f0f2f5;
        }
        body { 
            background: linear-gradient(135deg, var(--background-color) 0%, #ffffff 100%);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-attachment: fixed;
        }
        .login-container {
            max-width: 480px;
            margin: 30px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
     
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #4158D0, #C850C0);
        }
        .company-logo {
            text-align: center;
            margin-bottom: 35px;
            padding: 20px;
            position: relative;
        }
        .company-logo::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, #4158D0, #C850C0);
            border-radius: 2px;
        }
        .company-logo img { 
            max-width: 180px;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            filter: drop-shadow(0 8px 15px rgba(0,0,0,0.1));
        }
        .company-logo img:hover {
            transform: scale(1.08);
        }
        .form-control {
            padding: 14px 18px;
            border-radius: 12px;
            border: 2px solid #eef0f7;
            transition: all 0.3s ease;
            font-size: 1rem;
            background-color: #f8faff;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
            border-color: var(--secondary-color);
            background-color: #ffffff;
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group-text {
            background-color: #f8faff;
            border: 2px solid #eef0f7;
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }
        .input-group:focus-within .input-group-text {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4158D0 0%, #C850C0 100%);
            border: none;
            padding: 16px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(65, 88, 208, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .btn-primary::after {
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
        .btn-primary:hover::after {
            transform: rotate(45deg) translate(50%, 50%);
        }
        .alert {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            background-color: rgba(231, 76, 60, 0.08);
            color: var(--accent-color);
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 3px 6px rgba(231, 76, 60, 0.08);
        }
        .alert i {
            font-size: 1.25rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        .additional-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #eef0f7;
        }
        .additional-links a {
            color: var(--secondary-color);
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 0;
        }
        .additional-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(to right, #4158D0, #C850C0);
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .additional-links a:hover {
            color: #4158D0;
        }
        .additional-links a:hover::after {
            width: 100%;
        }
        .loading {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.95);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        
            border-radius: 24px;
        }
        .loading.active {
            display: flex;
        }
        .spinner {
            width: 45px;
            height: 45px;
            border: 4px solid #eef0f7;
            border-top: 4px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 576px) {
            .login-container {
                margin: 15px;
                padding: 30px 20px;
            }
            .additional-links a {
                display: block;
                margin: 12px 0;
            }
            .additional-links span {
                display: none;
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
            background: linear-gradient(45deg, #4158D0, #C850C0);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #3147b8, #b846ac);
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
            <h2 class="text-center mb-4">تسجيل دخول الموظفين</h2>
            <?php if ($error): ?>
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
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" required
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
                
                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    تسجيل الدخول
                </button>
                
                <div class="additional-links">
                    <a href="login.php">
                        <i class="fas fa-building me-1"></i>
                        تسجيل دخول الشركات
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

        // Disable autocomplete for security
        document.getElementById('password').setAttribute('autocomplete', 'new-password');
    </script>
</body>
</html>