<?php
require_once '../config.php';
if (!isset($_SESSION['company_id']) && !isset($_SESSION['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}?>

<!-- تحميل المكتبات المطلوبة -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- تحديث سكربت خرائط Google -->
<script>
// تعريف دالة callback قبل تحميل الخريطة
function initMapsCallback() {
    console.log('Maps callback initiated');
    initMap('pickup_map');
    initMap('delivery_map');
}

window.initMapsCallback = initMapsCallback;
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAVVKK9ZJ_ZnugxJmw3BSns-BsJ4V_pxIA&libraries=places&language=ar&callback=initMapsCallback" async defer></script>

<!-- تحسين مظهر الخريطة -->
<style>
.map-container {
    position: relative;
    height: 350px;
    margin: 8px 0;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

#pickup_map, #delivery_map, #edit_pickup_map, #edit_delivery_map {
    width: 100% !important;
    height: 100% !important;
    min-height: 350px;
}

.location-input-group {
    margin-bottom: 15px;
}

.location-search-container {
    margin-bottom: 10px;
}

.location-search-input {
    border-radius: 8px !important;
    padding: 10px 15px;
    font-size: 14px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.location-search-input:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.location-search-button {
    border-radius: 8px !important;
    padding: 8px 15px;
    margin-right: 5px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.location-search-button:hover {
    background-color: #e9ecef;
    border-color: #ddd;
}

.location-details {
    margin-top: 12px;
}

.location-address-input {
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 14px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
    margin-top: 8px;
}

.location-address-input:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.custom-map-control {
    background-color: #fff;
    border: none !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 6px rgba(0,0,0,.2);
    cursor: pointer;
    margin: 10px;
    padding: 8px 15px;
    text-align: center;
    direction: rtl;
    font-size: 14px;
    transition: all 0.3s ease;
}

.custom-map-control:hover {
    background-color: #f8f9fa;
    box-shadow: 0 4px 8px rgba(0,0,0,.2);
}

.custom-map-control i {
    margin-left: 5px;
}

.pac-container {
    direction: rtl;
    text-align: right;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,.1);
    margin-top: 5px;
    font-family: inherit;
}

.pac-item {
    padding: 8px 15px;
    font-size: 14px;
    border-bottom: 1px solid #f0f0f0;
}

.pac-item:hover {
    background-color: #f8f9fa;
}

.location-section {
    background-color: #fff;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.location-section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 15px;
    color: #333;
}

.input-group {
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.input-group:focus-within {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.input-group-text {
    border-radius: 8px 0 0 8px !important;
    border: 1px solid #ddd;
    padding: 0.5rem 1rem;
    color: #666;
}

.input-group .form-control {
    border: 1px solid #ddd;
    padding: 0.5rem 1rem;
    font-size: 14px;
}

.input-group .form-control:focus {
    border-color: #80bdff;
    box-shadow: none;
}

.input-group-text, .form-control {
    background-color: #fff !important;
}

/* تحسين مظهر الاقتراحات */
.pac-container {
    border-radius: 8px;
    margin-top: 5px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    border: none;
    padding: 5px 0;
}

.pac-item {
    padding: 8px 15px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.pac-item:hover {
    background-color: #f8f9fa;
}

.pac-item-query {
    font-size: 14px;
    color: #333;
}

.pac-matched {
    font-weight: bold;
    color: #007bff;
}
</style>

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
                                <label class="form-label">تاريخ ووقت التوصيل <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-6">
                                <input type="date" class="form-control" name="delivery_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="delivery_time" required
                                               value="<?php echo date('H:i'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 location-section">
                                <label class="location-section-title">موقع الاستلام <span class="text-danger">*</span></label>
                                <small class="d-block text-muted mb-2">اكتب اسم الحي أو الشارع أو المنطقة للبحث عن الموقع (مثال: حي النزهة، شارع التحلية، الرياض)</small>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-start-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-end-0 shadow-none ps-0" 
                                               id="pickup_search" 
                                               placeholder="ابحث عن موقع الاستلام (مثال: حي النزهة، شارع التحلية، الرياض)" 
                                               autocomplete="off"
                                               style="border-radius: 8px;">
                                        <button type="button" class="btn btn-primary ms-2" onclick="getCurrentLocation('pickup')">
                                            <i class="bi bi-geo-alt"></i> موقعي الحالي
                                        </button>
                                    </div>
                                </div>
                                <div class="location-details">
                                    <label class="form-label mb-2">تفاصيل العنوان (اكتب وصف دقيق للموقع مثل: اسم المبنى، رقم الشقة، علامة مميزة)</label>
                                    <textarea class="form-control location-address-input" name="pickup_location" rows="2" required minlength="10"></textarea>
                                    <input type="text" class="form-control location-address-input mt-2" name="pickup_location_link" readonly
                                           placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="pickup_map" style="height: 100%;"></div>
                                </div>
                            </div>
                            <div class="mb-3 location-section">
                                <label class="location-section-title">موقع التوصيل <span class="text-danger">*</span></label>
                                <small class="d-block text-muted mb-2">اكتب اسم الحي أو الشارع أو المنطقة للبحث عن الموقع (مثال: حي النزهة، شارع التحلية، الرياض)</small>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-start-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-end-0 shadow-none ps-0" 
                                               id="delivery_search" 
                                               placeholder="ابحث عن موقع التوصيل..." 
                                               autocomplete="off"
                                               style="border-radius: 8px;">
                                    </div>
                                </div>
                                <div class="location-details">
                                    <label class="form-label mb-2">تفاصيل العنوان (اكتب وصف دقيق للموقع مثل: اسم المبنى، رقم الشقة، علامة مميزة)</label>
                                    <textarea class="form-control location-address-input" name="delivery_location" rows="2" required minlength="10"></textarea>
                                    <input type="text" class="form-control location-address-input mt-2" name="delivery_location_link" readonly
                                           placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="delivery_map" style="height: 100%;"></div>
                                </div>
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
                                <label class="form-label">حالة الدفع<span class="text-danger">*</span></label>
                                <select class="form-select" name="payment_method" id="payment_method" required onchange="toggleTotalCost(this.value)">
                                    <option value="">اختر طريقة الدفع</option>
                                    <option value="cash">الدفع عند الاستلام</option>
                                    <option value="card">مدفوع</option>
                                 
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
                                <label class="form-label">تاريخ ووقت التوصيل</label>
                                <div class="row">
                                    <div class="col-md-6">
                                <input type="date" class="form-control" name="delivery_date" id="edit_delivery_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="time" class="form-control" name="delivery_time" id="edit_delivery_time" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 location-section">
                                <label class="location-section-title">موقع الاستلام</label>
                                <small class="d-block text-muted mb-2">اكتب اسم الحي أو الشارع أو المنطقة للبحث عن الموقع (مثال: حي النزهة، شارع التحلية، الرياض)</small>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-start-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-end-0 shadow-none ps-0" 
                                               id="edit_pickup_search" 
                                               placeholder="ابحث عن موقع الاستلام..." 
                                               autocomplete="off"
                                               style="border-radius: 8px;">
                                    </div>
                                </div>
                                <div class="location-details">
                                    <label class="form-label mb-2">تفاصيل العنوان (اكتب وصف دقيق للموقع مثل: اسم المبنى، رقم الشقة، علامة مميزة)</label>
                                    <textarea class="form-control location-address-input" name="pickup_location" id="edit_pickup_location" rows="2" required></textarea>
                                    <input type="text" class="form-control location-address-input mt-2" name="pickup_location_link" id="edit_pickup_location_link" readonly
                                           placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="edit_pickup_map" style="height: 100%;"></div>
                                </div>
                            </div>
                            <div class="mb-3 location-section">
                                <label class="location-section-title">موقع التوصيل</label>
                                <small class="d-block text-muted mb-2">اكتب اسم الحي أو الشارع أو المنطقة للبحث عن الموقع (مثال: حي النزهة، شارع التحلية، الرياض)</small>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-start-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control border-end-0 shadow-none ps-0" 
                                               id="edit_delivery_search" 
                                               placeholder="ابحث عن موقع التوصيل..." 
                                               autocomplete="off"
                                               style="border-radius: 8px;">
                                    </div>
                                </div>
                                <div class="location-details">
                                    <label class="form-label mb-2">تفاصيل العنوان (اكتب وصف دقيق للموقع مثل: اسم المبنى، رقم الشقة، علامة مميزة)</label>
                                    <textarea class="form-control location-address-input" name="delivery_location" id="edit_delivery_location" rows="2" required></textarea>
                                    <input type="text" class="form-control location-address-input mt-2" name="delivery_location_link" id="edit_delivery_location_link" readonly
                                           placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="edit_delivery_map" style="height: 100%;"></div>
                                </div>
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
                <h5 class="modal-title"><i class="bi bi-star"></i> تقييم السائق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rateDriverForm">
                <input type="hidden" name="request_id" id="rate_request_id">
                <input type="hidden" name="driver_id" id="rate_driver_id">
                <div class="modal-body">
                    <div class="rating-stars mb-3">
                        <div class="stars-container text-center">
                            <div class="stars d-flex justify-content-center flex-row-reverse gap-2">
                                <input type="radio" id="star5" name="rating" value="5">
                                <label for="star5" title="5 نجوم">★</label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 نجوم">★</label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 نجوم">★</label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="نجمتان">★</label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="نجمة واحدة">★</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تعليق على التقييم (اختياري)</label>
                        <textarea class="form-control" name="rating_comment" rows="3"></textarea>
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
                <input type="hidden" name="request_id" id="complaint_request_id">
                <input type="hidden" name="driver_id" id="complaint_driver_id">
                <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">موضوع الشكوى</label>
                            <input type="text" class="form-control" name="complaint_subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">وصف الشكوى</label>
                        <textarea class="form-control" name="complaint_description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الأولوية</label>
                            <select class="form-select" name="complaint_priority">
                                <option value="low">منخفضة</option>
                                <option value="medium" selected>متوسطة</option>
                                <option value="high">عالية</option>
                            </select>
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

<script>
let maps = {};
let markers = {};
let searchBoxes = {};

// تحديث دالة تهيئة الخريطة
function initMap(elementId, defaultLat = 24.7136, defaultLng = 46.6753) {
    console.log('Initializing map:', elementId);
    const mapElement = document.getElementById(elementId);
    if (!mapElement) {
        console.error('Map element not found:', elementId);
        return;
    }

    try {
        const mapOptions = {
            center: { lat: defaultLat, lng: defaultLng },
            zoom: 13,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_RIGHT
            },
            fullscreenControl: true,
            streetViewControl: true,
            zoomControl: true
        };

        const map = new google.maps.Map(mapElement, mapOptions);
        console.log('Map created successfully');
        
        // إضافة علامة قابلة للسحب
        const marker = new google.maps.Marker({
            position: { lat: defaultLat, lng: defaultLng },
            map: map,
            draggable: true,
            animation: google.maps.Animation.DROP
        });

        // تحسين البحث باستخدام Autocomplete
        const searchInput = document.getElementById(elementId.replace('map', 'search'));
        if (searchInput) {
            const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                componentRestrictions: { country: 'sa' },
                fields: ['address_components', 'geometry', 'name', 'formatted_address'],
                types: ['address', 'establishment', 'geocode']
            });

            autocomplete.bindTo('bounds', map);

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                
                if (!place.geometry || !place.geometry.location) {
                    showAlert('لم يتم العثور على الموقع المحدد', 'warning');
                    return;
                }

                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }

                marker.setPosition(place.geometry.location);
                
                updateLocation(elementId, {
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                }, place.formatted_address);
            });
        }

        // حفظ المراجع
        maps[elementId] = map;
        markers[elementId] = marker;

        // تحديث عند سحب العلامة
        marker.addListener('dragend', () => {
            const position = marker.getPosition();
            updateLocation(elementId, {
                lat: position.lat(),
                lng: position.lng()
            });
        });

        // تحديث عند النقر على الخريطة
        map.addListener('click', (e) => {
            marker.setPosition(e.latLng);
            updateLocation(elementId, {
                lat: e.latLng.lat(),
                lng: e.latLng.lng()
            });
        });

        return { map, marker };
    } catch (error) {
        console.error('Error initializing map:', error);
        showAlert('حدث خطأ أثناء تحميل الخريطة', 'danger');
    }
}

// تحديث دالة تحديث الموقع
function updateLocation(mapId, position, address = null) {
    const type = mapId.includes('pickup') ? 'pickup' : 'delivery';
    const prefix = mapId.includes('edit') ? 'edit_' : '';
    
    // تحديث حقل الرابط
    const linkInput = document.querySelector(`[name="${type}_location_link"]${prefix ? `#${prefix}${type}_location_link` : ''}`);
    if (linkInput) {
        const googleMapsLink = `https://www.google.com/maps?q=${position.lat},${position.lng}`;
        linkInput.value = googleMapsLink;
    }

    // تحديث حقل العنوان
    const locationInput = document.getElementById(`${prefix}${type}_location`);
    if (locationInput) {
        if (address) {
            locationInput.value = address;
            showAlert('تم تحديث العنوان بنجاح', 'success');
        } else {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: position }, (results, status) => {
                if (status === "OK" && results[0]) {
                    locationInput.value = results[0].formatted_address;
                    // تحديث حقل البحث أيضاً
                    const searchInput = document.getElementById(`${prefix}${type}_search`);
                    if (searchInput) {
                        searchInput.value = results[0].formatted_address;
                    }
                } else {
                    locationInput.value = `${position.lat}, ${position.lng}`;
                }
            });
        }
    }
}

