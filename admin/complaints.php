<?php
require_once '../config.php';

// التحقق من الصلاحيات - فقط المدير يمكنه الوصول
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// التحقق من نوع المستخدم - فقط المدراء يمكنهم الوصول للوحة التحكم
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'مدير_عام' && $_SESSION['department'] !== 'drivers_supervisor') {
    header('Location: index.php');
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
                WHEN cr.admin_id IS  NULL THEN 'الإدارة'
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
     
        
        <main class="col ms-sm-auto px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?php echo __('complaints_management'); ?></h1>
            </div>

            <?php if (empty($complaints)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0"><?php echo __('no_complaints'); ?></p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($complaints as $complaint): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card complaint-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php echo getPriorityClass($complaint['priority']); ?>">
                                        <i class="fas fa-flag me-1"></i>
                                        <?php echo __('priority_' . $complaint['priority']); ?>
                                    </span>
                                    <span class="badge bg-<?php echo getStatusClass($complaint['status']); ?>">
                                        <?php echo __('status_' . $complaint['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="complaint-info mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted"><?php echo __('complaint_number'); ?></small>
                                            <strong><?php echo htmlspecialchars($complaint['complaint_number'] ?? ''); ?></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted"><?php echo __('complaint_company'); ?></small>
                                            <strong><?php echo htmlspecialchars($complaint['company_name'] ?? ''); ?></strong>
                                        </div>
                                        <?php if (!empty($complaint['driver_name'])): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted"><?php echo __('complaint_driver'); ?></small>
                                            <strong><?php echo htmlspecialchars($complaint['driver_name'] ?? ''); ?></strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (!empty($complaint['request_number'])): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted"><?php echo __('complaint_request'); ?></small>
                                            <strong><?php echo htmlspecialchars($complaint['request_number'] ?? ''); ?></strong>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h6 class="card-subtitle mb-3 text-primary">
                                        <?php echo htmlspecialchars($complaint['subject'] ?? ''); ?>
                                    </h6>

                                    <?php if ($complaint['response_count'] > 0): ?>
                                        <div class="latest-response mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-reply text-info me-2"></i>
                                                <small class="text-muted"><?php echo __('response_by'); ?>: <?php echo htmlspecialchars($complaint['latest_response_by'] ?? __('management')); ?></small>
                                            </div>
                                            <div class="response-preview">
                                                <?php echo mb_substr(htmlspecialchars($complaint['latest_response'] ?? ''), 0, 100) . '...'; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="complaint-footer">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i>
                                                <?php 
                                                if ($complaint['latest_response_date']) {
                                                    echo date('Y-m-d H:i', strtotime($complaint['latest_response_date']));
                                                } else {
                                                    echo date('Y-m-d H:i', strtotime($complaint['created_at']));
                                                }
                                                ?>
                                            </small>
                                            <?php if ($complaint['response_count'] > 0): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-comments me-1"></i>
                                                    <?php echo $complaint['response_count']; ?> <?php echo __('responses'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-top-0">
                                    <button class="btn btn-primary w-100" onclick="viewComplaint(<?php echo $complaint['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>
                                        <?php echo __('view_details'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                    <?php echo __('complaint_details'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('close'); ?>"></button>
            </div>
            <div class="modal-body" id="complaintDetails">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<style>
.complaint-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.complaint-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.complaint-card .card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1rem;
}

.complaint-card .card-body {
    padding: 1.25rem;
}

.complaint-info {
    font-size: 0.9rem;
}

.complaint-info .text-muted {
    font-size: 0.85rem;
}

.latest-response {
    background: rgba(0,0,0,0.02);
    padding: 1rem;
    border-radius: 8px;
}

.response-preview {
    font-size: 0.9rem;
    color: #666;
    line-height: 1.5;
}

.complaint-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.badge {
    font-size: 0.85rem;
    padding: 0.5em 0.75em;
    border-radius: 6px;
}

.card-footer {
    padding: 1rem;
}

.btn-primary {
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.2);
}

@media (max-width: 768px) {
    .complaint-card {
        margin-bottom: 1rem;
    }
    
    .complaint-info {
        font-size: 0.85rem;
    }
}
</style>

<script>
let complaintModal;

document.addEventListener('DOMContentLoaded', function() {
    complaintModal = new bootstrap.Modal(document.getElementById('complaintModal'));
});

function viewComplaint(complaintId) {
    // Show loading state
    document.getElementById('complaintDetails').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2"><?php echo __('loading'); ?></p>
        </div>
    `;
    
    // Load complaint details via AJAX
    fetch('ajax/get_complaint_details.php?id=' + complaintId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('complaintDetails').innerHTML = html;
            complaintModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo __('response_error'); ?>');
        });
}

function submitResponse(complaintId) {
    const response = document.getElementById('adminResponse').value.trim();
    const status = document.getElementById('complaintStatus').value;
    
    if (!response) {
        alert('<?php echo __('write_response'); ?>');
        return;
    }

    // Disable submit button and show loading
    const submitBtn = document.querySelector('#adminResponseForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> <?php echo __('loading'); ?>';
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
            alert(data.message || '<?php echo __('response_success'); ?>');
            complaintModal.hide();
            location.reload();
        } else {
            alert(data.message || '<?php echo __('response_error'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php echo __('response_error'); ?>');
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

function getStatusClass($status) {
    switch ($status) {
        case 'new': return 'primary';
        case 'in_progress': return 'warning';
        case 'resolved': return 'success';
        case 'closed': return 'secondary';
        default: return 'info';
    }
}

include '../includes/footer.php';
?> 