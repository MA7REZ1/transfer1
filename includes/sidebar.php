<?php

// Get count of unresolved complaints
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE status = 'new'");
$stmt->execute();
$unresolved_complaints = $stmt->fetchColumn();

if ($_SESSION['admin_role'] === 'super_admin' || $_SESSION['admin_role'] === 'مدير_عام') {
?>
<!-- بداية الـ HTML -->

<div class="sidebar main-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span><?php echo __('system_title'); ?></span>
        </a>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span><?php echo __('dashboard'); ?></span>
        </a>
        
        <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span><?php echo __('orders'); ?></span>
        </a>
        
        <a href="companies.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i>
            <span><?php echo __('companies'); ?></span>
        </a>
        
        <a href="drivers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span><?php echo __('drivers'); ?></span>
        </a>
        
        <a href="complaints.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo __('complaints'); ?></span>
        </a>
        
        <a href="feedback.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt"></i>
            <span><?php echo __('feedback'); ?></span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="reports.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span><?php echo __('reports'); ?></span>
        </a>
        
        <a href="revenue.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'revenue.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span><?php echo __('revenue'); ?></span>
        </a>
        
        <a href="driver_earnings_settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'driver_earnings_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-usd"></i>
            <span><?php echo __('driver_earnings_settings'); ?></span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="employees.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span><?php echo __('employees'); ?></span>
        </a>
        
        <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span><?php echo __('profile'); ?></span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span><?php echo __('logout'); ?></span>
        </a>
    </div>
</div>
<!-- نهاية الـ HTML -->
<?php
} else {
    
    require_once '../config.php';

    $stmt = $conn->prepare("SELECT department FROM employees WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$employee = $stmt->fetch();

if ($employee && $employee['department'] === 'accounting') {
    ?><div class="sidebar main-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span><?php echo __('system_title'); ?></span>
        </a>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span><?php echo __('dashboard'); ?></span>
        </a>
        
        <a href="revenue.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'revenue.php' ? 'active' : ''; ?>">
            <i class="fas fa-dollar-sign"></i>
            <span><?php echo __('revenue'); ?></span>
        </a>

        <a href="driver_earnings_settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'driver_earnings_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span><?php echo __('driver_earnings'); ?></span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span><?php echo __('profile'); ?></span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span><?php echo __('logout'); ?></span>
        </a>
    </div>
</div> <?php
} elseif ($employee && $employee['department'] === 'drivers_supervisor') {
    ?><div class="sidebar main-sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span><?php echo __('system_title'); ?></span>
        </a>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span><?php echo __('dashboard'); ?></span>
        </a>
        
        <div class="sidebar-divider"></div>
         <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span><?php echo __('orders'); ?></span>
        </a>
        <a href="drivers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span><?php echo __('drivers'); ?></span>
        </a>
          <a href="order_analysis.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'order_analysis.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>تحليل الطلبات</span>
        </a>
        
        <a href="driver_analysis.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'driver_analysis.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>تحليل أداء السائقين</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="complaints.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>
                <?php echo __('complaints'); ?>
                <?php if ($unresolved_complaints > 0): ?>
                    <span class="complaints-badge"><?php echo $unresolved_complaints; ?></span>
                <?php endif; ?>
            </span>
        </a>
        
        <a href="feedback.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt"></i>
            <span><?php echo __('feedback'); ?></span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="driver_analysis.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'driver_analysis.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>تحليل أداء السائقين</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span><?php echo __('profile'); ?></span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span><?php echo __('logout'); ?></span>
        </a>
    </div>
</div><?php
} else {} } 
?>
<style>
/* Sidebar Base Styles */
.main-sidebar {
    background: linear-gradient(180deg, #1f31a8 0%, #102de7 100%);
    color: white;
    position: fixed;
    height: 100vh;
    z-index: 1040;
    display: flex;
    flex-direction: column;
    width: 250px;
    transition: all 0.3s ease-in-out;
}

/* RTL Support */
html[dir="rtl"] .main-sidebar {
    right: 0;
    transform: translateX(100%);
}

html[dir="ltr"] .main-sidebar {
    left: 0;
    transform: translateX(-100%);
}

/* Mobile Styles */
@media (max-width: 991px) {
    .main-sidebar {
        width: 85%;
        max-width: 300px;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }

    .main-sidebar.show {
        transform: translateX(0) !important;
    }
}

/* Desktop Styles */
@media (min-width: 992px) {
    .main-sidebar {
        transform: none !important;
    }
}

/* Sidebar Header */
.sidebar-header {
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand {
    color: white;
    text-decoration: none;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-brand i {
    font-size: 1.5rem;
}

/* Sidebar Menu */
.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
    -webkit-overflow-scrolling: touch;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    gap: 0.75rem;
}

.sidebar-item:hover {
    color: white;
    background: rgba(255,255,255,0.1);
}

.sidebar-item.active {
    color: white;
    background: rgba(255,255,255,0.15);
    position: relative;
}

.sidebar-item.active::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #fff;
}

html[dir="rtl"] .sidebar-item.active::before {
    right: 0;
}

html[dir="ltr"] .sidebar-item.active::before {
    left: 0;
}

.sidebar-item i {
    font-size: 1.25rem;
    width: 1.5rem;
    text-align: center;
}

.sidebar-divider {
    margin: 1rem 0;
    border-top: 1px solid rgba(255,255,255,0.1);
}

/* Overlay Styles */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1039;
    backdrop-filter: blur(2px);
}

.sidebar-overlay.show {
    display: block;
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    position: fixed;
    top: 1rem;
    width: 45px;
    height: 45px;
    border-radius: 10px;
    background: #1f31a8;
    border: none;
    color: white;
    z-index: 1060;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}

@media (max-width: 991px) {
    .mobile-menu-toggle {
        display: flex !important;
    }
}

html[dir="rtl"] .mobile-menu-toggle {
    right: 1rem;
    left: auto;
}

html[dir="ltr"] .mobile-menu-toggle {
    left: 1rem;
    right: auto;
}

.mobile-menu-toggle i {
    font-size: 1.5rem;
    color: white;
}

.mobile-menu-toggle:active {
    transform: scale(0.95);
    opacity: 0.9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.main-sidebar');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
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
    overlay.addEventListener('click', hideSidebar);

    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 991 && 
            sidebar.classList.contains('show') && 
            !sidebar.contains(e.target) && 
            !mobileMenuToggle.contains(e.target)) {
            hideSidebar();
        }
    });

    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
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
</script>

