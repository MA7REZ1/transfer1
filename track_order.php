<?php
require_once 'config.php';

$order_number = isset($_GET['order_number']) ? $_GET['order_number'] : '';
$order = null;
$error = '';
$feedback_message = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $request_id = $_POST['request_id'];
    $customer_phone = $_POST['customer_phone'];
    $feedback = $_POST['feedback'];
    
    // Verify phone number matches order
    $stmt = $conn->prepare("SELECT customer_phone FROM requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $order_phone = $stmt->fetchColumn();
    
    if ($order_phone === $customer_phone) {
        // Check if feedback already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_feedback WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $feedback_exists = $stmt->fetchColumn() > 0;
        
        if (!$feedback_exists) {
            $stmt = $conn->prepare("INSERT INTO customer_feedback (request_id, customer_phone, feedback) VALUES (?, ?, ?)");
            if ($stmt->execute([$request_id, $customer_phone, $feedback])) {
                $feedback_message = '<div class="alert alert-success">تم إرسال ملاحظاتك بنجاح. شكراً لك!</div>';
            } else {
                $feedback_message = '<div class="alert alert-danger">عذراً، حدث خطأ أثناء إرسال ملاحظاتك. يرجى المحاولة مرة أخرى.</div>';
            }
        } else {
            $feedback_message = '<div class="alert alert-warning">لقد قمت بالفعل بإرسال ملاحظات لهذا الطلب.</div>';
        }
    } else {
        $feedback_message = '<div class="alert alert-danger">رقم الهاتف غير مطابق لرقم الهاتف المسجل في الطلب.</div>';
    }
}

