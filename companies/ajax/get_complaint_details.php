<?php
require_once '../../config.php';

// Check if company is logged in
if (!isset($_SESSION['company_id'])) {
    die('Unauthorized');
}

if (!isset($_GET['complaint_number'])) {
    die('Invalid request');
}

// Add company_id column if it doesn't exist
try {
    // First check if columns exist
    $stmt = $conn->query("SHOW COLUMNS FROM complaint_responses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $alterQueries = [];
    
    // Check if company_id column exists
    if (!in_array('company_id', $columns)) {
        // Check if the key already exists
        $stmt = $conn->query("SHOW INDEX FROM complaint_responses WHERE Key_name = 'company_id'");
        $keyExists = $stmt->fetch();
        
        if (!$keyExists) {
            $alterQueries[] = "ADD COLUMN company_id INT NULL";
            $alterQueries[] = "ADD KEY `company_id` (`company_id`)";
            $alterQueries[] = "ADD CONSTRAINT `complaint_responses_company_fk` 
                FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) 
                ON DELETE SET NULL";
        }
    }
    
    if (!in_array('is_company_reply', $columns)) {
        $alterQueries[] = "ADD COLUMN is_company_reply TINYINT(1) DEFAULT 0";
    }
    
    if (!empty($alterQueries)) {
        $conn->exec("ALTER TABLE complaint_responses " . implode(", ", $alterQueries));
    }
} catch (Exception $e) {
    // Log error but continue
    error_log("Error modifying complaint_responses table: " . $e->getMessage());
}

$complaint_number = $_GET['complaint_number'];
$company_id = $_SESSION['company_id'];

