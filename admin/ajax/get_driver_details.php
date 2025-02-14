<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => __('driver_id_required')]);
    exit;
}

$driver_id = $_GET['id'];

try {
    // Fetch driver details and total trips with rating
    $stmt = $conn->prepare("SELECT 
        d.*, 
        COUNT(DISTINCT CASE WHEN r.status = 'delivered' THEN r.id ELSE NULL END) as total_trips,
        COALESCE(AVG(dr.rating), 0) as rating
    FROM drivers d 
    LEFT JOIN requests r ON d.id = r.driver_id 
    LEFT JOIN driver_ratings dr ON d.id = dr.driver_id
    WHERE d.id = ? 
    GROUP BY d.id");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch order statuses from requests
    $stmt = $conn->prepare("SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_orders,
        SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM requests WHERE driver_id = ?");
    $stmt->execute([$driver_id]);
    $order_statuses = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch activity logs with translated status
    $stmt = $conn->prepare("SELECT 
        COALESCE(action, ?) AS activity_type,
        COALESCE(details, ?) AS activity_details,
        created_at
    FROM activity_log 
    WHERE driver_id = ?
    ORDER BY created_at DESC 
    LIMIT 10");

    $stmt->execute([__('no_action'), __('no_details'), $driver_id]);
    $activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($driver) {
        // Format the response data
        $response = [
            'success' => true,
            'driver' => [
                'username' => $driver['username'],
                'email' => $driver['email'],
                'phone' => $driver['phone'],
                'is_active' => (bool)$driver['is_active'],
                'vehicle_type' => $driver['vehicle_type'],
                'total_trips' => (int)$driver['total_trips'],
                'rating' => round($driver['rating'], 1),
                'cancelled_orders' => (int)$driver['cancelled_orders'],
                'current_status' => $driver['current_status'],
                'total_earnings' => $driver['total_earnings']
            ],
            'order_statuses' => [
                'pending_orders' => (int)$order_statuses['pending_orders'],
                'accepted_orders' => (int)$order_statuses['accepted_orders'],
                'in_transit_orders' => (int)$order_statuses['in_transit_orders'],
                'completed_orders' => (int)$order_statuses['completed_orders'],
                'cancelled_orders' => (int)$order_statuses['cancelled_orders']
            ],
            'activity_logs' => array_map(function($log) {
                return [
                    'activity_type' => $log['activity_type'],
                    'activity_details' => $log['activity_details'],
                    'created_at' => $log['created_at']
                ];
            }, $activity_logs),
            'translations' => [
                'status_active' => __('status_active'),
                'status_inactive' => __('status_inactive'),
                'driver_available' => __('driver_available'),
                'driver_busy' => __('driver_busy'),
                'driver_offline' => __('driver_offline'),
                'total_trips' => __('total_trips'),
                'completed_trips' => __('completed_trips'),
                'cancelled_trips' => __('cancelled_trips'),
                'current_orders' => __('current_orders'),
                'latest_activities' => __('latest_activities'),
                'contact_info' => __('contact_info'),
                'trip_statistics' => __('trip_statistics'),
                'driver_rating' => __('driver_rating'),
                'no_action' => __('no_action'),
                'no_details' => __('no_details')
            ]
        ];

        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => __('driver_not_found')]);
    }
} catch (PDOException $e) {
    error_log("Driver Details Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => __('database_error')]);
} 