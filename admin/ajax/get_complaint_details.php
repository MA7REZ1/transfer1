<?php
require_once '../../config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized');
}

if (!isset($_GET['id'])) {
    die('Invalid request');
}

$complaint_id = $_GET['id'];

// Fetch complaint details with related information
$stmt = $conn->prepare("
    SELECT 
        c.*,
        comp.name as company_name,
        d.username as driver_name,
        d.phone as driver_phone,
        r.order_number,
        r.pickup_location,
        r.delivery_location
    FROM complaints c
    LEFT JOIN companies comp ON c.company_id = comp.id
    LEFT JOIN drivers d ON c.driver_id = d.id
    LEFT JOIN requests r ON c.request_id = r.id
    WHERE c.id = ?
");

$stmt->execute([$complaint_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die('Complaint not found');
}

// Fetch complaint responses
$stmt = $conn->prepare("
    SELECT 
        cr.*,
        comp.name as company_name,
        a.username as admin_name,
        CASE 
            WHEN cr.is_company_reply = 1 THEN 'company'
            WHEN cr.admin_id IS NOT NULL THEN 'admin'
            ELSE 'unknown'
        END as responder_type
    FROM complaint_responses cr
    LEFT JOIN companies comp ON cr.company_id = comp.id
    LEFT JOIN admins a ON cr.admin_id = a.id
    WHERE cr.complaint_id = ?
    ORDER BY cr.created_at ASC
");
$stmt->execute([$complaint_id]);
$responses = $stmt->fetchAll();

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

$priority_class = match($complaint['priority']) {
    'high' => 'danger',
    'medium' => 'warning',
    'low' => 'info',
    default => 'secondary'
};

$priority_text = match($complaint['priority']) {
    'high' => 'عالية',
    'medium' => 'متوسطة',
    'low' => 'منخفضة',
    default => 'غير محدد'
};
?>

<div class="complaint-details">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>
                معلومات الشكوى #<?php echo htmlspecialchars($complaint['complaint_number'] ?? ''); ?>
            </h5>
            <div>
                <span class="badge bg-<?php echo $priority_class; ?> me-2">
                    <?php echo $priority_text; ?>
                </span>
                <span class="badge bg-<?php echo $status_class; ?>">
                    <?php echo $status_text; ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="info-group">
                        <label class="text-muted mb-2">معلومات الشركة والسائق</label>
                        <div class="d-flex align-items-center p-3 border rounded">
                            <i class="bi bi-building fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($complaint['company_name'] ?? ''); ?></h6>
                                <div class="text-muted mb-2">السائق: <?php echo htmlspecialchars($complaint['driver_name'] ?? ''); ?></div>
                                <span class="text-muted">رقم الطلب: <?php echo htmlspecialchars($complaint['order_number'] ?? ''); ?></span>
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
                                <p class="mb-2 ms-4"><?php echo htmlspecialchars($complaint['pickup_location'] ?? ''); ?></p>
                            </div>
                            <div>
                                <i class="bi bi-geo text-success"></i>
                                <strong>موقع التسليم:</strong>
                                <p class="mb-0 ms-4"><?php echo htmlspecialchars($complaint['delivery_location'] ?? ''); ?></p>
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
                            <?php echo htmlspecialchars($complaint['subject'] ?? ''); ?>
                        </h6>
                        <p class="card-text mt-3"><?php echo nl2br(htmlspecialchars($complaint['description'] ?? '')); ?></p>
                        <div class="text-muted mt-2">
                            <small>
                                <i class="bi bi-clock"></i>
                                <?php echo date('Y-m-d H:i', strtotime($complaint['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Responses Section -->
    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">
                <i class="bi bi-chat-dots me-2"></i>
                الردود والمتابعة
            </h5>
        </div>
        <div class="card-body">
            <?php if ($complaint['status'] !== 'closed'): ?>
            <div class="mb-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <form id="adminResponseForm" onsubmit="submitResponse(<?php echo $complaint['id']; ?>); return false;">
                            <div class="mb-3">
                                <label class="form-label">إضافة رد</label>
                                <textarea class="form-control" id="adminResponse" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تحديث حالة الشكوى</label>
                                <select class="form-select" id="complaintStatus">
                                    <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                    <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>تم الحل</option>
                                    <option value="closed" <?php echo $complaint['status'] === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                                </select>
                            </div>
                            <div class="text-end">
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
                            <div class="card <?php echo $response['responder_type'] === 'company' ? 'border-primary bg-primary bg-opacity-10' : ''; ?>">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center">
                                        <?php if ($response['responder_type'] === 'company'): ?>
                                            <div class="response-icon bg-primary bg-opacity-10 rounded-circle p-2 me-2">
                                                <i class="bi bi-building text-primary fs-4"></i>
                                            </div>
                                            <div>
                                                <strong class="text-primary"><?php echo htmlspecialchars($response['company_name'] ?? ''); ?></strong>
                                                <div class="text-muted small">رد الشركة</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="response-icon bg-success bg-opacity-10 rounded-circle p-2 me-2">
                                                <i class="bi bi-person text-success fs-4"></i>
                                            </div>
                                            <div>
                                                <strong class="text-success"><?php echo htmlspecialchars($response['admin_name'] ?? ''); ?></strong>
                                                <div class="text-muted small">رد منا</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted text-end">
                                        <div class="small">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?php echo date('Y-m-d', strtotime($response['created_at'])); ?>
                                        </div>
                                        <div class="small">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('H:i', strtotime($response['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="response-content">
                                        <?php echo nl2br(htmlspecialchars($response['response'] ?? '')); ?>
                                    </div>
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

.response-item .card {
    border-radius: 0.75rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border-width: 2px;
}

.response-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.badge {
    font-weight: 500;
}

.response-content {
    font-size: 1rem;
    line-height: 1.6;
    white-space: pre-wrap;
}
</style> 