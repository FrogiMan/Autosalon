<?php
// api/admin/edit_employee.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = intval($_POST['employee_id']);
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string(trim($_POST['position']));
    $salary = floatval($_POST['salary']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'ID сотрудника не указан']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Проверка уникальности email
        $checkStmt = $conn->prepare("SELECT employee_id FROM employees WHERE email = ? AND employee_id != ?");
        $checkStmt->bind_param("si", $email, $employeeId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('Этот email уже используется другим сотрудником');
        }

        // Обработка пароля
        $passwordQuery = '';
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $passwordQuery = ", password = '$password'";
        }

        // Обработка фото
        $photoQuery = '';
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../images/employees/';
            $photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $photoName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                // Удаление старого фото
                $oldPhotoStmt = $conn->prepare("SELECT photo FROM employees WHERE employee_id = ?");
                $oldPhotoStmt->bind_param("i", $employeeId);
                $oldPhotoStmt->execute();
                $oldPhoto = $oldPhotoStmt->get_result()->fetch_assoc()['photo'];
                if ($oldPhoto && $oldPhoto !== 'default.jpg' && file_exists($uploadDir . $oldPhoto)) {
                    unlink($uploadDir . $oldPhoto);
                }
                $photoQuery = ", photo = '$photoName'";
            }
        }

        // Обновление сотрудника
        $query = "UPDATE employees SET 
                  first_name = '$firstName', 
                  last_name = '$lastName', 
                  phone = '$phone', 
                  email = '$email', 
                  department = '$department', 
                  position = '$position', 
                  salary = $salary, 
                  is_admin = $isAdmin 
                  $passwordQuery $photoQuery 
                  WHERE employee_id = $employeeId";

        if (!$conn->query($query)) {
            throw new Exception('Ошибка при обновлении сотрудника: ' . $conn->error);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Сотрудник успешно обновлен']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in edit_employee: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>