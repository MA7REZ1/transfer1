<?php
require_once 'config.php';

// Update last logout time based on user type
if (isset($_SESSION['admin_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE admins SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
    } catch (PDOException $e) {
        error_log("Admin logout error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['company_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE companies SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['company_id']]);
    } catch (PDOException $e) {
        error_log("Company logout error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['staff_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE company_staff SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['staff_id']]);
    } catch (PDOException $e) {
        error_log("Staff logout error: " . $e->getMessage());
    }
} elseif (isset($_SESSION['driver_id'])) {
    try {
        // Log the logout action
        $stmt = $conn->prepare("INSERT INTO activity_log (driver_id, action, details) VALUES (?, 'logout', 'Driver logged out')");
        $stmt->execute([$_SESSION['driver_id']]);
        
        // Update last logout time
        $stmt = $conn->prepare("UPDATE drivers SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['driver_id']]);
    } catch (PDOException $e) {
        error_log("Driver logout error: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy all other cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Determine redirect location based on the directory
$current_path = dirname($_SERVER['PHP_SELF']);

if (strpos($current_path, '/admin') !== false) {
    header('Location: login.php');
} elseif (strpos($current_path, '/companies') !== false) {
    header('Location: login.php');
} elseif (strpos($current_path, '/drivers') !== false) {
    header('Location: login.php');
} else {
    header('Location: login.php');
}

exit();