// تحديث الخرائط عند فتح النوافذ المنبثقة
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('newRequestModal');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal shown, initializing maps');
            setTimeout(() => {
                initMap('pickup_map');
                initMap('delivery_map');
                // تحديث حجم الخرائط
                Object.values(maps).forEach(map => {
                    google.maps.event.trigger(map, 'resize');
                });
            }, 500);
        });
    }
});

document.getElementById('editOrderModal').addEventListener('shown.bs.modal', function() {
    setTimeout(() => {
        ['edit_pickup_map', 'edit_delivery_map'].forEach(mapId => {
            if (!maps[mapId]) {
                initMap(mapId);
            } else {
                google.maps.event.trigger(maps[mapId], 'resize');
            }
        });
    }, 100);
});

// إضافة مستمعي الأحداث لحقول البحث
['pickup_search', 'delivery_search', 'edit_pickup_search', 'edit_delivery_search'].forEach(id => {
    const input = document.getElementById(id);
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchBox = searchBoxes[id.replace('_search', '_map')];
                if (searchBox) {
                    const places = searchBox.getPlaces();
                    if (places && places.length > 0) {
                        const place = places[0];
                        const mapId = id.replace('_search', '_map');
                        if (maps[mapId] && markers[mapId]) {
                            maps[mapId].setCenter(place.geometry.location);
                            maps[mapId].setZoom(17);
                            markers[mapId].setPosition(place.geometry.location);
                            updateLocation(mapId, {
                                lat: place.geometry.location.lat(),
                                lng: place.geometry.location.lng()
                            }, place.formatted_address);
                        }
                    }
                }
            }
        });
    }
});

