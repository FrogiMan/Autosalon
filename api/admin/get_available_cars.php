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
    $query = "SELECT car_id, make, model, year, price FROM cars WHERE status = 'available' ORDER BY make, model";
    $result = $conn->query($query);
    $cars = [];

    while ($row = $result->fetch_assoc()) {
        $cars[] = [
            'car_id' => $row['car_id'],
            'make' => $row['make'],
            'model' => $row['model'],
            'year' => $row['year'],
            'price' => floatval($row['price'])
        ];
    }

    echo json_encode(['success' => true, 'cars' => $cars]);
} catch (Exception $e) {
    error_log("Error in get_available_cars: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка при загрузке автомобилей']);
} finally {
    $conn->close();
}
?>