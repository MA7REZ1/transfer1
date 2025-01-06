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
    <title>تسجيل دخول الموظفين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
        }
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 90%;
            padding: 2rem;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0 !important;
            padding: 2rem;
        }
        .card-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
        }
        .btn-primary:hover {
            opacity: 0.9;
        }
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-circle d-block"></i>
                <h4 class="mb-0">تسجيل دخول الموظفين</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php" class="back-link">
                        <i class="bi bi-arrow-right"></i>
                        العودة لتسجيل دخول الشركات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>