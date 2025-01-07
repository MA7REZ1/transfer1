<?php
require_once 'config.php';
require_once 'driver_auth.php';

// Function to get available requests
function getAvailableRequests() {
    global $conn;
    try {
        $sql = "SELECT r.*, c.name as company_name, c.phone as company_phone,
                       r.pickup_location, r.delivery_location,
                       r.pickup_location_link, r.delivery_location_link,
                       r.pickup_lat, r.pickup_lng, r.delivery_lat, r.delivery_lng
                FROM requests r
                JOIN companies c ON r.company_id = c.id
                WHERE r.status = 'pending' 
                AND r.driver_id IS NULL
                AND r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY r.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting available requests: " . $e->getMessage());
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
    try {
        $sql = "SELECT de.*, r.order_number, r.delivery_date
                FROM driver_earnings de
                JOIN requests r ON de.request_id = r.id
                WHERE de.driver_id = :driver_id";
        
        switch($period) {
            case 'today':
                $sql .= " AND DATE(de.created_at) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND YEARWEEK(de.created_at) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $sql .= " AND MONTH(de.created_at) = MONTH(CURDATE()) AND YEAR(de.created_at) = YEAR(CURDATE())";
                break;
        }
        
        $sql .= " ORDER BY de.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':driver_id', $_SESSION['driver_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting driver earnings: " . $e->getMessage());
        return [];
    }
}

// Function to get driver's notifications
function getDriverNotifications($limit = 5, $unreadOnly = false) {
    global $conn;
    try {
        $sql = "SELECT dn.*, r.order_number, c.name as company_name
                FROM driver_notifications dn
                JOIN requests r ON dn.request_id = r.id
                JOIN companies c ON r.company_id = c.id
                WHERE dn.driver_id = :driver_id";
        
        if ($unreadOnly) {
            $sql .= " AND dn.is_read = 0";
        }
        
        $sql .= " ORDER BY dn.created_at DESC LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':driver_id', $_SESSION['driver_id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2) . ' ريال';
    }
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

// Function to calculate driver's acceptance rate
function calculateAcceptanceRate($driver_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN status IN ('accepted', 'in_transit', 'delivered') THEN 1 END) as accepted,
                COUNT(*) as total
            FROM request_assignments
            WHERE driver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$driver_id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return ($result['accepted'] / $result['total']) * 100;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Error calculating acceptance rate: " . $e->getMessage());
        return 0;
    }
}

// Function to calculate driver's completion rate
function calculateCompletionRate($driver_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed,
                COUNT(CASE WHEN status IN ('accepted', 'in_transit', 'delivered') THEN 1 END) as total
            FROM requests
            WHERE driver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$driver_id]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return ($result['completed'] / $result['total']) * 100;
        }
        return 0;
    } catch (PDOException $e) {
        error_log("Error calculating completion rate: " . $e->getMessage());
        return 0;
    }
}

// Function to calculate driver's total earnings
function calculateTotalEarnings($driver_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT SUM(amount) as total
            FROM driver_earnings
            WHERE driver_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$driver_id]);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error calculating total earnings: " . $e->getMessage());
        return 0;
    }
}

// Function to calculate driver's total distance
function calculateTotalDistance($driver_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT SUM(distance) as total
            FROM requests
            WHERE driver_id = ? AND status = 'delivered' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$driver_id]);
        $result = $stmt->fetch();
        
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error calculating total distance: " . $e->getMessage());
        return 0;
    }
}

// Function to get time ago in Arabic
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    $intervals = [
        31536000 => 'سنة',
        2592000 => 'شهر',
        604800 => 'أسبوع',
        86400 => 'يوم',
        3600 => 'ساعة',
        60 => 'دقيقة',
        1 => 'ثانية'
    ];
    
    foreach ($intervals as $seconds => $label) {
        $interval = floor($diff / $seconds);
        if ($interval >= 1) {
            if ($interval > 10) {
                return "منذ " . $interval . " " . $label;
            } else {
                // Special handling for Arabic numbers 2-10
                $labels = [
                    'سنة' => ['سنتين', 'سنوات'],
                    'شهر' => ['شهرين', 'شهور'],
                    'أسبوع' => ['أسبوعين', 'أسابيع'],
                    'يوم' => ['يومين', 'أيام'],
                    'ساعة' => ['ساعتين', 'ساعات'],
                    'دقيقة' => ['دقيقتين', 'دقائق'],
                    'ثانية' => ['ثانيتين', 'ثواني']
                ];
                
                if ($interval == 2) {
                    return "منذ " . $labels[$label][0];
                } else {
                    return "منذ " . $interval . " " . $labels[$label][1];
                }
            }
        }
    }
    
    return "الآن";
}
?> 