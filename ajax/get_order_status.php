<?php
require_once '../config.php';
header('Content-Type: application/json');

$order_number = isset($_GET['order_number']) ? $_GET['order_number'] : '';
$response = ['status' => null];

if ($order_number) {
    $stmt = $conn->prepare("SELECT status FROM requests WHERE order_number = ?");
    $stmt->execute([$order_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $response['status'] = $result['status'];
    }
}

echo json_encode($response); 