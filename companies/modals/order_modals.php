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
                            <div class="mb-3">
                                <label class="form-label">موقع الاستلام <span class="text-danger">*</span></label>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="pickup_search" placeholder="ابحث عن موقع..." autocomplete="off">
                                        <button class="btn btn-outline-primary" type="button" onclick="searchLocation('pickup')">
                                            <i class="bi bi-search"></i> بحث
                                        </button>
                                    </div>
                                    <div id="pickup_search_results" class="search-results"></div>
                                </div>
                                <div class="location-details">
                                    <textarea class="form-control" name="pickup_location" rows="2" required minlength="10"></textarea>
                                <input type="text" class="form-control mt-2" name="pickup_location_link" readonly
                                       placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="pickup_map" style="height: 100%;"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">موقع التوصيل <span class="text-danger">*</span></label>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="delivery_search" placeholder="ابحث عن موقع..." autocomplete="off">
                                        <button class="btn btn-outline-primary" type="button" onclick="searchLocation('delivery')">
                                            <i class="bi bi-search"></i> بحث
                                        </button>
                                    </div>
                                    <div id="delivery_search_results" class="search-results"></div>
                                </div>
                                <div class="location-details">
                                    <textarea class="form-control" name="delivery_location" rows="2" required minlength="10"></textarea>
                                <input type="text" class="form-control mt-2" name="delivery_location_link" readonly
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
                            <div class="mb-3">
                                <label class="form-label">موقع الاستلام</label>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_pickup_search" placeholder="ابحث عن موقع..." autocomplete="off">
                                        <button class="btn btn-outline-primary" type="button" onclick="searchLocation('edit_pickup')">
                                            <i class="bi bi-search"></i> بحث
                                        </button>
                                    </div>
                                    <div id="edit_pickup_search_results" class="search-results"></div>
                                </div>
                                <div class="location-details">
                                <textarea class="form-control" name="pickup_location" id="edit_pickup_location" rows="2" required></textarea>
                                <input type="text" class="form-control mt-2" name="pickup_location_link" id="edit_pickup_location_link" readonly
                                       placeholder="رابط الموقع (سيتم تعبئته تلقائياً)">
                                </div>
                                <div class="map-container">
                                    <div id="edit_pickup_map" style="height: 100%;"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">موقع التوصيل</label>
                                <div class="location-input-group">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_delivery_search" placeholder="ابحث عن موقع..." autocomplete="off">
                                        <button class="btn btn-outline-primary" type="button" onclick="searchLocation('edit_delivery')">
                                            <i class="bi bi-search"></i> بحث
                                        </button>
                                    </div>
                                    <div id="edit_delivery_search_results" class="search-results"></div>
                                </div>
                                <div class="location-details">
                                <textarea class="form-control" name="delivery_location" id="edit_delivery_location" rows="2" required></textarea>
                                <input type="text" class="form-control mt-2" name="delivery_location_link" id="edit_delivery_location_link" readonly
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
.search-results {
    position: absolute;
    z-index: 1050;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    max-height: 200px;
    overflow-y: auto;
    width: calc(100% - 70px);
    display: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.search-results .result-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.search-results .result-item:hover {
    background-color: #f8f9fa;
}

.search-results .result-item:last-child {
    border-bottom: none;
}

.modal-dialog.modal-lg {
    max-width: 900px;
}

.modal-body {
    max-height: calc(100vh - 210px);
    overflow-y: auto;
    padding: 20px;
    position: relative;
}

.map-container {
    position: relative;
    height: 300px;
    margin-top: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.location-input-group {
    position: relative;
    margin-bottom: 10px;
}

.location-details {
    margin-top: 10px;
}

/* تحسين مظهر حقول الإدخال */
.form-control {
    border-radius: 4px;
}

.input-group {
    position: relative;
    z-index: 1;
}

/* تحسين مظهر الخريطة */
.leaflet-container {
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

/* تحسين مظهر النافذة المنبثقة في الخريطة */
.leaflet-popup-content {
    direction: rtl;
    text-align: right;
    min-width: 200px;
}

.leaflet-popup-content .btn {
    margin: 2px;
}

/* تعديل حجم المودال للشاشات الصغيرة */
@media (max-width: 992px) {
    .modal-dialog.modal-lg {
        max-width: 95%;
        margin: 10px auto;
    }
    
    .modal-body {
        max-height: calc(100vh - 150px);
        padding: 15px;
    }
    
    .map-container {
        height: 250px;
    }
}
</style>

<script>
let maps = {};
let markers = {};

// دالة لإنشاء خريطة جديدة
function initMap(elementId, defaultLat = 24.7136, defaultLng = 46.6753) {
    const map = L.map(elementId).setView([defaultLat, defaultLng], 13);
    
    // إضافة طبقة الخريطة الأساسية مع تفاصيل أكثر
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // إضافة علامة قابلة للسحب مع نافذة منبثقة
    const marker = L.marker([defaultLat, defaultLng], { 
        draggable: true,
        autoPan: true
    }).addTo(map);
    
    // إضافة دائرة حول العلامة
    const circle = L.circle([defaultLat, defaultLng], {
        color: '#007bff',
        fillColor: '#007bff',
        fillOpacity: 0.1,
        radius: 100
    }).addTo(map);
    
    maps[elementId] = map;
    markers[elementId] = marker;
    
    // تحديث الموقع عند تحريك العلامة
    marker.on('dragend', function(event) {
        const latlng = event.target.getLatLng();
        circle.setLatLng(latlng);
        updateLocation(elementId, latlng, true);
    });
    
    // تحديث الموقع عند النقر على الخريطة
    map.on('click', function(event) {
        marker.setLatLng(event.latlng);
        circle.setLatLng(event.latlng);
        updateLocation(elementId, event.latlng, true);
    });
    
    return { map, marker, circle };
}

// دالة لتحديث الموقع
function updateLocation(mapId, latlng, shouldUpdateMap = false) {
    const isEdit = mapId.includes('edit');
    const type = mapId.includes('pickup') ? 'pickup' : 'delivery';
    const prefix = isEdit ? 'edit_' : '';
    
                    // تحديث حقل الرابط
    const linkInput = document.querySelector(`[name="${type}_location_link"]${isEdit ? `#${prefix}${type}_location_link` : ''}`);
    const osmLink = `https://www.openstreetmap.org/?mlat=${latlng.lat}&mlon=${latlng.lng}`;
    const googleLink = `https://www.google.com/maps?q=${latlng.lat},${latlng.lng}`;
    linkInput.value = osmLink;
    
    // تحديث حقل الموقع
    const locationInput = document.getElementById(`${prefix}${type}_location`);
    if (locationInput) {
        // استخدام Nominatim للحصول على العنوان التفصيلي
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&accept-language=ar&zoom=18&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                if (data.address) {
                    // تنسيق العنوان بشكل أفضل
                    const address = [];
                    if (data.address.road) address.push(data.address.road);
                    if (data.address.suburb) address.push(data.address.suburb);
                    if (data.address.city || data.address.town) address.push(data.address.city || data.address.town);
                    if (data.address.state) address.push(data.address.state);
                    if (data.address.country) address.push(data.address.country);
                    
                    locationInput.value = address.join('، ');
                    
                    // تحديث النافذة المنبثقة للعلامة
                    if (shouldUpdateMap && markers[mapId]) {
                        const popupContent = `
                            <div style="text-align: right; direction: rtl;">
                                <strong>${address.join('، ')}</strong><br>
                                <small class="text-muted">${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}</small><br>
                                <div class="mt-2">
                                    <a href="${osmLink}" target="_blank" class="btn btn-sm btn-outline-primary">OpenStreetMap</a>
                                    <a href="${googleLink}" target="_blank" class="btn btn-sm btn-outline-primary">خرائط جوجل</a>
                                </div>
                            </div>
                        `;
                        markers[mapId].bindPopup(popupContent).openPopup();
                    }
                } else {
                    locationInput.value = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
                }
            })
            .catch(() => {
                locationInput.value = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
            });
    }
}

// تهيئة الخرائط عند فتح النوافذ المنبثقة
document.getElementById('newRequestModal').addEventListener('shown.bs.modal', function() {
    setTimeout(() => {
        if (!maps['pickup_map']) {
            initMap('pickup_map');
        }
        if (!maps['delivery_map']) {
            initMap('delivery_map');
        }
        maps['pickup_map'].invalidateSize();
        maps['delivery_map'].invalidateSize();
    }, 100);
});

document.getElementById('editOrderModal').addEventListener('shown.bs.modal', function() {
    setTimeout(() => {
        if (!maps['edit_pickup_map']) {
            initMap('edit_pickup_map');
        }
        if (!maps['edit_delivery_map']) {
            initMap('edit_delivery_map');
        }
        maps['edit_pickup_map'].invalidateSize();
        maps['edit_delivery_map'].invalidateSize();
    }, 100);
});

// تحديث دالة loadOrderData لتحديث الخرائط
function loadOrderData(orderId) {
    fetch(`ajax/get_order.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                
                // تعبئة البيانات في النموذج
                document.getElementById('edit_order_id').value = order.id;
                document.getElementById('edit_order_type').value = order.order_type;
                document.getElementById('edit_customer_name').value = order.customer_name;
                document.getElementById('edit_customer_phone').value = order.customer_phone;
                
                // فصل التاريخ والوقت
                if (order.delivery_date) {
                const deliveryDateTime = new Date(order.delivery_date);
                    // تنسيق التاريخ YYYY-MM-DD
                document.getElementById('edit_delivery_date').value = deliveryDateTime.toISOString().split('T')[0];
                    // تنسيق الوقت HH:mm
                    const hours = String(deliveryDateTime.getHours()).padStart(2, '0');
                    const minutes = String(deliveryDateTime.getMinutes()).padStart(2, '0');
                    document.getElementById('edit_delivery_time').value = `${hours}:${minutes}`;
                }
                
                document.getElementById('edit_pickup_location').value = order.pickup_location;
                document.getElementById('edit_delivery_location').value = order.delivery_location;
                document.getElementById('edit_items_count').value = order.items_count;
                document.getElementById('edit_total_cost').value = order.total_cost;
                document.getElementById('edit_payment_method').value = order.payment_method;
                document.getElementById('edit_is_fragile').checked = order.is_fragile == 1;
                document.getElementById('edit_additional_notes').value = order.additional_notes || '';
                
                // عرض الفاتورة الحالية إن وجدت
                const currentInvoiceDiv = document.getElementById('current_invoice');
                if (order.invoice_file) {
                    currentInvoiceDiv.innerHTML = `
                        <img src="../uploads/invoices/${order.invoice_file}" class="img-thumbnail" style="max-height: 100px">
                        <p class="mb-0 mt-1">الفاتورة الحالية</p>
                    `;
                } else {
                    currentInvoiceDiv.innerHTML = '<p class="text-muted">لا توجد فاتورة</p>';
                }
                
                // تحديث الخرائط إذا كان هناك إحداثيات
                if (order.pickup_location_link) {
                    const pickupMatch = order.pickup_location_link.match(/mlat=(-?\d+\.\d+)&mlon=(-?\d+\.\d+)/);
                    if (pickupMatch && maps['edit_pickup_map']) {
                        const [_, lat, lng] = pickupMatch;
                        maps['edit_pickup_map'].setView([lat, lng], 13);
                        markers['edit_pickup_map'].setLatLng([lat, lng]);
                    }
                }
                
                if (order.delivery_location_link) {
                    const deliveryMatch = order.delivery_location_link.match(/mlat=(-?\d+\.\d+)&mlon=(-?\d+\.\d+)/);
                    if (deliveryMatch && maps['edit_delivery_map']) {
                        const [_, lat, lng] = deliveryMatch;
                        maps['edit_delivery_map'].setView([lat, lng], 13);
                        markers['edit_delivery_map'].setLatLng([lat, lng]);
                    }
                }
            } else {
                alert('حدث خطأ أثناء تحميل بيانات الطلب');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال بالخادم');
        });
}

// معالجة تحديث الطلب
document.getElementById('editOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('ajax/update_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
});

// دالة للبحث عن موقع
function searchLocation(type) {
    const searchInput = document.getElementById(`${type}_search`);
    const resultsDiv = document.getElementById(`${type}_search_results`);
    const query = searchInput.value.trim();
    
    if (!query) {
        alert('الرجاء إدخال موقع للبحث عنه');
        return;
    }
    
    // استخدام Nominatim للبحث عن الموقع
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&accept-language=ar&limit=5&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if (data && data.length > 0) {
                data.forEach(location => {
                    const div = document.createElement('div');
                    div.className = 'result-item';
                    
                    // تنسيق نتيجة البحث بشكل أفضل
                    const address = [];
                    if (location.address) {
                        if (location.address.road) address.push(location.address.road);
                        if (location.address.suburb) address.push(location.address.suburb);
                        if (location.address.city || location.address.town) address.push(location.address.city || location.address.town);
                        if (location.address.state) address.push(location.address.state);
                    }
                    
                    div.innerHTML = `
                        <div style="font-weight: bold;">${address.join('، ') || location.display_name}</div>
                        <small class="text-muted">${location.display_name}</small>
                    `;
                    
                    div.onclick = () => {
                        const lat = parseFloat(location.lat);
                        const lng = parseFloat(location.lon);
                        
                        // تحديث الخريطة والعلامة
                        const mapId = `${type}_map`;
                        if (maps[mapId]) {
                            maps[mapId].setView([lat, lng], 17);
                            markers[mapId].setLatLng([lat, lng]);
                            // تحديث الدائرة
                            if (maps[mapId]._layers) {
                                Object.values(maps[mapId]._layers).forEach(layer => {
                                    if (layer instanceof L.Circle) {
                                        layer.setLatLng([lat, lng]);
                                    }
                                });
                            }
                            updateLocation(mapId, { lat, lng }, true);
                        }
                        
                        // إخفاء نتائج البحث
                        resultsDiv.style.display = 'none';
                        searchInput.value = address.join('، ') || location.display_name;
                    };
                    resultsDiv.appendChild(div);
                });
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.innerHTML = '<div class="result-item">لم يتم العثور على نتائج</div>';
                resultsDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error searching location:', error);
            alert('حدث خطأ أثناء البحث عن الموقع');
        });
}

// إخفاء نتائج البحث عند النقر خارجها
document.addEventListener('click', function(e) {
    const searchResults = document.querySelectorAll('.search-results');
    searchResults.forEach(results => {
        if (!results.contains(e.target) && !e.target.closest('.input-group')) {
            results.style.display = 'none';
        }
    });
});

// تحديث مستمعي الأحداث لحقول البحث
['pickup_search', 'delivery_search', 'edit_pickup_search', 'edit_delivery_search'].forEach(id => {
    const input = document.getElementById(id);
    if (input) {
        // مستمع لمفتاح Enter
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchLocation(id.replace('_search', ''));
            }
        });
        
        // مستمع للكتابة المباشرة
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value.trim().length >= 3) {
                    searchLocation(id.replace('_search', ''));
                }
            }, 500);
        });
    }
});
</script>