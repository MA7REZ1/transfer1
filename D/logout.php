<?php
require_once 'config.php';

// تسجيل نشاط تسجيل الخروج
if (isset($_SESSION['driver_id'])) {
    logActivity($_SESSION['driver_id'], 'logout', 'تم تسجيل الخروج');
}

// تنظيف وإنهاء الجلسة
session_unset();
session_destroy();

// توجيه المستخدم إلى صفحة تسجيل الدخول
header('Location: login.php');
exit;
?> 