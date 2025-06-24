<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['is_favorite' => false]);
    exit;
}

if (isset($_GET['car_id'])) {
    $carId = intval($_GET['car_id']);
    $clientId = $_SESSION['client_id'];
    
    $check = $conn->prepare("SELECT favorite_id FROM favorites WHERE client_id = ? AND car_id = ?");
    $check->bind_param("ii", $clientId, $carId);
    $check->execute();
    $result = $check->get_result();
    
    echo json_encode(['is_favorite' => $result->num_rows > 0]);
} else {
    echo json_encode(['is_favorite' => false]);
}
?>