<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    $_SESSION['message'] = 'Войдите, чтобы просмотреть сравнение автомобилей.';
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$compare_cars = [];

$sql = "SELECT c.id, c.model, c.year, c.price, c.image, b.name AS brand_name, cat.name AS category_name, 
               cs.engine, cs.horsepower, cs.transmission, cs.fuel_type
        FROM comparisons comp
        JOIN cars c ON comp.car_id = c.id
        JOIN brands b ON c.brand_id = b.id
        JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN car_specifications cs ON c.id = cs.car_id
        WHERE comp.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $compare_cars[] = $row;
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="comparison">
            <h2>Сравнение автомобилей (<?php echo count($compare_cars); ?>)</h2>
            <?php if (empty($compare_cars)): ?>
                <p>Выберите автомобили для сравнения в каталоге.</p>
            <?php else: ?>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Характеристика</th>
                            <?php foreach ($compare_cars as $car): ?>
                                <th><?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Изображение</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><img src="<?php echo !empty($car['image']) && file_exists('Uploads/' . $car['image']) 
                                    ? 'Uploads/' . htmlspecialchars($car['image']) 
                                    : 'images/no-image.jpg'; ?>" 
                                    alt="<?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?>" width="150"></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Год</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo $car['year']; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Цена</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo format_price($car['price']); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Категория</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo htmlspecialchars($car['category_name']); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Двигатель</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo htmlspecialchars($car['engine'] ?? 'N/A'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Мощность</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo $car['horsepower'] ? $car['horsepower'] . ' л.с.' : 'N/A'; ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Трансмиссия</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo htmlspecialchars($car['transmission'] ?? 'N/A'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <td>Тип топлива</td>
                            <?php foreach ($compare_cars as $car): ?>
                                <td><?php echo htmlspecialchars($car['fuel_type'] ?? 'N/A'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>