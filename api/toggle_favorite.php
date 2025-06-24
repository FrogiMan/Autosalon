<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carId = intval($_POST['car_id']);
    $clientId = $_SESSION['client_id'];
    
    // Проверяем, есть ли уже в избранном
    $check = $conn->prepare("SELECT favorite_id FROM favorites WHERE client_id = ? AND car_id = ?");
    $check->bind_param("ii", $clientId, $carId);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Удаляем из избранного
        $delete = $conn->prepare("DELETE FROM favorites WHERE client_id = ? AND car_id = ?");
        $delete->bind_param("ii", $clientId, $carId);
        $delete->execute();
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Добавляем в избранное
        $insert = $conn->prepare("INSERT INTO favorites (client_id, car_id) VALUES (?, ?)");
        $insert->bind_param("ii", $clientId, $carId);
        $insert->execute();
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>