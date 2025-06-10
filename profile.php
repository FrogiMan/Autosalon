<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_birth_date'])) {
    $birth_date = sanitize_input($_POST['birth_date']);
    if ($birth_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $sql = "UPDATE users SET birth_date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = 'Ошибка сервера: ' . $conn->error;
        } else {
            $stmt->bind_param('si', $birth_date, $user_id);
            if ($stmt->execute()) {
                $success = 'Дата рождения обновлена.';
            } else {
                $error = 'Ошибка при обновлении даты рождения.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Укажите корректную дату рождения.';
    }
}

$sql = "SELECT username, email, role, birth_date FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = 'Ошибка сервера: ' . $conn->error;
} else {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        $error = 'Пользователь не найден.';
    }
}

// Получение заявок на тест-драйв
$sql_requests = "SELECT r.id, r.request_date, r.status, c.model, b.name AS brand_name 
                 FROM test_drive_requests r 
                 JOIN cars c ON r.car_id = c.id 
                 JOIN brands b ON c.brand_id = b.id 
                 WHERE r.user_id = ?";
$stmt_requests = $conn->prepare($sql_requests);
if ($stmt_requests === false) {
    $error = 'Ошибка сервера: ' . $conn->error;
} else {
    $stmt_requests->bind_param('i', $user_id);
    $stmt_requests->execute();
    $requests = $stmt_requests->get_result();
}

// Получение истории покупок
$sql_purchases = "SELECT p.id, p.amount, p.status, p.created_at, c.model, b.name AS brand_name 
                  FROM purchases p 
                  JOIN cars c ON p.car_id = c.id 
                  JOIN brands b ON c.brand_id = b.id 
                  WHERE p.user_id = ?";
$stmt_purchases = $conn->prepare($sql_purchases);
if ($stmt_purchases === false) {
    $error = 'Ошибка сервера: ' . $conn->error;
} else {
    $stmt_purchases->bind_param('i', $user_id);
    $stmt_purchases->execute();
    $purchases = $stmt_purchases->get_result();
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="profile">
            <h2>Личный кабинет</h2>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if (isset($user)): ?>
                <p><strong>Имя пользователя:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Роль:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                <p><strong>Дата рождения:</strong> <?php echo $user['birth_date'] ? htmlspecialchars($user['birth_date']) : 'Не указана'; ?></p>
                <?php if (is_admin_or_manager($_SESSION['user_role'])): ?>
                    <p><a href="admin/index.php" class="btn">Перейти в админ-панель</a></p>
                <?php endif; ?>
                <h3>Обновить дату рождения</h3>
                <form method="POST" action="profile.php">
                    <label for="birth_date">Дата рождения:</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>" required>
                    <button type="submit" name="update_birth_date" class="btn">Обновить</button>
                </form>
                <h3>Ваши заявки на тест-драйв</h3>
                <table class="profile-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Автомобиль</th>
                            <th>Дата</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></td>
                                <td><?php echo $row['request_date']; ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <h3>История покупок</h3>
                <table class="profile-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Автомобиль</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                            <th>Дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $purchases->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></td>
                                <td><?php echo format_price($row['amount']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td>
                                <td><?php echo format_datetime($row['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $stmt_requests->close(); $stmt_purchases->close(); $conn->close(); ?>