<?php
// api/admin/get_client.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if (isset($_GET['client_id'])) {
    $clientId = intval($_GET['client_id']);
    
    // Получение данных клиента
    $clientStmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
    $clientStmt->bind_param("i", $clientId);
    $clientStmt->execute();
    $clientResult = $clientStmt->get_result();
    
    if ($client = $clientResult->fetch_assoc()) {
        // Получение количества заказов
        $orderStmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE client_id = ?");
        $orderStmt->bind_param("i", $clientId);
        $orderStmt->execute();
        $ordersCount = $orderStmt->get_result()->fetch_assoc()['count'];
        
        // Получение автомобилей клиента
        $carsStmt = $conn->prepare(
            "SELECT c.car_id, c.make, c.model, c.year, c.price, c.status 
             FROM cars c 
             JOIN orders o ON c.car_id = o.car_id 
             WHERE o.client_id = ?"
        );
        $carsStmt->bind_param("i", $clientId);
        $carsStmt->execute();
        $carsResult = $carsStmt->get_result();
        $cars = [];
        while ($car = $carsResult->fetch_assoc()) {
            $cars[] = $car;
        }
        
        echo json_encode([
            'success' => true,
            'client' => $client,
            'orders_count' => $ordersCount,
            'cars' => $cars
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Клиент не найден']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Не указан ID клиента']);
}

$conn->close();
?>