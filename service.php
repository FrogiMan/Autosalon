<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Получаем типы сервисных работ
$serviceTypesQuery = "SELECT service_type_id, type_name, price, category FROM service_types ORDER BY category, type_name";
$serviceTypesResult = $conn->query($serviceTypesQuery);

if (!$serviceTypesResult) {
    die("Ошибка при получении типов сервисных работ: " . $conn->error);
}

$serviceTypes = $serviceTypesResult->fetch_all(MYSQLI_ASSOC);

// Группируем по категориям
$groupedServiceTypes = [];
foreach ($serviceTypes as $type) {
    $groupedServiceTypes[$type['category']][] = $type;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сервис - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <h1>Сервисное обслуживание</h1>
                
                <div class="service-info">
                    <p>Наш автосервис предлагает полный спектр услуг по техническому обслуживанию и ремонту автомобилей всех марок.</p>
                    
                    <div class="service-types">
                        <?php foreach ($groupedServiceTypes as $category => $types): ?>
                            <div class="service-type">
                                <h3>
                                    <?= $category === 'Maintenance' ? 'Техническое обслуживание' :
                                       ($category === 'Repair' ? 'Ремонтные работы' : 'Дополнительные услуги') ?>
                                </h3>
                                <ul>
                                    <?php foreach ($types as $type): ?>
                                        <li><?= htmlspecialchars($type['type_name']) ?> - $<?= number_format($type['price'], 2) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="service-request">
                    <h2>Записаться на обслуживание</h2>
                    <?php if (isset($_SESSION['client_id'])): ?>
                        <form id="serviceRequestForm" action="api/process_service_request.php" method="POST">
                            <div class="form-group">
                                <label for="service-car">Ваш автомобиль</label>
                                <select name="car_id" id="service-car" required>
                                    <?php
                                    $clientId = $_SESSION['client_id'];
                                    $carsQuery = "SELECT c.car_id, c.make, c.model 
                                                 FROM orders o
                                                 JOIN cars c ON o.car_id = c.car_id
                                                 WHERE o.client_id = ? AND o.order_status = 'completed'";
                                    $carsStmt = $conn->prepare($carsQuery);
                                    $carsStmt->bind_param("i", $clientId);
                                    $carsStmt->execute();
                                    $carsResult = $carsStmt->get_result();
                                    
                                    if ($carsResult->num_rows > 0):
                                        while ($car = $carsResult->fetch_assoc()):
                                    ?>
                                        <option value="<?= $car['car_id'] ?>">
                                            <?= htmlspecialchars($car['make'].' '.$car['model']) ?>
                                        </option>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <option value="" disabled>У вас нет автомобилей</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-type">Тип услуги</label>
                                <select name="service_type_id" id="service-type" required onchange="updateServicePrice()">
                                    <option value="">Выберите тип услуги</option>
                                    <?php foreach ($serviceTypes as $type): ?>
                                        <option value="<?= $type['service_type_id'] ?>" data-price="<?= $type['price'] ?>">
                                            <?= htmlspecialchars($type['type_name']) ?> ($<?= number_format($type['price'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="service-price" class="form-info"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="service-date">Желаемая дата</label>
                                <input type="date" name="preferred_date" id="service-date" required>
                            </div>
                            
                            <button type="submit" class="btn">Отправить заявку</button>
                        </form>
                    <?php else: ?>
                        <div class="auth-required">
                            <p>Для записи на обслуживание необходимо <a href="login.php">войти</a> в личный кабинет.</p>
                            <p>Еще нет аккаунта? <a href="reg.php">Зарегистрируйтесь</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php require_once 'includes/footer.php'; ?>

    <script>
function updateServicePrice() {
    const select = document.getElementById('service-type');
    const priceDiv = document.getElementById('service-price');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption ? selectedOption.getAttribute('data-price') : '';
    priceDiv.textContent = price ? `Стоимость услуги: $${parseFloat(price).toFixed(2)}` : '';
}

document.getElementById('serviceRequestForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Отправка формы сервисной заявки...');
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
        return response.text().then(text => ({ response, text })); // Получаем текст ответа
    })
    .then(({ response, text }) => {
        try {
            const data = JSON.parse(text);
            console.log('Данные ответа:', data);
            if (data.success) {
                alert('Ваша заявка принята! Мы свяжемся с вами для подтверждения.');
                this.reset();
                updateServicePrice();
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

// Установка минимальной даты (сегодня + 1 день)
const today = new Date();
today.setDate(today.getDate() + 1);
const minDate = today.toISOString().split('T')[0];
document.getElementById('service-date')?.setAttribute('min', minDate);

// Инициализация цены услуги
updateServicePrice();
    </script>
</body>
</html>