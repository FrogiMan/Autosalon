<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Проверка аутентификации и прав
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен: требуется авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
    exit;
}

// Проверка и валидация входных данных
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указаны обязательные параметры']);
    exit;
}

$orderId = intval($_POST['order_id']);
$status = trim($_POST['status']);
$employeeId = $_SESSION['employee_id'];

// Проверяем допустимый статус
$allowedStatuses = ['completed', 'cancelled', 'processing'];
if (!in_array($status, $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недопустимый статус']);
    exit;
}

$conn->begin_transaction();

try {
    // Получаем информацию о заказе
    $orderQuery = $conn->prepare("SELECT client_id, car_id, order_type FROM orders WHERE order_id = ?");
    if (!$orderQuery) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $orderQuery->bind_param("i", $orderId);
    if (!$orderQuery->execute()) {
        throw new Exception('Ошибка выполнения запроса: ' . $orderQuery->error);
    }
    
    $order = $orderQuery->get_result()->fetch_assoc();
    $orderQuery->close();
    
    if (!$order) {
        http_response_code(404);
        throw new Exception('Заказ не найден');
    }
    
    // Обновляем только статус заказа (без employee_id)
    $updateOrder = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    if (!$updateOrder) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $updateOrder->bind_param("si", $status, $orderId);
    if (!$updateOrder->execute()) {
        throw new Exception('Ошибка обновления заказа: ' . $updateOrder->error);
    }
    $updateOrder->close();
    
    if ($status === 'completed') {
        // Если заказ завершен, добавляем запись в sales_department с employee_id
        $carQuery = $conn->prepare("SELECT price FROM cars WHERE car_id = ?");
        if (!$carQuery) {
            throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
        }
        
        $carQuery->bind_param("i", $order['car_id']);
        if (!$carQuery->execute()) {
            throw new Exception('Ошибка выполнения запроса: ' . $carQuery->error);
        }
        
        $car = $carQuery->get_result()->fetch_assoc();
        $carQuery->close();
        
        if (!$car) {
            throw new Exception('Автомобиль не найден');
        }
        
        $salePrice = $car['price'];
        
        $insertSale = $conn->prepare("INSERT INTO sales_department 
                                    (employee_id, client_id, car_id, sale_date, sale_price, order_id, payment_method) 
                                    VALUES (?, ?, ?, NOW(), ?, ?, 'completed')");
        if (!$insertSale) {
            throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
        }
        
        $insertSale->bind_param("iiidi", $employeeId, $order['client_id'], $order['car_id'], $salePrice, $orderId);
        if (!$insertSale->execute()) {
            throw new Exception('Ошибка добавления продажи: ' . $insertSale->error);
        }
        $insertSale->close();
        
        // Меняем статус автомобиля
        $updateCar = $conn->prepare("UPDATE cars SET status = 'sold' WHERE car_id = ?");
        if (!$updateCar) {
            throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
        }
        
        $updateCar->bind_param("i", $order['car_id']);
        if (!$updateCar->execute()) {
            throw new Exception('Ошибка обновления автомобиля: ' . $updateCar->error);
        }
        $updateCar->close();
    }
    
    // Отправляем уведомление клиенту
    $carQuery = $conn->prepare("SELECT make, model FROM cars WHERE car_id = ?");
    if (!$carQuery) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $carQuery->bind_param("i", $order['car_id']);
    if (!$carQuery->execute()) {
        throw new Exception('Ошибка выполнения запроса: ' . $carQuery->error);
    }
    
    $car = $carQuery->get_result()->fetch_assoc();
    $carQuery->close();
    
    if (!$car) {
        throw new Exception('Автомобиль не найден для уведомления');
    }
    
    $message = $status === 'completed' 
        ? "Ваш заказ на {$car['make']} {$car['model']} завершен. Спасибо за покупку!"
        : ($status === 'processing' 
            ? "Ваш заказ на {$car['make']} {$car['model']} взят в работу."
            : "Ваш заказ на {$car['make']} {$car['model']} отменен.");
    
    $notify = $conn->prepare("INSERT INTO notifications 
                             (client_id, title, message, related_entity_type, related_entity_id) 
                             VALUES (?, 'Обновление статуса заказа', ?, 'order', ?)");
    if (!$notify) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    
    $notify->bind_param("isi", $order['client_id'], $message, $orderId);
    if (!$notify->execute()) {
        throw new Exception('Ошибка отправки уведомления: ' . $notify->error);
    }
    $notify->close();
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка при обновлении заказа: ' . $e->getMessage()
    ]);
}
?>