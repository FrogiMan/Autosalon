<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = $_SESSION['client_id'];
    $firstName = htmlspecialchars(trim($_POST['first_name']));
    $lastName = htmlspecialchars(trim($_POST['last_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    if ($password) {
        $query = "UPDATE clients SET first_name = ?, last_name = ?, email = ?, phone = ?, password = ? WHERE client_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $password, $clientId);
    } else {
        $query = "UPDATE clients SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE client_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $clientId);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении профиля']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>