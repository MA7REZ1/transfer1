<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method Not Allowed']);
    exit();
}

// التحقق من وجود معرف السائق
if (!isset($_GET['driver_id'])) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Driver ID is required']);
    exit();
}

$driver_id = $_GET['driver_id'];

try {
    // الاستعلام للحصول على الطلبات النشطة للسائق
    $query = "
        SELECT 
            r.id as order_id,
            r.order_number,
            r.created_at as order_date,
            r.delivery_date,
            c.name as company_name,
            c.logo as company_logo,
            c.address as company_address,
            c.phone as company_phone,
            r.customer_name,
            r.customer_phone,
            r.order_type,
            r.pickup_location,
            r.delivery_location,
            r.pickup_location_link,
            r.delivery_location_link,
            r.items_count,
            r.total_cost,
            r.delivery_fee,
            (r.total_cost) as total_amount,
            r.payment_status,
            r.status as order_status,
            r.is_fragile,
            r.additional_notes
        FROM requests r
        LEFT JOIN companies c ON r.company_id = c.id
        WHERE r.driver_id = ? 
        AND r.status IN ('accepted', 'in_transit')
        ORDER BY 
            CASE r.status
                WHEN 'accepted' THEN 1
                WHEN 'in_transit' THEN 2
            END,
            r.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute([$driver_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تحويل مسار الصور إلى مسار كامل
    foreach ($orders as &$order) {
        if (!empty($order['company_logo'])) {
            $order['company_logo'] = 'http://alwarsh.net/END/uploads/company_logos/' . $order['company_logo'];
        }
    }

    // تنسيق البيانات
    $formattedOrders = array_map(function($order) {
        return [
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
            'dates' => [
                'order_date' => $order['order_date'],
                'delivery_date' => $order['delivery_date']
            ],
            'company' => [
                'name' => $order['company_name'],
                'logo' => $order['company_logo'],
                'address' => $order['company_address'],
                'phone' => $order['company_phone']
            ],
            'customer' => [
                'name' => $order['customer_name'],
                'phone' => $order['customer_phone']
            ],
            'order_details' => [
                'type' => $order['order_type'],
                'locations' => [
                    'pickup' => [
                        'address' => $order['pickup_location'],
                        'map_link' => $order['pickup_location_link']
                    ],
                    'delivery' => [
                        'address' => $order['delivery_location'],
                        'map_link' => $order['delivery_location_link']
                    ]
                ],
                'items_count' => $order['items_count'],
                'costs' => [
                    'order_cost' => (float)$order['total_cost'],
                    'delivery_fee' => (float)$order['delivery_fee'],
                    'total_amount' => (float)$order['total_amount']
                ],
                'payment_status' => $order['payment_status'],
                'order_status' => $order['order_status'],
                'is_fragile' => (bool)$order['is_fragile'],
                'additional_notes' => $order['additional_notes']
            ]
        ];
    }, $orders);

    // تجميع الطلبات حسب الحالة
    $groupedOrders = [
        'accepted' => array_filter($formattedOrders, function($order) {
            return $order['order_details']['order_status'] === 'accepted';
        }),
        'in_transit' => array_filter($formattedOrders, function($order) {
            return $order['order_details']['order_status'] === 'in_transit';
        })
    ];

    // تحويل المصفوفات المرتبطة إلى مصفوفات عددية
    $groupedOrders['accepted'] = array_values($groupedOrders['accepted']);
    $groupedOrders['in_transit'] = array_values($groupedOrders['in_transit']);

    echo json_encode([
        'status' => true,
        'message' => 'Active orders retrieved successfully',
        'data' => [
            'total_orders' => count($formattedOrders),
            'accepted_orders_count' => count($groupedOrders['accepted']),
            'in_transit_orders_count' => count($groupedOrders['in_transit']),
            'orders' => $groupedOrders
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} 