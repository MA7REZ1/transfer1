<?php
require_once 'config.php';

// Function to check if driver is logged in
function isDriverLoggedIn() {
    return isset($_SESSION['driver_id']);
}

// Function to get current driver info
function getCurrentDriver() {
    global $conn;
    if (!isDriverLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$_SESSION['driver_id']]);
    return $stmt->fetch();
}

// Function to authenticate driver
function authenticateDriver($email, $password) {
    global $conn;
    
    error_log("=== بداية محاولة تسجيل الدخول ===");
    error_log("البريد الإلكتروني المدخل: " . $email);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM drivers WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $driver = $stmt->fetch();
        
        error_log("نتيجة البحث عن السائق: " . ($driver ? 'تم العثور عليه' : 'غير موجود'));
        
        if ($driver) {
            error_log("معرف السائق: " . $driver['id']);
            error_log("اسم السائق: " . $driver['username']);
            
            if (password_verify($password, $driver['password'])) {
                session_regenerate_id(true);
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_name'] = $driver['username'];
                error_log("تم تسجيل الدخول بنجاح");
                return true;
            }
        }
        
        error_log("فشل تسجيل الدخول");
        return false;
    } catch (Exception $e) {
        error_log("خطأ في المصادقة: " . $e->getMessage());
        throw $e;
    }
}

// Function to update driver status
function updateDriverStatus($status) {
    global $conn;
    
    if (!isDriverLoggedIn() || !in_array($status, ['available', 'busy', 'offline'])) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE drivers SET current_status = ? WHERE id = ?");
        $stmt->execute([$status, $_SESSION['driver_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating driver status: " . $e->getMessage());
        return false;
    }
}

// Function to logout driver
function logoutDriver() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>
