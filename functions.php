<?php
require_once 'config.php';
require_once 'driver_auth.php';

// Function to get available requests
function getAvailablerequests() {
    global $conn;
    
    try {
        // Simple query to get all pending requests
        $query = "SELECT * FROM requests WHERE status = 'pending' ORDER BY created_at DESC";
        
        error_log("=== تنفيذ استعلام الطلبات المتاحة ===");
        error_log("SQL Query: " . $query);
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        error_log("عدد النتائج: " . count($results));
        
        // Log the first result for debugging
        if (!empty($results)) {
            error_log("نموذج للبيانات المسترجعة:");
            error_log(print_r($results[0], true));
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log("خطأ في جلب الطلبات المتاحة: " . $e->getMessage());
        return [];
    }
}

// Function to get driver's current requests
function getDriverrequests($status = null) {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return [];
    }
    
    try {
        $query = "SELECT r.* 
                  FROM requests r 
                  WHERE r.driver_id = ?";
        
        if ($status) {
            $query .= " AND r.status = ?";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if ($status) {
            $stmt->execute([$_SESSION['driver_id'], $status]);
        } else {
            $stmt->execute([$_SESSION['driver_id']]);
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting driver requests: " . $e->getMessage());
        return [];
    }
}

// Function to get driver's earnings
function getDriverEarnings($period = 'all') {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return [
            'total' => 0,
            'pending' => 0,
            'paid' => 0,
            'details' => []
        ];
    }
    
    try {
        $where = "WHERE de.driver_id = ?";
        $params = [$_SESSION['driver_id']];
        
        switch ($period) {
            case 'today':
                $where .= " AND DATE(de.created_at) = CURDATE()";
                break;
            case 'week':
                $where .= " AND de.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where .= " AND de.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
        
        // Get totals
        $stmt = $conn->prepare("SELECT 
            SUM(amount) as total,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid
            FROM driver_earnings de " . $where);
        $stmt->execute($params);
        $totals = $stmt->fetch();
        
        // Get detailed earnings
        $stmt = $conn->prepare("SELECT de.*, r.order_number 
                              FROM driver_earnings de
                              JOIN requests r ON de.request_id = r.id " . 
                              $where . " ORDER BY de.created_at DESC");
        $stmt->execute($params);
        $details = $stmt->fetchAll();
        
        return [
            'total' => $totals['total'] ?? 0,
            'pending' => $totals['pending'] ?? 0,
            'paid' => $totals['paid'] ?? 0,
            'details' => $details
        ];
    } catch (PDOException $e) {
        error_log("Error getting driver earnings: " . $e->getMessage());
        return [
            'total' => 0,
            'pending' => 0,
            'paid' => 0,
            'details' => []
        ];
    }
}

// Function to get driver's notifications
function getDriverNotifications($limit = 10, $unread_only = false) {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return [];
    }
    
    try {
        $query = "SELECT * FROM driver_notifications WHERE driver_id = ?";
        if ($unread_only) {
            $query .= " AND is_read = 0";
        }
        $query .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['driver_id'], $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting driver notifications: " . $e->getMessage());
        return [];
    }
}

// Function to mark notification as read
function markNotificationAsRead($notification_id) {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE driver_notifications SET is_read = 1 
                              WHERE id = ? AND driver_id = ?");
        $stmt->execute([$notification_id, $_SESSION['driver_id']]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

// Function to update driver location
function updateDriverLocation($latitude, $longitude, $accuracy = null, $speed = null, $heading = null) {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return false;
    }
    
    try {
        $conn->beginTransaction();
        
        // Update current location in drivers table
        $stmt = $conn->prepare("UPDATE drivers SET last_location = POINT(?, ?) WHERE id = ?");
        $stmt->execute([$longitude, $latitude, $_SESSION['driver_id']]);
        
        // Add to location history
        $stmt = $conn->prepare("INSERT INTO driver_locations (driver_id, latitude, longitude, accuracy, speed, heading) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['driver_id'],
            $latitude,
            $longitude,
            $accuracy,
            $speed,
            $heading
        ]);
        
        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error updating driver location: " . $e->getMessage());
        return false;
    }
}

// Function to format date in Arabic
function formatArabicDate($date) {
    $months = [
        'January' => 'يناير',
        'February' => 'فبراير',
        'March' => 'مارس',
        'April' => 'أبريل',
        'May' => 'مايو',
        'June' => 'يونيو',
        'July' => 'يوليو',
        'August' => 'أغسطس',
        'September' => 'سبتمبر',
        'October' => 'أكتوبر',
        'November' => 'نوفمبر',
        'December' => 'ديسمبر'
    ];
    
    $days = [
        'Saturday' => 'السبت',
        'Sunday' => 'الأحد',
        'Monday' => 'الإثنين',
        'Tuesday' => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة'
    ];
    
    $timestamp = strtotime($date);
    $day = date('l', $timestamp);
    $month = date('F', $timestamp);
    
    return $days[$day] . ' ' . date('d', $timestamp) . ' ' . $months[$month] . ' ' . date('Y', $timestamp);
}

// Function to format currency
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ريال';
}

// Function to get request status in Arabic
function getArabicStatus($status) {
    $statuses = [
        'pending' => 'معلق',
        'accepted' => 'تم القبول',
        'in_transit' => 'في الطريق',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي'
    ];
    
    return $statuses[$status] ?? $status;
}

// Function to get payment method in Arabic
function getArabicPaymentMethod($method) {
    $methods = [
        'cash' => 'نقداً',
        'card' => 'بطاقة',
        'bank_transfer' => 'تحويل بنكي'
    ];
    
    return $methods[$method] ?? $method;
}
?> 