<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    // Redirect admins/managers to admin panel, others to profile
    if (is_admin_or_manager($_SESSION['user_role'])) {
        header('Location: admin/index.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}

$error = '';
$message = isset($_GET['message']) ? urldecode($_GET['message']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if ($username && $password) {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = 'Ошибка сервера: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    // Redirect based on role
                    if (is_admin_or_manager($user['role'])) {
                        header('Location: admin/index.php');
                    } else {
                        header('Location: profile.php');
                    }
                    exit;
                } else {
                    $error = 'Неверный пароль.';
                }
            } else {
                $error = 'Пользователь не найден.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Заполните все поля.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="login">
            <h2>Вход</h2>
            <?php if ($message): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" class="btn">Войти</button>
            </form>
            <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>