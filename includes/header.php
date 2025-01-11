<?php
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE admin_id = ? AND is_read = 0");
$stmt->execute([$admin_id]);
$unread_notifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div class="page-title">
                <h1 class="h3 mb-0 text-gray-800">
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
                        default:
                            echo 'نظام إدارة النقل';
                    }
                    ?>
                </h1>
            </div>
            
            <div class="header-actions d-flex align-items-center">
                <!-- Notifications Dropdown -->
                <style>
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
                    right: -300px;
                    width: 400px;
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
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <span class="me-2 d-none d-lg-inline text-gray-600"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                        <img class="img-profile rounded-circle" src="assets/img/default-avatar.png" width="32" height="32">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i>الملف الشخصي</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>الإعدادات</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>تسجيل خروج</a></li>
                    </ul>
                </div>
            </div>
        </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.querySelector('.notification-icon');
    const notificationsDropdown = document.querySelector('.notifications-dropdown');
    
    // Toggle notifications dropdown
    notificationIcon.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle dropdown visibility
        notificationsDropdown.classList.toggle('show');
        
        // Add ringing animation to bell icon
        const bellIcon = this.querySelector('i');
        bellIcon.classList.add('ringing');
        setTimeout(() => bellIcon.classList.remove('ringing'), 1000);
        
        // Load notifications if dropdown is shown
        if (notificationsDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationsDropdown.contains(e.target) && !notificationIcon.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    // Function to load notifications
    function loadNotifications() {
        fetch('get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('notificationsContainer');
                    if (data.notifications.length > 0) {
                        const notificationsHtml = data.notifications.map(notification => `
                            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                                <div class="notification-content">
                                    <p>${notification.message}</p>
                                    <div class="notification-time">
                                        <i class="far fa-clock"></i>
                                        ${notification.time_ago}
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        container.innerHTML = notificationsHtml;
                    } else {
                        container.innerHTML = `
                            <div class="text-center py-4">
                                <i class="far fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted">لا توجد إشعارا�� جديدة</p>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Handle notification clicks
    document.addEventListener('click', function(e) {
        const notificationItem = e.target.closest('.notification-item');
        if (notificationItem) {
            const notificationId = notificationItem.dataset.id;
            if (notificationItem.classList.contains('unread')) {
                markNotificationAsRead(notificationId);
            }
        }
    });

    // Function to mark notification as read
    function markNotificationAsRead(notificationId) {
        fetch('ajax/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notificationItem) {
                    notificationItem.classList.remove('unread');
                }
                
                // Update notification count
                const countElement = document.getElementById('notificationCount');
                if (data.unread_count > 0) {
                    if (countElement) {
                        countElement.textContent = data.unread_count;
                    }
                } else if (countElement) {
                    countElement.remove();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Check for new notifications periodically
    setInterval(loadNotifications, 30000); // Check every 30 seconds
});

// Alert System
const showAlert = ({
    title = 'تنبيه',
    message = '',
    type = 'info',
    confirmText = 'موافق',
    cancelText = 'إلغاء',
    showCancel = false,
    onConfirm = () => {},
    onCancel = () => {}
}) => {
    const overlay = document.getElementById('alertOverlay');
    const alert = document.getElementById('customAlert');
    const titleEl = document.getElementById('alertTitle');
    const messageEl = document.getElementById('alertMessage');
    const confirmBtn = document.getElementById('alertConfirm');
    const cancelBtn = document.getElementById('alertCancel');
    
    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;
    
    // Set type
    alert.className = 'custom-alert ' + type;
    
    // Show/hide cancel button
    cancelBtn.style.display = showCancel ? 'block' : 'none';
    
    // Add event listeners
    const closeAlert = (callback) => {
        overlay.dataset.animation = 'out';
        setTimeout(() => {
            overlay.classList.remove('show');
            overlay.dataset.animation = '';
            callback();
        }, 300);
    };
    
    const confirmHandler = () => {
        closeAlert(onConfirm);
        confirmBtn.removeEventListener('click', confirmHandler);
        cancelBtn.removeEventListener('click', cancelHandler);
    };
    
    const cancelHandler = () => {
        closeAlert(onCancel);
        confirmBtn.removeEventListener('click', confirmHandler);
        cancelBtn.removeEventListener('click', cancelHandler);
    };
    
    confirmBtn.addEventListener('click', confirmHandler);
    cancelBtn.addEventListener('click', cancelHandler);
    
    // Show alert
    overlay.classList.add('show');
    overlay.dataset.animation = 'in';
};

// Example usage:
/*
showAlert({
    title: 'نجاح',
    message: 'تم حفظ التغييرات بنجاح',
    type: 'success',
    onConfirm: () => {
        console.log('��م الضغط على موافق');
    }
});

showAlert({
    title: 'تأكيد الحذف',
    message: 'هل أنت متأكد من حذف هذا العنصر؟',
    type: 'danger',
    showCancel: true,
    confirmText: 'نعم، احذف',
    cancelText: 'إلغاء',
    onConfirm: () => {
        console.log('تم تأكيد الحذف');
    },
    onCancel: () => {
        console.log('تم إلغاء الحذف');
    }
});
*/
</script>
</body>
</html>