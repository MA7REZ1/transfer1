<?php
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// جلب عدد الإشعارات غير المقروءة لجميع المستخدمين
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$stmt->execute();
$unread_notifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>لوحة التحكم</title>
    
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/responsive.css" rel="stylesheet">
    <style>
    /* تثبيت السايدبار */
    .main-sidebar {
        position: fixed !important;
        width: 250px !important;
        right: 0 !important;
        top: 0 !important;
        bottom: 0 !important;
        z-index: 1040 !important;
        transition: none !important;
        transform: none !important;
    }
    .main-sidebar * {
        transition: none !important;
    }
    .main-sidebar .nav-link {
        transition: none !important;
    }
    .main-sidebar:hover {
        width: 250px !important;
    }
    .content-wrapper {
        margin-right: 250px !important;
        transition: none !important;
    }
    body {
        overflow-x: hidden !important;
    }
    /* Mobile Menu Toggle Button */
    .mobile-menu-toggle {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1050;
        background: var(--primary-gradient);
        border: none;
        color: white;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-menu-toggle:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .mobile-menu-toggle i {
        font-size: 1.25rem;
    }

    /* Hide toggle on desktop */
    @media (min-width: 992px) {
        .mobile-menu-toggle {
            display: none;
        }
    }

    /* Overlay for mobile menu */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1030;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.show {
        display: block;
        opacity: 1;
    }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>

    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div class="page-title">
                <h1 class="h3 mb-0 text-gray-800 system-title">
    <?php
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    switch ($current_page) {
        case 'dashboard':
            echo 'لوحة التحكم';
            break;
        case 'orders':
            echo 'إدارة الطلبات';
            break;
        case 'companies':
            echo 'الشركات';
            break;
        case 'drivers':
            echo 'السائقين';
            break;
        case 'complaints':
            echo 'الشكاوى';
            break;
        case 'reports':
            echo 'التقارير';
            break;
        case 'settings':
            echo 'الإعدادات';
            break;
        case 'profile':
            echo 'الملف الشخصي';
            break;
        case 'employees':
            echo 'إدارة الموظفين';
            break;
        case 'driver_earnings_settings':
            echo 'التحصيل من السواق';
            break;
        default:
            echo 'نظام إدارة النقل';
    }
    ?>
</h1>
            </div>
            
            <div class="header-actions d-flex align-items-center">
                <!-- Notifications Dropdown -->
                 <style>
.complaints-badge {
    background-color: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 50%;
    font-size: 12px;
    display: inline-block;
    margin-right: 5px;
}

                .notification-icon {
                    color: #e67e22;
                    font-size: 20px;
                    transition: color 0.3s ease;
                }

                .notification-icon:hover {
                    color: #d35400;
                }

                .notification-badge {
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: #e67e22;
                    color: white;
                    border-radius: 50%;
                    padding: 3px 6px;
                    font-size: 10px;
                    min-width: 18px;
                    height: 18px;
                    text-align: center;
                    line-height: 12px;
                }

                .notifications-dropdown {
    position: absolute;
    top: 100%;
    right: -230px;
    width: 300px;
                    max-height: 500px;
                    overflow-y: auto;
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 2px 15px rgba(230, 126, 34, 0.2);
                    z-index: 1000;
                    margin-top: 15px;
                    display: none;
                }

                .notifications-dropdown.show {
                    display: block;
                    animation: fadeIn 0.3s ease-in-out;
                }

                .notifications-header {
                    padding: 15px 20px;
                    border-bottom: 1px solid #f3f3f3;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    background: #fff;
                    color: #e67e22;
                    font-weight: bold;
                }

                .notifications-header .badge {
                    background-color: #e67e22 !important;
                    color: white;
                }

                .notification-item {
                    padding: 15px 20px;
                    border-bottom: 1px solid #f3f3f3;
                    cursor: pointer;
                    transition: background-color 0.2s;
                }

                .notification-item:hover {
                    background-color: #fff5e6;
                }

                .notification-item.unread {
                    background-color: #fff5e6;
                }

                .notification-item.unread:hover {
                    background-color: #ffe5cc;
                }

                .notification-content {
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                }

                .notification-content p {
                    margin: 0;
                    color: #333;
                    font-size: 14px;
                    line-height: 1.5;
                }

                .notification-time {
                    font-size: 12px;
                    color: #e67e22;
                    margin-top: 5px;
                }

                .notification-time i {
                    color: #e67e22;
                }

                .notification-footer {
                    padding: 12px 15px;
                    text-align: center;
                    border-top: 1px solid #f3f3f3;
                    background: #fff;
                }

                .notification-footer a {
                    color: #e67e22;
                    text-decoration: none;
                    font-weight: bold;
                }

                .notification-footer a:hover {
                    color: #d35400;
                }

                /* تحسين مظهر شريط التمرير */
                .notifications-dropdown::-webkit-scrollbar {
                    width: 8px;
                }

                .notifications-dropdown::-webkit-scrollbar-track {
                    background: #fff5e6;
                    border-radius: 4px;
                }

                .notifications-dropdown::-webkit-scrollbar-thumb {
                    background: #e67e22;
                    border-radius: 4px;
                }

                .notifications-dropdown::-webkit-scrollbar-thumb:hover {
                    background: #d35400;
                }

                /* إضافة سهم صغير في الأعلى */
                .notifications-dropdown::before {
                    content: '';
                    position: absolute;
                    top: -8px;
                    right: 310px;
                    width: 16px;
                    height: 16px;
                    background: #fff;
                    transform: rotate(45deg);
                    border-top: 1px solid rgba(230, 126, 34, 0.1);
                    border-left: 1px solid rgba(230, 126, 34, 0.1);
                }

                /* تأثير حركي لأيقونة الجرس */
                @keyframes bellRing {
                    0% { transform: rotate(0); }
                    10% { transform: rotate(15deg); }
                    20% { transform: rotate(-15deg); }
                    30% { transform: rotate(10deg); }
                    40% { transform: rotate(-10deg); }
                    50% { transform: rotate(5deg); }
                    60% { transform: rotate(-5deg); }
                    70% { transform: rotate(2deg); }
                    80% { transform: rotate(-2deg); }
                    90% { transform: rotate(1deg); }
                    100% { transform: rotate(0); }
                }

                .notification-icon i {
                    display: inline-block;
                    transform-origin: top;
                    color: #e67e22;
                    font-size: 20px;
                    transition: color 0.3s ease;
                }

                .notification-icon i.ringing {
                    animation: bellRing 1s ease-in-out;
                }
                </style>

                <div class="dropdown me-3">
                    <a class="nav-link position-relative notification-icon" href="#" role="button">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge" id="notificationCount"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notifications-dropdown">
                        <div class="notifications-header">
                            <span>الإشعارات</span>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="badge"><?php echo $unread_notifications; ?> جديد</span>
                            <?php endif; ?>
                        </div>
                        <div id="notificationsContainer">
                            <!-- سيتم تحميل الإشعارات هنا -->
                        </div>
                        <div class="notification-footer">
                            <a href="notifications.php">عرض كل الإشعارات</a>
                        </div>
                    </div>
                </div>
                
                <!-- User Dropdown -->
                <div class="nav-item">
                    <a class="nav-link" href="admin_profile.php">
                        <span class="me-2 d-none d-lg-inline text-gray-600">
                            <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                        </span>
                        <img class="img-profile rounded-circle" src="assets/img/default-avatar.png" width="32" height="32">
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt fa-sm fa-fw"></i>
                    </a>
                </div>
            </div>
        </div>

<!-- JavaScript Files -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/responsive.js"></script>
<script src="../assets/js/notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix for modals on mobile
    const fixModalBackdrop = () => {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('show.bs.modal', function() {
                document.body.style.overflow = 'hidden';
                if (window.innerWidth < 768) {
                    document.body.style.position = 'fixed';
                    document.body.style.width = '100%';
                }
            });

            modal.addEventListener('hidden.bs.modal', function() {
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
            });
        });
    };

    // Call the function when DOM is ready
    fixModalBackdrop();

    // Also call it when new modals are dynamically added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                fixModalBackdrop();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>
</body>
</html>