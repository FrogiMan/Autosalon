<?php
session_start();
require_once '../includes/db.php';


header('Content-Type: application/json');

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$clientId = $_SESSION['client_id'];
$query = "SELECT first_name, last_name, phone FROM clients WHERE client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $clientId);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();

if ($client) {
    echo json_encode([
        'success' => true,
        'first_name' => $client['first_name'],
        'last_name' => $client['last_name'],
        'phone' => $client['phone']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Клиент не найден']);
}
?>