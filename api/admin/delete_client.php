<?php
// api/admin/delete_client.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $clientId = intval($_DELETE['client_id']);

    if (!$clientId) {
        echo json_encode(['success' => false, 'message' => 'ID клиента не указан']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Обновление связанных записей
        $conn->query("UPDATE orders SET client_id = NULL WHERE client_id = $clientId");
        $conn->query("UPDATE sales_department SET client_id = NULL WHERE client_id = $clientId");
        $conn->query("UPDATE test_drive_requests SET client_id = NULL WHERE client_id = $clientId");
        $conn->query("UPDATE service_requests SET client_id = NULL WHERE client_id = $clientId");
        $conn->query("DELETE FROM notifications WHERE client_id = $clientId");

        // Удаление клиента
        $deleteStmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
        $deleteStmt->bind_param("i", $clientId);
        if (!$deleteStmt->execute()) {
            throw new Exception('Ошибка при удалении клиента');
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Клиент успешно удален']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in delete_client: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>