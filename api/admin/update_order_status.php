<?php
// api/admin/update_order_status.php
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
    $status = $_POST['status'] ?? '';

    if (!$orderId || !in_array($status, ['completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Проверка заказа
        $orderStmt = $conn->prepare("SELECT client_id, car_id FROM orders WHERE order_id = ?");
        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        if ($orderResult->num_rows === 0) {
            throw new Exception('Заказ не найден');
        }
        $order = $orderResult->fetch_assoc();
        $clientId = $order['client_id'];
        $carId = $order['car_id'];

        // Обновление статуса заказа
        $updateStmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $updateStmt->bind_param("si", $status, $orderId);
        if (!$updateStmt->execute()) {
            throw new Exception('Ошибка при обновлении статуса заказа');
        }

        // Обновление статуса автомобиля
        $newCarStatus = ($status === 'completed') ? 'sold' : 'available';
        $carStmt = $conn->prepare("UPDATE cars SET status = ? WHERE car_id = ?");
        $carStmt->bind_param("si", $newCarStatus, $carId);
        if (!$carStmt->execute()) {
            throw new Exception('Ошибка при обновлении статуса автомобиля');
        }

        // Уведомление клиента
        $carInfo = $conn->query("SELECT make, model FROM cars WHERE car_id = $carId")->fetch_assoc();
        $message = sprintf(
            'Статус вашего заказа #%d (%s %s) изменен на: %s.',
            $orderId,
            $carInfo['make'],
            $carInfo['model'],
            $status
        );
        $notifyStmt = $conn->prepare(
            "INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id, created_at) 
             VALUES (?, 'Обновление статуса заказа', ?, 'order', ?, NOW())"
        );
        $notifyStmt->bind_param("isi", $clientId, $message, $orderId);
        if (!$notifyStmt->execute()) {
            throw new Exception('Ошибка при отправке уведомления');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Статус заказа успешно обновлен']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in update_order_status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>