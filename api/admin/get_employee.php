<?php
// api/admin/get_employee.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if (isset($_GET['employee_id'])) {
    $employeeId = intval($_GET['employee_id']);
    
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($employee = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'employee' => $employee
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Сотрудник не найден']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Не указан ID сотрудника']);
}

$conn->close();
?>