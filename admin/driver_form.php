<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
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
                // Update existing driver
                $sql = "UPDATE drivers SET 
                    username = ?, email = ?, phone = ?, age = ?, 
                    about = ?, address = ?, id_number = ?, 
                    license_number = ?, vehicle_type = ?,
                    vehicle_model = ?, plate_number = ?, is_active = ?,
                    current_status = ?";
                
                $params = [
                    $username, $email, $phone, $age,
                    $about, $address, $id_number,
                    $license_number, $vehicle_type,
                    $vehicle_model, $plate_number, $is_active,
                    $current_status
                ];

                // Add password to update if provided
                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                
                // Handle profile image upload for existing driver
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['profile_image']['name'];
                    $file_tmp = $_FILES['profile_image']['tmp_name'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $new_file_name = uniqid() . '_' . $file_name;
                    
                    if (move_uploaded_file($file_tmp, "uploads/profiles/" . $new_file_name)) {
                        $stmt = $conn->prepare("UPDATE drivers SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$new_file_name, $id]);
                    }
                }
                
                // Add notification
                $stmt = $conn->prepare("INSERT INTO notifications (admin_id, message, type, link) VALUES (?, ?, 'info', ?)");
                $stmt->execute([$_SESSION['admin_id'], "تم تحديث بيانات السائق: $username", "drivers.php"]);
                
                $success = 'تم تحديث بيانات السائق بنجاح';
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
            $error = 'حدث خطأ أثناء حفظ البيانات';
        }
    }
}

// Include header after all possible redirects
require_once 'includes/header.php';
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
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">اسم السائق <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($driver['username']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($driver['email']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo $id > 0 ? 'كلمة المرور الجديدة' : 'كلمة المرور'; ?> <?php echo $id > 0 ? '' : '<span class="text-danger">*</span>'; ?></label>
                    <input type="password" class="form-control" name="password" <?php echo $id > 0 ? '' : 'required'; ?>>
                    <?php if ($id > 0): ?>
                        <small class="text-muted">اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">العمر</label>
                    <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($driver['age']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم الهوية</label>
                    <input type="text" class="form-control" name="id_number" value="<?php echo htmlspecialchars($driver['id_number']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم رخصة القيادة</label>
                    <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($driver['license_number']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع المركبة</label>
                    <input type="text" class="form-control" name="vehicle_type" value="<?php echo htmlspecialchars($driver['vehicle_type']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">موديل المركبة</label>
                    <input type="text" class="form-control" name="vehicle_model" value="<?php echo htmlspecialchars($driver['vehicle_model']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">لوحة المركبة</label>
                    <input type="text" class="form-control" name="plate_number" value="<?php echo htmlspecialchars($driver['plate_number']); ?>">
                </div>
                
                <?php if ($id == 0): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الصورة الشخصية</label>
                        <input type="file" class="form-control" name="profile_image" accept="image/*">
                    </div>
                <?php endif; ?>

                <?php if ($id > 0): ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">التقييم</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($driver['rating']); ?>" readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">عدد الرحلات</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($driver['total_trips']); ?>" readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الطلبات المكتملة</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($driver['completed_orders']); ?>" readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الطلبات الملغاة</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($driver['cancelled_orders']); ?>" readonly>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">إجمالي الأرباح</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($driver['total_earnings']); ?>" readonly>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">نبذة عن السائق</label>
                    <textarea class="form-control" name="about" rows="3"><?php echo htmlspecialchars($driver['about']); ?></textarea>
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label">العنوان</label>
                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($driver['address']); ?></textarea>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">الحالة</label>
                    <select class="form-control" name="current_status">
                        <option value="offline" <?php echo $driver['current_status'] == 'offline' ? 'selected' : ''; ?>>غير متصل</option>
                        <option value="available" <?php echo $driver['current_status'] == 'available' ? 'selected' : ''; ?>>متاح</option>
                        <option value="busy" <?php echo $driver['current_status'] == 'busy' ? 'selected' : ''; ?>>مشغول</option>
                    </select>
                </div>
                
                <div class="col-md-12 mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $driver['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">السائق نشط</label>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> حفظ
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 