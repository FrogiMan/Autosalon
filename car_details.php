<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_GET['car_id'])) {
    header('Location: cars.php');
    exit;
}

$carId = intval($_GET['car_id']);

// Получаем данные об автомобиле
$carQuery = "SELECT c.*, 
                    cf.engine_type, cf.engine_volume, cf.power, cf.transmission, 
                    cf.drive_type, cf.color, cf.interior, cf.equipment_level, cf.fuel_consumption
             FROM cars c
             LEFT JOIN car_features cf ON c.car_id = cf.car_id
             WHERE c.car_id = ?";
$carStmt = $conn->prepare($carQuery);
$carStmt->bind_param("i", $carId);
$carStmt->execute();
$carResult = $carStmt->get_result();
$car = $carResult->fetch_assoc();

if (!$car) {
    header('Location: cars.php');
    exit;
}

// Получаем дополнительные изображения
$imagesQuery = "SELECT image_path FROM car_images WHERE car_id = ?";
$imagesStmt = $conn->prepare($imagesQuery);
$imagesStmt->bind_param("i", $carId);
$imagesStmt->execute();
$imagesResult = $imagesStmt->get_result();
$images = $imagesResult->fetch_all(MYSQLI_ASSOC);

// Если нет дополнительных изображений, используем основное
if (empty($images)) {
    $images = [['image_path' => $car['image']]];
}

// Получаем похожие автомобили
$similarQuery = "SELECT * FROM cars 
                WHERE car_id != ? AND make = ? AND status = 'available'
                ORDER BY RAND() LIMIT 3";
$similarStmt = $conn->prepare($similarQuery);
$similarStmt->bind_param("is", $carId, $car['make']);
$similarStmt->execute();
$similarResult = $similarStmt->get_result();
$similarCars = $similarResult->fetch_all(MYSQLI_ASSOC);

// Получаем отзывы
$reviewsQuery = "SELECT r.*, c.first_name, c.last_name 
                FROM reviews r
                JOIN clients c ON r.client_id = c.client_id
                WHERE r.car_id = ?
                ORDER BY r.created_at DESC";
