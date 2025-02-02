<?php
require_once '../config.php';

if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

// Get company information
$stmt = $conn->prepare("SELECT name, logo FROM companies WHERE id = ?");
$stmt->execute([$_SESSION['company_id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all complaints if no specific ID is provided
if (!isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT c.*, d.username as driver_name, r.order_number as request_number,
        CASE 
            WHEN c.status = 'new' THEN 'جديدة'
            WHEN c.status = 'in_progress' THEN 'قيد المعالجة'
            WHEN c.status = 'closed' THEN 'مغلقة'
            ELSE c.status
        END as status_text,
        CASE 
            WHEN c.status = 'new' THEN 'danger'
            WHEN c.status = 'in_progress' THEN 'warning'
            WHEN c.status = 'closed' THEN 'success'
            ELSE 'secondary'
        END as status_class
        FROM complaints c
        LEFT JOIN drivers d ON c.driver_id = d.id
        LEFT JOIN requests r ON c.request_id = r.id
        WHERE c.company_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $complaints = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشكاوى - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar-brand img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-left: 10px;
        }
        .company-name {
            font-size: 1.2rem;
            font-weight: 500;
            color: white;
            margin-right: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #343a40;
            color: white;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 6px;
        }
        .response-item {
            margin-bottom: 1rem;
        }
        .response-item .card {
            border-radius: 15px;
            box-shadow: none;
        }
        .response-item .card-body {
            padding: 1rem;
        }
        .response-item .text-primary {
            color: #0d6efd !important;
        }
        .response-item .text-success {
            color: #198754 !important;
        }
        .response-item .text-muted {
            color: #6c757d !important;
        }
        .response-item .bg-light {
            background-color: #f0f7ff !important;
        }
        .response-item .bg-success.bg-opacity-10 {
            background-color: #e8f5e9 !important;
        }
        .response-item .card-body > div:last-child {
            margin-top: 0.5rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <?php if (!empty($company['logo'])): ?>
                    <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="rounded">
                <?php else: ?>
                    <i class="bi bi-building"></i>
                <?php endif; ?>
                <span class="company-name"><?php echo htmlspecialchars($company['name']); ?></span>
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-exclamation-circle text-danger"></i>
                الشكاوى
            </h2>
            <a href="dashboard.php" class="btn btn-primary">
                <i class="bi bi-arrow-right"></i>
                العودة للوحة التحكم
            </a>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رقم الشكوى</th>
                                        <th>الموضوع</th>
                                        <th>رقم الطلب</th>
                                        <th>السائق</th>
                                        <th>التاريخ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($complaints as $complaint): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($complaint['complaint_number']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['request_number']); ?></td>
                                        <td><?php echo htmlspecialchars($complaint['driver_name']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($complaint['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $complaint['status_class']; ?>">
                                                <?php echo $complaint['status_text']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="complaints.php?id=<?php echo $complaint['complaint_number']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> عرض التفاصيل
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (empty($complaints)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-emoji-smile fs-1 text-muted"></i>
                                <p class="mt-2 text-muted">لا توجد شكاوى حتى الآن</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
} else {
    $complaint_number = $_GET['id'];
    
    // Get complaint details
    $stmt = $conn->prepare("
        SELECT c.*, d.username as driver_name, r.order_number as request_number,
        CASE 
            WHEN c.status = 'new' THEN 'جديدة'
            WHEN c.status = 'in_progress' THEN 'قيد المعالجة'
            WHEN c.status = 'closed' THEN 'مغلقة'
            ELSE c.status
        END as status_text,
        CASE 
            WHEN c.status = 'new' THEN 'danger'
            WHEN c.status = 'in_progress' THEN 'warning'
            WHEN c.status = 'closed' THEN 'success'
            ELSE 'secondary'
        END as status_class
        FROM complaints c
        LEFT JOIN drivers d ON c.driver_id = d.id
        LEFT JOIN requests r ON c.request_id = r.id
        WHERE c.complaint_number = ? AND c.company_id = ?
    ");
    $stmt->execute([$complaint_number, $_SESSION['company_id']]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$complaint) {
        header("Location: complaints.php");
        exit();
    }
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الشكوى - <?php echo $complaint_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40;
        }
        .navbar-brand img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-left: 10px;
        }
        .company-name {
            font-size: 1.2rem;
            font-weight: 500;
            color: white;
            margin-right: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .complaint-details {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .response-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .response-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .response-content {
            background-color: #fff;
            padding: 15px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <?php if (!empty($company['logo'])): ?>
                    <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="rounded">
                <?php else: ?>
                    <i class="bi bi-building"></i>
                <?php endif; ?>
                <span class="company-name"><?php echo htmlspecialchars($company['name']); ?></span>
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-exclamation-circle text-danger"></i>
                تفاصيل الشكوى #<?php echo htmlspecialchars($complaint_number); ?>
            </h2>
            <a href="complaints.php" class="btn btn-primary">
                <i class="bi bi-arrow-right"></i>
                العودة للشكاوى
            </a>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Complaint Details Section -->
                        <div class="complaint-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>معلومات الشكوى</h5>
                                    <div class="mb-3">
                                        <strong>الموضوع:</strong>
                                        <p><?php echo htmlspecialchars($complaint['subject']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>الوصف:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>الحالة:</strong>
                                        <span class="badge bg-<?php echo $complaint['status_class']; ?>">
                                            <?php echo $complaint['status_text']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>معلومات الطلب</h5>
                                    <div class="mb-3">
                                        <strong>رقم الطلب:</strong>
                                        <p><?php echo htmlspecialchars($complaint['request_number']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>السائق:</strong>
                                        <p><?php echo htmlspecialchars($complaint['driver_name']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>تاريخ الشكوى:</strong>
                                        <p><?php echo date('Y-m-d H:i', strtotime($complaint['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Responses Section -->
                        <div class="mt-4">
                            <h4>الردود</h4>
                            <div id="complaint-responses"></div>
                        </div>

                        <!-- Reply Form -->
                        <?php if ($complaint['status'] !== 'closed'): ?>
                        <div class="mt-4">
                            <h4>إضافة رد</h4>
                            <form id="reply-form" class="needs-validation" novalidate>
                                <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint_number); ?>" required>
                                <div class="form-group">
                                    <label for="response" class="form-label">الرد <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="response" id="response" rows="4" required 
                                        placeholder="اكتب ردك هنا..."
                                        minlength="3"
                                        data-error="الرجاء كتابة الرد"></textarea>
                                    <div class="invalid-feedback">
                                        الرجاء كتابة الرد (3 أحرف على الأقل)
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="bi bi-send"></i>
                                    إرسال الرد
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<style>.company-name:hover {
    color: darkgray !important;} </style></script>
    <script>
    $(document).ready(function() {
        // Form validation
        const form = document.getElementById('reply-form');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }

        // Load complaint responses
        function loadComplaintResponses() {
            const complaintId = <?php echo json_encode($complaint_number); ?>;
            
            // Show loading indicator
            $('#complaint-responses').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            `);

            $.ajax({
                url: 'ajax/get_complaint_responses.php',
                method: 'POST',
                data: { complaint_id: complaintId },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.success) {
                            let html = '';
                            if (!data.responses || data.responses.length === 0) {
                                html = `
                                    <div class="text-center py-4">
                                        <i class="bi bi-chat-square-text fs-1 text-muted"></i>
                                        <p class="mt-2 text-muted">لا توجد ردود حتى الآن</p>
                                    </div>
                                `;
                            } else {
                                data.responses.forEach(response => {
                                    const date = new Date(response.created_at);
                                    const formattedDate = new Intl.DateTimeFormat('ar-SA', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: 'numeric',
                                        minute: 'numeric'
                                    }).format(date);

                                    html += `
                                        <div class="response-item mb-3">
                                            <div class="card ${response.is_company_reply ? 'bg-light border-0' : 'bg-success bg-opacity-10 border-0'}">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="d-flex flex-column">
                                                            ${response.is_company_reply ? `
     <a class="navbar-brand d-flex align-items-center" href="profile.php">
    <?php if (!empty($company['logo'])): ?>
        <img src="../uploads/companies/<?php echo htmlspecialchars($company['logo']); ?>" alt="شعار الشركة" class="rounded">
    <?php else: ?>
        <i class="bi bi-building"></i>
    <?php endif; ?>
    <span class="company-name" style="color: black;"><?php echo htmlspecialchars($company['name']); ?></span>
</a>
                                                            ` : response.admin_role === 'super_admin' || response.admin_role === 'مدير_عام' ? `
                                                                <span class="text-success">${response.admin_name || 'مدير النظام'}</span>
                                                                <small class="text-muted">مدير النظام</small>
                                                            ` : `
                                                                <span class="text-success">${response.admin_name || response.employee_name || 'موظف'}</span>
                                                                <small class="text-muted">موظف</small>
                                                            `}
                                                        </div>
                                                        <div class="text-muted small text-end">
                                                            ${formattedDate}
                                                            <br>
                                                            ${new Date(response.created_at).toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit' })}
                                                        </div>
                                                    </div>
                                                    <div class="response-text">
                                                        ${response.response}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                            }
                            $('#complaint-responses').html(html);
                        } else {
                            throw new Error(data.message || 'حدث خطأ أثناء تحميل الردود');
                        }
                    } catch (error) {
                        console.error('Error parsing response:', error);
                        $('#complaint-responses').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                ${error.message}
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $('#complaint-responses').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            حدث خطأ في الاتصال بالخادم
                        </div>
                    `);
                }
            });
        }

        // Handle reply submission
        $('#reply-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!this.checkValidity()) {
                e.stopPropagation();
                $(this).addClass('was-validated');
                return;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Get form data
            const response = $(this).find('textarea[name="response"]').val().trim();
            
            // Validate response
            if (!response) {
                const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                    .html(`
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        الرجاء كتابة الرد
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `);
                $('#reply-form').before(alertDiv);
                setTimeout(() => alertDiv.alert('close'), 3000);
                return;
            }
            
            // Disable button and show loading
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الإرسال...').prop('disabled', true);
            
            $.ajax({
                url: 'ajax/submit_complaint_reply.php',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    var result = JSON.parse(response);
                    if(result.success) {
                        // Show success message
                        const alertDiv = $('<div class="alert alert-success alert-dismissible fade show" role="alert">')
                            .html(`
                                <i class="bi bi-check-circle-fill me-2 text-success"></i>
                                <span class="text-success">${result.message}</span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `);
                        $('#reply-form').before(alertDiv);
                        
                        // Reset form and validation
                        $('#reply-form')[0].reset();
                        $('#reply-form').removeClass('was-validated');
                        
                        // Refresh responses
                        loadComplaintResponses();
                        
                        // Remove alert after 3 seconds
                        setTimeout(() => alertDiv.alert('close'), 3000);
                    } else {
                        // Show error message
                        const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                            .html(`
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                ${result.message || 'حدث خطأ أثناء إرسال الرد'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `);
                        $('#reply-form').before(alertDiv);
                    }
                },
                error: function() {
                    // Show error message
                    const alertDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                        .html(`
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            حدث خطأ في الاتصال بالخادم
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `);
                    $('#reply-form').before(alertDiv);
                },
                complete: function() {
                    // Re-enable button and restore text
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Initial load
        loadComplaintResponses();

        // Refresh responses every 30 seconds
        setInterval(loadComplaintResponses, 30000);
    });
    </script>

    <style>
        .response-item {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .response-content {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .badge {
            padding: 6px 10px;
            font-weight: 500;
        }
        .form-label {
            font-weight: 500;
        }
        .text-danger {
            color: #dc3545;
        }
        .was-validated .form-control:invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .was-validated .form-control:valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
    </style>

<?php
}
include '../includes/footer.php';
?> 