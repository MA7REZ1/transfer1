<?php
// Set session settings before starting the session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.use_only_cookies', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$db_host = 'localhost';
$db_name = 'admin_panel';
$db_user = 'root';
$db_pass = '';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    error_log("تم الاتصال بقاعدة البيانات بنجاح");
} catch(PDOException $e) {
    error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Constants for application settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('API_RATE_LIMIT', 100); // requests per minute

// Enhanced security functions
function isLoggedIn() {
    if (!isset($_SESSION['driver_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

function validateInput($data, $type = 'string') {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT) ? $data : false;
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT) ? $data : false;
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL) ? $data : false;
        default:
            return $data;
    }
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function logActivity($action, $details = '', $user_id = null) {
    global $conn;
    $user_id = $user_id ?? ($_SESSION['driver_id'] ?? null);
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip, $user_agent]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

// API Response helper
function jsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

// Rate limiting function
function checkRateLimit($key, $limit = API_RATE_LIMIT, $period = 60) {
    $redis = new Redis();
    try {
        $redis->connect('127.0.0.1', 6379);
        $current = $redis->incr($key);
        if ($current === 1) {
            $redis->expire($key, $period);
        }
        return $current <= $limit;
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
        return true; // Fail open if Redis is not available
    }
}

// Function to debug query
function debugQuery($query, $params = []) {
    error_log("=== تنفيذ استعلام SQL ===");
    error_log("Query: " . $query);
    if (!empty($params)) {
        error_log("Parameters: " . print_r($params, true));
    }
}
?> 