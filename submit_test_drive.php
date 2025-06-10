<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    $_SESSION['message'] = 'Войдите, чтобы записаться на тест-драйв.';
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';
$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : (isset($_POST['car_id']) ? (int)$_POST['car_id'] : 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch user data for pre-filling
$sql_user = "SELECT username, email FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if ($stmt_user === false) {
    $error = 'Ошибка подготовки запроса для пользователя: ' . $conn->error;
} else {
    $stmt_user->bind_param('i', $user_id);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    if (!$user) {
        $error = 'Пользователь не найден.';
    }
}

// Validate car_id and availability
if ($car_id && !$error) {
    $sql_car = "SELECT c.id, c.model, c.is_available, b.name AS brand_name 
                FROM cars c JOIN brands b ON c.brand_id = b.id 
                WHERE c.id = ?";
    $stmt_car = $conn->prepare($sql_car);
    if ($stmt_car === false) {
        $error = 'Ошибка подготовки запроса для автомобиля: ' . $conn->error;
    } else {
        $stmt_car->bind_param('i', $car_id);
        $stmt_car->execute();
        $car = $stmt_car->get_result()->fetch_assoc();
        $stmt_car->close();

        if (!$car) {
            $error = 'Автомобиль не найден.';
            $car_id = 0;
        } elseif (!$car['is_available']) {
            $error = 'Этот автомобиль недоступен для тест-драйва.';
            $car_id = 0;
        }
    }
} else if (!$error) {
    $error = 'Выберите автомобиль для тест-драйва.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $car_id && !$error) {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $request_date = sanitize_input($_POST['request_date']);

    // Validate inputs
    if ($name && validate_phone($phone) && (!$email || validate_email($email)) && $request_date) {
        // Check if request_date is in the future
        $request_datetime = new DateTime($request_date);
        $now = new DateTime();
        if ($request_datetime <= $now) {
            $error = 'Дата тест-драйва должна быть в будущем.';
        } else {
            // Check for existing test drive requests
            $sql_check = "SELECT COUNT(*) FROM test_drive_requests 
                          WHERE user_id = ? AND car_id = ? AND status IN ('pending', 'approved')";
            $stmt_check = $conn->prepare($sql_check);
            if ($stmt_check === false) {
                $error = 'Ошибка проверки дублирующих заявок: ' . $conn->error;
            } else {
                $stmt_check->bind_param('ii', $user_id, $car_id);
                $stmt_check->execute();
                $count = $stmt_check->get_result()->fetch_row()[0];
                $stmt_check->close();

                if ($count > 0) {
                    $error = 'У вас уже есть активная заявка на тест-драйв этого автомобиля.';
                } else {
                    $sql = "INSERT INTO test_drive_requests (car_id, user_id, name, phone, email, request_date, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $error = 'Ошибка подготовки запроса для вставки: ' . $conn->error;
                    } else {
                        $stmt->bind_param('iissss', $car_id, $user_id, $name, $phone, $email, $request_date);
                        if ($stmt->execute()) {
                            $success = 'Заявка на тест-драйв успешно отправлена.';
                            $_SESSION['message'] = $success;
                            header('Location: profile.php');
                            exit;
                        } else {
                            $error = 'Ошибка при отправке заявки: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    } else {
        $error = 'Заполните все обязательные поля корректно.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="test-drive-form">
            <h2>Запись на тест-драйв</h2>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if ($car_id && !$error && isset($user)): ?>
                <p>Автомобиль: <?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?></p>
                <form id="test-drive-form" method="POST" action="submit_test_drive.php">
                    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    <label for="phone">Телефон:</label>
                    <input type="tel" id="phone" name="phone" required>
                    <label for="email">Email (опционально):</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <label for="request_date">Дата и время:</label>
                    <input type="datetime-local" id="request_date" name="request_date" required>
                    <button type="submit" class="btn">Отправить заявку</button>
                </form>
            <?php endif; ?>
            <a href="catalog.php" class="btn">Вернуться в каталог</a>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>