// Fetch complaint details with related information
$stmt = $conn->prepare("
    SELECT 
        c.*,
        d.username as driver_name,
        r.order_number as request_number,
        r.pickup_location,
        r.delivery_location
    FROM complaints c
    LEFT JOIN drivers d ON c.driver_id = d.id
    LEFT JOIN requests r ON c.request_id = r.id
    WHERE c.complaint_number = ? AND c.company_id = ?
");

$stmt->execute([$complaint_number, $company_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die('Complaint not found');
}

// Define status variables
$status_class = match($complaint['status']) {
    'new' => 'warning',
    'in_progress' => 'info',
    'resolved' => 'success',
    'closed' => 'secondary',
    default => 'secondary'
};

$status_text = match($complaint['status']) {
    'new' => 'جديدة',
    'in_progress' => 'قيد المعالجة',
    'resolved' => 'تم الحل',
    'closed' => 'مغلقة',
    default => 'غير معروف'
};

// Fetch complaint responses with proper handling of both admin and company responses
$stmt = $conn->prepare("
    SELECT 
        cr.*,
        CASE 
            WHEN cr.is_company_reply = 1 THEN CONCAT(c.name, ' (الشركة)')
            WHEN cr.admin_id IS NOT NULL THEN a.username
            ELSE 'غير معروف'
        END as responder_name,
        CASE 
            WHEN cr.is_company_reply = 1 THEN 'company'
            WHEN cr.admin_id IS NOT NULL THEN 'admin'
            ELSE 'unknown'
        END as responder_type
    FROM complaint_responses cr
    LEFT JOIN admins a ON cr.admin_id = a.id
    LEFT JOIN companies c ON cr.company_id = c.id
    WHERE cr.complaint_id = ?
    ORDER BY cr.created_at ASC
");
$stmt->execute([$complaint['id']]);
$responses = $stmt->fetchAll();
?>

<div class="complaint-details">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>
                معلومات الشكوى #<?php echo htmlspecialchars($complaint['complaint_number']); ?>
            </h5>
            <span class="badge bg-<?php echo $status_class; ?> fs-6">
                <?php echo $status_text; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="info-group">
                        <label class="text-muted mb-2">معلومات السائق</label>
                        <div class="d-flex align-items-center p-3 border rounded">
                            <i class="bi bi-person-circle fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($complaint['driver_name']); ?></h6>
                                <span class="text-muted">رقم الطلب: <?php echo htmlspecialchars($complaint['request_number']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <label class="text-muted mb-2">معلومات التوصيل</label>
                        <div class="p-3 border rounded">
                            <div class="mb-2">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <strong>موقع الاستلام:</strong>
                                <p class="mb-2 ms-4"><?php echo htmlspecialchars($complaint['pickup_location']); ?></p>
                            </div>
                            <div>
                                <i class="bi bi-geo text-success"></i>
                                <strong>موقع التسليم:</strong>
                                <p class="mb-0 ms-4"><?php echo htmlspecialchars($complaint['delivery_location']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="complaint-content mt-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-chat-right-text me-2"></i>
                            <?php echo htmlspecialchars($complaint['subject']); ?>
                        </h6>
                        <p class="card-text mt-3"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-chat-dots me-2"></i>
                الردود والمتابعة
            </h5>
            <?php if ($complaint['status'] !== 'closed'): ?>
            <button class="btn btn-primary btn-sm" onclick="toggleReplyForm()">
                <i class="bi bi-reply me-1"></i>
                إضافة رد
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($complaint['status'] !== 'closed'): ?>
            <div id="replyForm" style="display: none;" class="mb-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <form id="complaintReplyForm" onsubmit="submitReply(event)">
                            <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['id']); ?>">
                            <div class="mb-3">
                                <label class="form-label">الرد</label>
                                <textarea class="form-control" name="reply" rows="3" required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" onclick="toggleReplyForm()">إلغاء</button>
                                <button type="submit" class="btn btn-primary">إرسال الرد</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="responses-timeline">
                <?php if (empty($responses)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-chat-square-text fs-1"></i>
                        <p class="mt-2">لا توجد ردود حتى الآن</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($responses as $response): ?>
                        <div class="response-item mb-3">
                            <div class="card <?php echo $response['is_company_reply'] == 1 ? 'border-primary' : ''; ?>">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi <?php echo $response['is_company_reply'] == 1 ? 'bi-building text-primary' : 'bi-shield-check text-success'; ?> fs-4 me-2"></i>
                                        <strong><?php echo htmlspecialchars($response['responder_name']); ?></strong>
                                        <?php if ($response['is_company_reply'] != 1): ?>
                                            <span class="badge bg-success ms-2">مدير النظام</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($response['response'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.complaint-details {
    max-height: 80vh;
    overflow-y: auto;
    padding: 1rem;
}

.info-group {
    height: 100%;
}

.responses-timeline {
    position: relative;
}

.responses-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
    display: none;
}

.response-item {
    position: relative;
    padding-left: 15px;
}

.response-item .card {
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.badge {
    font-weight: 500;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
}
</style>

<script>
function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        form.querySelector('textarea').focus();
    }
}

function submitReply(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    // Validate form data
    const reply = formData.get('reply');
    const complaintId = formData.get('complaint_id');
    
    if (!reply || !reply.trim()) {
        showAlert('error', 'الرجاء إدخال الرد');
        return;
    }
    
    if (!complaintId) {
        showAlert('error', 'خطأ في معرف الشكوى');
        return;
    }

    // Add debugging logs
    console.log('Submitting reply...', {
        complaintId: complaintId,
        replyLength: reply.length
    });

    // Disable submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الإرسال...';
    submitBtn.disabled = true;

    // Create data object
    const data = {
        complaint_id: complaintId,
        reply: reply
    };

    console.log('Sending data:', data);

    fetch('../ajax/submit_complaint_reply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Hide the reply form
            toggleReplyForm();
            
            // Clear the textarea
            form.querySelector('textarea').value = '';
            
            // Refresh the responses section
            refreshComplaintDetails();
            
            // Show success message
            showAlert('success', data.message || 'تم إرسال الرد بنجاح');
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء إرسال الرد');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', error.message || 'حدث خطأ أثناء إرسال الرد');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function refreshComplaintDetails() {
    const complaintNumber = '<?php echo htmlspecialchars($complaint['complaint_number']); ?>';
    
    // Show loading spinner in the responses-timeline
    const responsesTimeline = document.querySelector('.responses-timeline');
    if (responsesTimeline) {
        responsesTimeline.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;
    }

    // Fetch updated content
    fetch(`../ajax/get_complaint_details.php?complaint_number=${complaintNumber}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            // Create a temporary container
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Find the new responses timeline
            const newResponsesTimeline = tempDiv.querySelector('.responses-timeline');
            if (newResponsesTimeline && responsesTimeline) {
                responsesTimeline.innerHTML = newResponsesTimeline.innerHTML;
            } else {
                throw new Error('Could not find responses timeline element');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (responsesTimeline) {
                responsesTimeline.innerHTML = `
                    <div class="alert alert-danger m-3">
                        حدث خطأ أثناء تحديث الردود. يرجى تحديث الصفحة.
                    </div>
                `;
            }
            showAlert('error', 'حدث خطأ أثناء تحديث الردود');
        });
}

function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to document
    document.body.appendChild(alertDiv);
    
    // Remove after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Add event listener for modal close
document.addEventListener('hidden.bs.modal', function (event) {
    if (event.target.id === 'complaintDetailsModal') {
        // Reset scroll position
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
}, false);
</script> 