// دالة للتحكم في ظقل التكلفة الإجمالية
function toggleTotalCost(paymentMethod) {
    const totalCostInput = document.querySelector('[name="total_cost"]');
    if (paymentMethod === 'cash') {
        totalCostInput.value = '';  // Reset value for cash payments
        totalCostInput.readOnly = false;  // Allow editing for cash payments
    } else {
        totalCostInput.value = '0';  // Set to 0 for non-cash payments
        totalCostInput.readOnly = true;  // Prevent editing for non-cash payments
    }
}

// تهيئة حالة حقل التكلفة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethod = document.querySelector('[name="payment_method"]');
    if (paymentMethod) {
    toggleTotalCost(paymentMethod.value);
    }
});

// تحديث حالة حقل التكلفة في نموذج التعديل
document.getElementById('editOrderModal').addEventListener('shown.bs.modal', function() {
    const editPaymentMethod = document.getElementById('edit_payment_method');
    const editTotalCostInput = document.getElementById('edit_total_cost');
    
    editPaymentMethod.addEventListener('change', function() {
        if (this.value === 'cash') {
            editTotalCostInput.value = '';  // Reset value for cash payments
            editTotalCostInput.readOnly = false;  // Allow editing for cash payments
        } else {
            editTotalCostInput.value = '0';  // Set to 0 for non-cash payments
            editTotalCostInput.readOnly = true;  // Prevent editing for non-cash payments
        }
    });
    
    // تهيئة الحالة الأولية
    if (editPaymentMethod.value === 'cash') {
        editTotalCostInput.readOnly = false;  // Allow editing for cash payments
    } else {
        editTotalCostInput.value = '0';  // Set to 0 for non-cash payments
        editTotalCostInput.readOnly = true;  // Prevent editing for non-cash payments
    }
});