$reviewsStmt = $conn->prepare($reviewsQuery);
$reviewsStmt->bind_param("i", $carId);
$reviewsStmt->execute();
$reviewsResult = $reviewsStmt->get_result();
$reviews = $reviewsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($car['make'].' '.$car['model']) ?> - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <div class="breadcrumbs">
                    <a href="cars.php">Каталог</a>
                    <span> / </span>
                    <a href="cars.php?make=<?= urlencode($car['make']) ?>"><?= htmlspecialchars($car['make']) ?></a>
                    <span> / </span>
                    <span><?= htmlspecialchars($car['model']) ?></span>
                </div>
                
                <div class="car-details">
                    <div class="car-header">
                        <h1><?= htmlspecialchars($car['make'].' '.$car['model']) ?></h1>
                        <p class="year-price"><?= htmlspecialchars($car['year']) ?> год • $<?= number_format($car['price'], 2) ?></p>
                    </div>
                    
                    <div class="car-gallery">
                        <div class="main-image">
                            <img src="images/cars/<?= htmlspecialchars($images[0]['image_path']) ?>" alt="<?= htmlspecialchars($car['make'].' '.$car['model']) ?>" id="mainCarImage">
                        </div>
                        <div class="thumbnails">
                            <?php foreach ($images as $image): ?>
                                <img src="images/cars/<?= htmlspecialchars($image['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($car['make'].' '.$car['model']) ?>" 
                                     onclick="document.getElementById('mainCarImage').src = this.src">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="car-actions">
                        <button class="btn test-drive-btn" onclick="openTestDriveModal()">Записаться на тест-драйв</button>
                        <button class="btn credit-btn" onclick="openCreditModal()">Рассчитать кредит</button>
                        <button class="btn btn-outline compare-btn" onclick="addToCompare(<?= $carId ?>)">Сравнить</button>
                        <?php if (isset($_SESSION['client_id'])): ?>
                            <button class="btn btn-outline favorite-btn" id="favorite-btn" data-car-id="<?= $carId ?>">
                                В избранное
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="car-specs">
                        <h2>Технические характеристики</h2>
                        <div class="specs-grid">
                            <div class="spec-item"><span>Марка:</span><span><?= htmlspecialchars($car['make']) ?></span></div>
                            <div class="spec-item"><span>Модель:</span><span><?= htmlspecialchars($car['model']) ?></span></div>
                            <div class="spec-item"><span>Год выпуска:</span><span><?= htmlspecialchars($car['year']) ?></span></div>
                            <div class="spec-item"><span>Цена:</span><span>$<?= number_format($car['price'], 2) ?></span></div>
                            <?php if ($car['mileage']): ?>
                                <div class="spec-item"><span>Пробег:</span><span><?= number_format($car['mileage']) ?> км</span></div>
                            <?php endif; ?>
                            <?php if ($car['engine_type']): ?>
                                <div class="spec-item"><span>Двигатель:</span><span><?= htmlspecialchars($car['engine_type'].' '.$car['engine_volume']) ?> л</span></div>
                                <div class="spec-item"><span>Мощность:</span><span><?= htmlspecialchars($car['power']) ?> л.с.</span></div>
                                <div class="spec-item"><span>Коробка передач:</span><span><?= htmlspecialchars($car['transmission']) ?></span></div>
                                <div class="spec-item"><span>Привод:</span><span><?= htmlspecialchars($car['drive_type']) ?></span></div>
                                <div class="spec-item"><span>Цвет:</span><span><?= htmlspecialchars($car['color']) ?></span></div>
                                <div class="spec-item"><span>Расход топлива:</span><span><?= htmlspecialchars($car['fuel_consumption']) ?> л/100км</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($car['description']): ?>
                    <div class="car-description">
                        <h2>Описание</h2>
                        <p><?= htmlspecialchars($car['description']) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="car-reviews">
                        <h2>Отзывы владельцев</h2>
                        <?php if (!empty($reviews)): ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <div class="review-author">
                                                <?= htmlspecialchars($review['first_name'].' '.$review['last_name']) ?>
                                            </div>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?= $i <= $review['rating'] ? 'filled' : '' ?>">★</span>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="review-date">
                                                <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="review-text">
                                            <?= htmlspecialchars($review['comment']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>Пока нет отзывов об этом автомобиле.</p>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['client_id'])): ?>
                            <button class="btn" onclick="openReviewModal()">Оставить отзыв</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($similarCars)): ?>
                    <div class="similar-cars">
                        <h2>Похожие автомобили</h2>
                        <div class="cars-grid">
                            <?php foreach ($similarCars as $similarCar): ?>
                                <div class="car-card">
                                    <img src="images/cars/<?= htmlspecialchars($similarCar['image']) ?>" alt="<?= htmlspecialchars($similarCar['make'].' '.$similarCar['model']) ?>">
                                    <div class="car-info">
                                        <h3><?= htmlspecialchars($similarCar['make'].' '.$similarCar['model']) ?></h3>
                                        <p>Год: <?= htmlspecialchars($similarCar['year']) ?></p>
                                        <p class="price">$<?= number_format($similarCar['price'], 2) ?></p>
                                        <a href="car_details.php?car_id=<?= $similarCar['car_id'] ?>" class="btn">Подробнее</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

   <!-- Модальное окно тест-драйва -->
<div id="testDriveModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('testDriveModal')">×</span>
        <h2>Запись на тест-драйв <?= htmlspecialchars($car['make'].' '.$car['model']) ?></h2>
        <form id="testDriveForm" action="api/process_test_drive.php" method="POST">
            <input type="hidden" name="car_id" value="<?= $carId ?>">
            <div class="form-group">
                <label for="td-date">Желаемая дата</label>
                <input type="date" id="td-date" name="date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
            </div>
            <div class="form-group">
                <label for="td-time">Желаемое время</label>
                <select id="td-time" name="time" required>
                    <option value="09:00">09:00</option>
                    <option value="10:00">10:00</option>
                    <option value="11:00">11:00</option>
                    <option value="12:00">12:00</option>
                    <option value="13:00">13:00</option>
                    <option value="14:00">14:00</option>
                    <option value="15:00">15:00</option>
                    <option value="16:00">16:00</option>
                    <option value="17:00">17:00</option>
                </select>
            </div>
            <div class="form-group">
                <label>Стоимость тест-драйва</label>
                <p>$<?= number_format($car['test_drive_price'], 2) ?></p>
            </div>
            <button type="submit" class="btn">Отправить заявку</button>
        </form>
    </div>
