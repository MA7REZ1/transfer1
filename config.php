<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language if not set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ar';
}

// Include language file
require_once __DIR__ . '/includes/languages.php';

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

// Define base URL
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_url .= $_SERVER['HTTP_HOST'];
define('BASE_URL', $base_url);

// Basic security functions
function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function isDriverLoggedIn() {
    return isset($_SESSION['driver_id']) && !empty($_SESSION['driver_id']);
}

function hasPermission($requiredRole) {
    return $_SESSION['admin_role'] === $requiredRole || $_SESSION['admin_role'] === 'super_admin';
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to debug query
function debugQuery($query, $params = []) {
    error_log("=== تنفيذ استعلام SQL ===");
    error_log("Query: " . $query);
    if (!empty($params)) {
        error_log("Parameters: " . print_r($params, true));
    }
}

// Function to change language
function setLanguage($lang) {
    if ($lang === 'ar' || $lang === 'en') {
        $_SESSION['lang'] = $lang;
        return true;
    }
    return false;
}
?> 