<?php
session_start();
$logged_in = is_authenticated();
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'client';

// Проверка сообщения
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автосалон</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/script.js" defer></script>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <ul>
                    <li><a href="/index.php">Главная</a></li>
                    <li><a href="/catalog.php">Каталог товаров</a></li>
                    <li><a href="/about.php">О нас</a></li>
                    <li><a href="/services.php">Сервис</a></li>
                    <li><a href="/compare.php">Сравнение</a></li>
                    <li><a href="/contacts.php">Контакты</a></li>
                    <?php if ($logged_in): ?>
                        <li><a href="/profile.php">Личный кабинет</a></li>
                        <?php if (is_admin_or_manager($user_role)): ?>
                            <li><a href="/admin/index.php">Админ-панель</a></li>
                        <?php endif; ?>
                        <li><a href="/logout.php">Выйти</a></li>
                    <?php else: ?>
                        <li><a href="/login.php">Войти</a></li>
                        <li><a href="/register.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php if ($message): ?>
            <div class="notification-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
    </header>