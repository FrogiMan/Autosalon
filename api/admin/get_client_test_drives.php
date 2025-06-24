<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if (isset($_GET['client_id'])) {
    $clientId = intval($_GET['client_id']);
    $query = "SELECT t.*, c.make, c.model 
              FROM test_drive_requests t
              JOIN cars c ON t.car_id = c.car_id
              WHERE t.client_id = ? AND t.status = 'Completed'
              ORDER BY t.preferred_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $testDrives = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'test_drives' => $testDrives]);
} else {
    echo json_encode(['success' => false, 'message' => 'Не указан ID клиента']);
}
?>