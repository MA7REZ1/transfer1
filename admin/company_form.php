<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام') {
    header('Location: ../index.php');
    exit;
}

// Initialize variables
$company = [
    'id' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'commercial_record' => '',
    'tax_number' => '',
    'contact_person' => '',
    'contact_phone' => '',
    'is_active' => 1,
    'logo' => ''
];
$errors = [];
$success = false;

// If editing existing company
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $found_company = $stmt->fetch();
    
    if ($found_company) {
        $company = array_merge($company, $found_company);
    } else {
        header('Location: companies.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (empty($_POST['name'])) {
        $errors['name'] = 'اسم الشركة مطلوب';
    }
    if (empty($_POST['email'])) {
        $errors['email'] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'البريد الإلكتروني غير صالح';
    }
    if (empty($_POST['phone'])) {
        $errors['phone'] = 'رقم الهاتف مطلوب';
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Handle logo upload
            $logo_name = $company['logo']; // Keep existing logo by default
            if (!empty($_FILES['logo']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                    $errors['logo'] = 'نوع الملف غير مدعوم. يرجى استخدام JPG, PNG, or GIF';
                } elseif ($_FILES['logo']['size'] > $max_size) {
                    $errors['logo'] = 'حجم الملف كبير جداً. الحد الأقصى هو 5MB';
                } else {
                    $logo_name = uniqid() . '_' . $_FILES['logo']['name'];
                    $upload_path = 'uploads/companies/' . $logo_name;
                    
                    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                        $errors['logo'] = 'فشل في رفع الصورة';
                    }
                }
            }
            
            if (empty($errors)) {
                if ($company['id']) {
                    // Update existing company
                    $password = $_POST['name'] . '@123';
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("
                        UPDATE companies 
                        SET name = ?, email = ?, phone = ?, address = ?, 
                            commercial_record = ?, tax_number = ?, 
                            contact_person = ?, contact_phone = ?, 
                            is_active = ?, logo = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        $_POST['commercial_record'],
                        $_POST['tax_number'],
                        $_POST['contact_person'],
                        $_POST['contact_phone'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $logo_name,
                        $hashed_password,
                        $company['id']
                    ]);
                    
                    // Add notification with password info
                    $notification_msg = "تم تحديث بيانات الشركة: " . $_POST['name'] . "\n" . 
                                     "كلمة المرور الجديدة: " . $password;
                    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'info', ?)");
                    $stmt->execute([$_SESSION['admin_id'], $notification_msg, "companies.php"]);
                    
                    // Show success message with new password
                    $_SESSION['success_msg'] = "تم تحديث بيانات الشركة بنجاح. كلمة المرور الجديدة هي: " . $password;
                } else {
                    // Insert new company
                    $password = $_POST['name'] . '@123';
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("
                        INSERT INTO companies (name, email, phone, address, 
                            commercial_record, tax_number, contact_person, 
                            contact_phone, is_active, logo, password, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['email'],
                        $_POST['phone'],
                        $_POST['address'],
                        $_POST['commercial_record'],
                        $_POST['tax_number'],
                        $_POST['contact_person'],
                        $_POST['contact_phone'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $logo_name,
                        $hashed_password
                    ]);
                    
                    // Add notification with password info
                    $notification_msg = "تم إضافة شركة جديدة: " . $_POST['name'] . "\n" . 
                                     "كلمة المرور: " . $password;
                    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'success', ?)");
                    $stmt->execute([$_SESSION['admin_id'], $notification_msg, "companies.php"]);

                    // Show success message with password
                    $_SESSION['success_msg'] = "تم إضافة الشركة بنجاح. كلمة المرور هي: " . $password;
                }
                
                $conn->commit();
                header('Location: companies.php');
                exit;
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errors['general'] = 'حدث خطأ أثناء حفظ البيانات';
        }
    }
    
    // If there are errors, update the company array with posted values
    if (!empty($errors)) {
        $company = array_merge($company, $_POST);
    }
}

// Include header after all possible redirects
require_once '../includes/header.php';
?>

<!-- Main Content -->
<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="companies.php">إدارة الشركات</a></li>
            <li class="breadcrumb-item active"><?php echo $company['id'] ? 'تعديل شركة' : 'إضافة شركة'; ?></li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0"><?php echo $company['id'] ? 'تعديل شركة' : 'إضافة شركة جديدة'; ?></h2>
    </div>

    <!-- Company Form -->
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php 
                    echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <!-- Company Logo -->
                <div class="col-md-12 mb-4 text-center">
                    <div class="logo-upload">
                        <img src="<?php echo !empty($company['logo']) ? 'uploads/companies/' . $company['logo'] : 'assets/img/company-placeholder.png'; ?>" 
                             alt="Company Logo" 
                             class="img-thumbnail mb-2" 
                             style="max-width: 200px;">
                        <div class="mt-2">
                            <label for="logo" class="btn btn-outline-primary">
                                <i class="fas fa-upload me-2"></i>تحميل شعار الشركة
                            </label>
                            <input type="file" id="logo" name="logo" class="d-none" accept="image/*">
                        </div>
                        <?php if (!empty($errors['logo'])): ?>
                            <div class="text-danger mt-2"><?php echo $errors['logo']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">اسم الشركة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" name="name" value="<?php echo htmlspecialchars($company['name']); ?>">
                        <?php if (!empty($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" 
                               id="email" name="email" value="<?php echo htmlspecialchars($company['email']); ?>">
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($errors['phone']) ? 'is-invalid' : ''; ?>" 
                               id="phone" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>">
                        <?php if (!empty($errors['phone'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group mb-3">
                        <label for="address" class="form-label">العنوان</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($company['address']); ?></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label for="commercial_record" class="form-label">السجل التجاري</label>
                        <input type="text" class="form-control" id="commercial_record" name="commercial_record" 
                               value="<?php echo htmlspecialchars($company['commercial_record']); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="tax_number" class="form-label">الرقم الضريبي</label>
                        <input type="text" class="form-control" id="tax_number" name="tax_number" 
                               value="<?php echo htmlspecialchars($company['tax_number']); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="contact_person" class="form-label">الشخص المسؤول</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                               value="<?php echo htmlspecialchars($company['contact_person']); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label for="contact_phone" class="form-label">هاتف المسؤول</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?php echo htmlspecialchars($company['contact_phone']); ?>">
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                               <?php echo $company['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">تفعيل الشركة</label>
                    </div>
                </div>

                <div class="col-12">
                    <hr>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="companies.php" class="btn btn-secondary">إلغاء</a>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Preview uploaded image
document.getElementById('logo').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.querySelector('.logo-upload img').src = e.target.result;
        }
        reader.readAsDataURL(e.target.files[0]);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 