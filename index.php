<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: admin/index.php');
    exit;
}