<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_SESSION['client_id']);
    $carId = intval($_POST['car_id'] ?? 0);
    $serviceTypeId = intval($_POST['service_type_id'] ?? 0);
    $preferredDate = $_POST['preferred_date'] ?? '';
    
    // Получаем название типа сервиса по ID
    $serviceTypeQuery = $conn->prepare("SELECT type_name, price FROM service_types WHERE service_type_id = ?");
    $serviceTypeQuery->bind_param("i", $serviceTypeId);
    $serviceTypeQuery->execute();
    $serviceType = $serviceTypeQuery->get_result()->fetch_assoc();
    $serviceTypeQuery->close();
    
    if (!$carId || !$serviceTypeId || !$preferredDate) {
        echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
        exit;
    }

    try {
        $conn->begin_transaction();
        
        // Проверка автомобиля
        $carCheck = $conn->prepare("SELECT car_id FROM cars WHERE car_id = ?");
        $carCheck->bind_param("i", $carId);
        $carCheck->execute();
        $car = $carCheck->get_result()->fetch_assoc();
        $carCheck->close();
        
        if (!$car) {
            throw new Exception("Автомобиль не найден");
        }
        
        // Создание сервисной заявки
        $insertRequest = $conn->prepare("
            INSERT INTO service_requests 
            (client_id, car_id, repair_type, request_date, status, created_at)
            VALUES (?, ?, ?, ?, 'Pending', NOW())
        ");
        $repairType = $serviceType['type_name'];
        $insertRequest->bind_param("iiss", $clientId, $carId, $repairType, $preferredDate);
        $insertRequest->execute();
        $requestId = $conn->insert_id;
        $insertRequest->close();
        
        // Создание заказа
        $insertOrder = $conn->prepare("
            INSERT INTO orders 
            (client_id, car_id, order_date, order_type, order_status)
            VALUES (?, ?, CURDATE(), 'service', 'pending')
        ");
        $insertOrder->bind_param("ii", $clientId, $carId);
        $insertOrder->execute();
        $orderId = $conn->insert_id;
        $insertOrder->close();
        
        // Создание записи в sales_department
        $insertSale = $conn->prepare("
            INSERT INTO sales_department 
            (employee_id, client_id, car_id, sale_date, sale_price, order_id, payment_method)
            VALUES (?, ?, ?, NOW(), ?, ?, 'pending')
        ");
        $employeeId = 2; // Замените на актуальный ID сотрудника
        $salePrice = $serviceType['price'];
        $insertSale->bind_param("iiidi", $employeeId, $clientId, $carId, $salePrice, $orderId);
        $insertSale->execute();
        $insertSale->close();
        
        // Уведомление
        $notifyQuery = $conn->prepare("INSERT INTO notifications 
                                     (client_id, title, message, related_entity_type, related_entity_id)
                                     VALUES (?, 'Сервисная заявка', ?, 'service', ?)");
        $message = "Ваша заявка на сервис ($repairType) на $preferredDate принята.";
        $notifyQuery->bind_param("isi", $clientId, $message, $requestId);
        $notifyQuery->execute();
        $notifyQuery->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Сервисная заявка успешно создана'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>