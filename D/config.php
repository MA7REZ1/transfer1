<?php
// إعدادات قاعدة البيانات
$db_host = 'localhost';
$db_name = 'admin_panel';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// إعدادات المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// دوال مساعدة
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . substr(uniqid(), -5);
}

function formatCurrency($amount) {
    return number_format($amount, 2) . ' ريال';
}

function getOrderStatus($status) {
    $statuses = [
        'pending' => 'في الانتظار',
        'accepted' => 'تم القبول',
        'picked_up' => 'تم الاستلام',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي'
    ];
    return $statuses[$status] ?? $status;
}

function logActivity($driverId, $action, $details = '') {
    global $conn;
    try {
        $stmt = $conn->prepare("INSERT INTO activity_log (driver_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$driverId, $action, $details]);
    } catch (PDOException $e) {
        error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
    }
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['driver_id']);
}

// التحقق من الجلسة
session_start();
if (isset($_SESSION['driver_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > 3600) { // ساعة واحدة
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
?> 