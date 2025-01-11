<?php
// Complaint Details Modal
?>
<!-- Complaint Details Modal -->
<div class="modal fade" id="complaintDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الشكوى</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openComplaintDetails(complaintNumber) {
    const modal = document.getElementById('complaintDetailsModal');
    modal.dataset.complaintNumber = complaintNumber;
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Fetch complaint details
    fetch(`ajax/get_complaint_details.php?complaint_number=${complaintNumber}`)
        .then(response => response.text())
        .then(html => {
            modal.querySelector('.modal-body').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            modal.querySelector('.modal-body').innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل تفاصيل الشكوى</div>';
        });
}

function toggleReplyForm() {
    const form = document.getElementById('replyForm');
    if (form) {
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        form.querySelector('textarea').focus();
        }
    }
}

function submitReply(event) {
    event.preventDefault();
    const form = event.target;
    const complaintNumber = document.querySelector('#complaintDetailsModal').dataset.complaintNumber;
    const replyText = form.querySelector('textarea[name="reply"]').value.trim();
    
    if (!replyText) {
        showAlert('تحذير', 'الرجاء كتابة رد');
        return;
    }

    // Disable submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الإرسال...';
    submitBtn.disabled = true;

    fetch('ajax/submit_complaint_reply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            complaint_number: complaintNumber,
            reply: replyText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide the reply form
            toggleReplyForm();
            
            // Clear the textarea
            form.querySelector('textarea').value = '';
            
            // Refresh the responses section
            refreshComplaintDetails();
            
            // Show success message
            showAlert('نجاح', 'تم إرسال الرد بنجاح');

            // Refresh complaint responses if they're visible
            if (typeof fetchComplaintResponses === 'function') {
                fetchComplaintResponses();
            }
        } else {
            showAlert('خطأ', data.message || 'حدث خطأ أثناء إرسال الرد');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('خطأ', 'حدث خطأ أثناء إرسال الرد');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function refreshComplaintDetails() {
    const complaintNumber = document.querySelector('#complaintDetailsModal').dataset.complaintNumber;
    const modalBody = document.querySelector('#complaintDetailsModal .modal-body');
    
    // Show loading spinner
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;

    // Fetch updated content
    fetch(`ajax/get_complaint_details.php?complaint_number=${complaintNumber}`)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="alert alert-danger m-3">حدث خطأ أثناء تحديث المحتوى</div>';
        });
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