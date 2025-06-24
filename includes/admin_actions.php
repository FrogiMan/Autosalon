<?php
// includes/admin_actions.php
require_once 'db.php';
require_once 'functions.php';

function handleAdminAction($postData, $conn) {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
        exit;
    }

    $action = $postData['action'] ?? '';

    switch ($action) {
        case 'add_car':
            include 'api/admin/add_car.php';
            break;
        case 'edit_car':
            include 'api/admin/edit_car.php';
            break;
        case 'delete_car':
            include 'api/admin/delete_car.php';
            break;
        case 'add_employee':
            include 'api/admin/add_employee.php';
            break;
        case 'edit_employee':
            include 'api/admin/edit_employee.php';
            break;
        case 'delete_employee':
            include 'api/admin/delete_employee.php';
            break;
        case 'add_car_to_client':
            include 'api/admin/add_car_to_client.php';
            break;
        case 'update_order':
            include 'api/admin/update_order.php';
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Неизвестное действие']);
            exit;
    }
}
?>