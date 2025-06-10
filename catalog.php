<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Фильтры
$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 9999999;

// Формируем запрос
$sql = "SELECT c.id, c.model, c.year, c.price, c.image, b.name AS brand_name, cat.name AS category_name 
        FROM cars c 
        JOIN brands b ON c.brand_id = b.id 
        JOIN categories cat ON c.category_id = cat.id 
        WHERE c.price BETWEEN ? AND ?";
$params = [$min_price, $max_price];
$types = 'dd';
if ($brand_id) {
    $sql .= " AND c.brand_id = ?";
    $params[] = $brand_id;
    $types .= 'i';
}
if ($category_id) {
    $sql .= " AND c.category_id = ?";
    $params[] = $category_id;
    $types .= 'i';
}
$sql .= " ORDER BY c.year DESC, c.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$brands = get_brands($conn);
$categories = get_categories($conn);
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="filters">
            <h2>Фильтры</h2>
            <form method="GET" action="catalog.php">
                <label for="brand_id">Бренд:</label>
                <select id="brand_id" name="brand_id">
                    <option value="">Все бренды</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>" <?php echo $brand_id == $brand['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="category_id">Категория:</label>
                <select id="category_id" name="category_id">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="min_price">Мин. цена:</label>
                <input type="number" id="min_price" name="min_price" value="<?php echo $min_price ?: ''; ?>">
                <label for="max_price">Макс. цена:</label>
                <input type="number" id="max_price" name="max_price" value="<?php echo $max_price ?: ''; ?>">
                <button type="submit" class="btn">Применить</button>
            </form>
        </section>

        <section class="catalog">
            <h2>Каталог автомобилей</h2>
            <?php if ($result->num_rows === 0): ?>
                <p>Нет автомобилей, соответствующих фильтрам.</p>
            <?php else: ?>
                <div class="cars-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="car-card">
                            <img src="<?php echo !empty($row['image']) && file_exists('Uploads/' . $row['image']) 
                                ? 'Uploads/' . htmlspecialchars($row['image']) 
                                : 'images/no-image.jpg'; ?>" 
                                alt="<?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?>">
                            <h3><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></h3>
                            <p>Категория: <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p>Год: <?php echo $row['year']; ?></p>
                            <p>Цена: <?php echo format_price($row['price']); ?></p>
                            <a href="car.php?id=<?php echo $row['id']; ?>" class="btn">Подробнее</a>
                            <?php if (is_authenticated()): ?>
                                <form action="add_to_compare.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="car_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn">Добавить в сравнение</button>
                                </form>
                            <?php else: ?>
                                <p><a href="login.php?message=<?php echo urlencode('Войдите, чтобы добавить в сравнение.'); ?>">Войдите</a>, чтобы добавить в сравнение.</p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $stmt->close(); $conn->close(); ?>