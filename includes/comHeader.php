<?php

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM company_notifications 
    WHERE company_id = ? AND is_read = 0
");
$stmt->execute([$company_id]);
$unread_notifications = $stmt->fetchColumn();


// Get recent notifications with complaint information
$stmt = $conn->prepare("
    SELECT 
        n.*,
        CASE 
            WHEN n.type = 'complaint_response' THEN c.complaint_number 
            ELSE NULL 
        END as complaint_number,
        CASE 
            WHEN n.type = 'complaint_response' THEN '#'
            ELSE n.link 
        END as link,
        CASE 
            WHEN n.is_read = 0 THEN 0
            ELSE 1
        END as is_read
    FROM company_notifications n
    LEFT JOIN complaints c ON n.reference_id = c.id
    WHERE n.company_id = ? 
    ORDER BY n.created_at DESC 
    LIMIT 5
");
$stmt->execute([$company_id]);
$notifications = $stmt->fetchAll();

// Add this after the ب query
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

$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM complaints 
    WHERE company_id = ? 
    AND status IN ('new', 'in_progress')
");
$stmt->execute([$_SESSION['company_id']]);
$ب = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT name, logo, delivery_fee FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
        }
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand img {
            height: 40px;
            width: auto;
            margin-left: 10px;
        }
        .stat-card {
            border-radius: 15px;
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        .table {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .order-details-row {
            display: none;
            background: #f8fafc !important;
        }
        .order-details-row.show {
            display: table-row;
        }
        .order-details-content {
            padding: 2rem;
        }
        .invoice-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            overflow: hidden;
            margin: 0.5rem;
        }
        .invoice-section {
            margin-bottom: 0;
        }
        .invoice-section-header {
            padding: 1rem 1.5rem;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
        }
        .invoice-section-header.primary {
            background: linear-gradient(45deg, #4158D0, #C850C0);
        }
        .invoice-section-header.secondary {
            background: linear-gradient(45deg, #0082c8, #0082c8);
        }
        .invoice-section-content {
            padding: 1.5rem;
            background: white;
        }
        .invoice-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .invoice-list dt {
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        .invoice-list dd {
            font-size: 1.1rem;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 6px;
            margin-right: 0;
        }
        .invoice-list dd:last-child {
            margin-bottom: 0;
        }
        .payment-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
        }
        .payment-badge.unpaid {
            background: #fff3cd;
            color: #856404;
        }
        .payment-badge.paid {
            background: #d4edda;
            color: #155724;
        }
        @media (max-width: 768px) {
            .order-details-content {
                padding: 1rem;
            }
            .invoice-section-header {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            .invoice-section-content {
                padding: 1rem;
            }
            .invoice-list dt {
                font-size: 0.85rem;
            }
            .invoice-list dd {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
            }
        }
        .btn-group .btn {
            margin: 0 2px;
        }
        .company-name {
            font-weight: bold;
            color: #fff;
            margin-right: 10px;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
        .badge.bg-warning {
            background-color: #ffeeba !important;
            color: #856404;
        }
        .badge.bg-success {
            background-color: #d4edda !important;
            color: #155724;
        }
        .badge.bg-primary {
            background-color: #cce5ff !important;
            color: #004085;
        }
        .badge.bg-danger {
            background-color: #f8d7da !important;
            color: #721c24;
        }
        .btn-group .btn {
            padding: 0.375rem 0.75rem;
            border-radius: 6px !important;
            margin: 0 2px;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
            color: white;
        }
        .table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }
        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }
        .customer-info {
            line-height: 1.2;
        }
        .customer-phone {
            color: #6c757d;
            font-size: 0.875rem;
        }
          .alert-float {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            min-width: 300px;
            z-index: 9999;
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.5s ease-out;
            display: none;
        }
        .alert-float.show {
            display: block;
        }

        .alert-float .close-btn {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .alert-float .close-btn:hover {
            opacity: 1;
            transform: translateY(-50%) rotate(90deg);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .highlight-update {
            animation: highlightRow 2s ease-in-out;
        }

        @keyframes highlightRow {
            0% { background-color: #fff; }
            50% { background-color: #e3f2fd; }
            100% { background-color: #fff; }
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .alert-float {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
 <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="profile.php">
                <?php if (!empty($company['logo'])): ?>
                    <img src="../uploads/company_logos/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="rounded">
                <?php else: ?>
                    <i class="bi bi-building"></i>
                <?php endif; ?>
                <span class="company-name"><?php echo htmlspecialchars($company['name']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'ordersadmin.php' ? 'active' : ''; ?>" href="ordersadmin.php">
                            <i class="bi bi-list-check"></i> الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                            <i class="bi bi-bar-chart"></i> تقارير مفصلة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'staff.php' ? 'active' : ''; ?>" href="staff.php">
                            <i class="bi bi-people"></i> إدارة الموظفين
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'complaints.php' ? 'active' : ''; ?>" href="complaints.php">
                            <i class="bi bi-exclamation-circle"></i> الشكاوى
                            <?php if ($ب > 0): ?>
                                <span class="badge bg-danger"><?php echo $ب; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="badge bg-danger"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                        <h6 class="dropdown-header">الإشعارات</h6>
                        <?php if (empty($notifications)): ?>
                            <div class="dropdown-item text-muted">لا توجد إشعارات</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                   onclick="handleNotificationClick(<?php echo $notification['id']; ?>, '<?php echo htmlspecialchars($notification['link']); ?>', event)"
                                   data-notification-id="<?php echo $notification['id']; ?>"
                                   data-type="<?php echo htmlspecialchars($notification['type']); ?>"
                                   <?php if ($notification['type'] === 'complaint_response' && $notification['complaint_number']): ?>
                                   data-complaint-number="<?php echo htmlspecialchars($notification['complaint_number']); ?>"
                                   <?php endif; ?>>
                                    <div class="notification-content">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="#" onclick="markAllNotificationsAsRead(event)">
                                تعليم الكل كمقروء
                            </a>
                        <?php endif; ?>
                    </div>
                </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
