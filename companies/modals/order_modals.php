<?php
// Prevent direct access
if (!defined('BASEPATH')) exit('No direct script access allowed');
?>

<!-- New Order Modal -->
<div class="modal fade" id="newRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> طلب جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newOrderForm" onsubmit="return false;" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">نوع الطلب <span class="text-danger">*</span></label>
                                <select class="form-select" name="order_type" required>
                                    <option value="">اختر نوع الطلب</option>
                                    <option value="delivery">توصيل</option>
                                    <option value="transport">نقل</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">هاتف العميل <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="customer_phone" required 
                                       pattern="[0-9]{10}" title="الرجاء إدخال رقم هاتف صحيح مكون من 10 أرقام">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تاريخ التوصيل <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="delivery_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">موقع الاستلام <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="pickup_location" rows="2" required 
                                          minlength="10"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">موقع التوصيل <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="delivery_location" rows="2" required 
                                          minlength="10"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">عدد القطع <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="items_count" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">التكلفة الإجمالية <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="total_cost" required min="0">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="">اختر طريقة الدفع</option>
                                    <option value="cash">نقدي</option>
                                    <option value="card">بطاقة</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">صورة الفاتورة</label>
                                <input type="file" class="form-control" name="invoice_file" 
                                       accept="image/*">
                                <small class="text-muted">يمكنك رفع صورة الفاتورة (اختياري)</small>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_fragile" id="is_fragile">
                                    <label class="form-check-label" for="is_fragile">شحنة قابلة للكسر</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات إضافية</label>
                                <textarea class="form-control" name="additional_notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="submitNewOrder()">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> تعديل الطلب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editOrderForm">
                <input type="hidden" name="order_id" id="edit_order_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">نوع الطلب</label>
                                <select class="form-select" name="order_type" id="edit_order_type" required>
                                    <option value="delivery">توصيل</option>
                                    <option value="transport">نقل</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">اسم العميل</label>
                                <input type="text" class="form-control" name="customer_name" id="edit_customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">هاتف العميل</label>
                                <input type="text" class="form-control" name="customer_phone" id="edit_customer_phone" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">تاريخ التوصيل</label>
                                <input type="date" class="form-control" name="delivery_date" id="edit_delivery_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">موقع الاستلام</label>
                                <textarea class="form-control" name="pickup_location" id="edit_pickup_location" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">موقع التوصيل</label>
                                <textarea class="form-control" name="delivery_location" id="edit_delivery_location" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">عدد القطع</label>
                                <input type="number" class="form-control" name="items_count" id="edit_items_count" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">التكلفة الإجمالية</label>
                                <input type="number" step="0.01" class="form-control" name="total_cost" id="edit_total_cost" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">طريقة الدفع</label>
                                <select class="form-select" name="payment_method" id="edit_payment_method" required>
                                    <option value="cash">نقدي</option>
                                    <option value="card">بطاقة</option>
                                    <option value="bank_transfer">تحويل بنكي</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">صورة الفاتورة</label>
                                <input type="file" class="form-control" name="invoice_file" id="edit_invoice_file" accept="image/*">
                                <small class="text-muted">يمكنك رفع صورة الفاتورة (اختياري)</small>
                                <div id="current_invoice" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_fragile" id="edit_is_fragile">
                                    <label class="form-check-label" for="edit_is_fragile">شحنة قابلة للكسر</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات إضافية</label>
                                <textarea class="form-control" name="additional_notes" id="edit_additional_notes" rows="2"></textarea>
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

<!-- Rate Driver Modal -->
<div class="modal fade" id="rateDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-star"></i> تقيم السائق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rateDriverForm">
                <div class="modal-body">
                    <input type="hidden" name="driver_id" id="rateDriverId">
                    <div class="mb-3">
                        <label class="form-label">التقييم</label>
                        <div class="rating">
                            <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                            <label for="star<?php echo $i; ?>">☆</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تعليق</label>
                        <textarea class="form-control" name="comment" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال التقييم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complaint Modal -->
<div class="modal fade" id="complaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> تقديم شكوى</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="complaintForm">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="complaintRequestId">
                    <div class="mb-3">
                        <label class="form-label">موضوع الشكوى</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تفاصيل الشكوى</label>
                        <textarea class="form-control" name="details" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال الشكوى</button>
                </div>
            </form>
        </div>
    </div>
</div> 