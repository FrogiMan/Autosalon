<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'])) {
    $carId = intval($_POST['car_id']);
    
    if (isset($_SESSION['compare'])) {
        $_SESSION['compare'] = array_filter($_SESSION['compare'], function($id) use ($carId) {
            return $id != $carId;
        });
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>