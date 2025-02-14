<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log('Language change request received: ' . print_r($_POST, true));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = $_POST['lang'] ?? 'ar';
    
    // Validate language
    if (in_array($lang, ['ar', 'en'])) {
        $_SESSION['lang'] = $lang;
        error_log('Language changed successfully to: ' . $lang);
        echo json_encode(['success' => true]);
    } else {
        error_log('Invalid language requested: ' . $lang);
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
    }
} else {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
} 