</div>

    <!-- Модальное окно кредита -->
    <div id="creditModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('creditModal')">&times;</span>
            <h2>Рассчитать кредит для <?= htmlspecialchars($car['make'].' '.$car['model']) ?></h2>
            <form id="creditForm">
                <input type="hidden" name="car_id" value="<?= $carId ?>">
                <div class="form-group">
                    <label for="credit-amount">Сумма кредита ($)</label>
                    <input type="number" id="credit-amount" name="amount" value="<?= $car['price'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="credit-term">Срок кредита (месяцев)</label>
                    <select id="credit-term" name="term" required>
                        <option value="12">12 месяцев</option>
                        <option value="24">24 месяца</option>
                        <option value="36" selected>36 месяцев</option>
                        <option value="48">48 месяцев</option>
                        <option value="60">60 месяцев</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="credit-rate">Процентная ставка (%)</label>
                    <input type="number" id="credit-rate" name="rate" step="0.1" value="5.5" required>
                </div>
                <button type="submit" class="btn">Рассчитать</button>
            </form>
            <div id="creditResult"></div>
        </div>
    </div>


    <!-- Модальное окно отзыва -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('reviewModal')">&times;</span>
            <h2>Оставить отзыв о <?= htmlspecialchars($car['make'].' '.$car['model']) ?></h2>
            <form id="reviewForm">
                <input type="hidden" name="car_id" value="<?= $carId ?>">
                <div class="form-group">
                    <label>Ваша оценка</label>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="rating" value="5"><label for="star5">★</label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                        <input type="radio" id="star3" name="rating" value="3" checked><label for="star3">★</label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review-comment">Ваш отзыв</label>
                    <textarea id="review-comment" name="comment" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn">Отправить отзыв</button>
            </form>
        </div>
    </div>

 <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
    <script>
        // Проверяем, добавлен ли автомобиль в избранное
        <?php if (isset($_SESSION['client_id'])): ?>
        fetch('api/check_favorite.php?car_id=<?= $carId ?>')
            .then(response => response.json())
            .then(data => {
                if (data.is_favorite) {
                    const btn = document.getElementById('favorite-btn');
                    btn.textContent = 'В избранном';
                    btn.classList.add('active');
                }
            });
        <?php endif; ?>

        // Обработка избранного
        document.getElementById('favorite-btn')?.addEventListener('click', function() {
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

        // Функции для работы с модальными окнами
function openTestDriveModal() {
    <?php if (!isset($_SESSION['client_id'])): ?>
        alert('Для записи на тест-драйв необходимо авторизоваться');
        window.location.href = 'login.php';
    <?php else: ?>
        document.getElementById('testDriveModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    <?php endif; ?>
}

        function openCreditModal() {
            document.getElementById('creditModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openReviewModal() {
            document.getElementById('reviewModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
        }

        // Обработка формы тест-драйва
document.getElementById('testDriveForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Форма тест-драйва отправляется...');
    const formData = new FormData(this);
    for (let pair of formData.entries()) {
        console.log(`${pair[0]}: ${pair[1]}`);
    }
    fetch(this.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Ответ сервера:', response.status, response.statusText);
        return response.text().then(text => ({ response, text }));
    })
    .then(({ response, text }) => {
        try {
            const data = JSON.parse(text);
            console.log('Данные ответа:', data);
            if (data.success) {
                alert('Заявка на тест-драйв успешно отправлена!');
                closeModal('testDriveModal');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        } catch (e) {
            console.error('Ошибка парсинга JSON:', e, 'Текст ответа:', text);
            alert('Произошла ошибка при отправке заявки: Неверный формат ответа сервера');
        }
    })
    .catch(error => {
        console.error('Ошибка запроса:', error);
        alert('Произошла ошибка при отправке заявки: ' + error.message);
    });
});

        // Обработка формы кредита
        document.getElementById('creditForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const amount = parseFloat(document.getElementById('credit-amount').value);
            const term = parseInt(document.getElementById('credit-term').value);
            const rate = parseFloat(document.getElementById('credit-rate').value) / 100;
            
            const monthlyRate = rate / 12;
            const payment = (amount * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -term));
            const totalPayment = payment * term;
            const totalInterest = totalPayment - amount;
            
            document.getElementById('creditResult').innerHTML = `
                <div class="calculation-result">
                    <p><strong>Ежемесячный платеж:</strong> $${payment.toFixed(2)}</p>
                    <p><strong>Общая сумма выплат:</strong> $${totalPayment.toFixed(2)}</p>
                    <p><strong>Переплата по кредиту:</strong> $${totalInterest.toFixed(2)}</p>
                </div>
            `;
        });

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

        // Обработка формы отзыва
        document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('api/submit_review.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Спасибо за ваш отзыв!');
                    closeModal('reviewModal');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>