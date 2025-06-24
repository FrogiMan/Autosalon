<?php
// api/admin/update_order.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $clientId = intval($_POST['client_id'] ?? 0);
    $carId = intval($_POST['car_id'] ?? 0);
    $orderDate = $_POST['order_date'] ?? '';
    $orderType = $_POST['order_type'] ?? '';
    $orderStatus = $_POST['order_status'] ?? '';
    $salePrice = floatval($_POST['sale_price'] ?? 0);

    if (!$orderId || !$clientId || !$carId || !$orderDate || !$orderType || !$orderStatus || !$salePrice) {
        echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Проверка существования заказа
        $orderStmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        if ($orderStmt->get_result()->num_rows === 0) {
            throw new Exception('Заказ не найден');
        }

        // Проверка клиента
        $clientStmt = $conn->prepare("SELECT client_id FROM clients WHERE client_id = ?");
        $clientStmt->bind_param("i", $clientId);
        $clientStmt->execute();
        if ($clientStmt->get_result()->num_rows === 0) {
            throw new Exception('Клиент не найден');
        }

        // Проверка автомобиля
        $carStmt = $conn->prepare("SELECT car_id, status FROM cars WHERE car_id = ?");
        $carStmt->bind_param("i", $carId);
        $carStmt->execute();
        $carResult = $carStmt->get_result();
        if ($carResult->num_rows === 0) {
            throw new Exception('Автомобиль не найден');
        }
        $car = $carResult->fetch_assoc();
        if ($car['status'] === 'sold' && $orderStatus === 'pending') {
            throw new Exception('Автомобиль уже продан');
        }

        // Обновление заказа
        $updateOrderStmt = $conn->prepare(
            "UPDATE orders SET client_id = ?, car_id = ?, order_date = ?, order_type = ?, order_status = ? WHERE order_id = ?"
        );
        $updateOrderStmt->bind_param("iisssi", $clientId, $carId, $orderDate, $orderType, $orderStatus, $orderId);
        if (!$updateOrderStmt->execute()) {
            throw new Exception('Ошибка при обновлении заказа');
        }

        // Обновление или создание записи о продаже
        $checkSaleStmt = $conn->prepare("SELECT sale_id FROM sales_department WHERE order_id = ?");
        $checkSaleStmt->bind_param("i", $orderId);
        $checkSaleStmt->execute();
        $saleResult = $checkSaleStmt->get_result();

        if ($saleResult->num_rows > 0) {
            $updateSaleStmt = $conn->prepare(
                "UPDATE sales_department SET client_id = ?, car_id = ?, sale_price = ?, payment_method = ? WHERE order_id = ?"
            );
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $updateSaleStmt->bind_param("iidsi", $clientId, $carId, $salePrice, $paymentMethod, $orderId);
        } else {
            $insertSaleStmt = $conn->prepare(
                "INSERT INTO sales_department (employee_id, client_id, car_id, order_id, sale_price, payment_method, sale_date) 
                 VALUES (NULL, ?, ?, ?, ?, ?, NOW())"
            );
            $paymentMethod = $_POST['payment_method'] ?? 'cash';
            $insertSaleStmt->bind_param("iidsi", $clientId, $carId, $orderId, $salePrice, $paymentMethod);
            $updateSaleStmt = $insertSaleStmt;
        }
        if (!$updateSaleStmt->execute()) {
            throw new Exception('Ошибка при обновлении записи о продаже');
        }

        // Обновление статуса автомобиля
        $newCarStatus = ($orderStatus === 'completed') ? 'sold' : ($orderStatus === 'pending' ? 'reserved' : 'available');
        $updateCarStmt = $conn->prepare("UPDATE cars SET status = ? WHERE car_id = ?");
        $updateCarStmt->bind_param("si", $newCarStatus, $carId);
        if (!$updateCarStmt->execute()) {
            throw new Exception('Ошибка при обновлении статуса автомобиля');
        }

        // Уведомление клиента
        $carInfo = $conn->query("SELECT make, model FROM cars WHERE car_id = $carId")->fetch_assoc();
        $message = sprintf(
            'Ваш заказ #%d (%s %s) обновлен. Новый статус: %s, цена: $%.2f.',
            $orderId,
            $carInfo['make'],
            $carInfo['model'],
            $orderStatus,
            $salePrice
        );
        $notifyStmt = $conn->prepare(
            "INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id, created_at) 
             VALUES (?, 'Обновление заказа', ?, 'order', ?, NOW())"
        );
        $notifyStmt->bind_param("isi", $clientId, $message, $orderId);
        if (!$notifyStmt->execute()) {
            throw new Exception('Ошибка при отправке уведомления');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Заказ успешно обновлен']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in update_order: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>