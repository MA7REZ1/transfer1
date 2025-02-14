<?php
// Check if this is a staff page
$is_staff_page = isset($use_staff_header) && $use_staff_header === true;

if ($is_staff_page) {
?>
<!-- Staff Profile Header -->
<div class="profile-header">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="staff-info">
                    <div class="text-center">
                        <div class="staff-avatar mx-auto">
                            <i class="bi bi-person"></i>
                        </div>
                        <h2 class="staff-name"><?php echo htmlspecialchars($staff['name']); ?></h2>
                        <div class="staff-role">
                            <?php echo $staff['role'] === 'order_manager' ? 'مدير طلبات' : 'موظف'; ?>
                        </div>
                    </div>
                    <div class="staff-meta">
                        <div class="meta-item">
                            <i class="bi bi-envelope"></i>
                            <span><?php echo htmlspecialchars($staff['email']); ?></span>
                        </div>
                        <?php if ($staff['phone']): ?>
                        <div class="meta-item">
                            <i class="bi bi-telephone"></i>
                            <span><?php echo htmlspecialchars($staff['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="bi bi-clock-history"></i>
                            <span>آخر دخول: <?php echo $staff['last_login'] ? date('Y-m-d H:i', strtotime($staff['last_login'])) : 'لم يسجل الدخول بعد'; ?></span>
                        </div>
                    </div>
                    <div class="staff-actions mt-3">
                        <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-speedometer2"></i> لوحة التحكم
                        </a>
                        <a href="orders.php" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-list-check"></i> الطلبات
                        </a>
                        <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'order_manager'): ?>
                        <a href="analytics.php" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-graph-up"></i> التحليلات
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    background: linear-gradient(45deg, #4158D0, #C850C0);
    padding: 2rem 0;
    margin-bottom: 2rem;
    color: white;
}

.staff-info {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1.5rem;

}

.staff-avatar {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #2c3e50;
    margin-bottom: 1rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.staff-name {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.staff-role {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 1rem;
}

.staff-meta {
    display: flex;
    gap: 2rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    justify-content: center;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.meta-item i {
    font-size: 1.2rem;
    opacity: 0.8;
}

.staff-actions {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.staff-actions .btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.staff-actions .btn-light {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
}

.staff-actions .btn-light:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.staff-actions .btn-danger {
    background: rgba(220, 53, 69, 0.9);
    border: none;
}

.staff-actions .btn-danger:hover {
    background: rgba(220, 53, 69, 1);
    transform: translateY(-2px);
}

.staff-actions .btn i {
    margin-left: 5px;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 1rem 0;
    }
    .staff-meta {
        flex-direction: column;
        gap: 1rem;
    }
    .staff-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    .staff-actions .btn {
        margin: 0 !important;
    }
}
</style>
<?php 
} // End of staff header check
?>