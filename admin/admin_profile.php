<?php
require_once '../config.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Check if user is super_admin
if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Location: profile.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$success_msg = '';
$error_msg = '';

// Get admin information
try {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch();
} catch (PDOException $e) {
    $error_msg = "حدث خطأ في جلب البيانات";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';

        try {
            $stmt = $conn->prepare("UPDATE admins SET email = ?, username = ? WHERE id = ?");
            $stmt->execute([$email, $username, $admin_id]);
            $success_msg = "تم تحديث الملف الشخصي بنجاح";
            
            // Refresh admin data
            $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->execute([$admin_id]);
            $admin_data = $stmt->fetch();
        } catch (PDOException $e) {
            $error_msg = "حدث خطأ في تحديث البيانات";
        }
    }
    // Handle password update
    elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_msg = "الرجاء ملء جميع حقول كلمة المرور";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "كلمة المرور الجديدة غير متطابقة";
        } elseif (strlen($new_password) < 6) {
            $error_msg = "كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل";
        } else {
            try {
                if (password_verify($current_password, $admin_data['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $admin_id]);
                    $success_msg = "تم تحديث كلمة المرور بنجاح";
                } else {
                    $error_msg = "كلمة المرور الحالية غير صحيحة";
                }
            } catch (PDOException $e) {
                $error_msg = "حدث خطأ في تحديث كلمة المرور";
            }
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
                <h1 class="h3 mb-0">الملف الشخصي</h1>
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

            <div class="row">
                <!-- Profile Information Card -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>المعلومات الشخصية</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-12 text-center mb-4">
                                        <div class="profile-image-container">
                                            <img src="../assets/images/default-avatar.png" class="profile-image" alt="صورة الملف الشخصي">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">اسم المستخدم</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin_data['username'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">الدور</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['role'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">آخر تسجيل دخول</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['last_login'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">تاريخ الإنشاء</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['created_at'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>حفظ التغييرات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-transparent py-3">
                            <h5 class="mb-0"><i class="fas fa-lock me-2"></i>تغيير كلمة المرور</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الحالية</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الجديدة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_password" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>تحديث كلمة المرور
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
.card {
    border-radius: 12px;
    border: none;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.card-body {
    padding: 2rem;
}

.profile-image-container {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    border-radius: 50%;
    overflow: hidden;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.input-group-text {
    background-color: #f8f9fa;
    border-start-start-radius: 8px;
    border-end-start-radius: 8px;
    width: 40px;
    justify-content: center;
}

.form-control {
    border-start-end-radius: 8px;
    border-end-end-radius: 8px;
    padding: 0.6rem 1rem;
}

.btn-primary {
    border-radius: 8px;
    padding: 0.6rem 1.5rem;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.alert {
    border-radius: 8px;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #495057;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }
    
    .profile-image-container {
        width: 120px;
        height: 120px;
    }
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