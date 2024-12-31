<?php
require_once '../../config.php';

try {
   
    // إنشاء كلمة مرور مشفرة للمشرف
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);

    // إضافة المشرف الرئيسي
    $stmt = $conn->prepare("
        INSERT INTO admins (email, username, password, role, is_active) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'admin1@system.com',
        'المدير العام',
        $admin_password,
        'super_admin',
        true
    ]);

    echo "<div style='text-align: center; margin-top: 20px; font-family: Arial;'>
            <h3 style='color: green;'>تم إنشاء حساب المشرف بنجاح</h3>
            
            <div style='margin: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                <h4>المدير العام</h4>
                <p>البريد الإلكتروني: admin@system.com</p>
                <p>كلمة المرور: admin123</p>
            </div>

            <a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                تسجيل الدخول
            </a>
          </div>";
} catch (PDOException $e) {
    echo "<div style='text-align: center; margin-top: 20px; color: red; font-family: Arial;'>
            <h3>حدث خطأ</h3>
            <p>" . $e->getMessage() . "</p>
          </div>";
}
?> 