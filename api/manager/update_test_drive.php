<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_POST['request_id']);
    $employeeId = $_SESSION['employee_id'];
    
    $conn->begin_transaction();

    try {
        if (isset($_POST['assign']) && $_POST['assign'] === 'true') {
            // Проверяем, что заявка не назначена другому менеджеру
            $check = $conn->prepare("SELECT employee_id FROM test_drive_requests WHERE request_id = ?");
            $check->bind_param("i", $requestId);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            $check->close();

            if ($result['employee_id'] !== null) {
                throw new Exception('Заявка уже назначена другому менеджеру');
            }

            // Назначаем заявку менеджеру
            $update = $conn->prepare("UPDATE test_drive_requests SET employee_id = ? WHERE request_id = ?");
            $update->bind_param("ii", $employeeId, $requestId);
            $update->execute();
            $update->close();

            // Уведомление клиенту
            $request = $conn->query("SELECT client_id, car_id, preferred_date, preferred_time FROM test_drive_requests WHERE request_id = $requestId")->fetch_assoc();
            $car = $conn->query("SELECT make, model FROM cars WHERE car_id = {$request['car_id']}")->fetch_assoc();
            $message = "Ваша заявка на тест-драйв {$car['make']} {$car['model']} на {$request['preferred_date']} в {$request['preferred_time']} назначена менеджеру.";

            $notify = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) VALUES (?, 'Обновление тест-драйва', ?, 'test_drive', ?)");
            $notify->bind_param("isi", $request['client_id'], $message, $requestId);
            $notify->execute();
            $notify->close();
        } else {
            $status = $conn->real_escape_string($_POST['status']);
            
            // Проверяем допустимый статус
            if (!in_array($status, ['Confirmed', 'Rejected', 'Completed'])) {
                throw new Exception('Недопустимый статус');
            }
            
            // Обновляем статус и менеджера
            $update = $conn->prepare("UPDATE test_drive_requests SET status = ?, employee_id = ? WHERE request_id = ?");
            $update->bind_param("sii", $status, $employeeId, $requestId);
            $update->execute();
            $update->close();

            // Если статус Completed, обновляем связанный заказ
            if ($status === 'Completed') {
                $orderQuery = $conn->prepare("SELECT order_id FROM orders WHERE car_id = (SELECT car_id FROM test_drive_requests WHERE request_id = ?) AND order_type = 'test_drive' AND order_status = 'pending'");
                $orderQuery->bind_param("i", $requestId);
                $orderQuery->execute();
                $order = $orderQuery->get_result()->fetch_assoc();
                $orderQuery->close();

                if ($order) {
                    $updateOrder = $conn->prepare("UPDATE orders SET order_status = 'completed' WHERE order_id = ?");
                    $updateOrder->bind_param("i", $order['order_id']);
                    $updateOrder->execute();
                    $updateOrder->close();
                }
            }

            // Уведомление клиенту
            $request = $conn->query("SELECT client_id, car_id, preferred_date, preferred_time FROM test_drive_requests WHERE request_id = $requestId")->fetch_assoc();
            $car = $conn->query("SELECT make, model FROM cars WHERE car_id = {$request['car_id']}")->fetch_assoc();
            
            $message = $status === 'Confirmed' 
                ? "Ваша заявка на тест-драйв {$car['make']} {$car['model']} на {$request['preferred_date']} в {$request['preferred_time']} подтверждена."
                : ($status === 'Rejected' 
                    ? "Ваша заявка на тест-драйв {$car['make']} {$car['model']} на {$request['preferred_date']} в {$request['preferred_time']} отклонена."
                    : "Тест-драйв {$car['make']} {$car['model']} завершен.");
            
            $notify = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) VALUES (?, 'Обновление статуса тест-драйва', ?, 'test_drive', ?)");
            $notify->bind_param("isi", $request['client_id'], $message, $requestId);
            $notify->execute();
            $notify->close();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>