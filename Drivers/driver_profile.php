<?php
require_once 'config.php';
require_once 'driver_auth.php';
require_once 'functions.php';

// Check if driver is logged in
if (!isDriverLoggedIn()) {
    header('Location: driver_login.php');
    exit;
}

// Get current driver info
$driver = getCurrentDriver();
if (!$driver) {
    logoutDriver();
    header('Location: driver_login.php');
    exit;
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $age = trim($_POST['age']);
    $about = trim($_POST['about']);
    $id_number = trim($_POST['id_number']);
    $license_number = trim($_POST['license_number']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $plate_number = trim($_POST['plate_number']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($phone)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني ��ير صالح';
    } else {
        try {
            $conn->beginTransaction();
            
            // Handle profile image upload
            $profile_image = $driver['profile_image'];
            if (!empty($_FILES['profile_image']['name'])) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
                    $error = 'نوع الملف غير مدعوم. يرجى استخدام JPG, PNG, or GIF';
                } elseif ($_FILES['profile_image']['size'] > $max_size) {
                    $error = 'حجم الملف كبير جداً. الحد الأقصى هو 5MB';
                } else {
                    $profile_image = uniqid() . '_' . $_FILES['profile_image']['name'];
                    $upload_path = 'uploads/driver/' . $profile_image;
                    
                    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $error = 'فشل في رفع الصورة';
                    }
                }
            }
            
            if (empty($error)) {
                // Update driver info
                $stmt = $conn->prepare("UPDATE drivers SET 
                    username = ?, email = ?, phone = ?, age = ?, about = ?,
                    id_number = ?, license_number = ?, vehicle_type = ?,
                    vehicle_model = ?, plate_number = ?, profile_image = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $username, $email, $phone, $age, $about,
                    $id_number, $license_number, $vehicle_type,
                    $vehicle_model, $plate_number, $profile_image,
                    $driver['id']
                ]);
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE id = ?");
                    $stmt->execute([
                        password_hash($_POST['password'], PASSWORD_DEFAULT),
                        $driver['id']
                    ]);
                }
                
                $conn->commit();
                $success = 'تم تحديث البيانات بنجاح';
                
                // Refresh driver info
                $driver = getCurrentDriver();
            } else {
                $conn->rollBack();
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'حدث خطأ ��ثناء تحديث البيانات';
            error_log("Error updating driver profile: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($driver['username'] ?? ''); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }

        .navbar {
            background: white;
            box-shadow: var(--box-shadow);
        }

        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }

        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 4px solid white;
            box-shadow: var(--box-shadow);
        }

        .profile-image-upload {
            position: relative;
            display: inline-block;
        }

        .profile-image-upload .upload-button {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-image-upload .upload-button:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="driver_dashboard.php">
                <i class="fas fa-car me-2"></i>
                نظام إدارة التوصيل
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="driver_dashboard.php">
                            <i class="fas fa-home me-1"></i>
                            الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_orders.php">
                            <i class="fas fa-box me-1"></i>
                            الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="driver_earnings.php">
                            <i class="fas fa-dollar-sign me-1"></i>
                            الأرباح
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <img src="<?php echo isset($driver['profile_image']) && $driver['profile_image'] ? 'uploads/driver/' . htmlspecialchars($driver['profile_image']) : 'assets/img/default-avatar.png'; ?>" 
                                 class="rounded-circle me-2" 
                                 width="32" height="32" 
                                 alt="Profile">
                            <?php echo htmlspecialchars($driver['username'] ?? ''); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item active" href="driver_profile.php">
                                    <i class="fas fa-user me-2"></i>
                                    الملف الشخصي
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="driver_logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="profile-card text-center">
                    <div class="profile-image-upload">
                        <img src="<?php echo isset($driver['profile_image']) && $driver['profile_image'] ? 'uploads/driver/' . htmlspecialchars($driver['profile_image']) : 'assets/img/default-avatar.png'; ?>" 
                             class="profile-image" 
                             alt="Profile Image" 
                             id="profileImage">
                        <label for="profileImageInput" class="upload-button">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <h4 class="mt-3"><?php echo htmlspecialchars($driver['username'] ?? ''); ?></h4>
                    <p class="text-muted">سائق</p>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>التقييم:</span>
                            <span class="text-warning">
                                <?php
                                $rating = $driver['rating'] ?? 0;
                                echo str_repeat('<i class="fas fa-star"></i>', floor($rating));
                                echo fmod($rating, 1) > 0 ? '<i class="fas fa-star-half-alt"></i>' : '';
                                echo str_repeat('<i class="far fa-star"></i>', 5 - ceil($rating));
                                echo " ($rating)";
                                ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>عدد الرحلات:</span>
                            <span><?php echo number_format($driver['total_trips'] ?? 0); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>تاريخ التسجيل:</span>
                            <span><?php echo isset($driver['created_at']) ? date('Y/m/d', strtotime($driver['created_at'])) : '-'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="profile-card">
                    <h4 class="mb-4">تعديل البيانات الشخصية</h4>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" id="profileImageInput" name="profile_image" class="d-none" accept="image/*">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم السائق <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($driver['username'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($driver['phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">العمر</label>
                                <input type="number" class="form-control" name="age" value="<?php echo htmlspecialchars($driver['age'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم الهوية</label>
                                <input type="text" class="form-control" name="id_number" value="<?php echo htmlspecialchars($driver['id_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">رقم رخصة لقيادة</label>
                                <input type="text" class="form-control" name="license_number" value="<?php echo htmlspecialchars($driver['license_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نوع المركبة</label>
                                <input type="text" class="form-control" name="vehicle_type" value="<?php echo htmlspecialchars($driver['vehicle_type'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">موديل المركبة</label>
                                <input type="text" class="form-control" name="vehicle_model" value="<?php echo htmlspecialchars($driver['vehicle_model'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">لوحة المركبة</label>
                                <input type="text" class="form-control" name="plate_number" value="<?php echo htmlspecialchars($driver['plate_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">نبذة عن السائق</label>
                                <textarea class="form-control" name="about" rows="3"><?php echo htmlspecialchars($driver['about'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" name="password">
                                <small class="text-muted">اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</small>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview uploaded image
        document.getElementById('profileImageInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
