<?php
require_once '../config.php';
if (!isset($_SESSION['company_email'])) {
      header("Location: login.php");
    exit();
}
    

$company_id = $_SESSION['company_id'];

// Get company information
$stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get staff members with passwords
$stmt = $conn->prepare("
    SELECT id, name, email, phone, role, is_active, last_login, created_at, password
    FROM company_staff
    WHERE company_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$company_id]);
$staff_members = $stmt->fetchAll();

// Handle staff member deletion
if (isset($_POST['delete_staff']) && isset($_POST['staff_id'])) {
    $stmt = $conn->prepare("DELETE FROM company_staff WHERE id = ? AND company_id = ?");
    $stmt->execute([$_POST['staff_id'], $company_id]);
    header("Location: staff.php?success=تم حذف الموظف بنجاح");
    exit();
}

// Handle staff member status toggle
if (isset($_POST['toggle_status']) && isset($_POST['staff_id'])) {
    $stmt = $conn->prepare("UPDATE company_staff SET is_active = NOT is_active WHERE id = ? AND company_id = ?");
    $stmt->execute([$_POST['staff_id'], $company_id]);
    header("Location: staff.php");
    exit();
}

// Generate new password if requested
if (isset($_POST['reset_password']) && isset($_POST['staff_id'])) {
    try {
        error_log("Starting password reset for staff ID: {$_POST['staff_id']}");
        
        // Get company name for password
        $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            error_log("Company not found for ID: {$company_id}");
            throw new Exception('Company not found');
        }
        
        // Debug company info
        error_log("Company found: " . print_r($company, true));
        
        // Generate a simple password using company name
        $new_password = $company['name']; // Use company name without trimming
        
        // Debug password info
        error_log("New password: " . $new_password);
        
        // Hash the password with default options
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        error_log("Generated hash: " . $hashed_password);
        
        // Test password verification immediately
        $verify_test = password_verify($new_password, $hashed_password);
        error_log("Initial hash verification test: " . ($verify_test ? "SUCCESS" : "FAILED"));
        
        if (!$verify_test) {
            throw new Exception('Password hash verification failed');
        }
        
        // Update password in database
        $stmt = $conn->prepare("UPDATE company_staff SET password = ? WHERE id = ? AND company_id = ?");
        $result = $stmt->execute([$hashed_password, $_POST['staff_id'], $company_id]);
        
        if (!$result) {
            error_log("Database update failed");
            throw new Exception('Failed to update password in database');
        }
        
        if ($stmt->rowCount() === 0) {
            error_log("No rows updated. Staff ID: {$_POST['staff_id']}, Company ID: {$company_id}");
            throw new Exception('No staff member was updated');
        }
        
        // Verify the password was saved correctly
        $verify_stmt = $conn->prepare("SELECT password FROM company_staff WHERE id = ? AND company_id = ?");
        $verify_stmt->execute([$_POST['staff_id'], $company_id]);
        $saved_data = $verify_stmt->fetch();
        
        if (!$saved_data) {
            throw new Exception('Could not verify saved password');
        }
        
        $final_verify = password_verify($new_password, $saved_data['password']);
        error_log("Final verification test: " . ($final_verify ? "SUCCESS" : "FAILED"));
        
        if (!$final_verify) {
            throw new Exception('Saved password verification failed');
        }
        
        // Get staff info for session
        $stmt = $conn->prepare("SELECT name, email, phone FROM company_staff WHERE id = ? AND company_id = ?");
        $stmt->execute([$_POST['staff_id'], $company_id]);
        $staff_info = $stmt->fetch();
        
        if (!$staff_info) {
            throw new Exception('Staff member not found');
        }
        
        // Store password temporarily
        $_SESSION['temp_password'] = [
            'staff_id' => $_POST['staff_id'],
            'password' => $new_password,
            'name' => $staff_info['name'],
            'email' => $staff_info['email'],
            'phone' => $staff_info['phone']
        ];
        
        // Log success
        error_log("Password reset successful");
        error_log("Staff info: " . print_r($staff_info, true));
        error_log("New password: " . $new_password);
        
        // Redirect with success message
        header("Location: staff.php?success=تم تحديث كلمة المرور بنجاح&staff_id={$_POST['staff_id']}&new_password=" . urlencode($new_password));
        exit();
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        header("Location: staff.php?error=حدث خطأ أثناء تحديث كلمة المرور: " . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --border-radius: 8px;
            --box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Button Styles */
        .btn {
            border-radius: var(--border-radius);
            padding: 0.6rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            min-width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0.2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
        }

        .btn-info {
            background: linear-gradient(45deg, #00BCD4, #00ACC1);
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(45deg, #f39c12, #f1c40f);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-reset {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
            color: white;
        }

        .btn-group {
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-group .btn {
            margin: 0;
            flex: 0 0 auto;
        }

        @media (max-width: 768px) {
            .btn {
                padding: 0.8rem;
                height: 48px;
                width: 48px;
            }

            .btn i {
                font-size: 1.3rem;
            }

            .btn-group {
                justify-content: center;
            }
        }

        /* Add Button Styles */
        .btn-add-staff {
            width: auto !important;
            padding: 0.8rem 2rem !important;
            font-size: 1rem;
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .btn-add-staff:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }

        /* Badge Styles */
        .badge {
            padding: 0.6rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .badge.bg-success {
            background: linear-gradient(45deg, #27ae60, #2ecc71) !important;
        }

        .badge.bg-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b) !important;
        }

        .badge.bg-info {
            background: linear-gradient(45deg, #00BCD4, #00ACC1) !important;
        }

        /* Table Styles */
        .table th {
            background: linear-gradient(45deg, #34495e, #2c3e50);
            color: white;
            padding: 1rem;
            font-weight: 500;
            border: none;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        /* Card Styles */
        .card {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .card-body {
            padding: 2rem;
        }

        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .staff-info {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 300px;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        /* تصميم التنبيهات */
        .alert-float {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 300px;
            z-index: 9999;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.5s ease-out;
            display: none;
        }

        @keyframes slideDown {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        .alert-float.show {
            display: block;
        }

        .alert-float .close-btn {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .alert-float .close-btn:hover {
            opacity: 1;
            transform: translateY(-50%) rotate(90deg);
        }

        .alert-float i {
            margin-left: 8px;
            font-size: 1.2rem;
        }

        /* Main Header Style */
        .main-header {
            background: linear-gradient(45deg, #5c258d, #4389a2);
            margin-bottom: 2rem;
        }

        /* Alert Style */
        .alert-success {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
            border: none;
        }

        .alert-float {
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            border: none;
        }

        /* Modal Header Style */
        .modal-header {
            background: linear-gradient(45deg, #34495e, #2c3e50);
            color: white;
            border: none;
        }

        /* Navbar Style */
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
        }

        /* Alert Styles */
        .custom-alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 400px;
            max-width: 90%;
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.5s ease-out forwards;
            font-size: 1rem;
            line-height: 1.5;
            direction: rtl;
        }

        .custom-alert.error {
            background: linear-gradient(45deg, #dc3545, #c82333);
        }

        .custom-alert .alert-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .custom-alert i {
            font-size: 1.5rem;
            margin-left: 0.5rem;
        }

        .custom-alert .alert-message {
            flex: 1;
            padding-left: 1rem;
        }

        .custom-alert .close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            margin-right: 1rem;
        }

        .custom-alert .close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        .custom-alert .password-display {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin: 0.5rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-alert .copy-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .custom-alert .copy-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        @keyframes slideDown {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, 0);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -20px);
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header class="main-header">
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <!-- Company Logo & Name -->
                <div class="d-flex align-items-center">
                    <?php if (!empty($company['logo'])): ?>
                        <img src="../uploads/logos/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="company-logo me-2">
                    <?php endif; ?>
                    <span class="company-name"><?php echo htmlspecialchars($company['name']); ?></span>
                </div>

                <!-- Navigation Links -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php#requests">
                                <i class="bi bi-list-check"></i> الطلبات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="staff.php">
                                <i class="bi bi-people"></i> الموظفين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="bi bi-person"></i> الملف الشخصي
                            </a>
                        </li>
                    </ul>
                    
                    <!-- User Menu -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="ms-1"><?php echo htmlspecialchars($_SESSION['company_name'] ?? ''); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-gear"></i> الإعدادات
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <style>
        .main-header {
            background: linear-gradient(45deg, #5c258d, #4389a2);
            margin-bottom: 2rem;
        }
        .navbar {
            padding: 1rem 0;
        }
        .company-logo {
            height: 40px;
            width: auto;
        }
        .company-name {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
        }
        .navbar-nav .nav-link i {
            margin-left: 5px;
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        .dropdown-item i {
            margin-left: 8px;
        }
        @media (max-width: 991px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.1);
                padding: 1rem;
                border-radius: 10px;
                backdrop-filter: blur(10px);
                margin-top: 1rem;
            }
        }
    </style>

    <div class="container py-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-3 mb-md-0">
                        <i class="bi bi-people-fill me-2"></i>
                        إدارة الموظفين
                    </h5>
                    <button type="button" class="btn btn-add-staff" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="bi bi-plus-lg me-2"></i>
                        إضافة موظف جديد
                    </button>
                </div>

                <!-- Modal إضافة موظف -->
                <div class="modal fade" id="addStaffModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">إضافة موظف جديد</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addStaffForm" method="post" action="ajax/add_staff.php">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">الاسم</label>
                                            <input type="text" name="name" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">البريد الإلكتروني</label>
                                            <input type="email" name="email" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">رقم الجوال</label>
                                            <input type="tel" name="phone" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">الدور</label>
                                            <select name="role" class="form-select" required>
                                                <option value="staff">موظف</option>
                                                <option value="order_manager">مدير طلبات</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" checked>
                                                <label class="form-check-label" for="isActive">حساب نشط</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" class="btn btn-primary">إضافة الموظف</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .modal-content {
                        border: none;
                        border-radius: 15px;
                        box-shadow: 0 0 30px rgba(0,0,0,0.1);
                    }
                    .modal-header {
                        background: linear-gradient(45deg, #5c258d, #4389a2);
                        color: white;
                        border-radius: 15px 15px 0 0;
                        padding: 1rem 1.5rem;
                    }
                    .modal-header .btn-close {
                        filter: brightness(0) invert(1);
                    }
                    .modal-body {
                        padding: 2rem;
                    }
                    .modal-footer {
                        border-top: 1px solid #eee;
                        padding: 1rem 1.5rem;
                    }
                    .form-control, .form-select {
                        border-radius: 8px;
                        padding: 0.6rem 1rem;
                        border: 1px solid #e0e0e0;
                    }
                    .form-control:focus, .form-select:focus {
                        border-color: #4158D0;
                        box-shadow: 0 0 0 0.2rem rgba(65, 88, 208, 0.25);
                    }
                    .form-check-input:checked {
                        background-color: #4158D0;
                        border-color: #4158D0;
                    }
                </style>

                <script>
                    document.getElementById('addStaffForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        fetch(this.action, {
                            method: 'POST',
                            body: new FormData(this)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // إخفاء المودال
                                bootstrap.Modal.getInstance(document.getElementById('addStaffModal')).hide();
                                // عرض رسالة النجاح
                                showAlert(data.message || 'تم إضافة الموظف بنجاح');
                                // إعادة تحميل الصفحة بعد 3 ثواني
                                setTimeout(() => {
                                    location.reload();
                                }, 3000);
                            } else {
                                showAlert(data.message || 'حدث خطأ أثناء إضافة الموظف');
                            }
                        })
                        .catch(error => {
                            showAlert('حدث خطأ في إرسال البيانات');
                        });
                    });
                </script>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                        <?php if (isset($_GET['staff_id']) && isset($_GET['new_password'])): ?>
                            <br>
                            كلمة المرور الجديدة: <span class="font-monospace"><?php echo htmlspecialchars($_GET['new_password']); ?></span>
                            <button class="btn btn-sm btn-secondary ms-2" onclick="copyText('<?php echo htmlspecialchars($_GET['new_password']); ?>')">نسخ</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>رقم الجوال</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>آخر دخول</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_members as $staff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td>
                                        <?php echo $staff['role'] === 'order_manager' ? 'مدير طلبات' : 'موظف'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $staff['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $staff['is_active'] ? 'نشط' : 'غير نشط'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $staff['last_login'] ? date('Y-m-d H:i', strtotime($staff['last_login'])) : 'لم يسجل الدخول بعد'; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($staff['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="showStaffInfo('<?php echo htmlspecialchars($staff['name']); ?>', 
                                                                         '<?php echo htmlspecialchars($staff['email']); ?>', 
                                                                         '<?php echo htmlspecialchars($staff['phone']); ?>', 
                                                                         '<?php 
                                                                            if (isset($_SESSION['temp_password']) && $_SESSION['temp_password']['staff_id'] == $staff['id']) {
                                                                                echo htmlspecialchars($_SESSION['temp_password']['password']);
                                                                            } elseif (isset($_SESSION['new_staff']) && $_SESSION['new_staff']['staff_id'] == $staff['id']) {
                                                                                echo htmlspecialchars($_SESSION['new_staff']['password']);
                                                                            } else {
                                                                                echo htmlspecialchars($company['name']);
                                                                            }
                                                                         ?>')">
                                                <i class="bi bi-clipboard"></i> نسخ البيانات
                                            </button>
                                            <?php if ($staff['phone']): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $staff['phone']); ?>" 
                                               target="_blank" class="btn btn-sm btn-success">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-primary" 
                                                    onclick="editStaff(<?php echo htmlspecialchars(json_encode([
                                                        'id' => $staff['id'],
                                                        'name' => $staff['name'],
                                                        'email' => $staff['email'],
                                                        'phone' => $staff['phone'],
                                                        'role' => $staff['role'],
                                                        'is_active' => $staff['is_active']
                                                    ])); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" class="d-inline" 
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا الموظف؟');">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="delete_staff" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-power"></i>
                                                </button>
                                            </form>
                                            <form method="post" class="d-inline" 
                                                  onsubmit="return confirmPasswordReset();">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                                <button type="submit" name="reset_password" class="btn btn-reset" title="إعادة تعيين كلمة المرور">
                                                    <i class="bi bi-key"></i>
                                                    <span class="d-none d-md-inline">تغيير كلمة المرور</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Info Modal -->
    <div class="overlay" id="overlay"></div>
    <div class="staff-info" id="staffInfo">
        <h5 class="mb-3">بيانات الموظف</h5>
        <div class="mb-3">
            <label class="form-label">جميع البيانات:</label>
            <div class="input-group">
                <textarea class="form-control" id="allInfo" rows="6" readonly></textarea>
                <button class="btn btn-outline-secondary" onclick="copyField('allInfo')">نسخ</button>
            </div>
        </div>
        <div class="text-end">
            <button class="btn btn-secondary" onclick="hideStaffInfo()">إغلاق</button>
        </div>
    </div>

    <!-- إضافة عنصر التنبيه -->
    <div id="alertFloat" class="alert-float">
        <i class="bi bi-check-circle-fill"></i>
        <span id="alertMessage"></span>
        <button type="button" class="close-btn" onclick="hideAlert()">×</button>
    </div>

    <!-- Modal تأكيد تغيير كلمة المرور -->
    <div class="modal fade" id="passwordResetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        تنبيه هام
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-shield-exclamation text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <div class="alert alert-warning">
                        <strong>تنبيه!</strong> سيتم تغيير كلمة المرور إلى اسم الشركة.
                        <br>
                        يرجى تنبيه الموظف بتغيير كلمة المرور فور تسجيل الدخول.
                    </div>
                    <p class="text-danger">
                        <i class="bi bi-info-circle"></i>
                        ملاحظة: لن تظهر كلمة المرور مرة أخرى بعد إغلاق النافذة.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-warning" id="confirmResetBtn">
                        <i class="bi bi-key"></i> تأكيد تغيير كلمة المرور
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تعديل الموظف -->
    <div class="modal fade" id="editStaffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل بيانات الموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm" method="post" action="ajax/update_staff.php">
                        <input type="hidden" name="staff_id" id="edit_staff_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">الاسم</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الجوال</label>
                                <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الدور</label>
                                <select name="role" id="edit_role" class="form-select" required>
                                    <option value="staff">موظف</option>
                                    <option value="order_manager">مدير طلبات</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="edit_is_active">
                                    <label class="form-check-label" for="edit_is_active">حساب نشط</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showStaffInfo(name, email, phone, password = '') {
            const loginUrl = window.location.origin + '/companies/staff_login.php';
            // إذا كانت كلمة المرور تحتوي على $ (علامة الهاش)، نستخدم اسم الشركة كبديل
            if (password.includes('$')) {
                password = '<?php echo htmlspecialchars($company['name']); ?>';
            }
            
            let infoText = 
                `معلومات الحساب:\n` +
                `------------------\n` +
                `الاسم: ${name}\n` +
                `البريد الإلكتروني: ${email}\n` +
                `رقم الجوال: ${phone}\n` +
                `كلمة المرور: ${password}\n` +
                `رابط تسجيل الدخول: ${loginUrl}`;

            document.getElementById('allInfo').value = infoText;
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('staffInfo').style.display = 'block';
        }

        function hideStaffInfo() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('staffInfo').style.display = 'none';
        }

        function copyField(fieldId) {
            const field = document.getElementById(fieldId);
            field.select();
            document.execCommand('copy');
            alert('تم نسخ البيانات بنجاح');
        }

        // Close modal when clicking overlay
        document.getElementById('overlay').addEventListener('click', hideStaffInfo);

        // دالة لعرض التنبيه
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert ${type}`;
            
            // إذا كانت الرسالة تحتوي على كلمة مرور
            if (message.includes('كلمة المرور الجديدة')) {
                const [title, ...rest] = message.split('كلمة المرور الجديدة هي:');
                const password = rest.join('').split('يرجى')[0].trim();
                
                alertDiv.innerHTML = `
                    <div class="alert-content">
                        <i class="bi bi-check-circle-fill"></i>
                        <div class="alert-message">
                            ${title}
                            <div class="password-display">
                                <span>${password}</span>
                                <button class="copy-btn" onclick="copyPassword('${password}')">
                                    <i class="bi bi-clipboard"></i> نسخ
                                </button>
                            </div>
                            <div>يرجى حفظ كلمة المرور في مكان آمن</div>
                        </div>
                    </div>
                    <button class="close-btn" onclick="closeAlert(this)">
                        <i class="bi bi-x"></i>
                    </button>
                `;
            } else {
                alertDiv.innerHTML = `
                    <div class="alert-content">
                        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
                        <div class="alert-message">${message}</div>
                    </div>
                    <button class="close-btn" onclick="closeAlert(this)">
                        <i class="bi bi-x"></i>
                    </button>
                `;
            }
            
            document.body.appendChild(alertDiv);
        }

        // دالة إغلاق التنبيه
        function closeAlert(button) {
            const alertDiv = button.closest('.custom-alert');
            alertDiv.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => alertDiv.remove(), 500);
        }

        // دالة نسخ كلمة المرور
        function copyPassword(password) {
            navigator.clipboard.writeText(password).then(() => {
                const copyBtn = event.target.closest('.copy-btn');
                copyBtn.innerHTML = '<i class="bi bi-check"></i> تم النسخ';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> نسخ';
                }, 2000);
            });
        }

        // عند نجاح تغيير كلمة المرور
        <?php if (isset($_SESSION['temp_password'])): ?>
        window.onload = function() {
            showAlert(`تم تغيير كلمة المرور بنجاح
                     كلمة المرور الجديدة هي: <?php echo $_SESSION['temp_password']['password']; ?>
                     يرجى تنبيه الموظف بتغييرها فور تسجيل الدخول`);
        };
        <?php endif; ?>

        // دالة تعديل الموظف
        function editStaff(staffData) {
            // تعبئة البيانات في النموذج
            document.getElementById('edit_staff_id').value = staffData.id;
            document.getElementById('edit_name').value = staffData.name;
            document.getElementById('edit_email').value = staffData.email;
            document.getElementById('edit_phone').value = staffData.phone;
            document.getElementById('edit_role').value = staffData.role;
            document.getElementById('edit_is_active').checked = staffData.is_active;

            // عرض المودال
            new bootstrap.Modal(document.getElementById('editStaffModal')).show();
        }

        // معالجة تحديث بيانات الموظف
        document.getElementById('editStaffForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إخفاء المودال
                    bootstrap.Modal.getInstance(document.getElementById('editStaffModal')).hide();
                    // عرض رسالة النجاح
                    showAlert(data.message || 'تم تحديث بيانات الموظف بنجاح');
                } else {
                    showAlert(data.message || 'حدث خطأ أثناء تحديث بيانات الموظف', 'error');
                }
            })
            .catch(error => {
                showAlert('حدث خطأ في إرسال البيانات', 'error');
            });
        });

        function confirmPasswordReset() {
            return confirm('هل أنت متأكد من تغيير كلمة المرور؟ سيتم تعيين كلمة المرور الجديدة إلى اسم الشركة.');
        }

        // إذا كان هناك رسالة نجاح مع كلمة مرور جديدة
        <?php if (isset($_GET['success']) && isset($_GET['new_password'])): ?>
        window.onload = function() {
            showAlert(`تم تغيير كلمة المرور بنجاح
                     كلمة المرور الجديدة هي: <?php echo htmlspecialchars($_GET['new_password']); ?>
                     يرجى حفظ كلمة المرور في مكان آمن`);
        };
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Clear temporary passwords after displaying
if (isset($_SESSION['temp_password'])) {
    unset($_SESSION['temp_password']);
}
if (isset($_SESSION['new_staff'])) {
    unset($_SESSION['new_staff']);
}
?>