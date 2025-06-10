<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    $_SESSION['message'] = 'Войдите, чтобы купить автомобиль.';
    header('Location: login.php');
    exit;
}

$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
if (!$car_id) {
    $_SESSION['message'] = 'Автомобиль не выбран.';
    header('Location: catalog.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Проверка ограничений
if (!is_user_of_age($conn, $user_id)) {
    $_SESSION['message'] = 'Для покупки автомобиля вам должно быть 18 лет или больше. Обновите дату рождения в профиле.';
    header('Location: profile.php');
    exit;
}

if (!can_purchase_car($conn, $user_id)) {
    $_SESSION['message'] = 'Вы не можете купить автомобиль, так как уже приобрели один в последние 6 месяцев.';
    header('Location: catalog.php');
    exit;
}

if (!is_car_available($conn, $car_id)) {
    $_SESSION['message'] = 'Этот автомобиль недоступен для покупки.';
    header('Location: catalog.php');
    exit;
}

// Получение данных автомобиля
$sql = "SELECT c.id, c.model, c.price, b.name AS brand_name FROM cars c JOIN brands b ON c.brand_id = b.id WHERE c.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    $_SESSION['message'] = 'Автомобиль не найден.';
    header('Location: catalog.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_number = sanitize_input($_POST['card_number']);
    $expiry = sanitize_input($_POST['expiry']);
    $cvv = sanitize_input($_POST['cvv']);

    if ($card_number && $expiry && $cvv) {
        // Mock payment processing
        $sql = "INSERT INTO purchases (user_id, car_id, amount, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iid', $user_id, $car_id, $car['price']);
        if ($stmt->execute()) {
            // Mark car as unavailable
            $sql = "UPDATE cars SET is_available = FALSE WHERE id = ?";
            $stmt_update = $conn->prepare($sql);
            $stmt_update->bind_param('i', $car_id);
            $stmt_update->execute();
            $stmt_update->close();

            $_SESSION['message'] = 'Заявка на покупку отправлена. Ожидайте подтверждения.';
            header('Location: purchase_confirmation.php');
            exit;
        } else {
            $error = 'Ошибка при обработке платежа.';
        }
        $stmt->close();
    } else {
        $error = 'Заполните все поля корректно.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="payment">
            <h2>Оплата автомобиля</h2>
            <p>Вы покупаете: <?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?></p>
            <p>Цена: <?php echo format_price($car['price']); ?></p>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <form id="payment-form" method="POST" action="payment.php?car_id=<?php echo $car_id; ?>">
                <label for="card_number">Номер карты:</label>
                <input type="text" id="card_number" name="card_number" required>
                <label for="expiry">Срок действия (MM/YY):</label>
                <input type="text" id="expiry" name="expiry" required>
                <label for="cvv">CVV:</label>
                <input type="text" id="cvv" name="cvv" required>
                <button type="submit" class="btn">Оплатить</button>
            </form>
            <a href="car.php?id=<?php echo $car_id; ?>" class="btn">Отмена</a>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>