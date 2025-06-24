<?php
// api/admin/edit_client.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_id']);
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));

    if (!$clientId) {
        echo json_encode(['success' => false, 'message' => 'ID клиента не указан']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Проверка уникальности email
        $checkStmt = $conn->prepare("SELECT client_id FROM clients WHERE email = ? AND client_id != ?");
        $checkStmt->bind_param("si", $email, $clientId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('Этот email уже используется другим клиентом');
        }

        // Обработка пароля
        $passwordQuery = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passwordQuery = ", password = '$password'";
        }

        // Обновление клиента
        $query = "UPDATE clients SET 
                  first_name = '$firstName', 
                  last_name = '$lastName', 
                  phone = '$phone', 
                  email = '$email' 
                  $passwordQuery 
                  WHERE client_id = $clientId";

        if (!$conn->query($query)) {
            throw new Exception('Ошибка при обновлении клиента: ' . $conn->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Клиент успешно обновлен']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in edit_client: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>