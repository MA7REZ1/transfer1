<?php
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get notifications
$stmt = $conn->prepare("
    SELECT n.*, 
           DATE_FORMAT(n.created_at, '%Y-%m-%d %H:%i') as formatted_date,
           TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) as minutes_ago,
           TIMESTAMPDIFF(HOUR, n.created_at, NOW()) as hours_ago,
           TIMESTAMPDIFF(DAY, n.created_at, NOW()) as days_ago
    FROM notifications n 
    ORDER BY n.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$stmt->execute();
$unread_count = $stmt->fetchColumn();

// Format notifications for response
$formatted_notifications = [];
foreach ($notifications as $notification) {
    // Format time ago
    if ($notification['minutes_ago'] < 60) {
        $time_ago = 'منذ ' . $notification['minutes_ago'] . ' دقيقة';
    } elseif ($notification['hours_ago'] < 24) {
        $time_ago = 'منذ ' . $notification['hours_ago'] . ' ساعة';
    } elseif ($notification['days_ago'] < 30) {
        $time_ago = 'منذ ' . $notification['days_ago'] . ' يوم';
    } else {
        $time_ago = $notification['formatted_date'];
    }
    
    $formatted_notifications[] = [
        'id' => $notification['id'],
        'message' => htmlspecialchars($notification['message']),
        'type' => $notification['type'],
        'is_read' => (bool)$notification['is_read'],
        'time_ago' => $time_ago,
        'link' => $notification['link']
    ];
}

echo json_encode([
    'success' => true,
    'notifications' => $formatted_notifications,
    'unread_count' => $unread_count
]);
?> 