<?php
require_once 'config.php';

// Verify if the user is actually logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Log the logout action if needed
if (isset($_SESSION['admin_id'])) {
    try {
        $stmt = $conn->prepare("UPDATE admins SET last_logout = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
    } catch (PDOException $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
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

// Redirect with a clean GET request
header('Location: index.php');
exit();
