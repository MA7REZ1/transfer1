<?php
require_once 'config.php';

$order_number = isset($_GET['order_number']) ? $_GET['order_number'] : '';
$order = null;
$error = '';

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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($order): ?>
    <script>
        function updateOrderStatus() {
            const orderNumber = '<?php echo $order_number; ?>';
            fetch(`ajax/get_order_status.php?order_number=${orderNumber}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status !== '<?php echo $order['status']; ?>') {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // تحديث كل 30 ثانية
        setInterval(updateOrderStatus, 30000);

        // إعداد الخريطة
        const map = L.map('map').setView([24.7136, 46.6753], 13); // الرياض كمركز افتراضي

        // إضافة طبقة الخريطة
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // تحويل العناوين إلى إحداثيات
        async function geocodeAddress(address) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
                const data = await response.json();
                if (data.length > 0) {
                    return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                }
                return null;
            } catch (error) {
                console.error('Error geocoding address:', error);
                return null;
            }
        }

        // إضافة العلامات والمسار
        async function initializeMap() {
            const pickupLocation = '<?php echo addslashes($order['pickup_location']); ?>';
            const deliveryLocation = '<?php echo addslashes($order['delivery_location']); ?>';

            const pickupCoords = await geocodeAddress(pickupLocation);
            const deliveryCoords = await geocodeAddress(deliveryLocation);

            if (pickupCoords && deliveryCoords) {
                // إضافة علامات البداية والنهاية
                const pickupMarker = L.marker(pickupCoords, {
                    icon: L.divIcon({
                        className: 'custom-marker pickup-marker',
                        html: '<i class="bi bi-geo-alt-fill" style="color: #4158D0; font-size: 24px;"></i>'
                    })
                }).addTo(map);

                const deliveryMarker = L.marker(deliveryCoords, {
                    icon: L.divIcon({
                        className: 'custom-marker delivery-marker',
                        html: '<i class="bi bi-geo-alt-fill" style="color: #C850C0; font-size: 24px;"></i>'
                    })
                }).addTo(map);

                // إضافة النوافذ المنبثقة
                pickupMarker.bindPopup('<strong>نقطة الانطلاق</strong><br>' + pickupLocation);
                deliveryMarker.bindPopup('<strong>نقطة الوصول</strong><br>' + deliveryLocation);

                // الحصول على المسار الحقيقي باستخدام OSRM
                try {
                    const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${pickupCoords[1]},${pickupCoords[0]};${deliveryCoords[1]},${deliveryCoords[0]}?overview=full&geometries=geojson`);
                    const data = await response.json();
                    
                    if (data.routes && data.routes[0]) {
                        const routeCoordinates = data.routes[0].geometry.coordinates.map(coord => [coord[1], coord[0]]);
                        
                        // رسم المسار الحقيقي
                        const path = L.polyline(routeCoordinates, {
                            color: '#4158D0',
                            weight: 4,
                            opacity: 0.7,
                            lineCap: 'round',
                            lineJoin: 'round'
                        }).addTo(map);

                        // إضافة تأثير حركي للمسار
                        const pathLength = path.getLatLngs().length;
                        let currentPoint = 0;
                        
                        // إضافة علامة الشاحنة إذا كانت الحالة "جاري التوصيل"
                        if ('<?php echo $order['status']; ?>' === 'in_transit') {
                            const truckIcon = L.divIcon({
                                className: 'custom-marker truck-marker',
                                html: '<i class="bi bi-truck" style="color: #4158D0; font-size: 24px;"></i>'
                            });
                            
                            const truckMarker = L.marker(routeCoordinates[Math.floor(pathLength / 2)], {
                                icon: truckIcon
                            }).addTo(map);

                            // تحريك الشاحنة على المسار
                            function animateTruck() {
                                currentPoint = (currentPoint + 1) % pathLength;
                                truckMarker.setLatLng(routeCoordinates[currentPoint]);
                                
                                // حساب الزاوية للشاحنة
                                if (currentPoint < pathLength - 1) {
                                    const currentPos = routeCoordinates[currentPoint];
                                    const nextPos = routeCoordinates[currentPoint + 1];
                                    const angle = Math.atan2(nextPos[1] - currentPos[1], nextPos[0] - currentPos[0]) * 180 / Math.PI;
                                    const truckElement = truckMarker.getElement().querySelector('i');
                                    truckElement.style.transform = `rotate(${angle}deg)`;
                                }
                                
                                // تأخير أطول للحركة (2 ثواني)
                                setTimeout(animateTruck, 2000);
                            }
                            
                            // إضافة نمط للحركة السلسة
                            const style = document.createElement('style');
                            style.textContent = `
                                .truck-marker i {
                                    transition: all 2s ease;
                                    font-size: 28px !important;
                                }
                                .leaflet-marker-icon {
                                    transition: all 2s ease;
                                }
                            `;
                            document.head.appendChild(style);
                            
                            animateTruck();
                        }

                        // تحريك الخريطة لتظهر المسار كاملاً
                        map.fitBounds(path.getBounds(), { padding: [50, 50] });

                        // إضافة معلومات المسار
                        const duration = Math.round(data.routes[0].duration / 60); // بالدقائق
                        const distance = Math.round(data.routes[0].distance / 1000); // بالكيلومترات
                        
                        const routeInfo = document.createElement('div');
                        routeInfo.className = 'route-info mt-3';
                        routeInfo.innerHTML = `
                            <div class="d-flex justify-content-between">
                                <div>
                                    <i class="bi bi-clock"></i>
                                    <strong>الوقت المتوقع:</strong> ${duration} دقيقة
                                </div>
                                <div>
                                    <i class="bi bi-signpost-2"></i>
                                    <strong>المسافة:</strong> ${distance} كم
                                </div>
                            </div>
                        `;
                        document.querySelector('.location-info').appendChild(routeInfo);
                    }
                } catch (error) {
                    console.error('Error fetching route:', error);
                    // في حالة الفشل، نعود للمسار البسيط
                    const path = L.polyline([pickupCoords, deliveryCoords], {
                        color: '#4158D0',
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '10, 10'
                    }).addTo(map);
                    map.fitBounds(path.getBounds(), { padding: [50, 50] });
                }
            }
        }

        // تهيئة الخريطة
        initializeMap();
    </script>
    <?php endif; ?>
</body>
</html> 