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
$success = '';
$user = null;
$items_per_page = 10;

// Load user data for editing
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $sql = "SELECT id, username, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error = 'Ошибка подготовки запроса: ' . $conn->error;
    } else {
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
    if ($id && $username && validate_email($email) && in_array($role, ['admin', 'manager', 'client'])) {
        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = 'Ошибка подготовки запроса: ' . $conn->error;
            } else {
                $stmt->bind_param('ssssi', $username, $email, $hashed_password, $role, $id);
            }
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = 'Ошибка подготовки запроса: ' . $conn->error;
            } else {
                $stmt->bind_param('sssi', $username, $email, $role, $id);
            }
        }
        if (!$error && $stmt->execute()) {
            $success = 'Пользователь обновлен.';
        } else {
            $error = 'Ошибка при обновлении пользователя.';
        }
        $stmt->close();
    } elseif (!$id && $username && validate_email($email) && $password && in_array($role, ['admin', 'manager', 'client'])) {
        if (is_user_exists($conn, $username, $email)) {
            $error = 'Пользователь с таким именем или email уже существует.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $error = 'Ошибка подготовки запроса: ' . $conn->error;
            } else {
                $stmt->bind_param('ssss', $username, $email, $hashed_password, $role);
                if ($stmt->execute()) {
                    $success = 'Пользователь добавлен.';
                } else {
                    $error = 'Ошибка при добавлении пользователя.';
                }
                $stmt->close();
            }
        }
    } else {
        $error = 'Заполните все поля корректно.';
    }
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id !== $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $error = 'Ошибка подготовки запроса: ' . $conn->error;
        } else {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Пользователь удален.';
            } else {
                $error = 'Ошибка при удалении пользователя.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Нельзя удалить текущего пользователя.';
    }
}

// Generate pagination
$pagination = generate_pagination($conn, 'users', 'users.php', $items_per_page);
$offset = $pagination['offset'];

// Fetch users with pagination
$sql = "SELECT id, username, email, role, created_at FROM users 
        ORDER BY created_at DESC 
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
        <h2>Управление пользователями</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        
        <h3><?php echo $user ? 'Редактировать пользователя' : 'Добавить пользователя'; ?></h3>
        <form method="POST" action="users.php">
            <input type="hidden" name="id" value="<?php echo $user['id'] ?? 0; ?>">
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            <label for="password">Пароль (оставьте пустым для редактирования без изменения):</label>
            <input type="password" id="password" name="password">
            <label for="role">Роль:</label>
            <select id="role" name="role" required>
                <option value="admin" <?php echo ($user && $user['role'] === 'admin') ? 'selected' : ''; ?>>Администратор</option>
                <option value="manager" <?php echo ($user && $user['role'] === 'manager') ? 'selected' : ''; ?>>Менеджер</option>
                <option value="client" <?php echo ($user && $user['role'] === 'client') ? 'selected' : ''; ?>>Клиент</option>
            </select>
            <button type="submit" class="btn">Сохранить</button>
        </form>
        
        <h3>Список пользователей</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['role']); ?></td>
                            <td><?php echo format_datetime($row['created_at']); ?></td>
                            <td>
                                <a href="users.php?edit=<?php echo $row['id']; ?>" class="btn">Редактировать</a>
                                <a href="users.php?action=delete&id=<?php echo $row['id']; ?>" class="btn" onclick="return confirm('Вы уверены?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">Пользователи не найдены.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo $pagination['html']; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>