if ($order_number) {
    $stmt = $conn->prepare("
        SELECT r.*, d.username as driver_name, d.phone as driver_phone,
        c.name as company_name, c.phone as company_phone
        FROM requests r
        LEFT JOIN drivers d ON r.driver_id = d.id
        LEFT JOIN companies c ON r.company_id = c.id
        WHERE r.order_number = ?
    ");
    $stmt->execute([$order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $error = 'رقم الطلب غير موجود';
    }
}

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

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع الطلب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places,geometry"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f6f8fd 0%, #f1f4f9 100%);
            min-height: 100vh;
        }
        .tracking-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .tracking-header {
            background: linear-gradient(135deg, #4158D0, #C850C0);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .tracking-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, 
                rgba(255,255,255,0.1) 0%,
                rgba(255,255,255,0.2) 100%);
            clip-path: polygon(0 0, 100% 0, 100% 100%);
        }
        .tracking-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .tracking-form {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border: 1px solid rgba(65, 88, 208, 0.1);
        }
        .tracking-form input {
            border-radius: 15px;
            padding: 1rem 1.5rem;
            border: 2px solid rgba(65, 88, 208, 0.1);
            transition: all 0.3s ease;
        }
        .tracking-form input:focus {
            border-color: #4158D0;
            box-shadow: 0 0 0 0.25rem rgba(65, 88, 208, 0.1);
        }
        .tracking-form button {
            border-radius: 15px;
            padding: 1rem;
            background: linear-gradient(135deg, #4158D0, #C850C0);
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .tracking-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(65, 88, 208, 0.3);
        }
        .order-details {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid rgba(65, 88, 208, 0.1);
        }
        .status-timeline {
            position: relative;
            padding: 2rem 1rem;
        }
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
            transition: all 0.3s ease;
        }
        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1.5rem;
            z-index: 2;
            transition: all 0.5s ease;
            border: 2px solid rgba(65, 88, 208, 0.1);
        }
        .status-icon.active {
            background: linear-gradient(135deg, #4158D0, #C850C0);
            color: white;
            transform: scale(1.2);
            box-shadow: 0 0 20px rgba(65, 88, 208, 0.3);
        }
        .status-line {
            position: absolute;
            right: 25px;
            top: 50px;
            bottom: -30px;
            width: 2px;
            background: rgba(65, 88, 208, 0.1);
            z-index: 1;
        }
        .status-content {
            flex: 1;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(65, 88, 208, 0.1);
        }
        .status-content:hover {
            transform: translateX(-5px);
            box-shadow: 0 8px 25px rgba(65, 88, 208, 0.15);
        }
        .contact-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(65, 88, 208, 0.1);
        }
        .contact-button {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .contact-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .contact-button i {
            font-size: 1.2rem;
        }
        .map-container {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            margin: 2rem 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .location-info {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .location-info .row {
            margin: 0;
        }
        .location-info i {
            font-size: 1.2rem;
            margin-left: 0.5rem;
            color: #4158D0;
        }
        .location-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        .location-badge.pickup {
            background: rgba(65, 88, 208, 0.1);
            color: #4158D0;
        }
        .location-badge.delivery {
            background: rgba(200, 80, 192, 0.1);
            color: #C850C0;
        }
        .leaflet-popup-content {
            text-align: right;
            direction: rtl;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .custom-marker {
            background: none;
            border: none;
            padding: 0;
        }
        .marker-label {
            color: white;
            background-color: #4158D0;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .route-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .route-info i {
            margin-left: 5px;
            color: #4158D0;
        }
        .leaflet-routing-container {
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 10px;
            max-width: 300px;
            direction: rtl;
            text-align: right;
        }
        .leaflet-routing-alt {
            max-height: 200px;
            overflow-y: auto;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .leaflet-routing-alt h2 {
            font-size: 16px;
            margin: 0 0 10px 0;
        }
        .leaflet-routing-alt h3 {
            font-size: 14px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <!-- Header -->
        <div class="tracking-header">
            <h1 class="mb-0">تتبع الطلب</h1>
            <p class="mb-0">أدخل رقم الطلب لمعرفة حالته</p>
        </div>

        <!-- Search Form -->
        <div class="tracking-form">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-9">
                    <input type="text" name="order_number" class="form-control form-control-lg" 
                           placeholder="أدخل رقم الطلب" value="<?php echo htmlspecialchars($order_number); ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">تتبع</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
            <!-- Order Details -->
            <div class="order-details">
                <h3 class="mb-4">تفاصيل الطلب #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                
                <!-- Status Timeline -->
                <div class="status-timeline">
                    <?php
                    $statuses = [
                        'pending' => ['icon' => 'clock', 'text' => 'قيد الانتظار'],
                        'accepted' => ['icon' => 'check-circle', 'text' => 'تم القبول'],
                        'in_transit' => ['icon' => 'truck', 'text' => 'جاري التوصيل'],
                        'delivered' => ['icon' => 'check-all', 'text' => 'تم التوصيل']
                    ];
                    
                    $current_status_found = false;
                    foreach ($statuses as $status_key => $status_info):
                        $is_active = !$current_status_found && 
                                   ($order['status'] === $status_key || 
                                    ($order['status'] === 'delivered' && $status_key === 'delivered'));
                        if ($is_active) $current_status_found = true;
                    ?>
                    <div class="status-item <?php echo $is_active ? 'active' : ''; ?>">
                        <?php if ($status_key === 'in_transit' && $order['status'] === 'in_transit'): ?>
                        <div class="moving-truck">
                            <i class="bi bi-truck"></i>
                        </div>
                        <?php endif; ?>
                        <div class="status-icon <?php echo $is_active ? 'active' : ''; ?>">
                            <i class="bi bi-<?php echo $status_info['icon']; ?>"></i>
                        </div>
                        <?php if (!end($statuses) === $status_info): ?>
                            <div class="status-line"></div>
                        <?php endif; ?>
                        <div class="status-content">
                            <h5 class="mb-1"><?php echo $status_info['text']; ?></h5>
                            <?php if ($is_active): ?>
                                <p class="mb-0 text-muted">
                                    <?php echo date('Y-m-d H:i', strtotime($order['updated_at'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Order Information -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5>معلومات التوصيل</h5>
                        <ul class="list-unstyled">
                            <li><strong>موقع الاستلام:</strong> <?php echo htmlspecialchars($order['pickup_location']); ?></li>
                            <li><strong>موقع التوصيل:</strong> <?php echo htmlspecialchars($order['delivery_location']); ?></li>
                            <li><strong>تاريخ التوصيل:</strong> <?php echo date('Y-m-d', strtotime($order['delivery_date'])); ?></li>
                            <li><strong>عدد القطع:</strong> <?php echo htmlspecialchars($order['items_count']); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>معلومات الدفع</h5>
                        <ul class="list-unstyled">
                            <li><strong>التكلفة الإجمالية:</strong> <?php echo number_format($order['total_cost'], 2); ?> ريال</li>
                            <li><strong>طريقة الدفع:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></li>
                            <li>
                                <strong>حالة الدفع:</strong>
                                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo $order['payment_status'] === 'paid' ? 'مدفوع' : 'غير مدفوع'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Information -->
                <?php if ($order['status'] !== 'pending' && $order['status'] !== 'cancelled'): ?>
                <div class="contact-info mt-4">
                    <h5>معلومات التواصل</h5>
                    
                    <?php if ($order['driver_name']): ?>
                    <div class="mb-3">
                        <h6>السائق</h6>
                        <div>
                            <strong>الاسم:</strong> <?php echo htmlspecialchars($order['driver_name']); ?>
                        </div>
                        <?php if ($order['driver_phone']): ?>
                        <div class="mt-2">
                            <a href="tel:<?php echo $order['driver_phone']; ?>" class="btn btn-primary btn-sm contact-button">
                                <i class="bi bi-telephone"></i> اتصال
                            </a>
                            <a href="https://wa.me/<?php echo $order['driver_phone']; ?>" class="btn btn-success btn-sm contact-button">
                                <i class="bi bi-whatsapp"></i> واتساب
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div>
                        <h6>الشركة</h6>
                        <div>
                            <strong>الاسم:</strong> <?php echo htmlspecialchars($order['company_name']); ?>
                        </div>
                        <?php if ($order['company_phone']): ?>
                        <div class="mt-2">
                            <a href="tel:<?php echo $order['company_phone']; ?>" class="btn btn-primary btn-sm contact-button">
                                <i class="bi bi-telephone"></i> اتصال
                            </a>
                            <a href="https://wa.me/<?php echo $order['company_phone']; ?>" class="btn btn-success btn-sm contact-button">
                                <i class="bi bi-whatsapp"></i> واتساب
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Map Section -->
                <div class="map-container">
                    <div id="map"></div>
                    <div class="location-info">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="location-badge pickup">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>نقطة الانطلاق: <?php echo htmlspecialchars($order['pickup_location']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="location-badge delivery">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>نقطة الوصول: <?php echo htmlspecialchars($order['delivery_location']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($order && $order['status'] === 'delivered'): ?>
                    <!-- Feedback Form -->
                    <?php
                    // Check if feedback already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_feedback WHERE request_id = ?");
                    $stmt->execute([$order['id']]);
                    $feedback_exists = $stmt->fetchColumn() > 0;
                    
                    if (!$feedback_exists) {
                        echo $feedback_message;
                        ?>
                        <div class="feedback-form mt-4">
                            <h3 class="mb-3">أخبرنا عن تجربتك</h3>
                            <form method="POST" class="bg-white p-4 rounded shadow-sm">
                                <input type="hidden" name="request_id" value="<?php echo $order['id']; ?>">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required 
                                           placeholder="أدخل رقم هاتفك للتحقق">
                                </div>
                                <div class="mb-3">
                                    <label for="feedback" class="form-label">ملاحظاتك</label>
                                    <textarea class="form-control" id="feedback" name="feedback" rows="4" required 
                                             placeholder="شاركنا رأيك في الخدمة"></textarea>
                                </div>
                                <button type="submit" name="submit_feedback" class="btn btn-primary">إرسال الملاحظات</button>
                            </form>
                        </div>
                        <?php
                    } else {
                        echo '<div class="alert alert-info mt-4">شكراً لك! لقد تم استلام ملاحظاتك بنجاح.</div>';
                    }
                    ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($order): ?>
    <script>
        let map;
        let directionsService;
        let directionsRenderer;
        let pickupMarker;
        let deliveryMarker;
        let truckMarker;
        let animationPath = [];
        let currentPathIndex = 0;

        function initMap() {
            // إنشاء خريطة جديدة
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: { lat: 24.7136, lng: 46.6753 }, // الرياض كمركز افتراضي
                styles: [
                    {
                        featureType: "administrative",
                        elementType: "geometry",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "transit",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });

            // إنشاء خدمات الاتجاهات
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true, // إخفاء العلامات الافتراضية
                polylineOptions: {
                    strokeColor: '#4158D0',
                    strokeWeight: 4,
                    strokeOpacity: 0.7
                }
            });

            // تحميل مواقع الاستلام والتوصيل
            const pickupLocation = '<?php echo addslashes($order['pickup_location']); ?>';
            const deliveryLocation = '<?php echo addslashes($order['delivery_location']); ?>';
            const orderStatus = '<?php echo $order['status']; ?>';

            // البحث عن الإحداثيات وعرض المسار
            geocodeLocations(pickupLocation, deliveryLocation, orderStatus);
        }

        function geocodeLocations(pickupAddress, deliveryAddress, orderStatus) {
            const geocoder = new google.maps.Geocoder();

            // البحث عن موقع الاستلام
            geocoder.geocode({ address: pickupAddress }, (pickupResults, pickupStatus) => {
                if (pickupStatus === 'OK') {
                    const pickupLocation = pickupResults[0].geometry.location;

                    // البحث عن موقع التوصيل
                    geocoder.geocode({ address: deliveryAddress }, (deliveryResults, deliveryStatus) => {
                        if (deliveryStatus === 'OK') {
                            const deliveryLocation = deliveryResults[0].geometry.location;

                            // إضافة العلامات
                            addMarkers(pickupLocation, deliveryLocation, pickupAddress, deliveryAddress);

                            // حساب وعرض المسار
                            calculateAndDisplayRoute(pickupLocation, deliveryLocation, orderStatus);
                        }
                    });
                }
            });
        }

        function addMarkers(pickupLocation, deliveryLocation, pickupAddress, deliveryAddress) {
            // علامة نقطة الاستلام
            pickupMarker = new google.maps.Marker({
                position: pickupLocation,
                map: map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="#4158D0" d="M12 0C7.6 0 4 3.6 4 8c0 5.4 8 16 8 16s8-10.6 8-16c0-4.4-3.6-8-8-8z"/>
                            <circle fill="white" cx="12" cy="8" r="3"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 32)
                },
                title: 'نقطة الاستلام'
            });

            // علامة نقطة التوصيل
            deliveryMarker = new google.maps.Marker({
                position: deliveryLocation,
                map: map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="#C850C0" d="M12 0C7.6 0 4 3.6 4 8c0 5.4 8 16 8 16s8-10.6 8-16c0-4.4-3.6-8-8-8z"/>
                            <circle fill="white" cx="12" cy="8" r="3"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 32)
                },
                title: 'نقطة التوصيل'
            });

            // إضافة نوافذ معلومات
            const pickupInfoWindow = new google.maps.InfoWindow({
                content: `<div style="text-align: right"><strong>نقطة الاستلام</strong><br>${pickupAddress}</div>`
            });

            const deliveryInfoWindow = new google.maps.InfoWindow({
                content: `<div style="text-align: right"><strong>نقطة التوصيل</strong><br>${deliveryAddress}</div>`
            });

            pickupMarker.addListener('click', () => {
                pickupInfoWindow.open(map, pickupMarker);
            });

            deliveryMarker.addListener('click', () => {
                deliveryInfoWindow.open(map, deliveryMarker);
            });
        }

        function calculateAndDisplayRoute(pickupLocation, deliveryLocation, orderStatus) {
            const request = {
                origin: pickupLocation,
                destination: deliveryLocation,
                travelMode: 'DRIVING'
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);

                    // حفظ مسار الرحلة للرسوم المتحركة
                    animationPath = google.maps.geometry.encoding.decodePath(
                        result.routes[0].overview_polyline
                    );

                    // إضافة معلومات المسار
                    const route = result.routes[0].legs[0];
                        const routeInfo = document.createElement('div');
                        routeInfo.className = 'route-info';
                        routeInfo.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                    <i class="bi bi-clock"></i>
                                <strong>الوقت المتوقع:</strong> ${route.duration.text}
                                </div>
                            <div class="col-md-6">
                                    <i class="bi bi-signpost-2"></i>
                                <strong>المسافة:</strong> ${route.distance.text}
                            </div>
                            </div>
                        `;
                    document.querySelector('.map-container').appendChild(routeInfo);

                    // إضافة علامة الشاحنة إذا كانت الحالة "جاري التوصيل"
                    if (orderStatus === 'in_transit') {
                        addTruckMarker();
                    }

                    // تحريك الخريطة لتظهر المسار كاملاً
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend(pickupLocation);
                    bounds.extend(deliveryLocation);
                    map.fitBounds(bounds);
                }
            });
        }

        function addTruckMarker() {
            // إنشاء علامة الشاحنة
            truckMarker = new google.maps.Marker({
                map: map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                            <path fill="#4158D0" d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                }
            });

            // بدء حركة الشاحنة
            animateTruck();
        }

        function animateTruck() {
            if (currentPathIndex >= animationPath.length) {
                currentPathIndex = 0;
            }

            const position = animationPath[currentPathIndex];
            truckMarker.setPosition(position);

            // حساب زاوية الدوران
            if (currentPathIndex < animationPath.length - 1) {
                const nextPosition = animationPath[currentPathIndex + 1];
                const heading = google.maps.geometry.spherical.computeHeading(position, nextPosition);
                truckMarker.setIcon({
                    ...truckMarker.getIcon(),
                    rotation: heading
                });
            }

            currentPathIndex++;
            setTimeout(animateTruck, 100);
        }

        // تهيئة الخريطة عند تحميل الصفحة
        window.initMap = initMap;
    </script>
    <?php endif; ?>
</body>
</html> 