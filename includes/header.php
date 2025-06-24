<?php
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$isEmployee = isset($_SESSION['employee_id']);
$isClient = isset($_SESSION['client_id']) && !$isAdmin && !$isEmployee;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">AutoDealer</a>
                <ul class="nav-menu">
                    <li><a href="index.php" <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : '' ?>>Главная</a></li>
                    <li><a href="cars.php" <?= basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'class="active"' : '' ?>>Каталог</a></li>
                    
                    <?php if ($isAdmin): ?>
                        <li><a href="admin_dashboard.php" <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'class="active"' : '' ?>>Панель админа</a></li>
                    <?php elseif ($isEmployee): ?>
                        <li><a href="manager_dashboard.php" <?= basename($_SERVER['PHP_SELF']) == 'manager_dashboard.php' ? 'class="active"' : '' ?>>Мои заявки</a></li>
                    <?php endif; ?>
                    
                    <?php if ($isClient): ?>
                        <li><a href="profile.php" <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'class="active"' : '' ?>>Личный кабинет</a></li>
                        <li><a href="compare.php" <?= basename($_SERVER['PHP_SELF']) == 'compare.php' ? 'class="active"' : '' ?>>Сравнение</a></li>
                    <?php endif; ?>
                    
                    <li><a href="about.php" <?= basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : '' ?>>О нас</a></li>
                    <li><a href="contact.php" <?= basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : '' ?>>Контакты</a></li>
                    <li><a href="service.php" <?= basename($_SERVER['PHP_SELF']) == 'service.php' ? 'class="active"' : '' ?>>Сервис</a></li>
                    
                    <?php if (!isset($_SESSION['client_id']) && !isset($_SESSION['employee_id'])): ?>
                        <li><a href="login.php" <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'class="active"' : '' ?>>Вход</a></li>
                        <li><a href="reg.php" <?= basename($_SERVER['PHP_SELF']) == 'reg.php' ? 'class="active"' : '' ?>>Регистрация</a></li>
                    <?php else: ?>
                        <li><a href="logout.php">Выйти</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>