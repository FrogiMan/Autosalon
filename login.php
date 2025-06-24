<?php
session_start();
require_once 'includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Проверка администратора
    if ($email === 'admin@autodealer.com' && $password === 'admin123') {
        $_SESSION['is_admin'] = true;
        $_SESSION['user_name'] = 'Администратор';
        header('Location: admin_dashboard.php');
        exit;
    }
    
    // Проверка сотрудников
    $employeeQuery = "SELECT employee_id, first_name, last_name, password, department, is_admin 
                     FROM employees WHERE email = ?";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    
    if ($employeeResult->num_rows === 1) {
        $employee = $employeeResult->fetch_assoc();
        if (password_verify($password, $employee['password'])) {
            $_SESSION['employee_id'] = $employee['employee_id'];
            $_SESSION['user_name'] = $employee['first_name'] . ' ' . $employee['last_name'];
            $_SESSION['department'] = $employee['department'];
            $_SESSION['is_admin'] = $employee['is_admin'];
            
            error_log("Login successful for employee_id: " . $_SESSION['employee_id']);
            
            $conn->query("UPDATE employees SET last_login = NOW() WHERE employee_id = {$employee['employee_id']}");
            header('Location: manager_dashboard.php');
            exit;
        } else {
            error_log("Password verification failed for email: $email");
        }
    } else {
        error_log("No employee found for email: $email");
    }
    
    // Проверка клиентов
    $clientQuery = "SELECT client_id, first_name, last_name, password FROM clients WHERE email = ?";
    $stmt = $conn->prepare($clientQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $clientResult = $stmt->get_result();
    
    if ($clientResult->num_rows === 1) {
        $client = $clientResult->fetch_assoc();
        if (password_verify($password, $client['password'])) {
            $_SESSION['client_id'] = $client['client_id'];
            $_SESSION['user_name'] = $client['first_name'];
            $_SESSION['is_admin'] = false;
            
            header('Location: profile.php');
            exit;
        }
    }
    
    $error = 'Неверный email или пароль';
}

// Теперь включаем header.php после всей логики
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - AutoDealer</title>
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-header">
                <h1>Вход в систему</h1>
                <p>Пожалуйста, авторизуйтесь для доступа к системе</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            
            <div class="auth-footer">
                <p>Нет аккаунта? <a href="reg.php">Зарегистрируйтесь</a></p>
                <p><a href="forgot_password.php">Забыли пароль?</a></p>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>