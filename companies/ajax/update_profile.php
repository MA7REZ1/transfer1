<?php
require_once '../../config.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the request
error_log("Profile update request received: " . print_r($_POST, true));
if (isset($_FILES['logo'])) {
    error_log("Logo upload details: " . print_r($_FILES['logo'], true));
}

if (!isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك']);
    exit();
}

try {
    $company_id = $_SESSION['company_id'];

    // Get current company data
    $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $current_company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_company) {
        throw new Exception('الشركة غير موجودة');
    }

    // Validate commercial record and tax number
    if (!preg_match('/^[0-9]{10}$/', $_POST['commercial_record'])) {
        throw new Exception('رقم السجل التجاري يجب أن يتكون من 10 أرقام');
    }

    if (!preg_match('/^[0-9]{15}$/', $_POST['tax_number'])) {
        throw new Exception('الرقم الضريبي يجب أن يتكون من 15 رقم');
    }

    // Handle logo upload
    $logo = $current_company['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Define upload directory
        $upload_dir = "../../uploads/company_logos/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("Failed to create directory: " . $upload_dir);
                throw new Exception('فشل في إنشاء مجلد الصور');
            }
        }

        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            error_log("Directory not writable: " . $upload_dir);
            throw new Exception('لا يمكن الكتابة في مجلد الصور');
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['logo']['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('نوع الملف غير مسموح به. يرجى رفع صورة بصيغة JPG, PNG, أو GIF فقط');
        }

        // Validate file size (max 5MB)
        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
            throw new Exception('حجم الصورة كبير جداً. الحد الأقصى هو 5 ميجابايت');
        }

        // Remove old logo if exists
        if ($logo && file_exists($upload_dir . $logo)) {
            if (!unlink($upload_dir . $logo)) {
                error_log("Failed to delete old logo: " . $upload_dir . $logo);
            }
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $logo = 'company_' . $company_id . '_' . uniqid() . '.' . $extension;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo)) {
            error_log("Failed to move uploaded file to: " . $upload_dir . $logo);
            throw new Exception('فشل في رفع الشعار');
        }

        // Log successful upload
        error_log("Logo successfully uploaded: " . $logo);
    }

    // Start transaction
    $conn->beginTransaction();

    try {
        // Prepare update SQL
        $sql = "UPDATE companies SET 
                phone = ?,
                address = ?,
                commercial_record = ?,
                tax_number = ?,
                logo = ?";

        $params = [
            $_POST['phone'],
            $_POST['address'],
            $_POST['commercial_record'],
            $_POST['tax_number'],
            $logo
        ];

        // Add password update if provided
        if (!empty($_POST['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $company_id;

        // Execute update
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception('فشل في تحديث الملف الشخصي');
        }

        // Log the activity
        $log_stmt = $conn->prepare("
            INSERT INTO activity_log (driver_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $log_stmt->execute([null, 'update', 'تم تحديث الملف الشخصي للشركة']);

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'logo' => $logo
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 