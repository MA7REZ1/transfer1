<?php
require_once 'config.php';


// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Get all feedback with related information
$query = "
    SELECT 
        cf.*,
        r.order_number,
        r.pickup_location,
        r.delivery_location,
        r.customer_name,
        r.customer_phone,
        r.status as order_status,
        r.created_at as order_date,
        d.username as driver_name,
        d.phone as driver_phone
    FROM customer_feedback cf
    JOIN requests r ON cf.request_id = r.id
    LEFT JOIN drivers d ON r.driver_id = d.id
    ORDER BY cf.created_at DESC
";

$stmt = $conn->query($query);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include '../includes/header.php';
?>

<style>
.modal-header .btn-close {
    margin: unset;
    left: 0;
    position: absolute;
    margin-left: 1rem;
}
.modal {
    direction: rtl;
}
.modal-header {
    display: flex;
    justify-content: center;
    position: relative;
}
.feedback-modal .modal-content {
    border-radius: 15px;
}
.feedback-modal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.feedback-modal .modal-body {
    padding: 1.5rem;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
}
.feedback-modal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}
.btn-view-feedback {
    transition: all 0.3s ease;
}
.btn-view-feedback:hover {
    transform: scale(1.05);
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">ملاحظات العملاء</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>العميل</th>
                                    <th>رقم الهاتف</th>
                                    <th>السائق</th>
                                    <th>من</th>
                                    <th>إلى</th>
                                    <th>الملاحظات</th>
                                    <th>تاريخ الملاحظة</th>
                                    <th>حالة الطلب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($feedbacks as $feedback): ?>
                                    <tr>
                                        <td>
                                            <a href="orders.php?search=<?php echo htmlspecialchars($feedback['order_number']); ?>">
                                                <?php echo htmlspecialchars($feedback['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['customer_phone']); ?></td>
                                        <td>
                                            <?php if ($feedback['driver_name']): ?>
                                                <?php echo htmlspecialchars($feedback['driver_name']); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($feedback['driver_phone']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">لم يتم تعيين سائق</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($feedback['pickup_location']); ?></td>
                                        <td><?php echo htmlspecialchars($feedback['delivery_location']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info btn-view-feedback" 
                                                    onclick="showFeedback('<?php echo htmlspecialchars(addslashes($feedback['feedback'])); ?>', '<?php echo htmlspecialchars($feedback['order_number']); ?>')">
                                                عرض الملاحظات
                                            </button>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($feedback['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusClass($feedback['order_status']); ?>">
                                                <?php echo getStatusText($feedback['order_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade feedback-modal" id="feedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ملاحظات الطلب رقم <span id="modalOrderNumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalFeedbackContent">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
function showFeedback(feedback, orderNumber) {
    document.getElementById('modalOrderNumber').textContent = orderNumber;
    document.getElementById('modalFeedbackContent').textContent = feedback;
    
    const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    modal.show();
}

// تهيئة DataTables
$(document).ready(function() {
    $('#feedbackTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
        },
        "order": [[7, "desc"]], // ترتيب حسب تاريخ الملاحظة تنازلياً
        "pageLength": 25
    });
});
</script>

<?php
// Helper functions for status
function getStatusClass($status) {
    return match($status) {
        'pending' => 'warning',
        'accepted' => 'info',
        'in_transit' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
        default => 'secondary'
    };
}

function getStatusText($status) {
    return match($status) {
        'pending' => 'قيد الانتظار',
        'accepted' => 'تم القبول',
        'in_transit' => 'جاري التوصيل',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        default => 'غير معروف'
    };
}
?> 