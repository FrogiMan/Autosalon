<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Получаем рекомендуемые автомобили
$recommendedCars = [];
if (isset($_SESSION['client_id'])) {
    $clientId = $_SESSION['client_id'];
    $query = "SELECT c.* FROM cars c 
              WHERE c.status = 'available' 
              AND c.car_id NOT IN (
                  SELECT car_id FROM orders WHERE client_id = ?
              )
              ORDER BY RAND() LIMIT 3";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $clientId);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $recommendedCars = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    }
}

// Если нет рекомендаций, показываем случайные автомобили
if (empty($recommendedCars)) {
    $query = "SELECT * FROM cars WHERE status = 'available' ORDER BY RAND() LIMIT 3";
    $result = $conn->query($query);
    if ($result) {
        $recommendedCars = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Получаем новинки
$query = "SELECT * FROM cars WHERE status = 'available' ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($query);
if ($result) {
    $newCars = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $newCars = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="main-banner">
            <div class="container">
                <h1>Автомобили вашей мечты</h1>
                <p>Широкий выбор новых и подержанных автомобилей по лучшим ценам</p>
                <a href="cars.php" class="btn">Посмотреть каталог</a>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2>Рекомендуем для вас</h2>
                <div class="cars-grid">
                    <?php foreach ($recommendedCars as $car): ?>
                        <div class="car-card">
                            <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>">
                            <div class="car-info">
                                <h3><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></h3>
                                <p>Год: <?= htmlspecialchars($car['year']) ?></p>
                                <p class="price">$<?= number_format($car['price'], 2) ?></p>
                                <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <h2>Новые поступления</h2>
                <div class="cars-grid">
                    <?php foreach ($newCars as $car): ?>
                        <div class="car-card">
                            <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'].' '.$car['model']) ?>">
                            <div class="car-info">
                                <h3><?= htmlspecialchars($car['make'].' '.$car['model']) ?></h3>
                                <p>Год: <?= htmlspecialchars($car['year']) ?></p>
                                <p class="price">$<?= number_format($car['price'], 2) ?></p>
                                <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section calculator-section">
            <div class="container">
                <h2>Кредитный калькулятор</h2>
                <div class="calculator">
                    <div class="form-group">
                        <label for="car-price">Стоимость автомобиля ($)</label>
                        <input type="number" id="car-price" value="25000">
                    </div>
                    <div class="form-group">
                        <label for="down-payment">Первоначальный взнос ($)</label>
                        <input type="number" id="down-payment" value="5000">
                    </div>
                    <div class="form-group">
                        <label for="loan-term">Срок кредита (месяцев)</label>
                        <select id="loan-term">
                            <option value="12">12 месяцев</option>
                            <option value="24">24 месяца</option>
                            <option value="36" selected>36 месяцев</option>
                            <option value="48">48 месяцев</option>
                            <option value="60">60 месяцев</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="interest-rate">Процентная ставка (%)</label>
                        <input type="number" id="interest-rate" step="0.1" value="5.5">
                    </div>
                    <button id="calculate-btn" class="btn">Рассчитать</button>
                    <div id="calculation-result" class="calculation-result"></div>
                </div>
            </div>
        </section>
    </main>

  <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
    <script>
        // Кредитный калькулятор
        document.getElementById('calculate-btn').addEventListener('click', function() {
            const price = parseFloat(document.getElementById('car-price').value);
            const downPayment = parseFloat(document.getElementById('down-payment').value);
            const term = parseInt(document.getElementById('loan-term').value);
            const rate = parseFloat(document.getElementById('interest-rate').value) / 100;
            
            const loanAmount = price - downPayment;
            const monthlyRate = rate / 12;
            const payment = (loanAmount * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -term));
            const totalPayment = payment * term;
            const totalInterest = totalPayment - loanAmount;
            
            document.getElementById('calculation-result').innerHTML = `
                <p><strong>Ежемесячный платеж:</strong> $${payment.toFixed(2)}</p>
                <p><strong>Общая сумма выплат:</strong> $${totalPayment.toFixed(2)}</p>
                <p><strong>Переплата по кредиту:</strong> $${totalInterest.toFixed(2)}</p>
            `;
        });
    </script>
</body>
</html>