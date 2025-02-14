<?php
require_once '../config.php';

// Get current language direction
$dir = $_SESSION['lang'] === 'ar' ? 'rtl' : 'ltr';
$lang = $_SESSION['lang'];

// Include language file
require_once '../includes/languages.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام') {
    header('Location: index.php');
    exit;
}

// التأكد من وجود معرف الموظف
if (!isset($_GET['id'])) {
    header('Location: employees.php');
    exit;
}

$employee_id = intval($_GET['id']);

// جلب بيانات الموظف
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

// التحقق من وجود الموظف
if (!$employee) {
    $_SESSION['error'] = __('employee_not_found');
    header('Location: employees.php');
    exit;
}

// التحقق من صلاحيات التعديل
if ($_SESSION['admin_role'] !== 'super_admin' && 
    $_SESSION['admin_id'] !== $employee['id'] && 
    $_SESSION['admin_role'] !== 'مدير_عام') {
    $_SESSION['error'] = __('no_permission_edit');
    header('Location: employees.php');
    exit;
}

// التحقق من عدم تعديل بيانات المدير العام من قبل غير المدير العام نفسه
if ($employee['role'] === 'مدير_عام' && 
    $_SESSION['admin_id'] !== $employee['id'] && 
    $_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['error'] = __('cannot_edit_gm');
    header('Location: employees.php');
    exit;
}

// التحقق من عدم تعديل بيانات مدير النظام من قبل غير مدير النظام نفسه
if ($employee['role'] === 'super_admin' && 
    $_SESSION['admin_id'] !== $employee['id'] && 
    $_SESSION['admin_role'] !== 'super_admin') {
    $_SESSION['error'] = __('cannot_edit_admin');
    header('Location: employees.php');
    exit;
}

