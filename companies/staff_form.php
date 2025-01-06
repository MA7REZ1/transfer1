<?php
require_once '../config.php';
if (!isset($_SESSION['company_email'])) {
      header("Location: login.php");
    exit();
}
    

$company_id = $_SESSION['company_id'];
$errors = [];
$success = '';
$generated_password = '';

// Initialize staff member data
$staff = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'staff',
    'is_active' => 1
];

// If editing existing staff member
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, role, is_active 
        FROM company_staff 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$_GET['id'], $company_id]);
    $found_staff = $stmt->fetch();
    
    if ($found_staff) {
        $staff = array_merge($staff, $found_staff);
    } else {
        header("Location: staff.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['name'])) {
        $errors['name'] = 'اسم الموظف مطلوب';
    }
    if (empty($_POST['email'])) {
        $errors['email'] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'البريد الإلكتروني غير صالح';
    }

    // Check if email exists for another staff member
    if (!empty($_POST['email'])) {
        $stmt = $conn->prepare("
            SELECT id FROM company_staff 
            WHERE email = ? AND id != ? AND company_id = ?
        ");
        $stmt->execute([$_POST['email'], $staff['id'] ?: 0, $company_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }

    if (empty($errors)) {
        try {
            if ($staff['id']) {
                // Update existing staff member
                $password_sql = '';
                $params = [
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['role'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $staff['id'],
                    $company_id
                ];

                // If password reset is requested
                if (!empty($_POST['reset_password'])) {
                    $generated_password = bin2hex(random_bytes(4)); // 8 characters
                    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                    $password_sql = ', password = ?';
                    array_splice($params, -2, 0, [$hashed_password]);
                }

                $stmt = $conn->prepare("
                    UPDATE company_staff 
                    SET name = ?, email = ?, phone = ?, role = ?, is_active = ? $password_sql
                    WHERE id = ? AND company_id = ?
                ");
                $stmt->execute($params);
                
                $success = 'تم تحديث بيانات الموظف بنجاح';
                if ($generated_password) {
                    $success .= sprintf(
                        '<br>كلمة المرور الجديدة هي: <span id="new_password" class="font-monospace">%s</span> '.
                        '<button class="btn btn-sm btn-secondary ms-2" onclick="copyPassword()">نسخ</button>',
                        $generated_password
                    );
                }
            } else {
                // Get company information
                $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
                $stmt->execute([$company_id]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC);

                // Generate password for new staff member (use company name)
                $generated_password = $company['name']; // Use company name as password
                $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
                
                // Insert new staff member
                $stmt = $conn->prepare("
                    INSERT INTO company_staff (company_id, name, email, phone, password, role, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_id,
                    $_POST['name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $hashed_password,
                    $_POST['role'],
                    isset($_POST['is_active']) ? 1 : 0
                ]);

                // Store the new staff info in session
                $_SESSION['new_staff'] = [
                    'staff_id' => $conn->lastInsertId(),
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                    'password' => $generated_password
                ];
                
                header("Location: staff.php?success=تم إضافة الموظف بنجاح");
                exit();
            }
        } catch (PDOException $e) {
            $errors['general'] = 'حدث خطأ أثناء حفظ البيانات';
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $staff['id'] ? 'تعديل موظف' : 'إضافة موظف جديد'; ?></title>
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
        }
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(44, 62, 80, 0.25);
        }
    </style>
</head>
<body>
     <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <?php echo $staff['id'] ? 'تعديل موظف' : 'إضافة موظف جديد'; ?>
                        </h5>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if (!empty($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>

                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم الموظف</label>
                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                       id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $staff['name']); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                       id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $staff['email']); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الجوال</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? $staff['phone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">الدور</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="staff" <?php echo ($staff['role'] === 'staff') ? 'selected' : ''; ?>>موظف</option>
                                    <option value="order_manager" <?php echo ($staff['role'] === 'order_manager') ? 'selected' : ''; ?>>مدير طلبات</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                           <?php echo (!$staff['id'] || $staff['is_active']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">الحساب نشط</label>
                                </div>
                            </div>

                            <?php if ($staff['id']): ?>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="reset_password" name="reset_password">
                                        <label class="form-check-label" for="reset_password">إعادة تعيين كلمة المرور</label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">حفظ</button>
                                <a href="staff.php" class="btn btn-secondary">رجوع</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyPassword() {
            const password = document.getElementById('new_password').textContent;
            navigator.clipboard.writeText(password);
            alert('تم نسخ كلمة المرور');
        }

        function copyEmail() {
            const email = document.getElementById('new_email').textContent;
            navigator.clipboard.writeText(email);
            alert('تم نسخ البريد الإلكتروني');
        }
    </script>
</body>
</html>