<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$clientId = intval($_SESSION['client_id']);

try {
    $conn->begin_transaction();

    // Удаляем связанные записи в sales_department
    $deleteSales = $conn->prepare("DELETE sd FROM sales_department sd 
                                   JOIN orders o ON sd.order_id = o.order_id 
                                   WHERE o.client_id = ?");
    $deleteSales->bind_param("i", $clientId);
    $deleteSales->execute();

    // Удаляем связанные уведомления
    $deleteNotifications = $conn->prepare("DELETE FROM notifications 
                                           WHERE client_id = ? AND related_entity_type = 'order'");
    $deleteNotifications->bind_param("i", $clientId);
    $deleteNotifications->execute();

    // Удаляем заказы
    $deleteOrders = $conn->prepare("DELETE FROM orders WHERE client_id = ?");
    $deleteOrders->bind_param("i", $clientId);
    $deleteOrders->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'История заказов успешно очищена']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Clear order history error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при очистке истории заказов']);
} finally {
    $conn->close();
}
?>