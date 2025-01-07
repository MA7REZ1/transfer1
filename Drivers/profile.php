<?php
require_once '../config.php';

// التحقق من تسجيل دخول السائق
if (!isset($_SESSION['driver_id']) || empty($_SESSION['driver_id'])) {
    header('Location: login.php');
    exit;
}

$driver_id = (int)$_SESSION['driver_id'];
if ($driver_id <= 0) {
    header('Location: login.php');
    exit;
}

// جلب بيانات السائق
$driver = [];
try {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch();
} catch (PDOException $e) {
    // يمكن إضافة معالجة الأخطاء هنا
}

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $vehicle_type = filter_var($_POST['vehicle_type'], FILTER_SANITIZE_STRING);
    $vehicle_model = filter_var($_POST['vehicle_model'], FILTER_SANITIZE_STRING);
    $vehicle_plate = filter_var($_POST['vehicle_plate'], FILTER_SANITIZE_STRING);

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "البريد الإلكتروني غير صالح";
    } else {
        try {
            // تحديث البيانات الشخصية
            $stmt = $conn->prepare("
                UPDATE drivers 
                SET username = ?, email = ?, phone = ?, address = ?,
                    vehicle_type = ?, vehicle_model = ?, vehicle_plate = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([
                $username, $email, $phone, $address,
                $vehicle_type, $vehicle_model, $vehicle_plate,
                $driver_id
            ])) {
                $_SESSION['success'] = "تم تحديث الملف الشخصي بنجاح";
                
                // تحديث بيانات السائق في المتغير
                $driver['username'] = $username;
                $driver['email'] = $email;
                $driver['phone'] = $phone;
                $driver['address'] = $address;
                $driver['vehicle_type'] = $vehicle_type;
                $driver['vehicle_model'] = $vehicle_model;
                $driver['vehicle_plate'] = $vehicle_plate;
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث البيانات";
        }
    }
}

// معالجة تحديث كلمة المرور
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "كلمة المرور الجديدة غير متطابقة";
    } else {
        try {
            // التحقق من كلمة المرور الحالية
            $stmt = $conn->prepare("SELECT password FROM drivers WHERE id = ?");
            $stmt->execute([$driver_id]);
            $current_hash = $stmt->fetchColumn();

            if (password_verify($current_password, $current_hash)) {
                // تحديث كلمة المرور
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE drivers SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$new_hash, $driver_id])) {
                    $_SESSION['success'] = "تم تحديث كلمة المرور بنجاح";
                }
            } else {
                $_SESSION['error'] = "كلمة المرور الحالية غير صحيحة";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث كلمة المرور";
        }
    }
}

// معالجة تحديث الصورة الشخصية
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
        $_SESSION['error'] = "نوع الملف غير مسموح به. يرجى اختيار صورة (JPG, PNG, GIF)";
    } elseif ($_FILES['profile_image']['size'] > $max_size) {
        $_SESSION['error'] = "حجم الصورة كبير جداً. الحد الأقصى هو 5 ميجابايت";
    } else {
        $upload_dir = 'uploads/driver/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $driver_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            // تحديث اسم الصورة في قاعدة البيانات
            try {
                $stmt = $conn->prepare("UPDATE drivers SET profile_image = ? WHERE id = ?");
                if ($stmt->execute([$new_filename, $driver_id])) {
                    $_SESSION['success'] = "تم تحديث الصورة الشخصية بنجاح";
                    $driver['profile_image'] = $new_filename;
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "حدث خطأ أثناء تحديث الصورة";
            }
        } else {
            $_SESSION['error'] = "حدث خطأ أثناء رفع الصورة";
        }
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - لوحة السائق</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --background-color: #f1f5f9;
            --text-color: #1e293b;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: var(--primary-color) !important;
        }

        .nav-link {
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .dropdown-item.active {
            background-color: var(--primary-color);
            color: white;
        }

        .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            background-color: white;
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.5rem;
        }

        .card-header h5 {
            margin: 0;
            color: var(--text-color);
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem;
        }

        .profile-image-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 4px solid white;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .profile-image-upload {
            position: relative;
            display: inline-block;
        }

        .profile-image-upload input[type="file"] {
            display: none;
        }

        .upload-button {
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

        .upload-button:hover {
            background: var(--secondary-color);
            transform: scale(1.1);
        }

        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
            margin-bottom: 1.5rem;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-card h3 {
            font-size: 2rem;
            margin: 0;
            font-weight: 600;
        }

        .stats-card p {
            margin: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="orders.php">
                <i class="fas fa-truck me-2"></i>
                نظام توصيل الطلبات
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-box"></i>
                            الطلبات المتاحة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php#my-orders">
                            <i class="fas fa-list"></i>
                            طلباتي
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i>
                            الملف الشخصي
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            تسجيل الخروج
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Summary -->
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="profile-image-upload">
                            <img src="<?php echo isset($driver['profile_image']) && $driver['profile_image'] ? 'uploads/driver/' . htmlspecialchars($driver['profile_image']) : 'assets/img/default-avatar.png'; ?>" 
                                 class="profile-image-large" 
                                 alt="Profile Image">
                            <label for="profile_image" class="upload-button">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="submitProfileImage(this)">
                        </div>
                        <h4 class="mt-3"><?php echo htmlspecialchars($driver['username'] ?? ''); ?></h4>
                        <p class="text-muted">سائق</p>
                        
                        <!-- Driver Stats -->
                        <div class="stats-card">
                            <h3><?php echo number_format($driver['completed_orders'] ?? 0); ?></h3>
                            <p>طلبات مكتملة</p>
                        </div>
                        <div class="stats-card">
                            <h3><?php echo number_format($driver['rating'] ?? 0, 1); ?></h3>
                            <p>التقييم العام</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="col-md-8">
                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user me-2"></i>المعلومات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($driver['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($driver['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($driver['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">العنوان</label>
                                <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($driver['address'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-car me-2"></i>معلومات المركبة</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نوع المركبة</label>
                                <input type="text" class="form-control" name="vehicle_type" value="<?php echo htmlspecialchars($driver['vehicle_type'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">موديل المركبة</label>
                                <input type="text" class="form-control" name="vehicle_model" value="<?php echo htmlspecialchars($driver['vehicle_model'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">رقم اللوحة</label>
                                <input type="text" class="form-control" name="vehicle_plate" value="<?php echo htmlspecialchars($driver['vehicle_plate'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-lock me-2"></i>تغيير كلمة المرور</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">كلمة المرور الحالية</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>
                                    تحديث كلمة المرور
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle profile image upload
        function submitProfileImage(input) {
            if (input.files && input.files[0]) {
                var formData = new FormData();
                formData.append('profile_image', input.files[0]);

                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    window.location.reload();
                }).catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // Dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html> 