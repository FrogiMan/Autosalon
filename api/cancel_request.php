<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Проверка авторизации клиента
if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
    exit;
}

// Получение данных из запроса
$clientId = $_SESSION['client_id'];
$requestId = intval($_POST['request_id'] ?? 0);
$type = $_POST['type'] ?? '';

if ($requestId <= 0 || !in_array($type, ['test_drive', 'service'])) {
    echo json_encode(['success' => false, 'message' => 'Недействительные параметры']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($type === 'test_drive') {
        // Проверка, что заявка принадлежит клиенту и имеет статус Pending
        $checkStmt = $conn->prepare("SELECT t.client_id, t.employee_id, t.preferred_date, t.preferred_time, c.make, c.model 
                                     FROM test_drive_requests t 
                                     JOIN cars c ON t.car_id = c.car_id 
                                     WHERE t.request_id = ? AND t.client_id = ? AND t.status = 'Pending'");
        if ($checkStmt === false) {
            throw new Exception("Ошибка подготовки запроса проверки тест-драйва: " . $conn->error);
        }
        $checkStmt->bind_param("ii", $requestId, $clientId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Заявка не найдена или не может быть отменена");
        }

        $request = $result->fetch_assoc();
        $checkStmt->close();

        // Обновление статуса заявки
        $updateStmt = $conn->prepare("UPDATE test_drive_requests SET status = 'Cancelled' WHERE request_id = ?");
        if ($updateStmt === false) {
            throw new Exception("Ошибка подготовки запроса обновления тест-драйва: " . $conn->error);
        }
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();
        $updateStmt->close();

        // Уведомление для клиента
        $clientMessage = "Вы отменили заявку на тест-драйв {$request['make']} {$request['model']} на {$request['preferred_date']} в {$request['preferred_time']}.";
        $clientNotifyStmt = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) 
                                            VALUES (?, 'Отмена тест-драйва', ?, 'test_drive', ?)");
        if ($clientNotifyStmt === false) {
            throw new Exception("Ошибка подготовки запроса уведомления клиента: " . $conn->error);
        }
        $clientNotifyStmt->bind_param("isi", $clientId, $clientMessage, $requestId);
        $clientNotifyStmt->execute();
        $clientNotifyStmt->close();

        // Уведомление для менеджера, если он назначен
        if ($request['employee_id'] !== null) {
            $managerMessage = "Клиент ID $clientId отменил заявку на тест-драйв {$request['make']} {$request['model']} на {$request['preferred_date']} в {$request['preferred_time']}.";
            $managerNotifyStmt = $conn->prepare("INSERT INTO notifications (employee_id, title, message, related_entity_type, related_entity_id) 
                                                 VALUES (?, 'Отмена тест-драйва', ?, 'test_drive', ?)");
            if ($managerNotifyStmt === false) {
                throw new Exception("Ошибка подготовки запроса уведомления менеджера: " . $conn->error);
            }
            $managerNotifyStmt->bind_param("isi", $request['employee_id'], $managerMessage, $requestId);
            $managerNotifyStmt->execute();
            $managerNotifyStmt->close();
        }
    } elseif ($type === 'service') {
        // Проверка, что сервисная заявка принадлежит клиенту и имеет статус Pending
        $checkStmt = $conn->prepare("SELECT s.client_id, s.employee_id, s.request_date, s.repair_type, c.make, c.model 
                                     FROM service_requests s 
                                     JOIN cars c ON s.car_id = c.car_id 
                                     WHERE s.request_id = ? AND s.client_id = ? AND s.status = 'Pending'");
        if ($checkStmt === false) {
            throw new Exception("Ошибка подготовки запроса проверки сервисной заявки: " . $conn->error);
        }
        $checkStmt->bind_param("ii", $requestId, $clientId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Сервисная заявка не найдена или не может быть отменена");
        }

        $request = $result->fetch_assoc();
        $checkStmt->close();

        // Обновление статуса сервисной заявки
        $updateStmt = $conn->prepare("UPDATE service_requests SET status = 'Cancelled' WHERE request_id = ?");
        if ($updateStmt === false) {
            throw new Exception("Ошибка подготовки запроса обновления сервисной заявки: " . $conn->error);
        }
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();
        $updateStmt->close();

        // Уведомление для клиента
        $clientMessage = "Вы отменили сервисную заявку на {$request['make']} {$request['model']} (тип работ: {$request['repair_type']}) от " . date('d.m.Y', strtotime($request['request_date'])) . ".";
        $clientNotifyStmt = $conn->prepare("INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id) 
                                            VALUES (?, 'Отмена сервисной заявки', ?, 'service', ?)");
        if ($clientNotifyStmt === false) {
            throw new Exception("Ошибка подготовки запроса уведомления клиента: " . $conn->error);
        }
        $clientNotifyStmt->bind_param("isi", $clientId, $clientMessage, $requestId);
        $clientNotifyStmt->execute();
        $clientNotifyStmt->close();

        // Уведомление для менеджера, если он назначен
        if ($request['employee_id'] !== null) {
            $managerMessage = "Клиент ID $clientId отменил сервисную заявку на {$request['make']} {$request['model']} (тип работ: {$request['repair_type']}) от " . date('d.m.Y', strtotime($request['request_date'])) . ".";
            $managerNotifyStmt = $conn->prepare("INSERT INTO notifications (employee_id, title, message, related_entity_type, related_entity_id) 
                                                 VALUES (?, 'Отмена сервисной заявки', ?, 'service', ?)");
            if ($managerNotifyStmt === false) {
                throw new Exception("Ошибка подготовки запроса уведомления менеджера: " . $conn->error);
            }
            $managerNotifyStmt->bind_param("isi", $request['employee_id'], $managerMessage, $requestId);
            $managerNotifyStmt->execute();
            $managerNotifyStmt->close();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Заявка успешно отменена']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Ошибка в cancel_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>