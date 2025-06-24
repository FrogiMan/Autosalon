<?php
// api/admin/add_car_to_client.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
    exit;
}

$clientId = intval($_POST['client_id'] ?? 0);
$carId = intval($_POST['car_id'] ?? 0);
$salePrice = floatval($_POST['sale_price'] ?? 0);
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$orderType = $_POST['order_type'] ?? 'purchase';
$orderStatus = $_POST['order_status'] ?? 'completed';
$testDriveId = intval($_POST['test_drive_id'] ?? 0);

// Get employee_id from session
$employeeId = isset($_SESSION['employee_id']) ? intval($_SESSION['employee_id']) : 0;

if (!$clientId || !$carId || !$salePrice || !$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля или войдите как сотрудник']);
    exit;
}

try {
    $conn->begin_transaction();

    // Check client
    $clientStmt = $conn->prepare("SELECT client_id FROM clients WHERE client_id = ?");
    $clientStmt->bind_param("i", $clientId);
    $clientStmt->execute();
    if ($clientStmt->get_result()->num_rows === 0) {
        throw new Exception('Клиент не найден');
    }

    // Check car
    $carStmt = $conn->prepare("SELECT car_id, price, status FROM cars WHERE car_id = ?");
    $carStmt->bind_param("i", $carId);
    $carStmt->execute();
    $carResult = $carStmt->get_result();
    if ($carResult->num_rows === 0) {
        throw new Exception('Автомобиль не найден');
    }
    $car = $carResult->fetch_assoc();
    if ($car['status'] !== 'available') {
        throw new Exception('Автомобиль недоступен для продажи');
    }

    // Check test drive
    if ($testDriveId) {
        $tdStmt = $conn->prepare("SELECT request_id FROM test_drive_requests WHERE request_id = ? AND client_id = ? AND status = 'Completed'");
        $tdStmt->bind_param("ii", $testDriveId, $clientId);
        $tdStmt->execute();
        if ($tdStmt->get_result()->num_rows === 0) {
            throw new Exception('Тест-драйв не найден или не завершен');
        }
    }

    // Add order
    $orderStmt = $conn->prepare(
        "INSERT INTO orders (client_id, car_id, order_date, order_type, order_status) 
         VALUES (?, ?, NOW(), ?, ?)"
    );
    $orderStmt->bind_param("iiss", $clientId, $carId, $orderType, $orderStatus);
    if (!$orderStmt->execute()) {
        throw new Exception('Ошибка при создании заказа');
    }
    $orderId = $conn->insert_id;

    // Add sale
    $saleStmt = $conn->prepare(
        "INSERT INTO sales_department (employee_id, client_id, car_id, order_id, sale_price, payment_method, sale_date) 
         VALUES (?, ?, ?, ?, ?, ?, NOW())"
    );
    $saleStmt->bind_param("iiiids", $employeeId, $clientId, $carId, $orderId, $salePrice, $paymentMethod);
    if (!$saleStmt->execute()) {
        throw new Exception('Ошибка при записи продажи');
    }

    // Update car status
    $newCarStatus = ($orderStatus === 'completed') ? 'sold' : 'reserved';
    $updateStmt = $conn->prepare("UPDATE cars SET status = ? WHERE car_id = ?");
    $updateStmt->bind_param("si", $newCarStatus, $carId);
    if (!$updateStmt->execute()) {
        throw new Exception('Ошибка при обновлении статуса автомобиля');
    }

    // Update test drive (if applicable)
    if ($testDriveId) {
        $updateTdStmt = $conn->prepare("UPDATE test_drive_requests SET order_id = ? WHERE request_id = ?");
        $updateTdStmt->bind_param("ii", $orderId, $testDriveId);
        if (!$updateTdStmt->execute()) {
            throw new Exception('Ошибка при привязке тест-драйва к заказу');
        }
    }

    // Send notification
    $carInfo = $conn->query("SELECT make, model FROM cars WHERE car_id = $carId")->fetch_assoc();
    $message = sprintf(
        'Вам добавлен автомобиль %s %s за $%.2f после %s. Статус заказа: %s.',
        $carInfo['make'],
        $carInfo['model'],
        $salePrice,
        $testDriveId ? 'тест-драйва #' . $testDriveId : 'прямой покупки',
        $orderStatus
    );
    $notifyStmt = $conn->prepare(
        "INSERT INTO notifications (client_id, title, message, related_entity_type, related_entity_id, created_at) 
         VALUES (?, 'Добавлен автомобиль', ?, 'order', ?, NOW())"
    );
    $notifyStmt->bind_param("isi", $clientId, $message, $orderId);
    if (!$notifyStmt->execute()) {
        throw new Exception('Ошибка при отправке уведомления');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Автомобиль успешно добавлен клиенту']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in add_car_to_client: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>