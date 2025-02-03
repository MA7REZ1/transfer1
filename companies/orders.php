<?php
require_once '../config.php';

// Set staff header
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

if (!isset($_SESSION['staff_id'])) {
      header("Location: login.php");
    exit();
}
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}


$company_id = $_SESSION['company_id'];
$staff_id = $_SESSION['staff_id'];

// Get company information
$stmt = $conn->prepare("SELECT name, logo FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get staff information
$stmt = $conn->prepare("
    SELECT name, email, phone, role, last_login, created_at 
    FROM company_staff 
    WHERE id = ? AND company_id = ?
");
$stmt->execute([$staff_id, $company_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Get requests statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
    FROM requests 
    WHERE company_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute([$company_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent requests
$stmt = $conn->prepare("
    SELECT r.*, d.username as driver_name, d.phone as driver_phone
    FROM requests r
    LEFT JOIN drivers d ON r.driver_id = d.id
    WHERE r.company_id = ?
    ORDER BY r.created_at DESC
    LIMIT 100
");
$stmt->execute([$company_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatPhoneForWhatsApp($phone) {
    if (empty($phone)) return '';
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Add Saudi country code if not present
    if (strlen($phone) == 9) {
        $phone = '966' . $phone;
    } else if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
        $phone = '966' . substr($phone, 1);
    }
    return $phone;
}

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM complaints 
    WHERE company_id = ? 
    AND status IN ('new', 'in_progress')
");
$stmt->execute([$_SESSION['company_id']]);
$active_complaints = $stmt->fetchColumn();

// Add this after the active_complaints query
$stmt = $conn->prepare("
    SELECT 
        cr.*, c.complaint_number, c.subject,
        a.username as admin_name
    FROM complaint_responses cr
    JOIN complaints c ON cr.complaint_id = c.id
    JOIN admins a ON cr.admin_id = a.id
    WHERE c.company_id = ?
    AND cr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY cr.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['company_id']]);
$complaint_responses = $stmt->fetchAll();
?>



<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلبات - <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Tajawal', sans-serif;
            min-height: 100vh;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-header h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .profile-header .text-white-50 {
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .profile-info {
            color: white;
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .profile-info i {
            margin-right: 0.5rem;
            color: var(--warning-color);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 0 1rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1));
            z-index: 1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            position: relative;
            z-index: 2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .stat-card .label {
            color: var(--secondary-color);
            font-size: 1.2rem;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            border: none;
            font-weight: 500;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            min-width: 160px;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2));
            transition: all 0.3s ease;
        }

        .action-button:hover::before {
            transform: translateX(100%);
        }

        .action-button i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .action-button:hover i {
            transform: scale(1.2);
        }

        .action-button.warning {
            background: linear-gradient(45deg, var(--warning-color), #f4b619);
            color: white;
        }

        .action-button.danger {
            background: linear-gradient(45deg, var(--danger-color), #d52a1a);
            color: white;
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .action-button .badge {
            position: relative;
            top: unset;
            right: unset;
            background: var(--danger-color);
            color: white;
            border-radius: 8px;
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            margin-right: 5px;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            padding: 0 8px;
            font-weight: bold;
        }

        .nav-link {
            color: white !important;
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            margin: 0 0.5rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-link .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .alert-float {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            transform: translateY(-100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .alert-float.show {
            transform: translateY(0);
            opacity: 1;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0;
            color: inherit;
            opacity: 0.7;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .close-btn:hover {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 1rem;
            }

            .profile-header h2 {
                font-size: 1.8rem;
            }

            .profile-info {
                flex-direction: column;
                gap: 0.8rem;
            }

            .profile-info span {
                font-size: 0.9rem;
            }

            .action-buttons {
                justify-content: center;
                padding: 0 1rem;
            }

            .action-button {
                width: 100%;
                min-width: unset;
                padding: 0.7rem 1rem;
                font-size: 0.9rem;
            }

            .stats-cards {
                grid-template-columns: 1fr;
                padding: 0 1rem;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-card .icon {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }

            .stat-card .value {
                font-size: 2rem;
            }

            .stat-card .label {
                font-size: 1rem;
            }

            .col-md-8, .col-md-4 {
                width: 100%;
            }

            .d-flex.align-items-center.gap-4 {
                flex-direction: column;
                text-align: center;
            }

            .d-flex.align-items-center.gap-4 img {
                margin-bottom: 1rem;
            }

            .text-md-end {
                text-align: center !important;
            }
        }

        @media (max-width: 576px) {
            .profile-header h2 {
                font-size: 1.5rem;
            }

            .action-button {
                font-size: 0.85rem;
                padding: 0.6rem 0.8rem;
            }

            .action-button i {
                font-size: 1rem;
            }

            .stat-card {
                padding: 1.2rem;
            }

            .stat-card .icon {
                font-size: 2rem;
            }

            .stat-card .value {
                font-size: 1.8rem;
            }

            .stat-card .label {
                font-size: 0.9rem;
            }
        }

        .orders-container {
            padding: 20px;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            overflow: hidden;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            background: linear-gradient(45deg, var(--primary-color), #6f42c1);
            color: white;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        .order-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1));
        }

        .order-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-body {
            padding: 1.5rem;
        }

        .customer-info {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .customer-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .customer-info-item i {
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-card {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .detail-card .label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .detail-card .value {
            color: var(--dark-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-card i {
            color: var(--primary-color);
        }

        .order-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }

        .btn-action {
            flex: 1;
            min-width: 110px;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-action:hover::before {
            width: 300%;
            height: 300%;
        }

        .btn-action i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2);
        }

        .btn-action.btn-primary {
            background: linear-gradient(45deg, #4e73df, #6f42c1);
        }

        .btn-action.btn-success {
            background: linear-gradient(45deg, #1cc88a, #15a675);
        }

        .btn-action.btn-warning {
            background: linear-gradient(45deg, #f6c23e, #f4b619);
        }

        .btn-action.btn-danger {
            background: linear-gradient(45deg, #e74a3b, #d52a1a);
        }

        .btn-action.btn-info {
            background: linear-gradient(45deg, #36b9cc, #2a9aad);
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-action:active {
            transform: translateY(0);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-action.disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-action.disabled:hover {
            transform: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* تحسين أزرار الإجراءات في معلومات السائق */
        .driver-contact {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .driver-contact .btn {
            flex: 1;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
            border: none;
        }

        .driver-contact .btn i {
            font-size: 1.1rem;
        }

        .driver-contact .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        /* تحسين أزرار الحالة */
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .status-badge i {
            font-size: 0.8rem;
        }

        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        /* تحسين حالات الطلب */
        .status-pending { 
            background: linear-gradient(45deg, var(--warning-color), #f4b619);
            color: white;
        }
        .status-accepted { 
            background: linear-gradient(45deg, var(--info-color), #2a9aad);
            color: white;
        }
        .status-in-transit { 
            background: linear-gradient(45deg, var(--primary-color), #6f42c1);
            color: white;
        }
        .status-delivered { 
            background: linear-gradient(45deg, var(--success-color), #15a675);
            color: white;
        }
        .status-cancelled { 
            background: linear-gradient(45deg, var(--danger-color), #d52a1a);
            color: white;
        }

        .driver-info {
            background: linear-gradient(45deg, #e3f2fd, #bbdefb);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .driver-info-header {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .driver-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .payment-info {
            background: linear-gradient(45deg, #e8f5e9, #c8e6c9);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 500;
        }

        .payment-status.paid {
            background: var(--success-color);
            color: white;
        }

        .payment-status.unpaid {
            background: var(--warning-color);
            color: white;
        }

        @media (max-width: 768px) {
            .customer-info,
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .order-actions {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }

            .stats-cards {
                grid-template-columns: 1fr;
            }
        }

        .search-and-actions {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: none;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.95);
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .search-box input:focus {
            outline: none;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .add-order-btn {
            padding: 0.8rem 2rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(45deg, var(--success-color), #15a675);
            color: white;
            font-weight: 500;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            white-space: nowrap;
        }

        .add-order-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .add-order-btn i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .add-order-btn:hover i {
            transform: scale(1.2);
        }

        @media (max-width: 768px) {
            .search-and-actions {
                flex-direction: column;
                padding: 1rem;
            }

            .search-box {
                min-width: 100%;
            }

            .add-order-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Staff Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-4">
                        <?php if($company['logo']): ?>
                            <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="Company Logo" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <h2 class="mb-0"><?php echo htmlspecialchars($staff['name']); ?></h2>
                            <div class="text-white-50 mb-2">
                                <?php echo $staff['role'] === 'order_manager' ? 'مدير طلبات' : 'موظف'; ?>
                                - <?php echo htmlspecialchars($company['name']); ?>
                            </div>
                            <div class="profile-info">
                                <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?></span>
                                <?php if ($staff['phone']): ?>
                                    <span><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($staff['phone']); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-clock-history"></i> آخر دخول: <?php echo date('Y-m-d H:i', strtotime($staff['last_login'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <div class="action-buttons">
                        <button type="button" class="action-button warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key"></i> تغيير كلمة المرور
                        </button>
                        <a href="complaints.php" class="action-button warning">
                            <?php if ($active_complaints > 0): ?>
                                <span class="badge"><?php echo $active_complaints; ?></span>
                            <?php endif; ?>
                            <i class="bi bi-exclamation-circle"></i>
                            الشكاوى
                        </a>
                        <a href="logout.php" class="action-button danger">
                            <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="value"><?php echo $stats['pending_count']; ?></div>
                    <div class="label">قيد الانتظار</div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="value"><?php echo $stats['active_count']; ?></div>
                    <div class="label">جاري التوصيل</div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="value"><?php echo $stats['delivered_count']; ?></div>
                    <div class="label">تم التوصيل</div>
                </div>
                <div class="stat-card">
                    <div class="icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="value"><?php echo $stats['total_requests']; ?></div>
                    <div class="label">إجمالي الطلبات</div>
                </div>
            </div>
        </div>
    </div>
 
    <div class="container mt-4">
        <!-- Search and Actions Section -->
        <div class="search-and-actions">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" id="orderSearch" placeholder="البحث في الطلبات..." onkeyup="performSearch()">
            </div>
            <button type="button" class="add-order-btn" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                <i class="bi bi-plus-circle"></i>
                طلب جديد
            </button>
        </div>

        <!-- حاوية الطلبات -->
        <div class="orders-container">
            <div class="row">
                <?php foreach ($requests as $request): ?>
                <div class="col-lg-6 mb-4">
                    <div class="order-card">
                        <div class="order-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>
                                    <i class="bi bi-box-seam"></i>
                                    طلب #<?php echo $request['order_number']; ?>
                                </h5>
                                <?php 
                                $status_class = match($request['status']) {
                                    'pending' => 'status-pending',
                                    'accepted' => 'status-accepted',
                                    'in_transit' => 'status-in-transit',
                                    'delivered' => 'status-delivered',
                                    'cancelled' => 'status-cancelled',
                                    default => 'status-pending'
                                };
                                $status_text = match($request['status']) {
                                    'pending' => 'قيد الانتظار',
                                    'accepted' => 'تم القبول',
                                    'in_transit' => 'جاري التوصيل',
                                    'delivered' => 'تم التوصيل',
                                    'cancelled' => 'ملغي',
                                    default => 'غير معروف'
                                };
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i class="bi bi-circle-fill"></i>
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <!-- معلومات العميل -->
                            <div class="customer-info">
                                <div class="customer-info-item">
                                    <i class="bi bi-person-circle"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo $request['customer_name']; ?></div>
                                        <small class="text-muted">اسم العميل</small>
                                    </div>
                                </div>
                                <div class="customer-info-item">
                                    <i class="bi bi-telephone"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo $request['customer_phone']; ?></div>
                                        <small class="text-muted">رقم الهاتف</small>
                                    </div>
                                </div>
                            </div>

                            <!-- تفاصيل الطلب -->
                            <div class="order-details">
                                <div class="detail-card">
                                    <div class="label">موقع الاستلام</div>
                                    <div class="value">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo $request['pickup_location']; ?>
                                    </div>
                                </div>
                                <div class="detail-card">
                                    <div class="label">موقع التوصيل</div>
                                    <div class="value">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo $request['delivery_location']; ?>
                                    </div>
                                </div>
                                <div class="detail-card">
                                    <div class="label">تاريخ التوصيل</div>
                                    <div class="value">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('Y-m-d', strtotime($request['delivery_date'])); ?>
                                    </div>
                                </div>
                                <div class="detail-card">
                                    <div class="label">التكلفة</div>
                                    <div class="value">
                                        <i class="bi bi-currency-dollar"></i>
                                        <?php echo number_format($request['total_cost'], 2); ?> ريال
                                    </div>
                                </div>
                            </div>

                            <!-- معلومات السائق -->
                            <?php if ($request['driver_id']): ?>
                            <div class="driver-info">
                                <div class="driver-info-header">
                                    <i class="bi bi-person-badge"></i>
                                    معلومات السائق
                                </div>
                                <div class="driver-details">
                                    <div>
                                        <i class="bi bi-person"></i>
                                        <?php echo $request['driver_name']; ?>
                                    </div>
                                    <div>
                                        <i class="bi bi-telephone"></i>
                                        <?php echo $request['driver_phone']; ?>
                                    </div>
                                </div>
                                <div class="driver-contact">
                                    <button class="btn btn-sm btn-info" onclick="window.location.href='tel:<?php echo $request['driver_phone']; ?>'">
                                        <i class="bi bi-telephone"></i> اتصال
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="openDriverWhatsApp('<?php echo $request['driver_phone']; ?>', '<?php echo $request['driver_name']; ?>', '<?php echo $request['order_number']; ?>')">
                                        <i class="bi bi-whatsapp"></i> واتساب
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- معلومات الدفع -->
                            <div class="payment-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-credit-card"></i>
                                        طريقة الدفع: <?php echo $request['payment_method']; ?>
                                    </div>
                                    <span class="payment-status <?php echo $request['payment_status'] === 'paid' ? 'paid' : 'unpaid'; ?>">
                                        <i class="bi bi-<?php echo $request['payment_status'] === 'paid' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                        <?php echo $request['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="order-actions">
                                <button class="btn-action btn-primary" onclick="trackOrder('<?php echo $request['order_number']; ?>')">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>تتبع</span>
                                </button>
                                
                                <?php if ($request['status'] === 'pending'): ?>
                                <button class="btn-action btn-warning" onclick="editOrder(<?php echo $request['id']; ?>)">
                                    <i class="bi bi-pencil-fill"></i>
                                    <span>تعديل</span>
                                </button>
                                <button class="btn-action btn-danger" onclick="cancelOrder(<?php echo $request['id']; ?>)">
                                    <i class="bi bi-x-circle-fill"></i>
                                    <span>إلغاء</span>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn-action btn-success" onclick="openWhatsApp(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'phone' => $request['customer_phone'],
                                        'orderNumber' => $request['order_number'],
                                        'customerName' => $request['customer_name'],
                                        'pickupLocation' => $request['pickup_location'],
                                        'deliveryLocation' => $request['delivery_location'],
                                        'deliveryDate' => date('Y-m-d', strtotime($request['delivery_date'])),
                                        'totalCost' => $request['total_cost'],
                                        'status' => $status_text
                                    ])); 
                                ?>)">
                                    <i class="bi bi-whatsapp"></i>
                                    <span>واتساب</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Include modals -->
    <?php include 'modals/order_modals.php'; ?>

    <!-- Modal تغيير كلمة المرور -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        تغيير كلمة المرور
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="changePasswordForm" method="post" action="ajax/change_password.php">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>تنبيه!</strong> يرجى اختيار كلمة مرور قوية وآمنة.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الحالية</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">كلمة المرور الجديدة</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">يجب أن تحتوي على 8 أحرف على الأقل</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" required>
                                <button type="button" class="btn btn-outline-secondary toggle-password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check2-circle"></i> تغيير كلمة المرور
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- إضافة عنصر التنبيه -->
    <div id="alertFloat" class="alert-float">
        <i class="bi bi-check-circle-fill me-2"></i>
        <span id="alertMessage"></span>
        <button type="button" class="close-btn" onclick="hideAlert()">×</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script>
        function submitNewOrder() {
            const form = document.getElementById('newOrderForm');
            const formData = new FormData(form);
            
            fetch('ajax/create_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal and reload page
                    bootstrap.Modal.getInstance(document.getElementById('newRequestModal')).hide();
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إنشاء الطلب');
            });
        }

        function viewOrderDetails(orderId) {
            const detailsRow = document.getElementById('details-' + orderId);
            if (detailsRow.classList.contains('show')) {
                detailsRow.classList.remove('show');
            } else {
                // Hide all other detail rows first
                document.querySelectorAll('.order-details-row.show').forEach(row => {
                    row.classList.remove('show');
                });
                // Show the clicked row
                detailsRow.classList.add('show');
            }
        }

        function editOrder(orderId) {
            // Fetch order details
            fetch('ajax/get_order.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill the edit modal with order data
                        populateEditForm(data.order);
                        // Show the edit modal
                        new bootstrap.Modal(document.getElementById('editOrderModal')).show();
                    } else {
                        showAlert('danger', data.message || 'حدث خطأ في جلب بيانات الطلب');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'حدث خطأ في جلب بيانات الطلب');
                });
        }

        function populateEditForm(order) {
            // Set the order ID
            document.getElementById('edit_order_id').value = order.id;
            
            // Set order type
            document.getElementById('edit_order_type').value = order.order_type;
            
            // Set customer details
            document.getElementById('edit_customer_name').value = order.customer_name;
            document.getElementById('edit_customer_phone').value = order.customer_phone;
            
            // Set delivery date and time
            const deliveryDateTime = new Date(order.delivery_date);
            document.getElementById('edit_delivery_date').value = deliveryDateTime.toISOString().split('T')[0];
            document.getElementById('edit_delivery_time').value = deliveryDateTime.toTimeString().slice(0,5);
            
            // Set locations
            document.getElementById('edit_pickup_location').value = order.pickup_location;
            if (order.pickup_location_link) {
                document.getElementById('edit_pickup_location_link').value = order.pickup_location_link;
            }
            
            document.getElementById('edit_delivery_location').value = order.delivery_location;
            if (order.delivery_location_link) {
                document.getElementById('edit_delivery_location_link').value = order.delivery_location_link;
            }
            
            // Set other details
            document.getElementById('edit_items_count').value = order.items_count;
            document.getElementById('edit_total_cost').value = order.total_cost;
            document.getElementById('edit_payment_method').value = order.payment_method;
            
            // Set fragile checkbox
            document.getElementById('edit_is_fragile').checked = order.is_fragile == 1;
            
            // Set additional notes
            if (order.additional_notes) {
                document.getElementById('edit_additional_notes').value = order.additional_notes;
            }
        }

        function cancelOrder(orderId) {
            if (confirm('هل أنت متأكد من إلغاء هذا الطلب؟')) {
                fetch('ajax/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('تم إلغاء الطلب بنجاح');
                        location.reload();
                    } else {
                        showAlert(data.message || 'حدث خطأ أثناء إلغاء الطلب', 'error');
                    }
                })
                .catch(error => {
                    showAlert('حدث خطأ في إرسال البيانات', 'error');
                });
            }
        }

        // تفعيل/تعطيل رؤية كلمة المرور
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
        
        // معالجة نموذج تغيير كلمة المرور
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('تم تغيير كلمة المرور بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
                    this.reset();
                } else {
                    showAlert(data.message || 'حدث خطأ أثناء تغيير كلمة المرور');
                }
            })
            .catch(error => {
                showAlert('حدث خطأ في إرسال البيانات');
            });
        });

        function openWhatsApp(orderData) {
            let phone = orderData.phone.replace(/^0+/, '');
            if (!phone.startsWith('966')) {
                phone = '966' + phone;
            }
            
            // Get the current domain
            const domain = window.location.protocol + '//' + window.location.host;
            const trackingUrl = domain + '/track_order.php?order_number=' + orderData.orderNumber;
            
            const message = `
مرحباً ${orderData.customerName}،
تفاصيل طلبك رقم: ${orderData.orderNumber}

موقع الاستلام: ${orderData.pickupLocation}
موقع التوصيل: ${orderData.deliveryLocation}
تاريخ التوصيل: ${orderData.deliveryDate}
التكلفة: ${orderData.totalCost} ريال
الحالة: ${orderData.status}

يمكنك تتبع طلبك من خلال الرابط التالي:
${trackingUrl}

شكراً لاختيارك خدماتنا!
            `.trim();

            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        function openRating(requestId, driverId) {
            document.getElementById('rate_request_id').value = requestId;
            document.getElementById('rate_driver_id').value = driverId;
            
            // Reset form
            document.getElementById('rateDriverForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('rateDriverModal')).show();
        }

        function openComplaint(requestId, driverId) {
            console.log('Opening complaint modal with:', { requestId, driverId });
            
            document.getElementById('complaint_request_id').value = requestId;
            document.getElementById('complaint_driver_id').value = driverId;
            
            // Reset form
            document.getElementById('complaintForm').reset();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('complaintModal')).show();
            
            // Log the values after setting them
            console.log('Form values after setting:', {
                request_id: document.getElementById('complaint_request_id').value,
                driver_id: document.getElementById('complaint_driver_id').value
            });
        }

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert-float alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="close-btn" onclick="this.parentElement.remove()">×</button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // دالة لإخفاء التنبيه
        function hideAlert() {
            const alert = document.getElementById('alertFloat');
            alert.classList.remove('show');
        }

        function revertOrder(orderId) {
            if (confirm('هل أنت متأكد من إرجاع هذا الطلب إلى حالة الانتظار؟')) {
                fetch('ajax/revert_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        request_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('تم إرجاع الطلب إلى حالة الانتظار بنجاح');
                        location.reload();
                    } else {
                        showAlert(data.message || 'حدث خطأ أثناء إرجاع الطلب', 'error');
                    }
                })
                .catch(error => {
                    showAlert('حدث خطأ في إرسال البيانات', 'error');
                });
            }
        }

        function updateOrder(orderId) {
            // Clear previous errors
            clearFormErrors();
            
            const form = document.getElementById('editOrderForm');
            const formData = new FormData(form);
            
            // Validate form before submission
            if (!validateOrderForm()) {
                return false;
            }

            // Show confirmation dialog
            if (!confirm('هل أنت متأكد من تحديث بيانات الطلب؟')) {
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري التحديث...';
            submitBtn.disabled = true;

            // Show loading overlay
            showLoadingOverlay();

            fetch('ajax/update_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message with animation
                    showAlert('success', 'تم تحديث الطلب بنجاح');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editOrderModal'));
                    modal.hide();
                    
                    // Refresh the specific row instead of full page reload
                    updateOrderRow(data.order);
                } else {
                    // Show error messages
                    if (data.errors && Array.isArray(data.errors)) {
                        data.errors.forEach(error => {
                            showFormError(error);
                        });
                    } else {
                        showAlert('danger', data.message || 'حدث خطأ أثناء تحديث الطلب');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                // Hide loading overlay
                hideLoadingOverlay();
            });
        }

        function validateOrderForm() {
            const form = document.getElementById('editOrderForm');
            let isValid = true;

            // Validate customer name
            const customerName = form.querySelector('[name="customer_name"]');
            if (!customerName.value.trim()) {
                showFormError('اسم العميل مطلوب');
                isValid = false;
            }

            // Validate phone number
            const phoneNumber = form.querySelector('[name="customer_phone"]');
            if (!phoneNumber.value.match(/^[0-9]{10}$/)) {
                showFormError('رقم الهاتف يجب أن يتكون من 10 أرقام');
                isValid = false;
            }

            // Validate delivery date and time
            const deliveryDate = form.querySelector('[name="delivery_date"]');
            const deliveryTime = form.querySelector('[name="delivery_time"]');
            if (deliveryDate.value && deliveryTime.value) {
                const deliveryDateTime = new Date(deliveryDate.value + ' ' + deliveryTime.value);
                if (deliveryDateTime < new Date()) {
                    showFormError('لا يمكن تحديد تاريخ ووقت توصيل في الماضي');
                    isValid = false;
                }
            }

            // Validate items count
            const itemsCount = form.querySelector('[name="items_count"]');
            if (!itemsCount.value || itemsCount.value < 1) {
                showFormError('عدد القطع يجب أن يكون رقماً موجباً');
                isValid = false;
            }

            // Validate total cost - properly handle zero values
            const totalCost = form.querySelector('[name="total_cost"]');
            const costValue = totalCost.value.trim();
            if (costValue === '') {
                showFormError('التكلفة الإجمالية مطلوبة');
                isValid = false;
            } else if (isNaN(costValue) || parseFloat(costValue) < 0) {
                showFormError('التكلفة الإجمالية يجب أن تكون رقماً صفر أو أكبر');
                isValid = false;
            }

            return isValid;
        }

        function showLoadingOverlay() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoadingOverlay() {
            const overlay = document.querySelector('.loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function updateOrderRow(orderData) {
            const row = document.querySelector(`tr[data-order-id="${orderData.id}"]`);
            if (row) {
                // Update each cell with new data
                row.querySelector('.order-number').textContent = orderData.order_number;
                row.querySelector('.customer-info').innerHTML = `
                    <div>${orderData.customer_name}</div>
                    <div class="customer-phone">${orderData.customer_phone}</div>
                `;
                // Update other cells...

                // Add highlight animation
                row.classList.add('highlight-update');
                setTimeout(() => {
                    row.classList.remove('highlight-update');
                }, 2000);
            }
        }

        function clearFormErrors() {
            // Remove all existing error messages
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        }

        function showFormError(error) {
            // Find the field mentioned in the error message
            const fields = {
                'نوع الطلب': 'edit_order_type',
                'اسم العميل': 'edit_customer_name',
                'رقم هاتف العميل': 'edit_customer_phone',
                'تاريخ التوصيل': 'edit_delivery_date',
                'وقت التوصيل': 'edit_delivery_time',
                'موقع الاستلام': 'edit_pickup_location',
                'موقع التوصيل': 'edit_delivery_location',
                'عدد القطع': 'edit_items_count',
                'التكلفة الإجمالية': 'edit_total_cost',
                'طريقة الدفع': 'edit_payment_method'
            };

            // Try to find the field ID based on the error message
            let fieldId = null;
            for (const [label, id] of Object.entries(fields)) {
                if (error.includes(label)) {
                    fieldId = id;
                    break;
                }
            }

            if (fieldId) {
                // Add error to specific field
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.add('is-invalid');
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = error;
                    field.parentNode.appendChild(feedback);
                }
            } else {
                // Show general error if field not found
                showAlert('danger', error);
            }
        }

        // Add form validation before submission
        document.getElementById('editOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateOrder(document.getElementById('edit_order_id').value);
        });

        // Initialize form fields when modal opens
        $('#editOrderModal').on('show.bs.modal', function (event) {
            clearFormErrors();
            const button = $(event.relatedTarget);
            const orderId = button.data('order-id');
            
            // Fetch order details and populate form
            fetch(`ajax/get_order.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditForm(data.order);
                    } else {
                        showAlert('danger', data.message);
                        $('#editOrderModal').modal('hide');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'حدث خطأ أثناء تحميل بيانات الطلب');
                    $('#editOrderModal').modal('hide');
                });
        });

        // Clear form when modal closes
        $('#editOrderModal').on('hidden.bs.modal', function () {
            document.getElementById('editOrderForm').reset();
            clearFormErrors();
        });

        function trackOrder(orderNumber) {
            // استخدام المسار النسبي للوصول إلى ملف التتبع
            const trackingUrl = '../track_order.php?order_number=' + orderNumber;
            
            // فتح صفحة التتبع في نافذة جديدة
            window.open(trackingUrl, '_blank');
        }

        // تحسين وظيفة البحث
        function performSearch() {
            const searchInput = document.getElementById('orderSearch');
            const searchValue = searchInput.value.toLowerCase().trim();
            const orderCards = document.querySelectorAll('.order-card');
            let hasResults = false;

            orderCards.forEach(card => {
                const orderNumber = card.querySelector('h5').textContent.toLowerCase();
                const customerName = card.querySelector('.customer-info-item:first-child .fw-bold').textContent.toLowerCase();
                const customerPhone = card.querySelector('.customer-info-item:last-child .fw-bold').textContent.toLowerCase();
                const pickupLocation = card.querySelector('.detail-card:nth-child(1) .value').textContent.toLowerCase();
                const deliveryLocation = card.querySelector('.detail-card:nth-child(2) .value').textContent.toLowerCase();
                
                const matchesSearch = orderNumber.includes(searchValue) || 
                                    customerName.includes(searchValue) || 
                                    customerPhone.includes(searchValue) ||
                                    pickupLocation.includes(searchValue) ||
                                    deliveryLocation.includes(searchValue);

                const parentCol = card.closest('.col-lg-6');
                if (matchesSearch) {
                    parentCol.style.display = '';
                    hasResults = true;
                } else {
                    parentCol.style.display = 'none';
                }
            });

            // إظهار رسالة عندما لا توجد نتائج
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (!hasResults && searchValue) {
                if (!noResultsMessage) {
                    const message = document.createElement('div');
                    message.id = 'noResultsMessage';
                    message.className = 'col-12 text-center py-5';
                    message.innerHTML = `
                        <div class="no-results-container">
                            <i class="bi bi-search" style="font-size: 3rem; color: rgba(255,255,255,0.7);"></i>
                            <h4 class="mt-3 text-white">لا توجد نتائج</h4>
                            <p class="text-white-50">لم يتم العثور على طلبات تطابق بحثك</p>
                            <button onclick="clearSearch()" class="btn btn-light mt-3">
                                <i class="bi bi-x-circle"></i> مسح البحث
                            </button>
                        </div>
                    `;
                    document.querySelector('.orders-container .row').appendChild(message);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('orderSearch');
            searchInput.value = '';
            performSearch();
            searchInput.focus();
        }

        // تحسين تجربة البحث
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('orderSearch');
            
            // إضافة تأخير بسيط للبحث لتحسين الأداء
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            });

            // البحث عند الضغط على Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });

            // إضافة زر مسح البحث داخل حقل البحث
            const clearButton = document.createElement('button');
            clearButton.type = 'button';
            clearButton.className = 'clear-search-btn';
            clearButton.innerHTML = '<i class="bi bi-x"></i>';
            clearButton.style.cssText = `
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: var(--secondary-color);
                cursor: pointer;
                padding: 0.5rem;
                display: none;
                transition: color 0.3s ease;
            `;
            clearButton.addEventListener('mouseover', function() {
                this.style.color = 'var(--danger-color)';
            });
            clearButton.addEventListener('mouseout', function() {
                this.style.color = 'var(--secondary-color)';
            });
            clearButton.onclick = clearSearch;

            searchInput.parentElement.appendChild(clearButton);

            // إظهار/إخفاء زر المسح
            searchInput.addEventListener('input', function() {
                clearButton.style.display = this.value ? 'block' : 'none';
            });
        });
    </script>
</body>
</html> 