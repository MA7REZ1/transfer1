<?php
session_start();
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $id_number = $_POST['id_number'];
    $license_number = $_POST['license_number'];
    $vehicle_type = $_POST['vehicle_type'];
    $vehicle_model = $_POST['vehicle_model'];
    $vehicle_plate = $_POST['vehicle_plate'];
    
    // Handle profile image upload
    $profile_image = '';
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "../uploads/drivers/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if(move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $profile_image = 'uploads/drivers/' . $new_filename;
        }
    }
    
    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ?");
    $check_stmt->execute([$email]);
    
    if ($check_stmt->rowCount() > 0) {
        $error = "البريد الإلكتروني مسجل مسبقاً";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("INSERT INTO drivers (username, email, phone, password, id_number, license_number, 
                        vehicle_type, vehicle_model, vehicle_plate, profile_image, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        
        if ($insert_stmt->execute([$username, $email, $phone, $hashed_password, $id_number, 
                                 $license_number, $vehicle_type, $vehicle_model, $vehicle_plate, $profile_image])) {
            $_SESSION['success_msg'] = "تم التسجيل بنجاح. يمكنك الآن تسجيل الدخول";
            header("Location: driver_login.php");
            exit();
        } else {
            $error = "حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى";
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب سائق جديد</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .preview-image {
            max-width: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">تسجيل حساب سائق جديد</h2>
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">رقم الهاتف</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">الصورة الشخصية</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <div id="image-preview" class="preview-image"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="id_number" class="form-label">رقم الهوية</label>
                            <input type="text" class="form-control" id="id_number" name="id_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="license_number" class="form-label">رقم رخصة القيادة</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">نوع المركبة</label>
                            <input type="text" class="form-control" id="vehicle_type" name="vehicle_type" required>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_model" class="form-label">موديل المركبة</label>
                            <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" required>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_plate" class="form-label">رقم لوحة المركبة</label>
                            <input type="text" class="form-control" id="vehicle_plate" name="vehicle_plate" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">تسجيل</button>
            </form>
            <p class="text-center mt-3">
                <a href="driver_login.php">لديك حساب؟ تسجيل الدخول</a>
            </p>
        </div>
    </div>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            const file = e.target.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-image';
                preview.innerHTML = '';
                preview.appendChild(img);
            }
            
            if(file) {
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
