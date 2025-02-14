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
        $_SESSION['success'] = __('employee_added');
    } else {
        $_SESSION['error'] = __('error_adding_employee');
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
        $_SESSION['error'] = __('cannot_delete_gm');
    } else {
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = __('employee_deleted');
        } else {
            $_SESSION['error'] = __('error_deleting_employee');
        }
    }
    header('Location: employees.php');
    exit;
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('employees_management'); ?> - <?php echo __('admin_panel'); ?></title>
    
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

<div class="container-fluid p-0">
    <div class="row g-0">
    
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0"><?php echo __('employees_management'); ?></h1>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-user-plus <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                    <?php echo __('add_new_employee'); ?>
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
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
                                <div class="<?php echo $dir === 'rtl' ? 'me-3' : 'ms-3'; ?>">
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('total_employees'); ?></h6>
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
                                <div class="<?php echo $dir === 'rtl' ? 'me-3' : 'ms-3'; ?>">
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('active_employees'); ?></h6>
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
                                <div class="<?php echo $dir === 'rtl' ? 'me-3' : 'ms-3'; ?>">
                                    <h6 class="card-subtitle text-muted mb-1"><?php echo __('inactive_employees'); ?></h6>
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
                    <h5 class="card-title mb-0"><?php echo __('employee_list'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th><?php echo __('employee_info'); ?></th>
                                    <th><?php echo __('contact_info'); ?></th>
                                    <th><?php echo __('department'); ?></th>
                                    <th><?php echo __('role'); ?></th>
                                    <th><?php echo __('status'); ?></th>
                                    <th class="text-center"><?php echo __('actions'); ?></th>
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
                                                <div class='avatar-circle bg-primary bg-opacity-10 text-primary " . ($dir === 'rtl' ? 'ms-3' : 'me-3') . "'>
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
                                                <div><i class='fas fa-envelope text-muted " . ($dir === 'rtl' ? 'ms-2' : 'me-2') . "'></i>{$employee['email']}</div>
                                                <div><i class='fas fa-phone text-muted " . ($dir === 'rtl' ? 'ms-2' : 'me-2') . "'></i>" . ($employee['phone'] ?: '-') . "</div>
                                            </div>
                                          </td>";
                                    echo "<td>
                                            <span class='badge bg-light text-dark'>
                                                <i class='fas fa-building " . ($dir === 'rtl' ? 'ms-1' : 'me-1') . "'></i>
                                                " . __($employee['department'] . '_dept') . "
                                            </span>
                                          </td>";
                                    echo "<td>
                                            <span class='badge " . ($employee['role'] === 'مدير_عام' ? 'bg-primary' : ($employee['role'] === 'super_admin' ? 'bg-dark' : 'bg-info')) . "'>
                                                <i class='fas " . ($employee['role'] === 'مدير_عام' ? 'fa-user-tie' : ($employee['role'] === 'super_admin' ? 'fa-user-shield' : 'fa-user')) . " " . ($dir === 'rtl' ? 'ms-1' : 'me-1') . "'></i>
                                                " . ($employee['role'] === 'مدير_عام' ? __('general_manager') : ($employee['role'] === 'super_admin' ? __('system_admin') : __('employee'))) . "
                                            </span>
                                          </td>";
                                    echo "<td>
                                            <span class='badge " . ($employee['status'] === 'active' ? 'bg-success' : 'bg-danger') . "'>
                                                <i class='fas " . ($employee['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle') . " " . ($dir === 'rtl' ? 'ms-1' : 'me-1') . "'></i>
                                                " . ($employee['status'] === 'active' ? __('status_active') : __('status_inactive')) . "
                                            </span>
                                          </td>";
                                    echo "<td class='text-center'>";
                                    if ($_SESSION['admin_id'] === $employee['id'] || 
                                        $_SESSION['admin_role'] === 'super_admin' || 
                                        $_SESSION['admin_role'] === 'مدير_عام') {
                                        echo "<div class='btn-group'>";
                                        echo "<a href='edit_employee.php?id={$employee['id']}' class='btn btn-sm btn-outline-primary' title='" . __('edit') . "'>
                                                <i class='fas fa-edit'></i>
                                              </a>";
                                        if (($_SESSION['admin_role'] === 'super_admin' && $employee['role'] !== 'super_admin') || 
                                            ($_SESSION['admin_role'] === 'مدير_عام' && $employee['role'] === 'موظف')) {
                                            echo "<button type='button' class='btn btn-sm btn-outline-danger' onclick='confirmDelete({$employee['id']})' title='" . __('delete') . "'>
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
                                            <p class='mb-0'>" . __('no_employees') . "</p>
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
                    <i class="fas fa-user-plus <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                    <?php echo __('add_new_employee'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('username'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('full_name'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                <input type="text" name="full_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('email'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('phone'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" name="phone" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('department'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-building"></i></span>
                                <select name="department" class="form-select" required>
                                    <option value="" disabled selected><?php echo __('select_department'); ?></option>
                                    <option value="management"><?php echo __('management_dept'); ?></option>
                                    <option value="accounting"><?php echo __('accounting_dept'); ?></option>
                                    <option value="drivers_supervisor"><?php echo __('drivers_supervisor_dept'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo __('role'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select name="role" class="form-select" required>
                                    <option value="" disabled selected><?php echo __('select_role'); ?></option>
                                    <option value="موظف"><?php echo __('employee'); ?></option>
                                    <?php if ($_SESSION['admin_role'] === 'مدير_عام'): ?>
                                    <option value="مدير_عام"><?php echo __('general_manager'); ?></option>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['admin_role'] === 'super_admin'): ?>
                                    <option value="مدير_عام"><?php echo __('general_manager'); ?></option>
                                    <option value="super_admin"><?php echo __('system_admin'); ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('cancel'); ?>
                    </button>
                    <button type="submit" name="add_employee" class="btn btn-primary">
                        <i class="fas fa-plus-circle <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('add_new_employee'); ?>
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
                <h5 class="modal-title"><?php echo __('confirm_delete'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                <p class="mb-0"><?php echo __('confirm_delete_employee'); ?></p>
                <p class="text-muted small mt-2"><?php echo __('delete_warning'); ?></p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" id="delete_employee_id" name="employee_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('cancel'); ?>
                    </button>
                    <button type="submit" name="delete_employee" class="btn btn-danger">
                        <i class="fas fa-trash-alt <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i><?php echo __('delete'); ?>
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