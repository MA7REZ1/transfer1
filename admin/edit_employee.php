<?php
require_once '../config.php';

// التحقق من تسجيل الدخول والصلاحيات
if (!isLoggedIn()) {
    header('Location: login.php');
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
    $_SESSION['error'] = "الموظف غير موجود";
    header('Location: employees.php');
    exit;
}

// التحقق من عدم تعديل بيانات المدير العام من قبل غير المدير العام نفسه
if ($employee['role'] === 'مدير_عام' && $_SESSION['user_id'] !== $employee['id']) {
    $_SESSION['error'] = "لا يمكن تعديل بيانات المدير العام";
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
        $_SESSION['error'] = "اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل";
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
            $_SESSION['success'] = "تم تحديث بيانات الموظف بنجاح";
            header('Location: employees.php');
            exit;
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث البيانات";
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                <div>
                    <h1 class="h2 mb-0">تعديل بيانات الموظف</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 mt-2">
                            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="employees.php">الموظفين</a></li>
                            <li class="breadcrumb-item active">تعديل موظف</li>
                        </ol>
                    </nav>
                </div>
                <a href="employees.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>
                    عودة للقائمة
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
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
                                    <i class="fas <?php echo $employee['role'] === 'مدير_عام' ? 'fa-user-tie' : 'fa-user'; ?> me-1"></i>
                                    <?php echo $employee['role'] === 'مدير_عام' ? 'مدير عام' : 'موظف'; ?>
                                </span>
                                <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <i class="fas <?php echo $employee['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                    <?php echo $employee['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">اسم المستخدم</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label">كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" name="password" placeholder="اتركها فارغة إذا لم ترد تغييرها">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الاسم الكامل</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">رقم الهاتف</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">القسم</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                                            <select class="form-select" name="department" required>
                                                <option value="إدارة" <?php echo $employee['department'] === 'إدارة' ? 'selected' : ''; ?>>إدارة</option>
                                                <option value="محاسبة" <?php echo $employee['department'] === 'محاسبة' ? 'selected' : ''; ?>>محاسبة</option>
                                                <option value="خدمة عملاء" <?php echo $employee['department'] === 'خدمة عملاء' ? 'selected' : ''; ?>>خدمة عملاء</option>
                                                <option value="تسويق" <?php echo $employee['department'] === 'تسويق' ? 'selected' : ''; ?>>تسويق</option>
                                                <option value="تقنية" <?php echo $employee['department'] === 'تقنية' ? 'selected' : ''; ?>>تقنية</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الدور</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                            <select class="form-select" name="role" required <?php echo $employee['role'] === 'مدير_عام' ? 'disabled' : ''; ?>>
                                                <option value="موظف" <?php echo $employee['role'] === 'موظف' ? 'selected' : ''; ?>>موظف</option>
                                                <option value="مدير_عام" <?php echo $employee['role'] === 'مدير_عام' ? 'selected' : ''; ?>>مدير عام</option>
                                            </select>
                                        </div>
                                        <?php if ($employee['role'] === 'مدير_عام'): ?>
                                            <input type="hidden" name="role" value="مدير_عام">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الحالة</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                            <select class="form-select" name="status" required <?php echo $employee['role'] === 'مدير_عام' ? 'disabled' : ''; ?>>
                                                <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                                <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                            </select>
                                        </div>
                                        <?php if ($employee['role'] === 'مدير_عام'): ?>
                                            <input type="hidden" name="status" value="active">
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="employees.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>إلغاء
                                    </a>
                                    <button type="submit" name="update_employee" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>حفظ التغييرات
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