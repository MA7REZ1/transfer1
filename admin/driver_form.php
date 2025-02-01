<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'drivers_supervisor') {
    header('Location: ../index.php');
    exit;
}

// Initialize variables
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';
$driver = [
    'username' => '',
    'email' => '',
    'password' => '',
    'phone' => '',
    'age' => '',
    'about' => '',
    'address' => '',
    'profile_image' => '',
    'id_number' => '',
    'license_number' => '',
    'vehicle_type' => '',
    'vehicle_model' => '',
    'plate_number' => '',
    'is_active' => 1,
    'current_status' => 'offline',
    'rating' => 0,
    'total_trips' => 0,
    'completed_orders' => 0,
    'cancelled_orders' => 0,
    'total_earnings' => 0
];

// If editing, get driver data
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        header('Location: drivers.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $age = trim($_POST['age']);
    $about = trim($_POST['about']);
    $address = trim($_POST['address']);
    $id_number = trim($_POST['id_number']);
    $license_number = trim($_POST['license_number']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $plate_number = trim($_POST['plate_number']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $current_status = isset($_POST['current_status']) ? trim($_POST['current_status']) : 'offline';
    
    // Validate required fields
    if (empty($username) || empty($email) || empty($phone)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        try {
            if ($id > 0) {
                // Get current driver data
                $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $currentDriver = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Compare and build update query for changed fields only
                $updates = [];
                $params = [':id' => $id];
                
                // تحديث البيانات الأساسية فقط وتجاهل الأرباح والإحصائيات
                $fieldsToUpdate = [
                    'username' => $username,
                    'email' => $email,
                    'phone' => $phone,
                    'age' => $age,
                    'about' => $about,
                    'address' => $address,
                    'id_number' => $id_number,
                    'license_number' => $license_number,
                    'vehicle_type' => $vehicle_type,
                    'vehicle_model' => $vehicle_model,
                    'plate_number' => $plate_number,
                    'is_active' => $is_active,
                    'current_status' => $current_status
                ];

                foreach ($fieldsToUpdate as $field => $value) {
                    if ($value !== $currentDriver[$field]) {
                        $updates[] = "$field = :$field";
                        $params[":$field"] = $value;
                    }
                }

                // Add password to update if provided
                if (!empty($password)) {
                    $updates[] = "password = :password";
                    $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                // Only update if there are changes
                if (!empty($updates)) {
                    $sql = "UPDATE drivers SET " . implode(", ", $updates) . " WHERE id = :id";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);

                    // Add notification
                    $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (:admin_id, :message, 'info', :link)");
                    $stmt->execute([
                        ':admin_id' => $_SESSION['admin_id'],
                        ':message' => "تم تحديث بيانات السائق: $username",
                        ':link' => "drivers.php"
                    ]);
                    
                    $success = 'تم تحديث بيانات السائق بنجاح';
                } else {
                    $success = 'لم يتم إجراء أي تغييرات';
                }
            } else {
                // Add new driver
                $stmt = $conn->prepare("INSERT INTO drivers (
                    username, email, phone, age, about, address,
                    id_number, license_number, vehicle_type,
                    vehicle_model, plate_number, is_active,
                    current_status, password, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->execute([
                    $username, $email, $phone, $age, $about, $address,
                    $id_number, $license_number, $vehicle_type,
                    $vehicle_model, $plate_number, $is_active,
                    $current_status, password_hash($password, PASSWORD_DEFAULT)
                ]);
                
                $new_driver_id = $conn->lastInsertId();
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['profile_image']['name'];
                    $file_tmp = $_FILES['profile_image']['tmp_name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $new_file_name = uniqid() . '_' . $file_name;
                    
                    if (move_uploaded_file($file_tmp, "uploads/profiles/" . $new_file_name)) {
                        $stmt = $conn->prepare("UPDATE drivers SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$new_file_name, $new_driver_id]);
                    }
                }
                
                // Add notification
                $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'success', ?)");
                $stmt->execute([$_SESSION['admin_id'], "تم إضافة سائق جديد: $username", "drivers.php"]);
                
                header('Location: drivers.php');
                exit;
            }
        } catch (PDOException $e) {
            // تسجيل تفاصيل الخطأ في ملف السجل
            $errorDetails = [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            error_log("=== Database Error Details ===\n" . print_r($errorDetails, true));
            
            // تجاهل أخطاء التكرار وتحديث البيانات على أي حال
            if ($e->getCode() == 23000) {
                // محاولة التحديث مرة أخرى بدون التحقق من التكرار
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $success = 'تم تحديث بيانات السائق بنجاح';
            } else {
                $error = 'حدث خطأ في قاعدة البيانات. الرجاء المحاولة مرة أخرى';
            }
        }
    }
}

// Include header after all possible redirects
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?php echo $id > 0 ? 'تعديل بيانات السائق' : 'إضافة سائق جديد'; ?></h1>
    <a href="drivers.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> عودة
    </a>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div>
                    <?php echo $error; ?>
                    <?php if (strpos($error, 'الدعم الفني') !== false): ?>
                        <br>
                        <small class="text-muted">رقم الخطأ: <?php echo time(); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?php echo $success; ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- المعلومات الأساسية -->
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary bg-gradient text-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-circle me-2"></i>
                                المعلومات الأساسية
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">اسم السائق <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($driver['username']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($driver['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold"><?php echo $id > 0 ? 'كلمة المرور الجديدة' : 'كلمة المرور'; ?> <?php echo $id > 0 ? '' : '<span class="text-danger">*</span>'; ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" <?php echo $id > 0 ? '' : 'required'; ?>>
                                    </div>
                                    <?php if ($id > 0): ?>
                                        <small class="text-muted"><i class="fas fa-info-circle"></i> اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">رقم الهاتف <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات الهوية -->
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info bg-gradient text-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-id-card me-2"></i>
                                معلومات الهوية
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">العمر</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-birthday-cake"></i></span>
                                        <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($driver['age']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">رقم الهوية</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                        <input type="text" class="form-control" name="id_number" value="<?php echo htmlspecialchars($driver['id_number']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">رقم رخصة القيادة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-card-alt"></i></span>
                                        <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($driver['license_number']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات المركبة -->
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success bg-gradient text-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-car me-2"></i>
                                معلومات المركبة
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">نوع المركبة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                        <input type="text" class="form-control" name="vehicle_type" value="<?php echo htmlspecialchars($driver['vehicle_type']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">موديل المركبة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-car-side"></i></span>
                                        <input type="text" class="form-control" name="vehicle_model" value="<?php echo htmlspecialchars($driver['vehicle_model']); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">لوحة المركبة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-digital-tachograph"></i></span>
                                        <input type="text" class="form-control" name="plate_number" value="<?php echo htmlspecialchars($driver['plate_number']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات إضافية -->
                <div class="col-md-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-secondary bg-gradient text-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                معلومات إضافية
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">نبذة عن السائق</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-comment-alt"></i></span>
                                        <textarea class="form-control" name="about" rows="3"><?php echo htmlspecialchars($driver['about']); ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold">العنوان</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($driver['address']); ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">الحالة</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                        <select class="form-select" name="current_status">
                                            <option value="offline" <?php echo $driver['current_status'] == 'offline' ? 'selected' : ''; ?>>غير متصل</option>
                                            <option value="available" <?php echo $driver['current_status'] == 'available' ? 'selected' : ''; ?>>متاح</option>
                                            <option value="busy" <?php echo $driver['current_status'] == 'busy' ? 'selected' : ''; ?>>مشغول</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">تفعيل الحساب</label>
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $driver['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">السائق نشط</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save me-2"></i> حفظ البيانات
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 