// Rating form submission
document.getElementById('rateDriverForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const rating = this.querySelector('input[name="rating"]:checked');
    if (!rating) {
        showAlert('الرجاء اختيار تقييم', 'danger');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    console.log('Sending rating data:', Object.fromEntries(formData));
    
    fetch('ajax/submit_rating.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse response:', text);
                throw new Error('خطأ في استجابة الخادم');
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // First hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('rateDriverModal'));
            if (modal) {
                modal.hide();
            }
            
            // Then reset the form
            this.reset();
            
            // Show the alert
            showAlert(data.message || 'تم إرسال التقييم بنجاح', 'success');
            
            // Wait a bit longer before reloading to ensure alert is visible
            setTimeout(() => location.reload(), 2000);
        } else {
            // Show the specific error message from the server
            showAlert(data.message || 'حدث خطأ أثناء إرسال التقييم', 'danger');
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        showAlert(error.message || 'حدث خطأ في الاتصال بالخادم', 'danger');
        submitBtn.disabled = false;
    });
});

// Complaint form submission
document.getElementById('complaintForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const subject = this.querySelector('input[name="complaint_subject"]').value.trim();
    const description = this.querySelector('textarea[name="complaint_description"]').value.trim();
    const requestId = document.getElementById('complaint_request_id').value;
    const driverId = document.getElementById('complaint_driver_id').value;
    
    if (!subject || !description) {
        showAlert('خطأ', 'الرجاء ملء جميع الحقول المطلوبة');
        return;
    }
    
    if (!requestId || !driverId) {
        showAlert('خطأ', 'بيانات الطلب غير مكتملة');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    console.log('Sending complaint data:', {
        request_id: requestId,
        driver_id: driverId,
        subject: subject,
        description: description,
        priority: formData.get('complaint_priority')
    });
    
    fetch('ajax/submit_complaint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse response:', text);
                throw new Error('خطأ في استجابة الخادم');
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // First hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('complaintModal'));
            if (modal) {
                modal.hide();
            }
            
            // Then reset the form
            this.reset();
            
            // Show the alert
            showAlert(data.message || 'تم إرسال الشكوى بنجاح', 'success');
            
            // Wait a bit longer before reloading to ensure alert is visible
            setTimeout(() => location.reload(), 2000);
        } else {
            // Show the specific error message from the server
            showAlert('خطأ', data.message || 'حدث خطأ أثناء إرسال الشكوى');
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        showAlert('خطأ', error.message || 'حدث خطأ في الاتصال بالخادم');
        submitBtn.disabled = false;
    });
});

