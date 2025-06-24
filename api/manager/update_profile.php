<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_SESSION['employee_id'];
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    // Проверяем, нет ли уже сотрудника с таким email
    $check = $conn->query("SELECT employee_id FROM employees WHERE email = '$email' AND employee_id != $employeeId");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Сотрудник с таким email уже существует']);
        exit;
    }
    
    // Обновляем данные сотрудника
    if ($password) {
        $query = "UPDATE employees SET 
                 first_name = '$firstName', 
                 last_name = '$lastName', 
                 phone = '$phone', 
                 email = '$email', 
                 password = '$password' 
                 WHERE employee_id = $employeeId";
    } else {
        $query = "UPDATE employees SET 
                 first_name = '$firstName', 
                 last_name = '$lastName', 
                 phone = '$phone', 
                 email = '$email' 
                 WHERE employee_id = $employeeId";
    }
    
    if ($conn->query($query)) {
        // Обновляем имя в сессии
        $_SESSION['user_name'] = "$firstName $lastName";
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении профиля: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>