<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$clientId = $_SESSION['client_id'];

try {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE client_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Уведомления очищены']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>