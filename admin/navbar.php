<?php
if (!isset($_SESSION)) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark d-lg-none">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">لوحة التحكم</a>
        <div class="ms-auto d-flex align-items-center">
            <div class="notifications-dropdown me-3">
                <?php include 'get_notifications.php'; ?>
            </div>
        </div>
    </div>
</nav>

<?php include 'sidebar.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">لوحة التحكم</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="companies.php">الشركات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="drivers.php">السائقين</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">الطلبات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="complaints.php">الشكاوى</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">التقارير</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="driver_earnings_settings.php">إعدادات أرباح السائقين</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i> الملف الشخصي
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 