// معالجة تحديث البيانات
if (isset($_POST['update_employee'])) {
    $username = sanitizeInput($_POST['username']);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $department = sanitizeInput($_POST['department']);
    $role = sanitizeInput($_POST['role']);
    $status = sanitizeInput($_POST['status']);

    // التحقق من عدم تكرار اسم المستخدم والبريد الإلكتروني
    $stmt = $conn->prepare("SELECT id FROM employees WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $employee_id]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = __('duplicate_username_email');
    } else {
        // تحديث البيانات الأساسية
        $sql = "UPDATE employees SET 
                username = ?, 
                full_name = ?, 
                email = ?, 
                phone = ?, 
                department = ?, 
                role = ?, 
                status = ?";
        $params = [$username, $full_name, $email, $phone, $department, $role, $status];

        // إذا تم إدخال كلمة مرور جديدة
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $params[] = $password;
        }

        $sql .= " WHERE id = ?";
        $params[] = $employee_id;

        $stmt = $conn->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['success'] = __('employee_updated');
            header('Location: employees.php');
            exit;
        } else {
            $_SESSION['error'] = __('error_updating_employee');
        }
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('edit_employee'); ?> - <?php echo __('admin_panel'); ?></title>
    
    <!-- Bootstrap CSS -->
    <?php if ($dir === 'rtl'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="container-fluid py-4">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-12 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                <div>
                    <h1 class="h2 mb-0"><?php echo __('edit_employee'); ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 mt-2">
                            <li class="breadcrumb-item"><a href="index.php"><?php echo __('dashboard'); ?></a></li>
                            <li class="breadcrumb-item"><a href="employees.php"><?php echo __('employees'); ?></a></li>
                            <li class="breadcrumb-item active"><?php echo __('edit_employee'); ?></li>
                        </ol>
                    </nav>
                </div>
                <a href="employees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-<?php echo $dir === 'rtl' ? 'left' : 'right'; ?> <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                    <?php echo __('back'); ?>
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4 mb-md-0">
                            <div class="employee-avatar mb-3">
                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary mx-auto">
                                    <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
                                </div>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($employee['full_name']); ?></h5>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($employee['username']); ?></p>
                            <div class="d-flex justify-content-center gap-2">
                                <span class="badge <?php echo $employee['role'] === 'مدير_عام' ? 'bg-primary' : 'bg-info'; ?>">
                                    <i class="fas <?php echo $employee['role'] === 'مدير_عام' ? 'fa-user-tie' : 'fa-user'; ?> <?php echo $dir === 'rtl' ? 'ms-1' : 'me-1'; ?>"></i>
                                    <?php echo $employee['role'] === 'مدير_عام' ? __('general_manager') : __('employee'); ?>
                                </span>
                                <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <i class="fas <?php echo $employee['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?> <?php echo $dir === 'rtl' ? 'ms-1' : 'me-1'; ?>"></i>
                                    <?php echo $employee['status'] === 'active' ? __('status_active') : __('status_inactive'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('username'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('new_password'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="password" placeholder="<?php echo __('leave_empty_password'); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('full_name'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('email'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('phone'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('department'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                            <select class="form-select" name="department" required>
                                                <option value="management" <?php echo $employee['department'] === 'management' ? 'selected' : ''; ?>><?php echo __('management_dept'); ?></option>
                                                <option value="accounting" <?php echo $employee['department'] === 'accounting' ? 'selected' : ''; ?>><?php echo __('accounting_dept'); ?></option>
                                                <option value="drivers_supervisor" <?php echo $employee['department'] === 'drivers_supervisor' ? 'selected' : ''; ?>><?php echo __('drivers_supervisor_dept'); ?></option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('role'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                            <select class="form-select" name="role" required 
                                                <?php echo ($_SESSION['admin_role'] !== 'super_admin' && 
                                                          ($employee['role'] === 'مدير_عام' || 
                                                           $employee['role'] === 'super_admin')) ? 'disabled' : ''; ?>>
                                                <option value="موظف" <?php echo $employee['role'] === 'موظف' ? 'selected' : ''; ?>><?php echo __('employee'); ?></option>
                                                <?php if ($_SESSION['admin_role'] === 'مدير_عام' || $_SESSION['admin_role'] === 'super_admin'): ?>
                                                <option value="مدير_عام" <?php echo $employee['role'] === 'مدير_عام' ? 'selected' : ''; ?>><?php echo __('general_manager'); ?></option>
                                                <?php endif; ?>
                                                <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                                <option value="super_admin" <?php echo $employee['role'] === 'super_admin' ? 'selected' : ''; ?>><?php echo __('system_admin'); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <?php if ($_SESSION['admin_role'] !== 'super_admin' && 
                                                ($employee['role'] === 'مدير_عام' || 
                                                 $employee['role'] === 'super_admin')): ?>
                                            <input type="hidden" name="role" value="<?php echo $employee['role']; ?>">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('status'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                            <select class="form-select" name="status" required 
                                                <?php echo ($_SESSION['admin_role'] !== 'super_admin' && 
                                                          ($employee['role'] === 'مدير_عام' || 
                                                           $employee['role'] === 'super_admin')) ? 'disabled' : ''; ?>>
                                                <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>><?php echo __('status_active'); ?></option>
                                                <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>><?php echo __('status_inactive'); ?></option>
                                            </select>
                                        </div>
                                        <?php if ($_SESSION['admin_role'] !== 'super_admin' && 
                                                ($employee['role'] === 'مدير_عام' || 
                                                 $employee['role'] === 'super_admin')): ?>
                                            <input type="hidden" name="status" value="<?php echo $employee['status']; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="employees.php" class="btn btn-secondary">
                                        <i class="fas fa-times <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('cancel'); ?>
                                    </a>
                                    <button type="submit" name="update_employee" class="btn btn-primary">
                                        <i class="fas fa-save <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('save'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
}

.employee-avatar {
    position: relative;
    display: inline-block;
}

.employee-avatar::after {
    content: '';
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: <?php echo $employee['status'] === 'active' ? 'var(--bs-success)' : 'var(--bs-danger)'; ?>;
    border: 2px solid white;
}
</style>

<script>
// تفعيل التحقق من صحة النموذج
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php include '../includes/footer.php'; ?>