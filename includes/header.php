<?php
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// جلب عدد الإشعارات غير المقروءة لجميع المستخدمين
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$stmt->execute();
$unread_notifications = $stmt->fetchColumn();

// Get current language direction
$dir = $_SESSION['lang'] === 'ar' ? 'rtl' : 'ltr';
$lang = $_SESSION['lang'];
?>
<!DOCTYPE html>
<html dir="<?php echo $dir; ?>" lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo __('system_title'); ?></title>
    
    <!-- CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/responsive.css" rel="stylesheet">
    <style>
    /* تعديلات السايدبار */
    .main-sidebar {
        width: 250px;
        z-index: 1040;
        display: block;
        position: fixed;
    }

    html[dir="rtl"] .main-sidebar {
        right: 0;
        transform: translateX(100%);
    }

    html[dir="ltr"] .main-sidebar {
        left: 0;
        transform: translateX(-100%);
    }

    .main-sidebar.show {
        transform: translateX(0) !important;
    }

    .content-wrapper {
        transition: margin 0.3s ease;
    }

    @media (min-width: 992px) {
        html[dir="rtl"] .content-wrapper {
            margin-right: 250px;
            margin-left: 0;
        }

        html[dir="ltr"] .content-wrapper {
            margin-left: 250px;
            margin-right: 0;
        }
    }

    @media (max-width: 991px) {
        .content-wrapper {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .mobile-menu-toggle {
            position: fixed;
            top: 1rem;
            z-index: 1041;
        }

        html[dir="rtl"] .mobile-menu-toggle {
            right: 1rem;
        }

        html[dir="ltr"] .mobile-menu-toggle {
            left: 1rem;
        }
    }

    /* تعديلات الهوامش */
    html[dir="rtl"] .me-2,
    html[dir="rtl"] .me-3 {
        margin-left: 0.5rem !important;
        margin-right: 0 !important;
    }

    html[dir="ltr"] .me-2,
    html[dir="ltr"] .me-3 {
        margin-right: 0.5rem !important;
        margin-left: 0 !important;
    }

    /* Language Switcher */
    .dropdown-menu {
        text-align: left;
    }

    html[dir="rtl"] .dropdown-menu {
        text-align: right;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    /* Language Specific Styles */
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

    html[dir="rtl"] .notification-badge {
        right: -5px;
    }

    html[dir="ltr"] .notification-badge {
        left: -5px;
    }

    .notifications-dropdown {
        position: absolute;
        top: 100%;
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

    html[dir="rtl"] .notifications-dropdown {
        right: -230px;
    }

    html[dir="ltr"] .notifications-dropdown {
        left: -230px;
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

    html[dir="rtl"] .notification-content {
        text-align: right;
    }

    html[dir="ltr"] .notification-content {
        text-align: left;
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
        width: 16px;
        height: 16px;
        background: #fff;
        transform: rotate(45deg);
        border-top: 1px solid rgba(230, 126, 34, 0.1);
        border-left: 1px solid rgba(230, 126, 34, 0.1);
    }

    html[dir="rtl"] .notifications-dropdown::before {
        right: 310px;
    }

    html[dir="ltr"] .notifications-dropdown::before {
        left: 310px;
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

    /* Language Switcher Styles */
    .dropdown-item {
        padding: 0.5rem 1rem;
        color: #333;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #e67e22;
    }

    .dropdown-item.active {
        background-color: #fff5e6;
        color: #e67e22;
    }

    .dropdown-item .fa-check {
        color: #e67e22;
        width: 16px;
    }

    #languageDropdown {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        border-color: #e67e22;
        color: #e67e22;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 120px;
        justify-content: center;
    }

    #languageDropdown:hover,
    #languageDropdown:focus {
        background-color: #e67e22;
        color: white;
        box-shadow: 0 2px 5px rgba(230, 126, 34, 0.2);
    }

    #languageDropdown .fa-globe {
        font-size: 1rem;
    }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button id="mainMobileMenuToggle" class="mobile-menu-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>

    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div class="page-title">
                <h1 class="h3 mb-0 text-gray-800 system-title">
                    <?php
                    $current_page = basename($_SERVER['PHP_SELF'], '.php');
                    echo __($current_page);
                    ?>
                </h1>
            </div>
            
            <div class="header-actions d-flex align-items-center">
                <!-- Language Switcher -->
                <div class="dropdown me-3">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown">
                        <?php if ($_SESSION['lang'] === 'ar'): ?>
                            <i class="fas fa-globe"></i> AR
                        <?php else: ?>
                            <i class="fas fa-globe"></i> En
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button type="button" class="dropdown-item <?php echo $_SESSION['lang'] === 'ar' ? 'active' : ''; ?>" onclick="changeLanguage('ar')">
                                <i class="fas fa-check me-2 <?php echo $_SESSION['lang'] === 'ar' ? '' : 'invisible'; ?>"></i>
                                AR
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item <?php echo $_SESSION['lang'] === 'en' ? 'active' : ''; ?>" onclick="changeLanguage('en')">
                                <i class="fas fa-check me-2 <?php echo $_SESSION['lang'] === 'en' ? '' : 'invisible'; ?>"></i>
                                En
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Notifications Dropdown -->
                <div class="dropdown me-3">
                    <a class="nav-link position-relative notification-icon" href="#" role="button">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge" id="notificationCount"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notifications-dropdown">
                        <div class="notifications-header">
                            <span><?php echo __('notifications'); ?></span>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="badge"><?php echo $unread_notifications; ?> <?php echo $_SESSION['lang'] === 'ar' ? 'جديد' : 'new'; ?></span>
                            <?php endif; ?>
                        </div>
                        <div id="notificationsContainer">
                            <!-- Notifications will be loaded here -->
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
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mainMobileMenuToggle');
    const sidebar = document.querySelector('.main-sidebar');
    let overlay = document.querySelector('.sidebar-overlay');

    // Create overlay if it doesn't exist
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }

    // Check localStorage for sidebar state
    function checkSidebarState() {
        if (window.innerWidth <= 991) {
            const sidebarState = localStorage.getItem('sidebarOpen');
            if (sidebarState === 'true') {
                showSidebar();
            } else {
                hideSidebar();
            }
        }
    }

    // Show sidebar
    function showSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        localStorage.setItem('sidebarOpen', 'true');
    }

    // Hide sidebar
    function hideSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
        localStorage.setItem('sidebarOpen', 'false');
    }

    // Toggle sidebar
    function toggleSidebar() {
        if (sidebar.classList.contains('show')) {
            hideSidebar();
        } else {
            showSidebar();
        }
    }

    // Add click event to menu toggle button
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', hideSidebar);
    }

    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991 && 
            sidebar && sidebar.classList.contains('show') && 
            !sidebar.contains(e.target) && 
            mobileMenuToggle && !mobileMenuToggle.contains(e.target)) {
            hideSidebar();
        }
    });

    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            hideSidebar();
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            hideSidebar();
        }
    });

    // Check sidebar state on page load
    checkSidebarState();

    // Handle sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar-item');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                // Small delay before hiding to allow for smooth navigation
                setTimeout(hideSidebar, 150);
            }
        });
    });
});

function changeLanguage(lang) {
    let path = window.location.pathname.includes('/admin/') ? 'change_language.php' : '../admin/change_language.php';
    
    fetch(path, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'lang=' + lang
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            console.error('Failed to change language:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// إضافة معالج النقر للقائمة المنسدلة
document.getElementById('languageDropdown').addEventListener('click', function(e) {
    e.preventDefault();
    var dropdown = new bootstrap.Dropdown(this);
    dropdown.toggle();
});
</script>
</body>
</html>