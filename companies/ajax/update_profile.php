<?php
require_once '../../config.php';

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
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['logo']['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('نوع الملف غير مسموح به. يرجى رفع صورة فقط');
        }

        // Remove old logo if exists
        if ($logo && file_exists("../../uploads/companies/" . $logo)) {
            unlink("../../uploads/companies/" . $logo);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo = uniqid() . '.' . $extension;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], "../../uploads/companies/" . $logo)) {
            throw new Exception('فشل في رفع الشعار');
        }
    }

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

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح'
        ]);
    } else {
        throw new Exception('فشل في تحديث الملف الشخصي');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 