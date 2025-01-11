<?php
require_once '../config.php';
require_once '../includes/header.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
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
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <title>إدارة الشكاوى</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        .badge {
            font-size: 0.9em;
            padding: 0.5em 1em;
        }
        .modal-header .close {
            margin: -1rem auto -1rem -1rem;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>إدارة الشكاوى</h2>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>رقم الشكوى</th>
                        <th>الشركة</th>
                        <th>السائق</th>
                        <th>رقم الطلب</th>
                        <th>الموضوع</th>
                        <th>الأولوية</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                    عرض التفاصيل
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Complaint Details Modal -->
    <div class="modal fade" id="complaintModal" tabindex="-1" role="dialog" aria-labelledby="complaintModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="complaintModalLabel">تفاصيل الشكوى</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="complaintDetails">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Include necessary JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

    <script>
        let complaintModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            complaintModal = new bootstrap.Modal(document.getElementById('complaintModal'));
        });

        function viewComplaint(complaintId) {
            // Load complaint details via AJAX
            $.get('ajax/get_complaint_details.php', { id: complaintId })
                .done(function(response) {
                    $('#complaintDetails').html(response);
                    complaintModal.show();
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    alert('حدث خطأ أثناء تحميل تفاصيل الشكوى');
                    console.error('Error:', textStatus, errorThrown);
                });
        }

        function submitResponse(complaintId) {
            const response = $('#adminResponse').val().trim();
            const status = $('#complaintStatus').val();
            
            if (!response) {
                alert('الرجاء كتابة الرد');
                return;
            }

            // Disable submit button and show loading
            const submitBtn = $('#adminResponseForm button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="spinner-border spinner-border-sm"></span> جاري الإرسال...').prop('disabled', true);

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
                submitBtn.html(originalText).prop('disabled', false);
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
    ?>
</body>
</html> 