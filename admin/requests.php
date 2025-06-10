<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_authenticated() || !is_admin_or_manager($_SESSION['user_role'])) {
    $_SESSION['message'] = 'Войдите с правами администратора или менеджера.';
    header('Location: /login.php');
    exit;
}

$success = '';
$error = '';
$items_per_page = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = sanitize_input($_POST['status']);
    if (in_array($status, ['pending', 'approved', 'rejected'])) {
        $sql = "UPDATE test_drive_requests SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = 'Ошибка подготовки запроса: ' . $conn->error;
        } else {
            $stmt->bind_param('si', $status, $request_id);
            if ($stmt->execute()) {
                $success = 'Статус заявки обновлен.';
            } else {
                $error = 'Ошибка при обновлении статуса.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Недопустимый статус.';
    }
}

// Generate pagination
$pagination = generate_pagination($conn, 'test_drive_requests', 'requests.php', $items_per_page);
$offset = $pagination['offset'];

// Fetch requests with pagination
$sql = "SELECT r.id, r.name, r.phone, r.email, r.request_date, r.status, c.model, b.name AS brand_name 
        FROM test_drive_requests r 
        JOIN cars c ON r.car_id = c.id 
        JOIN brands b ON c.brand_id = b.id 
        ORDER BY r.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = 'Ошибка подготовки запроса: ' . $conn->error;
} else {
    $stmt->bind_param('ii', $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<?php include '../includes/header.php'; ?>

<main>
    <div class="container">
        <h2>Управление заявками на тест-драйв</h2>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Автомобиль</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></td>
                            <td><?php echo format_datetime($row['request_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <form method="POST" action="requests.php" style="display:inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                    <select name="status">
                                        <option value="pending" <?php echo $row['status'] === 'pending' ? 'selected' : ''; ?>>Ожидается</option>
                                        <option value="approved" <?php echo $row['status'] === 'approved' ? 'selected' : ''; ?>>Одобрено</option>
                                        <option value="rejected" <?php echo $row['status'] === 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
                                    </select>
                                    <button type="submit" class="btn">Обновить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">Заявки не найдены.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo $pagination['html']; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>