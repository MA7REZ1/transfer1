<?php
require_once '../config.php';

// Get current language direction
$dir = $_SESSION['lang'] === 'ar' ? 'rtl' : 'ltr';
$lang = $_SESSION['lang'];

// Include language file
require_once '../includes/languages.php';

if (!isLoggedIn()) {
    header('Location: employee-login.php');
    exit;
}

$employee_id = $_SESSION['admin_id'];
$success_message = '';
$error_message = '';

// جلب بيانات الموظف
try {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = __('error_fetching_data');
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    try {
        if (!empty($current_password) && !empty($new_password)) {
            // التحقق من كلمة المرور الحالية
            if (password_verify($current_password, $employee['password'])) {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employees SET username = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $phone, $password_hash, $employee_id]);
            } else {
                $error_message = __('incorrect_current_password');
            }
        } else {
            $stmt = $conn->prepare("UPDATE employees SET username = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$username, $email, $phone, $employee_id]);
        }
        
        if (!$error_message) {
            $success_message = __('data_updated_successfully');
            // تحديث بيانات الجلسة
            $_SESSION['admin_username'] = $username;
        }
    } catch (PDOException $e) {
        $error_message = __('error_updating_data');
    }
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('profile'); ?> - <?php echo __('admin_panel'); ?></title>
    
    <!-- Bootstrap CSS -->
    <?php if ($dir === 'rtl'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <?php endif; ?>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><?php echo __('profile'); ?></h3>
                            <a href="financial.php" class="btn btn-light">
                                <i class="fas fa-arrow-<?php echo $dir === 'rtl' ? 'left' : 'right'; ?> <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                                <?php echo __('back'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label"><?php echo __('username'); ?></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($employee['username'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone'); ?></label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="current_password" class="form-label"><?php echo __('current_password'); ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                                <small class="text-muted"><?php echo __('leave_empty_password'); ?></small>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?php echo __('new_password'); ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save <?php echo $dir === 'rtl' ? 'ms-2' : 'me-2'; ?>"></i>
                                    <?php echo __('save_changes'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 