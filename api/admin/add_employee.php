<?php
// api/admin/add_employee.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string(trim($_POST['position']));
    $salary = floatval($_POST['salary']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Проверяем, нет ли уже сотрудника с таким email
    $check = $conn->query("SELECT employee_id FROM employees WHERE email = '$email'");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Сотрудник с таким email уже существует']);
        exit;
    }
    
    // Обработка фото
    $photoName = 'default.jpg';
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../images/employees/';
        $photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
        $targetPath = $uploadDir . $photoName;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Ошибка загрузки фото']);
            exit;
        }
    }
    
    // Добавляем сотрудника
    $stmt = $conn->prepare(
        "INSERT INTO employees (first_name, last_name, phone, email, password, department, position, hire_date, salary, photo, is_admin)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)"
    );
    $stmt->bind_param("sssssssdsd", $firstName, $lastName, $phone, $email, $password, $department, $position, $salary, $photoName, $isAdmin);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'employee_id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка при добавлении сотрудника: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}

$conn->close();
?>