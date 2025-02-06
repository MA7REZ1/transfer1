<?php
require_once '../config.php';

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

// إضافة موظف جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $username = sanitizeInput($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $department = sanitizeInput($_POST['department']);

    try {
        $stmt = $conn->prepare("INSERT INTO employees (username, password, full_name, email, phone, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $full_name, $email, $phone, $department]);
        $_SESSION['success'] = "تم إضافة الموظف بنجاح";
    } catch(PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء إضافة الموظف: " . $e->getMessage();
    }
}

// حذف موظف
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ? AND username != 'admin'");
        $stmt->execute([$id]);
        $_SESSION['success'] = "تم حذف الموظف بنجاح";
    } catch(PDOException $e) {
        $_SESSION['error'] = "حدث خطأ أثناء حذف الموظف";
    }
}

// جلب قائمة الموظفين
$stmt = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
$employees = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-users me-2"></i>
                        إدارة الموظفين
                    </h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-plus-circle me-2"></i>
                        إضافة موظف جديد
                    </button>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>الموظف</th>
                                    <th>معلومات الاتصال</th>
                                    <th>القسم</th>
                                    <th>الحالة</th>
                                    <th>تاريخ التعيين</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td class="text-center"><?php echo $employee['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                                                <?php echo strtoupper(substr($employee['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($employee['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($employee['username']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div><i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($employee['email']); ?></div>
                                            <div><i class="fas fa-phone text-muted me-2"></i><?php echo htmlspecialchars($employee['phone'] ?: '-'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo htmlspecialchars($employee['department']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $employee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                            <i class="fas <?php echo $employee['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                            <?php echo $employee['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar-alt text-muted me-2"></i>
                                        <?php echo date('Y/m/d', strtotime($employee['created_at'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($employee['username'] !== 'admin'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $employee['id']; ?>)"
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">لا يوجد موظفين حالياً</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة موظف -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>
                    إضافة موظف جديد
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم المستخدم</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة المرور</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الاسم الكامل</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">القسم</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select name="department" class="form-select" required>
                                    <option value="management">ادارة</option>
                                    <option value="accounting">محاسبة</option>
                                    <option value="drivers_supervisor">مشرف السواقين</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="submit" name="add_employee" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>إضافة موظف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>

<script>
function confirmDelete(employeeId) {
    if (confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
        window.location.href = `?delete=${employeeId}`;
    }
}

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