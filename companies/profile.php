<?php
require_once '../config.php';
if (!isset($_SESSION['company_email'])) {
      header("Location: login.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Get company information
$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get company statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN status = 'delivered' THEN total_cost ELSE 0 END) as total_earnings,
    AVG(CASE WHEN status = 'delivered' THEN total_cost ELSE NULL END) as avg_order_value
FROM requests 
WHERE company_id = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute([$company_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>الملف الشخصي | <?php echo htmlspecialchars($company['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        /* Add Tajawal font */
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap');

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --navbar-height: 70px;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --text-secondary: #475569;
        }
        
        body {
            background-color: #f8f9fa;
            padding-top: var(--navbar-height);
            font-family: 'Tajawal', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }
        
        .navbar {
            background-color: var(--primary-color) !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: var(--navbar-height);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 60px;
        }

        .navbar-brand img {
            height: 40px;
            width: auto;
            transition: all 0.3s ease;
        }

        .navbar.scrolled .navbar-brand img {
            height: 35px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 5px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.8;
        }
        .form-label {
            font-weight: 500;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .stat-card {
                padding: 1.75rem;
            }
            
            .stat-card h3 {
                font-size: 1.8rem;
            }
            
            .profile-card {
                padding: 2rem;
            }
        }

        @media (max-width: 992px) {
            .profile-header {
                padding: 3.5rem 0;
            }
            
            .profile-image {
                width: 180px;
                height: 180px;
            }
            
            .stats-container {
                margin-top: -40px;
            }
            
            .stat-card {
                margin-bottom: 1.5rem;
            }
            
            .stat-icon {
                font-size: 2.5rem;
                margin-bottom: 1.25rem;
            }
            
            .profile-card {
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 0;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .profile-header {
                padding: 2.5rem 0;
                text-align: center;
            }
            
            .profile-image {
                width: 150px;
                height: 150px;
                margin-bottom: 1.5rem;
            }
            
            .stats-container {
                margin-top: -30px;
            }
            
            .stat-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .stat-card h3 {
                font-size: 1.6rem;
            }
            
            .stat-icon {
                font-size: 2.25rem;
                margin-bottom: 1rem;
            }
            
            .profile-card {
                padding: 1.75rem;
                margin-bottom: 1.5rem;
            }
            
            .profile-card h4 {
                margin-bottom: 1.5rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 0.6rem 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .profile-header {
                padding: 2rem 0;
            }
            
            .profile-image {
                width: 130px;
                height: 130px;
                border-width: 6px;
            }
            
            h1 {
                font-size: 1.75rem;
            }
            
            .stats-container {
                margin-top: -20px;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-card h6 {
                font-size: 0.8rem;
                margin-bottom: 0.5rem;
            }
            
            .stat-card h3 {
                font-size: 1.4rem;
            }
            
            .stat-icon {
                font-size: 2rem;
                margin-bottom: 0.75rem;
            }
            
            .profile-card {
                padding: 1.5rem;
            }
            
            .profile-card h4 {
                font-size: 1.3rem;
                margin-bottom: 1.25rem;
                padding-bottom: 0.75rem;
            }
            
            .modal-header {
                padding: 1.25rem;
            }
            
            .modal-body {
                padding: 1.25rem;
            }
            
            .modal-title {
                font-size: 1.1rem;
            }
            
            .form-label {
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            
            .form-control {
                padding: 0.6rem 0.9rem;
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Print Styles */
        @media print {
            .navbar,
            .btn-edit,
            .modal {
                display: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            
            .profile-header {
                background: none !important;
                color: black !important;
                padding: 1rem 0 !important;
                margin-bottom: 2rem !important;
            }
            
            .profile-image {
                border: 2px solid #ddd !important;
                width: 120px !important;
                height: 120px !important;
            }
            
            .stat-card,
            .profile-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            
            .stat-icon {
                color: black !important;
                -webkit-text-fill-color: black !important;
            }
        }

        /* Typography Styles - Only text related */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Tajawal', sans-serif;
            font-weight: 700;
            line-height: 1.3;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
        }

        .nav-link {
            font-weight: 500;
            font-size: 1rem;
        }

        .profile-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .profile-header p {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-card h6 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .profile-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .profile-card label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .profile-card .h5 {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .btn {
            font-weight: 600;
            font-size: 1rem;
        }

        small {
            font-size: 0.85rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .profile-header h1 {
                font-size: 1.75rem;
            }
            
            .profile-header p {
                font-size: 1rem;
            }
            
            .stat-card h3 {
                font-size: 1.75rem;
            }
            
            .profile-card h4 {
                font-size: 1.25rem;
            }
            
            .profile-card .h5 {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .profile-header h1 {
                font-size: 1.5rem;
            }
            
            .stat-card h6 {
                font-size: 0.8rem;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
            }
            
            .btn {
                font-size: 0.9rem;
            }
        }

        /* Profile Card Typography and Layout */
        .profile-card {
            padding: 2.5rem;
        }

        .profile-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-right: 1rem;
            border-right: 4px solid var(--primary-color);
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }

        .profile-card label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            padding-right: 0.5rem;
        }

        .profile-card .h5 {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--text-dark);
            padding-right: 0.5rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .profile-info-row {
            margin-bottom: 1.5rem;
            padding-right: 1rem;
        }

        .profile-info-item {
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .profile-card {
                padding: 1.5rem;
            }
            
            .profile-card h4 {
                font-size: 1.3rem;
                margin-bottom: 1.5rem;
                padding: 0.75rem;
            }
            
            .profile-card .h5 {
                font-size: 1rem;
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <?php if (!empty($company['logo'])): ?>
                    <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" style="height: 40px; width: auto;" class="rounded me-2">
                <?php endif; ?>
                <span><?php echo htmlspecialchars($company['name']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">لوحة التحكم</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">الملف الشخصي</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">تسجيل الخروج</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-auto text-center mb-3 mb-md-0">
                    <?php if (!empty($company['logo'])): ?>
                        <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image d-flex align-items-center justify-content-center bg-light">
                            <i class="bi bi-building" style="font-size: 4rem; color: var(--primary-color);"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md">
                    <h1 class="mb-2"><?php echo htmlspecialchars($company['name']); ?></h1>
                    <p class="mb-0"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($company['email']); ?></p>
                    <p class="mb-0"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($company['phone']); ?></p>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="bi bi-pencil"></i> تعديل الملف الشخصي
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Statistics -->
        <div class="container stats-container">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-box"></i>
                        </div>
                        <h6>إجمالي الطلبات</h6>
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h6>الطلبات المكتملة</h6>
                        <h3><?php echo number_format($stats['completed_orders']); ?></h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h6>إجمالي الأرباح</h6>
                        <h3><?php echo number_format($stats['total_earnings'], 2); ?> ريال</h3>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h6>متوسط قيمة الطلب</h6>
                        <h3><?php echo number_format($stats['avg_order_value'], 2); ?> ريال</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company Information -->
        <div class="container mt-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="profile-card">
                        <h4>معلومات الشركة</h4>
                        <div class="row profile-info-row">
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">اسم الشركة</label>
                                <p class="h5"><?php echo htmlspecialchars($company['name']); ?></p>
                            </div>
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">البريد الإلكتروني</label>
                                <p class="h5"><?php echo htmlspecialchars($company['email']); ?></p>
                            </div>
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">رقم الهاتف</label>
                                <p class="h5"><?php echo htmlspecialchars($company['phone']); ?></p>
                            </div>
                        </div>
                        <div class="row profile-info-row">
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">السجل التجاري</label>
                                <p class="h5"><?php echo htmlspecialchars($company['commercial_record']); ?></p>
                            </div>
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">الرقم الضريبي</label>
                                <p class="h5"><?php echo htmlspecialchars($company['tax_number']); ?></p>
                            </div>
                            <div class="col-md-4 profile-info-item">
                                <label class="text-muted d-block">تاريخ التسجيل</label>
                                <p class="h5"><?php echo date('Y-m-d', strtotime($company['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 profile-info-item">
                                <label class="text-muted d-block">العنوان</label>
                                <p class="h5"><?php echo htmlspecialchars($company['address']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="profile-card">
                        <h4>الوثائق</h4>
                        <?php if (!empty($company['commercial_record_file'])): ?>
                            <div class="mb-4">
                                <label class="text-muted d-block mb-2">السجل التجاري</label>
                                <a href="../uploads/documents/<?php echo htmlspecialchars($company['commercial_record_file']); ?>" 
                                   class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-file-earmark-text me-2"></i> عرض المستند
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($company['tax_certificate'])): ?>
                            <div class="mb-4">
                                <label class="text-muted d-block mb-2">الشهادة الضريبية</label>
                                <a href="../uploads/documents/<?php echo htmlspecialchars($company['tax_certificate']); ?>" 
                                   class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="bi bi-file-earmark-text me-2"></i> عرض المستند
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الملف الشخصي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editProfileForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم الشركة</label>
                                    <input type="text" class="form-control bg-light" name="name" value="<?php echo htmlspecialchars($company['name']); ?>" readonly>
                                    <small class="text-muted">لا يمكن تغيير اسم الشركة</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control bg-light" name="email" value="<?php echo htmlspecialchars($company['email']); ?>" readonly>
                                    <small class="text-muted">لا يمكن تغيير البريد الإلكتروني</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">العنوان <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="address" rows="2" required minlength="10"><?php echo htmlspecialchars($company['address']); ?></textarea>
                                    <small class="text-muted">يجب أن يحتوي العنوان على 10 أحرف على الأقل</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">شعار الشركة</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                    <small class="text-muted">اترك فارغاً للإبقاء على الشعار الحالي</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">رقم السجل التجاري <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="commercial_record" value="<?php echo htmlspecialchars($company['commercial_record']); ?>" 
                                           required pattern="[0-9]{10}" title="الرجاء إدخال رقم سجل تجاري صحيح مكون من 10 أرقام">
                                    <small class="text-muted">يجب أن يتكون من 10 أرقام</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">الرقم الضريبي <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="tax_number" value="<?php echo htmlspecialchars($company['tax_number']); ?>" 
                                           required pattern="[0-9]{15}" title="الرجاء إدخال رقم ضريبي صحيح مكون من 15 رقم">
                                    <small class="text-muted">يجب أن يتكون من 15 رقم</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" name="password">
                                    <small class="text-muted">اترك فارغاً للإبقاء على كلمة المرور الحالية</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('ajax/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث الملف الشخصي');
            });
        });

        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html> 