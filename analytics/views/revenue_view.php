<!-- رأس الصفحة -->
<div class="container-fluid px-4">
    <h1 class="mt-4">تحليلات الإيرادات</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">لوحة التحكم</a></li>
        <li class="breadcrumb-item active">تحليلات الإيرادات</li>
    </ol>

    <!-- إعدادات رسوم التوصيل -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-cog me-1"></i>
            إعدادات رسوم التوصيل
        </div>
        <div class="card-body">
            <form method="POST" class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">رسوم التوصيل الثابتة</span>
                        <input type="number" step="0.01" name="delivery_fee" class="form-control" value="<?php echo $delivery_fee; ?>" required>
                        <span class="input-group-text">ر.س</span>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <p class="text-muted mb-0">
                        * سيتم تطبيق هذه الرسوم على جميع الطلبات
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row">
        <!-- إيرادات التوصيل -->
        <div class="col-xl-3 col-md-6">
            <div class="card mb-4">
                <div class="card-body bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">إيرادات التوصيل</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['delivery_revenue'], 2); ?> ر.س</h3>
                            <small>رسوم التوصيل فقط</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-truck fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إيرادات الشركات -->
        <div class="col-xl-3 col-md-6">
            <div class="card mb-4">
                <div class="card-body bg-gradient-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">إيرادات الشركات</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['company_revenue'], 2); ?> ر.س</h3>
                            <small>قيمة المنتجات</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-building fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الطلبات النقدية -->
        <div class="col-xl-3 col-md-6">
            <div class="card mb-4">
                <div class="card-body bg-gradient-warning text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">الطلبات النقدية</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['cash_orders']); ?></h3>
                            <small>تحصيل عند التوصيل</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الطلبات المدفوعة -->
        <div class="col-xl-3 col-md-6">
            <div class="card mb-4">
                <div class="card-body bg-gradient-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">الطلبات المدفوعة</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['paid_orders']); ?></h3>
                            <small>تم الدفع</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* تنسيقات إضافية */
.bg-gradient-primary {
    background: linear-gradient(135deg, #2980b9, #3498db);
}
.bg-gradient-success {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #f39c12, #f1c40f);
}
.bg-gradient-info {
    background: linear-gradient(135deg, #2c3e50, #34495e);
}
.stat-icon {
    opacity: 0.8;
}
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.05);
}
</style> 