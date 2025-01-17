<?php
require_once '../config.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Check if user is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$success_msg = '';
$error_msg = '';

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "الرجاء ملء جميع الحقول";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "كلمة المرور الجديدة غير متطابقة";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل";
    } else {
        try {
            // Get admin's current password
            $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($current_password, $admin['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $admin_id]);
                
                $success_msg = "تم تحديث كلمة المرور بنجاح";
            } else {
                $error_msg = "كلمة المرور الحالية غير صحيحة";
            }
        } catch (PDOException $e) {
            $error_msg = "حدث خطأ في النظام";
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">تعديل كلمة المرور</h1>
            </div>

            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">كلمة المرور الحالية</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.card {
    border-radius: 8px;
    border: none;
}

.card-body {
    padding: 2rem;
}

.input-group-text {
    background-color: #f8f9fa;
    border-start-start-radius: 8px;
    border-end-start-radius: 8px;
}

.form-control {
    border-start-end-radius: 8px;
    border-end-end-radius: 8px;
}

.btn-primary {
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
}

.alert {
    border-radius: 8px;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}
</style>

<script>
// Form validation
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