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
    $preferredDate = $_POST['date'] ?? '';  // Изменено с preferred_date на date
    $preferredTime = $_POST['time'] ?? '';  // Изменено с preferred_time на time
    
    if (!$carId || !$preferredDate || !$preferredTime) {
        echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
        exit;
    }

    try {
        $conn->begin_transaction();
        
        // Проверка автомобиля
        $carCheck = $conn->prepare("SELECT car_id, test_drive_price FROM cars WHERE car_id = ? AND status = 'available'");
        $carCheck->bind_param("i", $carId);
        $carCheck->execute();
        $car = $carCheck->get_result()->fetch_assoc();
        $carCheck->close();
        
        if (!$car) {
            throw new Exception("Автомобиль не найден или недоступен");
        }
        
        // Создание заявки на тест-драйв
        $insertRequest = $conn->prepare("
            INSERT INTO test_drive_requests 
            (client_id, car_id, preferred_date, preferred_time, status, request_date)
            VALUES (?, ?, ?, ?, 'Pending', NOW())
        ");
        $insertRequest->bind_param("iiss", $clientId, $carId, $preferredDate, $preferredTime);
        $insertRequest->execute();
        $requestId = $conn->insert_id;
        $insertRequest->close();
        
        // Создание заказа
        $insertOrder = $conn->prepare("
            INSERT INTO orders 
            (client_id, car_id, order_date, order_type, order_status)
            VALUES (?, ?, CURDATE(), 'test_drive', 'pending')
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
        $employeeId = 1; // Замените на актуальный ID сотрудника
        $salePrice = $car['test_drive_price'];
        $insertSale->bind_param("iiidi", $employeeId, $clientId, $carId, $salePrice, $orderId);
        $insertSale->execute();
        $insertSale->close();
        
        // Уведомление
        $notifyQuery = $conn->prepare("INSERT INTO notifications 
                                     (client_id, title, message, related_entity_type, related_entity_id)
                                     VALUES (?, 'Заявка на тест-драйв', ?, 'test_drive', ?)");
        $message = "Ваша заявка на тест-драйв на $preferredDate в $preferredTime принята.";
        $notifyQuery->bind_param("isi", $clientId, $message, $requestId);
        $notifyQuery->execute();
        $notifyQuery->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Заявка на тест-драйв успешно создана'
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