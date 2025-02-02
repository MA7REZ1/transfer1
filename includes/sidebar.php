<?php

// Get count of unresolved complaints
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE status = 'new'");
$stmt->execute();
$unresolved_complaints = $stmt->fetchColumn();

if ($_SESSION['admin_role'] === 'super_admin' || $_SESSION['admin_role'] === 'مدير_عام') {
?>
<!-- بداية الـ HTML -->


<div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span>نظام إدارة النقل</span>
        </a>
        <button class="sidebar-toggle d-none d-lg-block">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>لوحة التحكم</span>
        </a>
        
        <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>الطلبات</span>
        </a>
        
        <a href="companies.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i>
            <span>الشركات</span>
        </a>
        
        <a href="drivers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>السائقين</span>
        </a>
        
        <a href="complaints.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>الشكاوى</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="reports.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>التقارير</span>
        </a>
        
        <a href="revenue.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'revenue.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span>الإيرادات</span>
        </a>
        
        <a href="driver_earnings_settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'driver_earnings_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-hand-holding-usd"></i>
            <span>التحصيل من السواق</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="employees.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>الموظفين</span>
        </a>
        
        <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>الملف الشخصي</span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
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
    ?><div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span>نظام إدارة النقل</span>
        </a>
        <button class="sidebar-toggle">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>لوحة التحكم</span>
        </a>
        
        <a href="revenue.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'revenue.php') ? 'active' : ''; ?>">
            <i class="fas fa-dollar-sign"></i>
            <span>التحليل المالي</span>
        </a>

        <a href="driver_earnings_settings.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'driver_earnings_settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave"></i>
            <span>التحصيل من السواق</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="profile.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>الملف الشخصي</span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل خروج</span>
        </a>
    </div>
</div> <?php
} elseif ($employee && $employee['department'] === 'drivers_supervisor') {
    ?><div class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <i class="fas fa-truck"></i>
            <span>نظام إدارة النقل</span>
        </a>
        <button class="sidebar-toggle">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>لوحة التحكم</span>
        </a>
        
        <div class="sidebar-divider"></div>
         <a href="orders.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>إدارة الطلبات</span>
        </a>
        <a href="drivers.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'drivers.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>السائقين</span>
        </a>
          <a href="order_analysis.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'order_analysis.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>تحليل الطلبات</span>
        </a>
        
        <a href="driver_analysis.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'driver_analysis.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>تحليل أداء السائقين</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="complaints.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'complaints.php') ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>الشكاوى <?php if ($unresolved_complaints > 0): ?><span class="complaints-badge"><?php echo $unresolved_complaints; ?></span><?php endif; ?></span>
        </a>
        
        <a href="driver_analysis.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'driver_analysis.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>تحليل أداء السائقين</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="profile.php" class="sidebar-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>الملف الشخصي</span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل خروج</span>
        </a>
    </div>
</div><?php
} else {} } 
?>
<style>
/* Mobile Sidebar Improvements */
@media (max-width: 991px) {
    .sidebar {
        width: 280px;
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    }

    .sidebar-header {
        padding: 1.25rem;
    }

    .sidebar-brand {
        font-size: 1.1rem;
    }

    .sidebar-item {
        padding: 0.75rem 1.25rem;
    }

    .sidebar-item i {
        font-size: 1.25rem;
    }

    .sidebar-item span {
        font-size: 0.95rem;
    }

    .sidebar-divider {
        margin: 0.75rem 1.25rem;
    }
}

/* Active State Enhancement */
.sidebar-item.active {
    background: rgba(255,255,255,0.15);
    position: relative;
}

.sidebar-item.active::before {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--primary-gradient);
}

/* Touch Area Improvement */
@media (max-width: 991px) {
    .sidebar-item {
        min-height: 48px; /* Minimum touch target size */
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sidebar collapse on desktop
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Restore sidebar state on page load
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && window.innerWidth >= 992) {
        sidebar.classList.add('collapsed');
    }
    
    // Handle touch events for better mobile interaction
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    }, false);
    
    document.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, false);
    
    function handleSwipe() {
        const swipeThreshold = 100;
        const diff = touchEndX - touchStartX;
        
        if (Math.abs(diff) < swipeThreshold) return;
        
        if (diff > 0) { // Swipe right
            sidebar.classList.add('show');
            document.querySelector('.sidebar-overlay')?.classList.add('show');
        } else { // Swipe left
            sidebar.classList.remove('show');
            document.querySelector('.sidebar-overlay')?.classList.remove('show');
        }
    }
});
</script>
