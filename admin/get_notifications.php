<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'الوصول مرفوض']);
    exit;
}

try {
    // تعيين المنطقة الزمنية
    date_default_timezone_set('Asia/Riyadh');

    // جلب جميع الإشعارات لكل المستخدمين
    $query = "SELECT *, 
        UNIX_TIMESTAMP(created_at) as timestamp,
        created_at,
        DATE_FORMAT(CONVERT_TZ(created_at, 'UTC', 'Asia/Riyadh'), '%Y-%m-%d %H:%i') as formatted_date,
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
    $now = new DateTime('now', new DateTimeZone('Asia/Riyadh'));

    foreach ($notifications as $notification) {
        // تحويل التاريخ إلى المنطقة الزمنية الصحيحة
        $created_at = new DateTime($notification['created_at'], new DateTimeZone('UTC'));
        $created_at->setTimezone(new DateTimeZone('Asia/Riyadh'));
        
        // حساب الفرق الزمني
        $interval = $created_at->diff($now);
        $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        $hours = floor($minutes / 60);
        $days = floor($hours / 24);

        // تنسيق الوقت المنقضي
        if ($days > 30) {
            $time_ago = $created_at->format('Y-m-d H:i');
        } elseif ($days > 0) {
            $time_ago = "منذ $days يوم";
        } elseif ($hours > 0) {
            $time_ago = "منذ $hours ساعة";
        } else {
            $minutes = max($minutes, 1);
            $time_ago = "منذ $minutes دقيقة";
        }

        $formatted_notifications[] = [
            'id' => $notification['id'],
            'message' => htmlspecialchars($notification['message'], ENT_QUOTES),
            'type' => $notification['type'],
            'is_read' => true,
            'time_ago' => $time_ago,
            'timestamp' => $notification['timestamp'],
            'created_at' => $created_at->format('c'), // ISO 8601 format
            'link' => $notification['link']
        ];
    }

    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count,
        'server_time' => $now->format('c')
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