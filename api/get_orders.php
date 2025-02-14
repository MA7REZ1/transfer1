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

try {
    // الاستعلام الأساسي مع JOIN للحصول على معلومات الشركة
    $query = "
        SELECT 
            r.id as order_id,
            r.order_number,
            c.name as company_name,
            c.logo as company_logo,
            c.address as company_address,
            c.phone as company_phone,
            r.customer_name,
            r.customer_phone,
            r.order_type,
            r.delivery_date,
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
            r.additional_notes,
            r.invoice_file
        FROM requests r
        LEFT JOIN companies c ON r.company_id = c.id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تحويل مسار الصور إلى مسار كامل
    foreach ($orders as &$order) {
        if (!empty($order['company_logo'])) {
            $order['company_logo'] = 'https://sin-faya.com/uploads/company_logos/' . $order['company_logo'];
        }
        if (!empty($order['invoice_file'])) {
            $order['invoice_file'] = 'https://sin-faya.com/uploads/invoices/' . $order['invoice_file'];
        }
    }

    // تحويل البيانات إلى التنسيق المطلوب
    $formattedOrders = array_map(function($order) {
        return [
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
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
                'delivery_date' => $order['delivery_date'],
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
                'additional_notes' => $order['additional_notes'],
                'invoice_file' => $order['invoice_file']
            ]
        ];
    }, $orders);

    echo json_encode([
        'status' => true,
        'message' => 'Orders retrieved successfully',
        'data' => $formattedOrders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} 