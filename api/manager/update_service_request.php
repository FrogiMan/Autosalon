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
        // Проверяем, что сотрудник из отдела Service
        $employeeQuery = $conn->prepare("SELECT department FROM employees WHERE employee_id = ?");
        $employeeQuery->bind_param("i", $employeeId);
        $employeeQuery->execute();
        $employee = $employeeQuery->get_result()->fetch_assoc();
        $employeeQuery->close();

        if ($employee['department'] !== 'Service') {
            throw new Exception('Доступ разрешен только сотрудникам отдела Service');
        }

        if (isset($_POST['assign']) && $_POST['assign'] === 'true') {
            // Проверяем, что заявка не назначена другому менеджеру
            $check = $conn->prepare("SELECT employee_id FROM service_requests WHERE request_id = ?");
            $check->bind_param("i", $requestId);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            $check->close();

            if ($result['employee_id'] !== null) {
                throw new Exception('Заявка уже назначена другому менеджеру');
            }

            // Назначаем заявку менеджеру
            $update = $conn->prepare("UPDATE service_requests SET employee_id = ? WHERE request_id = ?");
            $update->bind_param("ii", $employeeId, $requestId);
            $update->execute();
            $update->close();

            // Уведомление клиенту
            $request = $conn->query("SELECT client_id, car_id, repair_type, request_date FROM service_requests WHERE request_id = $requestId")->fetch_assoc();
            $car = $conn->query("SELECT make, model FROM cars WHERE car_id = {$request['car_id']}")->fetch_assoc();
            $message = "Ваша сервисная заявка на {$car['make']} {$car['model']} ({$request['repair_type']}) на " . date('d.m.Y', strtotime($request['request_date'])) . " назначена менеджеру.";

            $notify = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) VALUES (?, 'Обновление сервисной заявки', ?, 'service', ?)");
            $notify->bind_param("isi", $request['client_id'], $message, $requestId);
            $notify->execute();
            $notify->close();
        } else {
            $status = $conn->real_escape_string($_POST['status']);
            
            // Проверяем допустимый статус
            if (!in_array($status, ['In Progress', 'Completed', 'Rejected'])) {
                throw new Exception('Недопустимый статус');
            }
            
            // Обновляем статус и менеджера
            $update = $conn->prepare("UPDATE service_requests SET status = ?, employee_id = ? WHERE request_id = ?");
            $update->bind_param("sii", $status, $employeeId, $requestId);
            $update->execute();
            $update->close();

            // Если статус Completed, обновляем связанный заказ
            if ($status === 'Completed') {
                $orderQuery = $conn->prepare("SELECT order_id FROM orders WHERE car_id = (SELECT car_id FROM service_requests WHERE request_id = ?) AND order_type = 'service' AND order_status = 'pending'");
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
            $request = $conn->query("SELECT client_id, car_id, repair_type FROM service_requests WHERE request_id = $requestId")->fetch_assoc();
            $car = $conn->query("SELECT make, model FROM cars WHERE car_id = {$request['car_id']}")->fetch_assoc();
            
            $message = $status === 'In Progress' 
                ? "Ваша сервисная заявка на {$car['make']} {$car['model']} ({$request['repair_type']}) взята в работу."
                : ($status === 'Completed' 
                    ? "Ваша сервисная заявка на {$car['make']} {$car['model']} ({$request['repair_type']}) завершена."
                    : "Ваша сервисная заявка на {$car['make']} {$car['model']} ({$request['repair_type']}) отклонена.");
            
            $notify = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) VALUES (?, 'Обновление статуса сервиса', ?, 'service', ?)");
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