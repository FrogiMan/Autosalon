<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'ID сотрудника не указан']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE employees SET is_admin = 0 WHERE employee_id = ?");
        $stmt->bind_param("i", $employeeId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Права администратора сняты']);
        } else {
            throw new Exception('Ошибка при обновлении статуса');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>