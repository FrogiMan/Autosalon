<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

try {
    $carId = intval($_GET['car_id'] ?? 0);
    if (!$carId) {
        throw new Exception('ID автомобиля не указан');
    }

    $stmt = $conn->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'car' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Автомобиль не найден']);
    }
} catch (Exception $e) {
    error_log("Error in get_car: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>