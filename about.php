<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Получаем информацию о команде
$teamQuery = "SELECT * FROM employees ORDER BY department, last_name";
$teamResult = $conn->query($teamQuery);
$team = $teamResult->fetch_all(MYSQLI_ASSOC);

// Группируем сотрудников по отделам
$departments = [];
foreach ($team as $employee) {
    if (!isset($departments[$employee['department']])) {
        $departments[$employee['department']] = [];
    }
    $departments[$employee['department']][] = $employee;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О компании - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <h1>О нашем автосалоне</h1>
                
                <div class="about-content">
                    <p>Добро пожаловать в AutoDealer - один из ведущих автомобильных салонов в вашем регионе. Мы работаем на рынке уже более 10 лет, предлагая нашим клиентам только лучшие автомобили и сервис.</p>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <span class="stat-number">10+</span>
                            <span class="stat-label">лет на рынке</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">1000+</span>
                            <span class="stat-label">довольных клиентов</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">моделей в наличии</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="section">
            <div class="container">
                <h2>Наша миссия</h2>
                <p>Мы стремимся сделать процесс покупки автомобиля максимально простым, прозрачным и приятным для наших клиентов. Наша цель - помочь вам найти автомобиль, который идеально соответствует вашим потребностям и бюджету.</p>
            </div>
        </section>
        
        <section class="section">
            <div class="container">
                <h2>Наша команда</h2>
                
                <?php foreach ($departments as $department => $employees): ?>
                    <h3><?= htmlspecialchars($department) ?></h3>
                    <div class="team-grid">
                        <?php foreach ($employees as $employee): ?>
                            <div class="team-member">
                                <h4><?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?></h4>
                                <p class="position"><?= htmlspecialchars($employee['department']) ?></p>
                                <p class="department"><?= htmlspecialchars($employee['position']) ?></p>
                                <p class="phone">Тел.: <?= htmlspecialchars($employee['phone']) ?></p>
                                <p class="email">Email: <?= htmlspecialchars($employee['email']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="section">
            <div class="container">
                <h2>Наши преимущества</h2>
                <div class="advantages-grid">
                    <div class="advantage-item">
                        <div class="advantage-icon">
                        </div>
                        <h3>Широкий выбор</h3>
                        <p>Более 50 моделей автомобилей разных марок и ценовых категорий</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon">
                        </div>
                        <h3>Гарантия качества</h3>
                        <p>Все автомобили проходят тщательную проверку перед продажей</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon">
                        </div>
                        <h3>Собственный сервис</h3>
                        <p>Профессиональное обслуживание и ремонт вашего автомобиля</p>
                    </div>
                    <div class="advantage-item">
                        <div class="advantage-icon">
                        </div>
                        <h3>Выгодные условия</h3>
                        <p>Гибкие программы кредитования и страхования</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

 <?php require_once 'includes/footer.php'; ?>
    <script src="assets/js/scripts.js"></script>
</body>
</html>