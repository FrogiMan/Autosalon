<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_authenticated() || !is_admin_or_manager($_SESSION['user_role'])) {
    $_SESSION['message'] = 'Войдите с правами правами администратора или менеджера.';
    header('Location: /login.php');
    exit;
}

// Получение статистики
$total_cars = $conn->query("SELECT COUNT(*) FROM cars")->fetch_row()[0];
$total_requests = $conn->query("SELECT COUNT(*) FROM test_drive_requests")->fetch_row()[0];
$total_reviews = $conn->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetch_row()[0];
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
?>

<?php include '../includes/header.php'; ?>

<main>
    <div class="container">
        <h2>Панель управления</h2>
        <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Автомобили</h3>
                <p><?php echo $total_cars; ?></p>
                <a href="cars.php" class="btn">Управление</a>
            </div>
            <div class="stat-card">
                <h3>Заявки на тест-драйв</h3>
                <p><?php echo $total_requests; ?></p>
                <a href="requests.php" class="btn">Управление</a>
            </div>
            <div class="stat-card">
                <h3>Пользователи</h3>
                <p><?php echo $total_users; ?></p>
                <a href="users.php" class="btn">Управление</a>
            </div>
        </div>
        <a href="reports.php" class="btn">Посмотреть отчеты</a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>