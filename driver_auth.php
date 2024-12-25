<?php
require_once 'config.php';

// Function to check if driver is logged in
function isDriverLoggedIn() {
    return isset($_SESSION['driver_id']);
}

// Function to get current driver info
function getCurrentDriver() {
    global $conn;
    if (!isDriverLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$_SESSION['driver_id']]);
    return $stmt->fetch();
}

// Function to authenticate driver
function authenticateDriver($email, $password) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT d.*, GROUP_CONCAT(c.name) as company_names 
                          FROM drivers d 
                          LEFT JOIN driver_company_assignments dca ON d.id = dca.driver_id 
                          LEFT JOIN companies c ON dca.company_id = c.id 
                          WHERE d.email = ? AND d.is_active = 1
                          GROUP BY d.id");
    $stmt->execute([$email]);
    $driver = $stmt->fetch();
    
    if ($driver && password_verify($password, $driver['password'])) {
        // Create session
        session_regenerate_id(true);
        $_SESSION['driver_id'] = $driver['id'];
        $_SESSION['driver_name'] = $driver['username'];
        $_SESSION['driver_companies'] = $driver['company_names'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE drivers SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$driver['id']]);
        
        // Create session record
        $stmt = $conn->prepare("INSERT INTO driver_sessions (driver_id, session_token, device_info, ip_address, expires_at) 
                              VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
        $stmt->execute([
            $driver['id'],
            session_id(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        return true;
    }
    
    return false;
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

// Function to update driver status
function updateDriverStatus($status) {
    global $conn;
    
    if (!isDriverLoggedIn() || !in_array($status, ['available', 'busy', 'offline'])) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE drivers SET current_status = ? WHERE id = ?");
        $stmt->execute([$status, $_SESSION['driver_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Error updating driver status: " . $e->getMessage());
        return false;
    }
}

// Function to get driver's current orders
function getDriverOrders($status = null) {
    global $conn;
    
    if (!isDriverLoggedIn()) {
        return [];
    }
    
    $query = "SELECT o.*, c.name as company_name, c.phone as company_phone 
              FROM orders o 
              JOIN companies c ON o.company_id = c.id 
              WHERE o.driver_id = ?";
    
    if ($status) {
        $query .= " AND o.status = ?";
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    try {
        $stmt = $conn->prepare($query);
        if ($status) {
            $stmt->execute([$_SESSION['driver_id'], $status]);
        } else {
            $stmt->execute([$_SESSION['driver_id']]);
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting driver orders: " . $e->getMessage());
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
        $where = "WHERE driver_id = ?";
        $params = [$_SESSION['driver_id']];
        
        switch ($period) {
            case 'today':
                $where .= " AND DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
        
        // Get totals
        $stmt = $conn->prepare("SELECT 
            SUM(amount) as total,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid
            FROM driver_earnings " . $where);
        $stmt->execute($params);
        $totals = $stmt->fetch();
        
        // Get detailed earnings
        $stmt = $conn->prepare("SELECT de.*, o.order_number 
                              FROM driver_earnings de
                              JOIN orders o ON de.order_id = o.id " . 
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
        return true;
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

// Function to logout driver
function logoutDriver() {
    global $conn;
    
    if (isDriverLoggedIn()) {
        try {
            // Update driver status to offline
            $stmt = $conn->prepare("UPDATE drivers SET current_status = 'offline' WHERE id = ?");
            $stmt->execute([$_SESSION['driver_id']]);
            
            // Invalidate session
            $stmt = $conn->prepare("UPDATE driver_sessions SET expires_at = NOW() 
                                  WHERE driver_id = ? AND session_token = ?");
            $stmt->execute([$_SESSION['driver_id'], session_id()]);
        } catch (PDOException $e) {
            error_log("Error during driver logout: " . $e->getMessage());
        }
    }
    
    // Clear session
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}
?>
