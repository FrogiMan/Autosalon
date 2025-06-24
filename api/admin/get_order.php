<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

try {
    $query = "
        SELECT o.*, c.first_name, c.last_name, car.make, car.model, car.year, car.price, 
               sd.sale_price, car.test_drive_price
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        JOIN cars car ON o.car_id = car.car_id
        LEFT JOIN sales_department sd ON o.order_id = sd.order_id
        ORDER BY o.order_date DESC
    ";
    
    $result = $conn->query($query);
    $orders = [];
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'order_id' => $row['order_id'],
            'order_date' => $row['order_date'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'make' => $row['make'],
            'model' => $row['model'],
            'order_type' => $row['order_type'],
            'order_status' => $row['order_status'],
            'price' => $row['price'],
            'test_drive_price' => $row['test_drive_price'],
            'sale_price' => $row['sale_price']
        ];
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Exception $e) {
    error_log("Error in get_orders: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке заказов']);
} finally {
    $conn->close();
}
?>