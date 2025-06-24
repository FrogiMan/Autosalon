<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Получаем параметры фильтрации
$make = isset($_GET['make']) ? $_GET['make'] : null;
$minPrice = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? intval($_GET['max_price']) : 1000000;
$yearFrom = isset($_GET['year_from']) ? intval($_GET['year_from']) : 0;
$yearTo = isset($_GET['year_to']) ? intval($_GET['year_to']) : date('Y');
$bodyType = isset($_GET['body_type']) ? $_GET['body_type'] : null;

// Базовый запрос
$query = "SELECT * FROM cars WHERE status = 'available' AND price BETWEEN ? AND ? AND year BETWEEN ? AND ?";
$params = [$minPrice, $maxPrice, $yearFrom, $yearTo];
$types = "iiii";

// Добавляем фильтр по марке
if ($make) {
    $query .= " AND make = ?";
    $params[] = $make;
    $types .= "s";
}

// Добавляем фильтр по типу кузова
if ($bodyType) {
    $query .= " AND body_type = ?";
    $params[] = $bodyType;
    $types .= "s";
}

// Добавляем сортировку
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'price_asc';
switch ($sort) {
    case 'price_desc': $query .= " ORDER BY price DESC"; break;
    case 'year_desc': $query .= " ORDER BY year DESC"; break;
    case 'mileage_asc': $query .= " ORDER BY mileage ASC"; break;
    default: $query .= " ORDER BY price ASC";
}

// Подготавливаем и выполняем запрос
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$cars = $result->fetch_all(MYSQLI_ASSOC);

// Получаем список марок для фильтра
$brandsQuery = "SELECT DISTINCT make FROM cars ORDER BY make";
$brandsResult = $conn->query($brandsQuery);
$brands = $brandsResult->fetch_all(MYSQLI_ASSOC);

// Получаем типы кузова
$bodyTypesQuery = "SELECT DISTINCT body_type FROM cars WHERE body_type IS NOT NULL ORDER BY body_type";
$bodyTypesResult = $conn->query($bodyTypesQuery);
$bodyTypes = $bodyTypesResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог автомобилей - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <h1>Каталог автомобилей</h1>
                
                <div class="catalog-tools">
                    <div class="sorting">
                        <label>Сортировка:</label>
                        <select id="sort-cars" onchange="applyFilters()">
                            <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>По цене (дешевые сначала)</option>
                            <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>По цене (дорогие сначала)</option>
                            <option value="year_desc" <?= $sort == 'year_desc' ? 'selected' : '' ?>>По году (новые сначала)</option>
                            <option value="mileage_asc" <?= $sort == 'mileage_asc' ? 'selected' : '' ?>>По пробегу (меньше сначала)</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-outline" onclick="toggleFilters()">Фильтры</button>
                </div>
                
                <div class="catalog-container">
                    <aside class="filters-sidebar" id="filters-sidebar">
                        <h3>Фильтры</h3>
                        <form id="car-filters" onsubmit="applyFilters(); return false;">
                            <div class="filter-group">
                                <h4>Марка</h4>
                                <select name="make" id="filter-make">
                                    <option value="">Все марки</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?= htmlspecialchars($brand['make']) ?>" <?= $make == $brand['make'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($brand['make']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <h4>Цена ($)</h4>
                                <div class="range-inputs">
                                    <input type="number" name="min_price" placeholder="От" value="<?= $minPrice ?>">
                                    <span>-</span>
                                    <input type="number" name="max_price" placeholder="До" value="<?= $maxPrice ?>">
                                </div>
                            </div>
                            
                            <div class="filter-group">
                                <h4>Год выпуска</h4>
                                <div class="range-inputs">
                                    <input type="number" name="year_from" placeholder="От" value="<?= $yearFrom ?>">
                                    <span>-</span>
                                    <input type="number" name="year_to" placeholder="До" value="<?= $yearTo ?>">
                                </div>
                            </div>
                            
                            <?php if (!empty($bodyTypes)): ?>
                            <div class="filter-group">
                                <h4>Тип кузова</h4>
                                <select name="body_type" id="filter-body-type">
                                    <option value="">Все типы</option>
                                    <?php foreach ($bodyTypes as $type): ?>
                                        <option value="<?= htmlspecialchars($type['body_type']) ?>" <?= $bodyType == $type['body_type'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['body_type']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn">Применить фильтры</button>
                        </form>
                    </aside>
                    
                    <div class="cars-container">
                        <?php if (!empty($cars)): ?>
                            <div class="cars-grid">
                                <?php foreach ($cars as $car): ?>
                                    <div class="car-card">
                                        <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'].' '.$car['model']) ?>">
                                        <div class="car-info">
                                            <h3><?= htmlspecialchars($car['make'].' '.$car['model']) ?></h3>
                                            <p>Год: <?= htmlspecialchars($car['year']) ?></p>
                                            <?php if ($car['mileage']): ?>
                                                <p>Пробег: <?= number_format($car['mileage']) ?> км</p>
                                            <?php endif; ?>
                                            <p class="price">$<?= number_format($car['price'], 2) ?></p>
                                            <div class="car-actions">
                                                <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn">Подробнее</a>
                                                <?php if (isset($_SESSION['client_id'])): ?>
                                                    <button class="btn btn-outline favorite-btn" data-car-id="<?= $car['car_id'] ?>">
                                                        В избранное
                                                    </button>
                                                    <button class="btn btn-outline compare-btn" data-car-id="<?= $car['car_id'] ?>" onclick="addToCompare(<?= $car['car_id'] ?>)">
    Сравнить
</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-results">По вашему запросу ничего не найдено. Попробуйте изменить параметры фильтрации.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

 <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
    <script>
        function toggleFilters() {
            const sidebar = document.getElementById('filters-sidebar');
            sidebar.classList.toggle('active');
        }
        
        function applyFilters() {
            const form = document.getElementById('car-filters');
            const sort = document.getElementById('sort-cars').value;
            const params = new URLSearchParams(new FormData(form));
            params.set('sort', sort);
            window.location.href = 'cars.php?' + params.toString();
        }

        function addToCompare(carId) {
    fetch('add_to_compare.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'car_id=' + carId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Автомобиль добавлен к сравнению!');
        } else {
            alert('Ошибка: ' + data.message);
        }
    });
}
        
        // Обработка избранного
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