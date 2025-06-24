<?php
session_start();
require_once 'includes/db.php';

if (empty($_SESSION['compare'])) {
    header('Location: cars.php');
    exit;
}

$carIds = $_SESSION['compare'];
$placeholders = implode(',', array_fill(0, count($carIds), '?'));
$types = str_repeat('i', count($carIds));

// Получаем данные об автомобилях
$query = "SELECT c.*, cf.engine_type, cf.engine_volume, cf.power, cf.transmission, 
                 cf.drive_type, cf.color, cf.fuel_consumption
          FROM cars c
          LEFT JOIN car_features cf ON c.car_id = cf.car_id
          WHERE c.car_id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$carIds);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);

// Теперь включаем header.php
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сравнение автомобилей - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="container">
        <h1>Сравнение автомобилей</h1>
        
        <?php if (!empty($cars)): ?>
            <div class="compare-container">
                <table class="compare-table">
                    <tr>
                        <th>Характеристика</th>
                        <?php foreach ($cars as $car): ?>
                            <th>
                                <?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>
                                <button class="remove-compare" data-car-id="<?= $car['car_id'] ?>">
                                    ×
                                </button>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Остальной HTML остается без изменений -->
                    <tr>
                        <td>Изображение</td>
                        <?php foreach ($cars as $car): ?>
                            <td>
                                <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" 
                                     alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>"
                                     class="compare-image">
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Год выпуска</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['year']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Цена</td>
                        <?php foreach ($cars as $car): ?>
                            <td>$<?= number_format($car['price'], 2) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Двигатель</td>
                        <?php foreach ($cars as $car): ?>
                            <td>
                                <?= htmlspecialchars($car['engine_type']) ?>, 
                                <?= htmlspecialchars($car['engine_volume']) ?> л
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Мощность</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['power']) ?> л.с.</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Коробка передач</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['transmission']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Привод</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['drive_type']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Цвет</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['color']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Расход топлива</td>
                        <?php foreach ($cars as $car): ?>
                            <td><?= htmlspecialchars($car['fuel_consumption']) ?> л/100км</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Действия</td>
                        <?php foreach ($cars as $car): ?>
                            <td>
                                <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn">
                                    Подробнее
                                </a>
                                <?php if (isset($_SESSION['client_id'])): ?>
                                    <button class="btn favorite-btn" data-car-id="<?= $car['car_id'] ?>">
                                        В избранное
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
                
                <div class="compare-actions">
                    <button id="clearCompare" class="btn">Очистить сравнение</button>
                    <a href="cars.php" class="btn">Вернуться в каталог</a>
                </div>
            </div>
        <?php else: ?>
            <p>Нет автомобилей для сравнения.</p>
            <a href="cars.php" class="btn">Вернуться в каталог</a>
        <?php endif; ?>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        // Удаление автомобиля из сравнения
        document.querySelectorAll('.remove-compare').forEach(btn => {
            btn.addEventListener('click', function() {
                const carId = this.getAttribute('data-car-id');
                
                fetch('api/remove_from_compare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'car_id=' + carId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    }
                });
            });
        });
        
        // Очистка всего сравнения
        document.getElementById('clearCompare').addEventListener('click', function() {
            fetch('api/clear_compare.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        });
        
        // Добавление в избранное
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const carId = this.getAttribute('data-car-id');
                
                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'car_id=' + carId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.action === 'added') {
                            this.textContent = 'В избранном';
                            this.classList.add('active');
                        } else {
                            this.textContent = 'В избранное';
                            this.classList.remove('active');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>