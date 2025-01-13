<?php
require_once '../config.php';

// التحقق من تسجيل الدخول والصلاحيات
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// إضافة موظف جديد
if (isset($_POST['add_employee'])) {
    $username = sanitizeInput($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $department = sanitizeInput($_POST['department']);
    $role = sanitizeInput($_POST['role']);

    $stmt = $conn->prepare("INSERT INTO employees (username, password, full_name, email, phone, department, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$username, $password, $full_name, $email, $phone, $department, $role])) {
        $_SESSION['success'] = "تم إضافة الموظف بنجاح";
    } else {
        $_SESSION['error'] = "حدث خطأ أثناء إضافة الموظف";
    }
    header('Location: employees.php');
    exit;
}

// حذف موظف
if (isset($_POST['delete_employee'])) {
    $id = intval($_POST['employee_id']);
    
    // التأكد من عدم حذف المدير العام
    $stmt = $conn->prepare("SELECT role FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if ($employee && $employee['role'] === 'مدير_عام') {
        $_SESSION['error'] = "لا يمكن حذف المدير العام";
    } else {
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "تم حذف الموظف بنجاح";
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء حذف الموظف";
        }
    }
    header('Location: employees.php');
    exit;
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                <div>
                    <h1 class="h2 mb-0">إدارة الموظفين</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 mt-2">
                            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                            <li class="breadcrumb-item active">الموظفين</li>
                        </ol>
                    </nav>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-user-plus me-2"></i>
                    إضافة موظف جديد
                </button>
            </div>

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

            <!-- إحصائيات سريعة -->
            <div class="row g-3 mb-4">
                <?php
                // إجمالي الموظفين
                $stmt = $conn->query("SELECT COUNT(*) as total FROM employees");
                $total = $stmt->fetch()['total'];
                
                // الموظفين النشطين
                $stmt = $conn->query("SELECT COUNT(*) as active FROM employees WHERE status = 'active'");
                $active = $stmt->fetch()['active'];
                
                // الموظفين غير النشطين
                $stmt = $conn->query("SELECT COUNT(*) as inactive FROM employees WHERE status = 'inactive'");
                $inactive = $stmt->fetch()['inactive'];
                ?>
                
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle text-muted mb-1">إجمالي الموظفين</h6>
                                    <h2 class="card-title mb-0"><?php echo $total; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stats-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                        <i class="fas fa-user-check fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle text-muted mb-1">الموظفين النشطين</h6>
                                    <h2 class="card-title mb-0"><?php echo $active; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="stats-icon bg-danger bg-opacity-10 text-danger rounded-3 p-3">
                                        <i class="fas fa-user-times fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="card-subtitle text-muted mb-1">الموظفين غير النشطين</h6>
                                    <h2 class="card-title mb-0"><?php echo $inactive; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول الموظفين -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent py-3">
                    <h5 class="card-title mb-0">قائمة الموظفين</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>الموظف</th>
                                    <th>معلومات الاتصال</th>
                                    <th>القسم</th>
                                    <th>الدور</th>
                                    <th>الحالة</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $conn->query("SELECT * FROM employees ORDER BY role DESC, id ASC");
                                while ($employee = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td class='text-center'>{$employee['id']}</td>";
                                    echo "<td>
                                            <div class='d-flex align-items-center'>
                                                <div class='avatar-circle bg-primary bg-opacity-10 text-primary me-3'>
                                                    " . strtoupper(substr($employee['full_name'], 0, 1)) . "
                                                </div>
                                                <div>
                                                    <h6 class='mb-0'>{$employee['full_name']}</h6>
                                                    <small class='text-muted'>{$employee['username']}</small>
                                                </div>
                                            </div>
                                          </td>";
                                    echo "<td>
                                            <div>
                                                <div><i class='fas fa-envelope text-muted me-2'></i>{$employee['email']}</div>
                                                <div><i class='fas fa-phone text-muted me-2'></i>" . ($employee['phone'] ?: '-') . "</div>
                                            </div>
                                          </td>";
                                    echo "<td>
                                            <span class='badge bg-light text-dark'>
                                                <i class='fas fa-building me-1'></i>
                                                {$employee['department']}
                                            </span>
                                          </td>";
                                    echo "<td>
                                            <span class='badge " . ($employee['role'] === 'مدير_عام' ? 'bg-primary' : 'bg-info') . "'>
                                                <i class='fas " . ($employee['role'] === 'مدير_عام' ? 'fa-user-tie' : 'fa-user') . " me-1'></i>
                                                " . ($employee['role'] === 'مدير_عام' ? 'مدير عام' : 'موظف') . "
                                            </span>
                                          </td>";
                                    echo "<td>
                                            <span class='badge " . ($employee['status'] === 'active' ? 'bg-success' : 'bg-danger') . "'>
                                                <i class='fas " . ($employee['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle') . " me-1'></i>
                                                " . ($employee['status'] === 'active' ? 'نشط' : 'غير نشط') . "
                                            </span>
                                          </td>";
                                    echo "<td class='text-center'>";
                                    if ($employee['role'] !== 'مدير_عام' || $_SESSION['user_id'] === $employee['id']) {
                                        echo "<div class='btn-group'>";
                                        echo "<a href='edit_employee.php?id={$employee['id']}' class='btn btn-sm btn-outline-primary' title='تعديل'>
                                                <i class='fas fa-edit'></i>
                                              </a>";
                                        if ($employee['role'] !== 'مدير_عام') {
                                            echo "<button type='button' class='btn btn-sm btn-outline-danger' onclick='confirmDelete({$employee['id']})' title='حذف'>
                                                    <i class='fas fa-trash'></i>
                                                  </button>";
                                        }
                                        echo "</div>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                if ($stmt->rowCount() === 0) {
                                    echo "<tr><td colspan='7' class='text-center py-4 text-muted'>
                                            <i class='fas fa-inbox fa-3x mb-3'></i>
                                            <p class='mb-0'>لا يوجد موظفين حالياً</p>
                                          </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- نافذة إضافة موظف جديد -->
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
                                    <option value="" disabled selected>اختر القسم</option>
                                    <option value="إدارة">إدارة</option>
                                    <option value="محاسبة">محاسبة</option>
                                    <option value="خدمة عملاء">خدمة عملاء</option>
                                    <option value="تسويق">تسويق</option>
                                    <option value="تقنية">تقنية</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدور الوظيفي</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select name="role" class="form-select" required>
                                    <option value="" disabled selected>اختر الدور</option>
                                    <option value="موظف">موظف</option>
                                    <?php if ($_SESSION['admin_role'] === 'مدير_عام'): ?>
                                    <option value="مدير_عام">مدير عام</option>
                                    <?php endif; ?>
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

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                <p class="mb-0">هل أنت متأكد من حذف هذا الموظف؟</p>
                <p class="text-muted small mt-2">لا يمكن التراجع عن هذا الإجراء</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" id="delete_employee_id" name="employee_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="submit" name="delete_employee" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-2"></i>حذف
                    </button>
                </form>
            </div>
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

.stats-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.table th {
    font-weight: 600;
}

.badge {
    padding: 0.5rem 0.75rem;
}
</style>

<script>
// دالة تأكيد الحذف
function confirmDelete(employeeId) {
    document.getElementById('delete_employee_id').value = employeeId;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
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