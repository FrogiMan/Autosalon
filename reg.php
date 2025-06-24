<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Валидация данных
    if (empty($firstName) || empty($lastName) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($password !== $confirmPassword) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать не менее 6 символов';
    } else {
        // Проверяем, не зарегистрирован ли уже email
        $check = $conn->prepare("SELECT client_id FROM clients WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Пользователь с таким email уже зарегистрирован';
        } else {
            // Регистрируем нового пользователя
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO clients (first_name, last_name, phone, email, password) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $firstName, $lastName, $phone, $email, $hashedPassword);
            
            if ($insert->execute()) {
                $_SESSION['client_id'] = $insert->insert_id;
                $_SESSION['user_name'] = $firstName;
                $_SESSION['is_admin'] = false;
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Ошибка при регистрации. Пожалуйста, попробуйте позже.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-form">
                        <h1>Регистрация</h1>
                        <?php if ($error): ?>
                            <div class="message error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">Имя</label>
                                    <input type="text" id="first_name" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="last_name">Фамилия</label>
                                    <input type="text" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Телефон</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Пароль</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Подтвердите пароль</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn">Зарегистрироваться</button>
                        </form>
                        <div class="auth-links">
                            <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

 <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
</body>
</html>