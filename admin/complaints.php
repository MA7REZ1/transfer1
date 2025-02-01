<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'drivers_supervisor') {
    header('Location: ../index.php');
    exit;
}

// Fetch complaints with related information
$stmt = $conn->prepare("
    SELECT 
        c.*,
        comp.name as company_name,
        d.username as driver_name,
        r.order_number as request_number,
        (SELECT COUNT(*) FROM complaint_responses WHERE complaint_id = c.id) as response_count,
        (SELECT response FROM complaint_responses WHERE complaint_id = c.id ORDER BY created_at DESC LIMIT 1) as latest_response,
        (SELECT created_at FROM complaint_responses WHERE complaint_id = c.id ORDER BY created_at DESC LIMIT 1) as latest_response_date,
        (SELECT 
            CASE 
                WHEN cr.is_company_reply = 1 THEN comp2.name
                WHEN cr.admin_id IS NOT NULL THEN 'الإدارة'
            END
         FROM complaint_responses cr
         LEFT JOIN companies comp2 ON cr.company_id = comp2.id
         WHERE cr.complaint_id = c.id 
         ORDER BY cr.created_at DESC 
         LIMIT 1
        ) as latest_response_by
    FROM complaints c
    LEFT JOIN companies comp ON c.company_id = comp.id
    LEFT JOIN drivers d ON c.driver_id = d.id
    LEFT JOIN requests r ON c.request_id = r.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$complaints = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">إدارة الشكاوى</h1>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>رقم الشكوى</th>
                                    <th>الشركة</th>
                                    <th>السائق</th>
                                    <th>رقم الطلب</th>
                                    <th>الموضوع</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($complaints)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">لا توجد شكاوى حالياً</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($complaints as $complaint): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($complaint['complaint_number']); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['company_name']); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['driver_name']); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['request_number']); ?></td>
                                            <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getPriorityClass($complaint['priority']); ?>">
                                                    <?php echo getPriorityLabel($complaint['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusClass($complaint['status']); ?>">
                                                    <?php echo getStatusLabel($complaint['status']); ?>
                                                </span>
                                                <?php if ($complaint['response_count'] > 0): ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $complaint['response_count']; ?> رد
                                                        <?php if ($complaint['latest_response_by']): ?>
                                                            (<?php echo htmlspecialchars($complaint['latest_response_by']); ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($complaint['latest_response_date']) {
                                                    echo date('Y-m-d H:i', strtotime($complaint['latest_response_date']));
                                                } else {
                                                    echo date('Y-m-d H:i', strtotime($complaint['created_at']));
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>
                                                    عرض التفاصيل
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Complaint Details Modal -->
<div class="modal fade" id="complaintModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    تفاصيل الشكوى
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 8px;
    border: none;
}

.badge {
    font-size: 0.85rem;
    padding: 0.5em 0.75em;
    border-radius: 6px;
}

.table th {
    font-weight: 600;
    white-space: nowrap;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
    border-radius: 6px;
}

.modal-content {
    border-radius: 8px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.modal-title {
    font-size: 1.1rem;
    font-weight: 600;
}
</style>

<script>
let complaintModal;

document.addEventListener('DOMContentLoaded', function() {
    complaintModal = new bootstrap.Modal(document.getElementById('complaintModal'));
});

function viewComplaint(complaintId) {
    // Load complaint details via AJAX
    fetch('ajax/get_complaint_details.php?id=' + complaintId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('complaintDetails').innerHTML = html;
            complaintModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء تحميل تفاصيل الشكوى');
        });
}

function submitResponse(complaintId) {
    const response = document.getElementById('adminResponse').value.trim();
    const status = document.getElementById('complaintStatus').value;
    
    if (!response) {
        alert('الرجاء كتابة الرد');
        return;
    }

    // Disable submit button and show loading
    const submitBtn = document.querySelector('#adminResponseForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الإرسال...';
    submitBtn.disabled = true;

    // Send request
    fetch('ajax/submit_complaint_response.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            complaint_id: complaintId,
            response: response,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'تم إرسال الرد بنجاح');
            complaintModal.hide();
            location.reload();
        } else {
            alert(data.message || 'حدث خطأ أثناء إرسال الرد');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إرسال الرد');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}
</script>

<?php
function getPriorityClass($priority) {
    switch ($priority) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'info';
        default: return 'secondary';
    }
}

function getPriorityLabel($priority) {
    switch ($priority) {
        case 'high': return 'عالية';
        case 'medium': return 'متوسطة';
        case 'low': return 'منخفضة';
        default: return 'غير محدد';
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'new': return 'primary';
        case 'in_progress': return 'warning';
        case 'resolved': return 'success';
        case 'closed': return 'secondary';
        default: return 'info';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'new': return 'جديدة';
        case 'in_progress': return 'قيد المعالجة';
        case 'resolved': return 'تم الحل';
        case 'closed': return 'مغلقة';
        default: return 'غير محدد';
    }
}

include '../includes/footer.php';
?> 