// Function to show alerts
function showAlert(message, type = 'success') {
    // Create alert container if it doesn't exist
    let alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 500px;
        `;
        document.body.appendChild(alertContainer);
    }

    // Remove any existing alerts
    const existingAlerts = alertContainer.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.cssText = `
        text-align: right;
        direction: rtl;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        margin-bottom: 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    `;

    // Add appropriate icon based on type
    let icon = '';
    switch (type) {
        case 'success':
            icon = '<i class="bi bi-check-circle-fill me-2"></i>';
            break;
        case 'danger':
            icon = '<i class="bi bi-exclamation-circle-fill me-2"></i>';
            break;
        case 'warning':
            icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
            break;
        case 'info':
            icon = '<i class="bi bi-info-circle-fill me-2"></i>';
            break;
    }

    alertDiv.innerHTML = `
        <div style="display: flex; align-items: center;">
            ${icon}
            <div style="flex-grow: 1; margin: 0 10px;">${message}</div>
        </div>
        <button type="button" class="btn-close" style="margin-right: 10px;" onclick="this.parentElement.remove()"></button>
    `;

    // Add alert to container
    alertContainer.appendChild(alertDiv);

    // Force a reflow to ensure the animation plays
    alertDiv.offsetHeight;

    // Show the alert
    alertDiv.style.opacity = '1';

    // Auto hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);

        // Remove container if empty
        if (alertContainer.children.length === 0) {
            alertContainer.remove();
        }
    }, 5000);
}

// دالة للحصول على الموقع الحالي
function getCurrentLocation(type) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // تحديث الخريطة بالموقع الحالي
                const map = maps[type + '_map'];
                const marker = markers[type + '_map'];
                if (map && marker) {
                    map.setCenter(pos);
                    marker.setPosition(pos);
                    map.setZoom(17);
                    
                    // تحديث تفاصيل العنوان
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: pos }, (results, status) => {
                        if (status === "OK" && results[0]) {
                            document.getElementById(type + '_search').value = results[0].formatted_address;
                            updateLocation(type + '_map', pos, results[0].formatted_address);
                            showAlert('تم تحديد موقعك بنجاح', 'success');
                        }
                    });
                }
            },
            (error) => {
                let message = 'تعذر الوصول إلى موقعك: ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        message += 'تم رفض الإذن';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message += 'معلومات الموقع غير متوفرة';
                        break;
                    case error.TIMEOUT:
                        message += 'انتهت مهلة طلب الموقع';
                        break;
                    default:
                        message += 'حدث خطأ غير معروف';
                }
                showAlert(message, 'warning');
            }
        );
    } else {
        showAlert('متصفحك لا يدعم تحديد الموقع', 'warning');
    }
}
</script>