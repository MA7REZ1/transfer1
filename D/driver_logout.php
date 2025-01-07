<?php
require_once 'config.php';
require_once 'driver_auth.php';

// Logout driver
logoutDriver();

// Redirect to login page
header('Location: driver_login.php');
exit;
?>
