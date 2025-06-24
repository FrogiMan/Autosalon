<?php
require_once '../includes/db.php';
require_once '../includes/payment_processor.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['client_id'])) {
        throw new Exception('Необходима авторизация');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Неправильный метод запроса');
    }

    $clientId = intval($_SESSION['client_id']);
    $orderId = intval($_POST['order_id'] ?? 0);
    $amount = floatval($_POST['amount']);
    $cardNumber = trim($_POST['card_number']);
    $cardholder = trim($_POST['cardholder_name']);
    $cardExpiry = trim($_POST['card_expiry']);
    $cardCvc = trim($_POST['card_cvc']);
    $paymentMethod = 'online';
    
    // Валидация
    if ($amount <= 0 || empty($cardNumber) || empty($cardholder) || empty($cardExpiry) || empty($cardCvc)) {
        throw new Exception('Заполните все поля');
    }

    $conn->begin_transaction();
    
    // Проверка заказа
    $orderCheck = $conn->prepare("
        SELECT o.order_id, o.order_type, o.car_id, 
               CASE 
                   WHEN o.order_type = 'test_drive' THEN c.test_drive_price
                   WHEN o.order_type = 'service' THEN COALESCE(st.price, 0)
                   WHEN o.order_type = 'purchase' THEN sd.sale_price
                   ELSE 0
               END as sale_price,
               sd.payment_method
        FROM orders o 
        JOIN cars c ON o.car_id = c.car_id
        LEFT JOIN sales_department sd ON o.order_id = sd.order_id
        LEFT JOIN service_requests sr ON o.car_id = sr.car_id AND o.order_type = 'service'
        LEFT JOIN service_types st ON sr.repair_type = st.type_name
        WHERE o.order_id = ? AND o.client_id = ? AND o.order_status = 'completed'
    ");
    if (!$orderCheck) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    $orderCheck->bind_param("ii", $orderId, $clientId);
    if (!$orderCheck->execute()) {
        throw new Exception('Ошибка выполнения запроса: ' . $orderCheck->error);
    }
    $order = $orderCheck->get_result()->fetch_assoc();
    $orderCheck->close();
    
    if (!$order) {
        throw new Exception("Заказ не найден");
    }
    
    // Проверка статуса оплаты
    if ($order['payment_method'] && $order['payment_method'] !== 'pending') {
        throw new Exception("Заказ уже оплачен");
    }
    
    // Проверка суммы
    if ($amount != $order['sale_price']) {
        throw new Exception("Неверная сумма платежа: ожидается $" . number_format($order['sale_price'], 2));
    }

    // Обработка платежа
    $paymentResult = processPayment($cardNumber, $amount, $cardholder, $cardExpiry, $cardCvc);
    if (!$paymentResult['success']) {
        throw new Exception($paymentResult['message']);
    }
    
    // Если записи в sales_department нет, создаем
    if (!$order['payment_method']) {
        $insertSale = $conn->prepare("
            INSERT INTO sales_department 
            (employee_id, client_id, car_id, sale_date, sale_price, order_id, payment_method)
            VALUES (?, ?, ?, NOW(), ?, ?, 'pending')
        ");
        if (!$insertSale) {
            throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
        }
        $employeeId = 1; // Замените на актуальный
        $insertSale->bind_param("iiidi", $employeeId, $clientId, $order['car_id'], $order['sale_price'], $orderId);
        if (!$insertSale->execute()) {
            throw new Exception('Ошибка выполнения запроса: ' . $insertSale->error);
        }
        $insertSale->close();
    }
    
    // Обновление платежа
    $updateSale = $conn->prepare("UPDATE sales_department SET payment_method = ?, transaction_id = ? WHERE order_id = ?");
    if (!$updateSale) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    $transactionId = $paymentResult['transaction_id'];
    $updateSale->bind_param("ssi", $paymentMethod, $transactionId, $orderId);
    if (!$updateSale->execute()) {
        throw new Exception('Ошибка выполнения запроса: ' . $updateSale->error);
    }
    $updateSale->close();
    
    // Уведомление клиенту
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
    
    $notifyQuery = $conn->prepare("INSERT INTO notifications 
                                 (client_id, title, message, related_entity_type, related_entity_id)
                                 VALUES (?, 'Оплата заказа', ?, ?, ?)");
    if (!$notifyQuery) {
        throw new Exception('Ошибка подготовки запроса: ' . $conn->error);
    }
    $message = "Ваш платеж на сумму $" . number_format($amount, 2) . " за {$car['make']} {$car['model']} прошел успешно. Номер заказа: $orderId";
    $relatedType = $order['order_type'];
    $relatedId = $orderId;
    $notifyQuery->bind_param("issi", $clientId, $message, $relatedType, $relatedId);
    if (!$notifyQuery->execute()) {
        throw new Exception('Ошибка выполнения запроса: ' . $notifyQuery->error);
    }
    $notifyQuery->close();
    
    $conn->commit();
    
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Платеж успешно обработан'
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction()) {
        $conn->rollback();
    }
    error_log("Payment error: " . $e->getMessage() . " in " . __FILE__ . " at line " . __LINE__);
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
?>