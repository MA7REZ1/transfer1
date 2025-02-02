<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'الوصول مرفوض']);
    exit;
}

try {
    // جلب جميع الإشعارات لكل المستخدمين
    $query = "SELECT *, 
        DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date,
        TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_ago
        FROM notifications
        ORDER BY created_at DESC 
        LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تحديث جميع الإشعارات كمقروءة
    $conn->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");

    // حساب جميع الإشعارات غير المقروءة (سيصبح 0 بعد التحديث)
    $unread_count = 0;

    // تنسيق الوقت المنقضي
    $formatted_notifications = [];
    foreach ($notifications as $notification) {
        $minutes = $notification['minutes_ago'];
        $hours = floor($minutes / 60);
        $days = floor($hours / 24);

        if ($days > 30) {
            $time_ago = date('d/m/Y', strtotime($notification['created_at']));
        } elseif ($days > 0) {
            $time_ago = "منذ $days يوم";
        } elseif ($hours > 0) {
            $time_ago = "منذ $hours ساعة";
        } else {
            $time_ago = "منذ " . max($minutes, 1) . " دقيقة";
        }

        $formatted_notifications[] = [
            'id' => $notification['id'],
            'message' => htmlspecialchars($notification['message'], ENT_QUOTES),
            'type' => $notification['type'],
            'is_read' => true, // سيصبح جميعها مقروءة
            'time_ago' => $time_ago,
            'link' => $notification['link']
        ];
    }

    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count
    ]);

} catch (PDOException $e) {
    error_log("Notifications Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في النظام',
        'error_code' => 'DB_ERROR'
    ]);
}
?>