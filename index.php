<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Получение популярных автомобилей (6 самых новых по году или created_at)
$sql = "SELECT c.id, c.model, c.year, c.price, c.image, b.name AS brand_name 
        FROM cars c JOIN brands b ON c.brand_id = b.id 
        WHERE c.year >= ? 
        ORDER BY c.year DESC, c.created_at DESC LIMIT 6";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Ошибка подготовки запроса: " . $conn->error);
}

$current_year = date('Y') - 2; // Показываем машины за последние 2 года
$stmt->bind_param('i', $current_year);
if (!$stmt->execute()) {
    die("Ошибка выполнения запроса: " . $stmt->error);
}
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="banner">
            <h1>Добро пожаловать в наш автосалон!</h1>
            <p>Найдите автомобиль своей мечты по лучшим ценам.</p>
            <a href="catalog.php" class="btn">Посмотреть каталог</a>
        </section>

        <section class="popular-cars">
            <h2>Популярные автомобили</h2>
            <?php if ($result->num_rows === 0): ?>
                <p>Нет доступных автомобилей.</p>
            <?php else: ?>
                <div class="cars-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="car-card">
                            <img src="<?php echo !empty($row['image']) && file_exists('Uploads/' . $row['image']) 
                                ? 'Uploads/' . htmlspecialchars($row['image']) 
                                : 'images/no-image.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?>">
                            <h3><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></h3>
                            <p>Год: <?php echo $row['year']; ?></p>
                            <p>Цена: <?php echo format_price($row['price']); ?></p>
                            <a href="car.php?id=<?php echo $row['id']; ?>" class="btn">Подробнее</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="test-drive-form">
            <h2>Запишитесь на тест-драйв</h2>
            <?php if (is_authenticated()): ?>
                <form action="submit_test_drive.php" method="POST">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" required>
                    <label for="phone">Телефон:</label>
                    <input type="tel" id="phone" name="phone" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email">
                    <label for="car_id">Автомобиль:</label>
                    <select id="car_id" name="car_id" required>
                        <option value="">Выберите автомобиль</option>
                        <?php
                        $cars_query = "SELECT c.id, b.name AS brand_name, c.model 
                                       FROM cars c JOIN brands b ON c.brand_id = b.id";
                        $cars_result = $conn->query($cars_query);
                        if ($cars_result === false) {
                            echo "<option value=''>Ошибка загрузки автомобилей: " . $conn->error . "</option>";
                        } else {
                            while ($car = $cars_result->fetch_assoc()): ?>
                                <option value="<?php echo $car['id']; ?>">
                                    <?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?>
                                </option>
                            <?php endwhile;
                        }
                        ?>
                    </select>
                    <label for="request_date">Дата:</label>
                    <input type="datetime-local" id="request_date" name="request_date" required>
                    <button type="submit" class="btn">Отправить заявку</button>
                </form>
            <?php else: ?>
                <p><a href="login.php?message=<?php echo urlencode('Войдите, чтобы записаться на тест-драйв.'); ?>">Войдите</a>, чтобы записаться на тест-драйв.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $stmt->close(); $conn->close(); ?>