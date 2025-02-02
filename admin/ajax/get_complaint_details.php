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

<style>
/* تحسينات الشكل والتنسيقات */
.complaint-details {
    max-height: 85vh;
    overflow-y: auto;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 20px;
}

.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    background: white;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 25px rgba(0,0,0,0.12);
}

.card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, #0d6efd, #0a58ca) !important;
}

.card-body {
    padding: 1.5rem;
}

.info-group {
    height: 100%;
}

.info-group .border {
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.1) !important;
    transition: all 0.3s ease;
}

.info-group .border:hover {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
}

.badge {
    padding: 0.6rem 1rem;
    font-weight: 500;
    border-radius: 50px;
}

.response-item {
    margin-bottom: 1.5rem;
}

.response-item .card {
    border-radius: 15px;
    border: 2px solid transparent;
}

.response-item .card.border-primary {
    border-color: rgba(13,110,253,0.3);
}

.response-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    margin-right: 1rem;
}

.response-content {
    font-size: 1rem;
    line-height: 1.7;
    color: #444;
}

.form-control {
    border-radius: 10px;
    border: 1px solid rgba(0,0,0,0.1);
    padding: 0.8rem 1rem;
    font-size: 1rem;
}

.form-control:focus {
    box-shadow: 0 0 0 4px rgba(13,110,253,0.1);
    border-color: #0d6efd;
}

.btn {
    padding: 0.6rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border: none;
    box-shadow: 0 4px 15px rgba(13,110,253,0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13,110,253,0.4);
}

/* تحسينات للموبايل */
@media (max-width: 767px) {
    .complaint-details {
        padding: 0.5rem;
        max-height: none;
        background: white;
    }

    .card {
        margin-bottom: 0.75rem;
        border-radius: 10px;
        box-shadow: 0 1px 10px rgba(0,0,0,0.05);
    }

    .card-header, .card-body {
        padding: 1rem;
    }

    .card-header.bg-primary {
        border-radius: 10px 10px 0 0;
    }

    .response-icon {
        width: 36px;
        height: 36px;
        margin-right: 0.75rem;
    }

    .badge {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }

    .info-group .border {
        padding: 0.75rem !important;
    }

    .info-group h6 {
        font-size: 0.9rem;
    }

    .info-group .text-muted {
        font-size: 0.85rem;
    }

    .response-content {
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .form-control {
        font-size: 0.9rem;
        padding: 0.6rem 0.8rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .response-item .card-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start !important;
    }

    .response-item .text-end {
        width: 100%;
        display: flex;
        justify-content: space-between;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.05);
    }

    .complaint-content .card {
        box-shadow: none;
        background: #f8f9fa;
    }

    .responses-timeline {
        display: flex;
        flex-direction: column-reverse;
    }

    .response-form-container {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1rem;
        border-top: 1px solid rgba(0,0,0,0.1);
        margin: 0 -0.5rem;
        border-radius: 15px 15px 0 0;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
    }

    .response-form-container .card {
        margin-bottom: 0;
        box-shadow: none;
    }
}
</style>

<div class="complaint-details">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
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
                            <i class="bi bi-building fs-3 me-3 text-primary"></i>
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
                            <div class="mb-3">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <strong>موقع الاستلام:</strong>
                                <p class="mb-2 ms-4 mt-2"><?php echo htmlspecialchars($complaint['pickup_location'] ?? ''); ?></p>
                            </div>
                            <div>
                                <i class="bi bi-geo text-success"></i>
                                <strong>موقع التسليم:</strong>
                                <p class="mb-0 ms-4 mt-2"><?php echo htmlspecialchars($complaint['delivery_location'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="complaint-content mt-4">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <h6 class="card-title d-flex align-items-center">
                            <i class="bi bi-chat-right-text me-2 text-primary"></i>
                            <?php echo htmlspecialchars($complaint['subject'] ?? ''); ?>
                        </h6>
                        <p class="card-text mt-3"><?php echo nl2br(htmlspecialchars($complaint['description'] ?? '')); ?></p>
                        <div class="text-muted mt-3">
                            <small>
                                <i class="bi bi-clock me-1"></i>
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
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="bi bi-chat-dots me-2 text-primary"></i>
                الردود والمتابعة
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="responses-timeline">
                <?php if (empty($responses)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-square-text fs-1 mb-3 d-block"></i>
                        <p class="mb-0">لا توجد ردود حتى الآن</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($responses as $response): ?>
                        <div class="response-item px-3">
                            <div class="card <?php echo $response['responder_type'] === 'company' ? 'border-primary bg-primary bg-opacity-10' : ''; ?>">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <?php if ($response['responder_type'] === 'company'): ?>
                                            <div class="response-icon bg-primary bg-opacity-10">
                                                <i class="bi bi-building text-primary fs-4"></i>
                                            </div>
                                            <div>
                                                <strong class="text-primary"><?php echo htmlspecialchars($response['company_name'] ?? ''); ?></strong>
                                                <div class="text-muted small">رد الشركة</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="response-icon bg-success bg-opacity-10">
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

            <?php if ($complaint['status'] !== 'closed'): ?>
            <div class="response-form-container">
                <div class="card bg-light border-0">
                    <div class="card-body">
                        <form id="adminResponseForm" onsubmit="submitResponse(<?php echo $complaint['id']; ?>); return false;">
                            <div class="mb-3">
                                <label class="form-label">إضافة رد</label>
                                <textarea class="form-control" id="adminResponse" rows="2" required placeholder="اكتب ردك هنا..."></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col">
                                    <select class="form-select" id="complaintStatus">
                                        <option value="in_progress" <?php echo $complaint['status'] === 'in_progress' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                        <option value="resolved" <?php echo $complaint['status'] === 'resolved' ? 'selected' : ''; ?>>تم الحل</option>
                                        <option value="closed" <?php echo $complaint['status'] === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>
                                        إرسال
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div> 