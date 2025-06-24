<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'])) {
    $carId = intval($_POST['car_id']);
    
    if (!isset($_SESSION['compare'])) {
        $_SESSION['compare'] = [];
    }
    
    if (!in_array($carId, $_SESSION['compare'])) {
        if (count($_SESSION['compare']) >= 3) {
            echo json_encode(['success' => false, 'message' => 'Максимум 3 автомобиля для сравнения']);
            exit;
        }
        $_SESSION['compare'][] = $carId;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Автомобиль уже добавлен']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Недопустимый запрос']);
}
?>