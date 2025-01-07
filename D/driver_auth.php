<?php
// Constants for login security
define('LOGIN_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5); // Maximum failed login attempts before timeout

require_once 'config.php';

class DriverAuth {
    private $conn;
    private $attempts_table = 'login_attempts';
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initializeLoginAttemptsTable();
        $this->initializeActivityLogTable();
    }
    
    private function initializeLoginAttemptsTable() {
        try {
            $this->conn->exec("CREATE TABLE IF NOT EXISTS {$this->attempts_table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (email, ip_address, attempt_time)
            )");
        } catch (PDOException $e) {
            error_log("Error creating login attempts table: " . $e->getMessage());
        }
    }

    private function initializeActivityLogTable() {
        try {
            $this->conn->exec("CREATE TABLE IF NOT EXISTS activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                driver_id INT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (driver_id),
                FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
            )");
        } catch (PDOException $e) {
            error_log("Error creating activity log table: " . $e->getMessage());
        }
    }
    
    public function isDriverLoggedIn() {
        return isLoggedIn();
    }
    
    public function getCurrentDriver() {
        if (!$this->isDriverLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    d.*,
                    COUNT(DISTINCT o.id) as total_orders,
                    AVG(r.rating) as average_rating,
                    (
                        SELECT COUNT(*) 
                        FROM requests 
                        WHERE status = 'pending' 
                        AND driver_id IS NULL
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) as available_orders
                FROM drivers d
                LEFT JOIN orders o ON d.id = o.driver_id
                LEFT JOIN ratings r ON d.id = r.driver_id
                WHERE d.id = ?
                GROUP BY d.id
            ");
            $stmt->execute([$_SESSION['driver_id']]);
            $driver = $stmt->fetch();
            
            if ($driver) {
                unset($driver['password']);
                return $driver;
            }
        } catch (PDOException $e) {
            error_log("Error fetching driver info: " . $e->getMessage());
        }
        return null;
    }
    
    private function checkLoginAttempts($email, $ip) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM {$this->attempts_table} 
                WHERE (email = ? OR ip_address = ?) 
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$email, $ip, LOGIN_TIMEOUT]);
            $result = $stmt->fetch();
            return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
        } catch (PDOException $e) {
            error_log("Error checking login attempts: " . $e->getMessage());
            return false;
        }
    }
    
    private function logLoginAttempt($email, $ip, $success = false) {
        try {
            if (!$success) {
                $stmt = $this->conn->prepare("
                    INSERT INTO {$this->attempts_table} (email, ip_address) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$email, $ip]);
            } else {
                $stmt = $this->conn->prepare("
                    DELETE FROM {$this->attempts_table} 
                    WHERE email = ? OR ip_address = ?
                ");
                $stmt->execute([$email, $ip]);
            }
        } catch (PDOException $e) {
            error_log("Error logging login attempt: " . $e->getMessage());
        }
    }
    
    public function authenticateDriver($email, $password) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if ($this->checkLoginAttempts($email, $ip)) {
            logActivity($_SESSION['driver_id'] ?? null, 'login_blocked', "Too many attempts for email: $email");
            return ['success' => false, 'message' => 'تم تجاوز الحد الأقصى لمحاولات تسجيل الدخول. الرجاء المحاولة لاحقاً'];
        }
        
        try {
            $stmt = $this->conn->prepare("
                SELECT d.*, 
                       (SELECT COUNT(*) FROM requests WHERE status = 'pending' AND driver_id IS NULL) as pending_orders
                FROM drivers d
                WHERE d.email = ? 
                AND d.is_active = 1 
            ");
            $stmt->execute([$email]);
            $driver = $stmt->fetch();
            
            if ($driver && password_verify($password, $driver['password'])) {
                // Successful login
                $this->logLoginAttempt($email, $ip, true);
                
                // Generate new session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_name'] = $driver['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['ip_address'] = $ip;
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                
                // Update last login and set initial status
                $this->conn->prepare("
                    UPDATE drivers 
                    SET last_login = CURRENT_TIMESTAMP,
                        current_status = 'available'
                    WHERE id = ?
                ")->execute([$driver['id']]);
                
                logActivity($driver['id'], 'login_success', "Driver logged in successfully");
                
                return [
                    'success' => true, 
                    'driver' => $driver,
                    'pending_orders' => $driver['pending_orders']
                ];
            }
            
            // Failed login
            $this->logLoginAttempt($email, $ip);
            logActivity(null, 'login_failed', "Failed login attempt for email: $email");
            return ['success' => false, 'message' => 'بيانات تسجيل الدخول غير صحيحة'];
            
        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء تسجيل الدخول'];
        }
    }
    
    public function updateDriverStatus($status) {
        if (!$this->isDriverLoggedIn() || !in_array($status, ['available', 'busy', 'offline'])) {
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare("
                UPDATE drivers 
                SET current_status = ?,
                    status_updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $_SESSION['driver_id']]);
            
            logActivity($_SESSION['driver_id'], 'status_update', "Status updated to: $status");
            return true;
        } catch (PDOException $e) {
            error_log("Error updating driver status: " . $e->getMessage());
            return false;
        }
    }
    
    public function logoutDriver() {
        if (isset($_SESSION['driver_id'])) {
            logActivity($_SESSION['driver_id'], 'logout', "Driver logged out");
            
            // Update driver status to offline
            $this->updateDriverStatus('offline');
        }
        
        // Clear all session data
        $_SESSION = array();
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
}

// Initialize the auth class
$driverAuth = new DriverAuth($conn);

// Export functions for backward compatibility
function isDriverLoggedIn() {
    global $driverAuth;
    return $driverAuth->isDriverLoggedIn();
}

function getCurrentDriver() {
    global $driverAuth;
    return $driverAuth->getCurrentDriver();
}

function authenticateDriver($email, $password) {
    global $driverAuth;
    return $driverAuth->authenticateDriver($email, $password);
}

function updateDriverStatus($status) {
    global $driverAuth;
    return $driverAuth->updateDriverStatus($status);
}

function logoutDriver() {
    global $driverAuth;
    $driverAuth->logoutDriver();
}
?>
