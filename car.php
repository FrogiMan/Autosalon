<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$car_id) {
    header('Location: catalog.php');
    exit;
}

// Получение данных автомобиля
$sql = "SELECT c.id, c.model, c.year, c.price, c.description, c.image, c.is_available, 
               b.name AS brand_name, cat.name AS category_name,
               cs.engine, cs.horsepower, cs.transmission, cs.fuel_type
        FROM cars c 
        JOIN brands b ON c.brand_id = b.id 
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN car_specifications cs ON c.id = cs.car_id
        WHERE c.id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param('i', $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    header('Location: catalog.php');
    exit;
}

// Проверка изображения
$image_path = !empty($car['image']) && file_exists('Uploads/' . $car['image']) 
    ? 'Uploads/' . htmlspecialchars($car['image']) 
    : 'images/no-image.jpg';
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="car-details">
            <h2><?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?></h2>
            <div class="car-info">
                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?>">
                <div class="car-specs">
                    <p><strong>Категория:</strong> <?php echo htmlspecialchars($car['category_name']); ?></p>
                    <p><strong>Год:</strong> <?php echo $car['year']; ?></p>
                    <p><strong>Цена:</strong> <?php echo format_price($car['price']); ?></p>
                    <p><strong>Двигатель:</strong> <?php echo htmlspecialchars($car['engine'] ?? 'N/A'); ?></p>
                    <p><strong>Мощность:</strong> <?php echo $car['horsepower'] ? $car['horsepower'] . ' л.с.' : 'N/A'; ?></p>
                    <p><strong>Трансмиссия:</strong> <?php echo htmlspecialchars($car['transmission'] ?? 'N/A'); ?></p>
                    <p><strong>Тип топлива:</strong> <?php echo htmlspecialchars($car['fuel_type'] ?? 'N/A'); ?></p>
                    <p><strong>Описание:</strong> <?php echo htmlspecialchars($car['description'] ?? 'Нет описания'); ?></p>
                    <p><strong>Статус:</strong> <?php echo $car['is_available'] ? 'В наличии' : 'Недоступен'; ?></p>
                    <?php if (is_authenticated()): ?>
                        <?php if ($car['is_available']): ?>
                            <a href="submit_test_drive.php?car_id=<?php echo $car['id']; ?>" class="btn">Записаться на тест-драйв</a>
                        <?php else: ?>
                            <button class="btn" disabled>Тест-драйв недоступен</button>
                        <?php endif; ?>
                        <form action="add_to_compare.php" method="POST" style="display:inline;">
                            <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                            <button type="submit" class="btn">Добавить в сравнение</button>
                        </form>
                        <?php if ($car['is_available']): ?>
                            <a href="payment.php?car_id=<?php echo $car['id']; ?>" class="btn">Купить сейчас</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="login.php?message=<?php echo urlencode('Войдите, чтобы записаться на тест-драйв, добавить в сравнение или купить автомобиль.'); ?>">Войдите</a>, чтобы записаться на тест-драйв, добавить в сравнение или купить автомобиль.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>