<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Валидация
    if (!$username || !validate_email($email) || !$password || !$confirm_password) {
        $error = 'Заполните все поля корректно.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Имя пользователя должно быть от 3 до 50 символов.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов.';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают.';
    } else {
        // Проверка уникальности
        $sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if ($count > 0) {
            $error = 'Имя пользователя или email уже заняты.';
        } else {
            // Регистрация
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'client';
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssss', $username, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = 'Регистрация успешна! Пожалуйста, войдите.';
                header('Location: login.php?message=' . urlencode($success));
                exit;
            } else {
                $error = 'Ошибка при регистрации.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="register">
            <h2>Регистрация</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST" action="register.php">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                <label for="confirm_password">Подтвердите пароль:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <button type="submit" class="btn">Зарегистрироваться</button>
            </form>
            <p>Уже есть аккаунт? <a href="login.php">Войдите</a>.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>