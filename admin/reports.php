<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_authenticated() || !is_admin_or_manager($_SESSION['user_role'])) {
    $_SESSION['message'] = 'Войдите с правами администратора или менеджера.';
    header('Location: /login.php');
    exit;
}

$error = '';
$items_per_page = 10;

// Requests by status (no pagination, typically small)
$requests_by_status = $conn->query("SELECT status, COUNT(*) AS count FROM test_drive_requests GROUP BY status");
if ($requests_by_status === false) {
    $error = 'Ошибка получения данных: ' . $conn->error;
}

// Generate pagination for top cars
$pagination = generate_pagination($conn, 'test_drive_requests', 'reports.php', $items_per_page);
$offset = $pagination['offset'];

// Fetch top cars with pagination
$sql = "SELECT c.model, b.name AS brand_name, COUNT(r.id) AS count 
        FROM test_drive_requests r 
        JOIN cars c ON r.car_id = c.id 
        JOIN brands b ON c.brand_id = b.id 
        GROUP BY c.id 
        ORDER BY count DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = 'Ошибка подготовки запроса: ' . $conn->error;
} else {
    $stmt->bind_param('ii', $items_per_page, $offset);
    $stmt->execute();
    $requests_by_car = $stmt->get_result();
    $stmt->close();
}
?>

<?php include '../includes/header.php'; ?>

<main>
    <div class="container">
        <h2>Отчеты</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <h3>Статистика заявок по статусу</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Статус</th>
                    <th>Количество</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests_by_status->num_rows > 0): ?>
                    <?php while ($row = $requests_by_status->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">Данные не найдены.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h3>Топ автомобилей по заявкам</h3>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Автомобиль</th>
                    <th>Количество заявок</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests_by_car->num_rows > 0): ?>
                    <?php while ($row = $requests_by_car->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></td>
                            <td><?php echo $row['count']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">Автомобили не найдены.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo $pagination['html']; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>