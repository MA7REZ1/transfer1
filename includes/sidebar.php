<div class="sidebar">
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
        <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>لوحة التحكم</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>إدارة الطلبات</span>
        </a>
        
        <a href="companies.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i>
            <span>الشركات</span>
        </a>
        
        <a href="drivers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'drivers.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>السائقين</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="orders.php" class="sidebar-item <?php echo strpos($_SERVER['PHP_SELF'], 'orders.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>تحليل الطلبات</span>
        </a>
        
        <a href="drivers.php" class="sidebar-item <?php echo strpos($_SERVER['PHP_SELF'], 'drivers.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>تحليل أداء السائقين</span>
        </a>
        
        <a href="revenue.php" class="sidebar-item <?php echo strpos($_SERVER['PHP_SELF'], 'revenue.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-dollar-sign"></i>
            <span>التحليل المالي</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="complaints.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'complaints.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>الشكاوى</span>
        </a>
        
        <a href="reports.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>التقارير</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>الإعدادات</span>
        </a>
        
        <a href="profile.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>الملف الشخصي</span>
        </a>
        
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل خروج</span>
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sidebar toggle
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const toggleIcon = sidebarToggle.querySelector('i');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
        } else {
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        }
    });
    
    // Handle mobile menu toggle
    const mobileToggle = document.querySelector('.navbar-toggler');
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 992) {
            if (!sidebar.contains(event.target) && !mobileToggle?.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
});
</script>