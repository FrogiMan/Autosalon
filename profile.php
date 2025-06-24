<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
$clientId = $_SESSION['client_id'];

// Получаем данные клиента
$clientQuery = "SELECT * FROM clients WHERE client_id = ?";
$clientStmt = $conn->prepare($clientQuery);
$clientStmt->bind_param("i", $clientId);
$clientStmt->execute();
$clientResult = $clientStmt->get_result();
$client = $clientResult->fetch_assoc();

// Получаем избранные автомобили
$favoritesQuery = "SELECT c.* FROM cars c
                  JOIN favorites f ON c.car_id = f.car_id
                  WHERE f.client_id = ? AND c.status = 'available'";
$favoritesStmt = $conn->prepare($favoritesQuery);
$favoritesStmt->bind_param("i", $clientId);
$favoritesStmt->execute();
$favoritesResult = $favoritesStmt->get_result();
$favorites = $favoritesResult->fetch_all(MYSQLI_ASSOC);

// Получаем историю просмотров
$historyQuery = "SELECT c.* FROM cars c
                JOIN orders o ON c.car_id = o.car_id
                WHERE o.client_id = ? AND c.status = 'available'
                ORDER BY o.order_date DESC LIMIT 5";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $clientId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$history = $historyResult->fetch_all(MYSQLI_ASSOC);

// Получаем уведомления
$notificationsQuery = "SELECT * FROM notifications 
                      WHERE client_id = ? 
                      ORDER BY created_at DESC LIMIT 10";
$notificationsStmt = $conn->prepare($notificationsQuery);
$notificationsStmt->bind_param("i", $clientId);
$notificationsStmt->execute();
$notificationsResult = $notificationsStmt->get_result();
$notifications = $notificationsResult->fetch_all(MYSQLI_ASSOC);

// Получаем заявки на тест-драйв
$testDrivesQuery = "SELECT t.*, c.make, c.model 
                   FROM test_drive_requests t
                   JOIN cars c ON t.car_id = c.car_id
                   WHERE t.client_id = ?
                   ORDER BY t.preferred_date DESC";
$testDrivesStmt = $conn->prepare($testDrivesQuery);
$testDrivesStmt->bind_param("i", $clientId);
$testDrivesStmt->execute();
$testDrivesResult = $testDrivesStmt->get_result();
$testDrives = $testDrivesResult->fetch_all(MYSQLI_ASSOC);

// Получаем сервисные заявки
$serviceRequestsQuery = "SELECT s.*, c.make, c.model 
                        FROM service_requests s
                        JOIN cars c ON s.car_id = c.car_id
                        WHERE s.client_id = ?
                        ORDER BY s.request_date DESC";
$serviceRequestsStmt = $conn->prepare($serviceRequestsQuery);
$serviceRequestsStmt->bind_param("i", $clientId);
$serviceRequestsStmt->execute();
$serviceRequestsResult = $serviceRequestsStmt->get_result();
$serviceRequests = $serviceRequestsResult->fetch_all(MYSQLI_ASSOC);

// Получаем заказы с правильной ценой
$ordersQuery = "SELECT o.*, c.make, c.model, 
                CASE 
                    WHEN o.order_type = 'test_drive' THEN c.test_drive_price
                    WHEN o.order_type = 'service' THEN COALESCE(st.price, 0)
                    WHEN o.order_type = 'purchase' THEN sd.sale_price
                    ELSE 0
                END as sale_price,
                sd.payment_method
                FROM orders o
                JOIN cars c ON o.car_id = c.car_id
                LEFT JOIN sales_department sd ON o.order_id = sd.order_id
                LEFT JOIN service_requests sr ON o.car_id = sr.car_id AND o.order_type = 'service'
                LEFT JOIN service_types st ON sr.repair_type = st.type_name
                WHERE o.client_id = ?
                ORDER BY o.order_date DESC";
$ordersStmt = $conn->prepare($ordersQuery);
$ordersStmt->bind_param("i", $clientId);
$ordersStmt->execute();
$ordersResult = $ordersStmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);

// Рассчитываем общую сумму для неоплаченных заказов
$totalPendingAmount = 0;
$pendingOrderIds = [];
foreach ($orders as $order) {
    if ($order['order_status'] == 'completed' && $order['sale_price'] > 0 && 
        (!$order['payment_method'] || $order['payment_method'] == 'pending')) {
        $totalPendingAmount += $order['sale_price'];
        $pendingOrderIds[] = $order['order_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <div class="profile-header">
                    <h1>Личный кабинет</h1>
                    <p>Добро пожаловать, <?= htmlspecialchars($client['first_name'].' '.$client['last_name']) ?>!</p>
                </div>
                
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="profile">Профиль</button>
                    <button class="tab-btn" data-tab="favorites">Избранное</button>
                    <button class="tab-btn" data-tab="history">История просмотров</button>
                    <button class="tab-btn" data-tab="notifications">Уведомления</button>
                    <button class="tab-btn" data-tab="test-drives">Тест-драйвы</button>
                    <button class="tab-btn" data-tab="service">Сервис</button>
                    <button class="tab-btn" data-tab="orders">Заказы</button>
                </div>
                
                <div class="tab-content active" id="profile-tab">
                    <div class="profile-info">
                        <div class="info-item">
                            <span class="info-label">Имя:</span>
                            <span class="info-value"><?= htmlspecialchars($client['first_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Фамилия:</span>
                            <span class="info-value"><?= htmlspecialchars($client['last_name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= htmlspecialchars($client['email']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Телефон:</span>
                            <span class="info-value"><?= htmlspecialchars($client['phone']) ?></span>
                        </div>
                        <button class="btn" onclick="openEditModal()">Редактировать профиль</button>
                    </div>
                </div>
                
                <div class="tab-content" id="favorites-tab">
                    <h3>Избранные автомобили</h3>
                    <?php if (!empty($favorites)): ?>
                        <div class="cars-grid">
                            <?php foreach ($favorites as $car): ?>
                                <div class="car-card">
                                    <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'].' '.$car['model']) ?>">
                                    <div class="car-info">
                                        <h3><?= htmlspecialchars($car['make'].' '.$car['model']) ?></h3>
                                        <p>Год: <?= htmlspecialchars($car['year']) ?></p>
                                        <p class="price">$<?= number_format($car['price'], 2) ?></p>
                                        <div class="car-actions">
                                            <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn">Подробнее</a>
                                            <button class="btn btn-outline favorite-btn" data-car-id="<?= $car['car_id'] ?>">
                                                Удалить
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>У вас пока нет избранных автомобилей.</p>
                        <a href="cars.php" class="btn">Посмотреть каталог</a>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="history-tab">
                    <h3>История просмотров</h3>
                    <?php if (!empty($history)): ?>
                        <div class="cars-grid">
                            <?php foreach ($history as $car): ?>
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
                    <?php else: ?>
                        <p>Ваша история просмотров пуста.</p>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="notifications-tab">
                    <h3>Уведомления</h3>
                    <button class="btn btn-danger" onclick="clearNotifications()">Очистить уведомления</button>
                    <?php if (!empty($notifications)): ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item <?= $notification['is_read'] ? '' : 'unread' ?>">
                                    <h4><?= htmlspecialchars($notification['title']) ?></h4>
                                    <p><?= htmlspecialchars($notification['message']) ?></p>
                                    <div class="notification-date">
                                        <?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>У вас нет новых уведомлений.</p>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="test-drives-tab">
                    <h3>Заявки на тест-драйв</h3>
                    <?php if (!empty($testDrives)): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Автомобиль</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testDrives as $testDrive): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($testDrive['preferred_date'])) ?></td>
                                        <td><?= htmlspecialchars($testDrive['make'].' '.$testDrive['model']) ?></td>
                                        <td>
                                            <span class="status-badge <?= strtolower($testDrive['status']) ?>">
                                                <?= htmlspecialchars($testDrive['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($testDrive['status'] == 'Pending'): ?>
                                                <button class="btn-small cancel-btn" 
                                                        data-request-id="<?= $testDrive['request_id'] ?>"
                                                        onclick="cancelTestDrive(this)">
                                                    Отменить
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>У вас нет активных заявок на тест-драйв.</p>
                        <a href="cars.php" class="btn">Записаться на тест-драйв</a>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="service-tab">
                    <h3>Сервисные заявки</h3>
                    <?php if (!empty($serviceRequests)): ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Автомобиль</th>
                                    <th>Тип работ</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($serviceRequests as $request): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($request['request_date'])) ?></td>
                                        <td><?= htmlspecialchars($request['make'].' '.$request['model']) ?></td>
                                        <td><?= htmlspecialchars($request['repair_type']) ?></td>
                                        <td>
                                            <span class="status-badge <?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                                                <?= htmlspecialchars($request['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] == 'Pending'): ?>
                                                <button class="btn-small cancel-btn" 
                                                        data-request-id="<?= $request['request_id'] ?>"
                                                        onclick="cancelServiceRequest(this)">
                                                    Отменить
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>У вас нет активных сервисных заявок.</p>
                        <a href="service.php" class="btn">Оставить заявку</a>
                    <?php endif; ?>
                </div>
                
              <div class="tab-content" id="orders-tab">
    <h3>Ваши заказы</h3>
    <button class="btn btn-danger" onclick="clearOrderHistory()">Очистить историю заказов</button>
    <?php if ($totalPendingAmount > 0): ?>
        <button class="btn btn-success pay-all-btn" 
                onclick="openPayAllModal(<?= htmlspecialchars(json_encode($pendingOrderIds)) ?>, <?= $totalPendingAmount ?>)">
            Оплатить все ($<?= number_format($totalPendingAmount, 2) ?>)
        </button>
    <?php endif; ?>
    <?php if (!empty($orders)): ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Автомобиль</th>
                    <th>Тип заказа</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                        <td><?= htmlspecialchars($order['make'].' '.$order['model']) ?></td>
                        <td><?= htmlspecialchars($order['order_type']) ?></td>
                        <td>
                            <?php if ($order['sale_price'] > 0): ?>
                                $<?= number_format($order['sale_price'], 2) ?>
                            <?php else: ?>
                                Не определена
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= strtolower($order['order_status']) ?>">
                                <?= htmlspecialchars($order['order_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($order['order_status'] == 'completed' && 
                                      $order['sale_price'] > 0 && 
                                      (!$order['payment_method'] || $order['payment_method'] == 'pending')): ?>
                                <button class="btn-small btn-success payment-btn" 
                                        data-order-id="<?= $order['order_id'] ?>"
                                        data-amount="<?= $order['sale_price'] ?>"
                                        onclick="openOrderPaymentModal(this)">
                                    Оплатить ($<?= number_format($order['sale_price'], 2) ?>)
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>У вас нет заказов.</p>
    <?php endif; ?>
</div>
            </div>
        </section>
    </main>

    <!-- Модальное окно оплаты заказа -->
    <div id="orderPaymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('orderPaymentModal')">×</span>
            <h2>Оплата заказа</h2>
            <form id="orderPaymentForm" action="api/process_payment.php" method="POST">
                <input type="hidden" name="order_id" id="order-order-id">
                <input type="hidden" name="request_id" id="order-request-id" value="0">
                <div class="form-group">
                    <label for="order-card-number">Номер карты</label>
                    <input type="text" id="order-card-number" name="card_number" placeholder="1234 5678 9012 3456" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="order-card-expiry">Срок действия</label>
                        <input type="text" id="order-card-expiry" name="card_expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="order-card-cvc">CVC</label>
                        <input type="text" id="order-card-cvc" name="card_cvc" placeholder="123" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="order-cardholder-name">Имя держателя карты</label>
                    <input type="text" id="order-cardholder-name" name="cardholder_name" required>
                </div>
                <div class="form-group">
                    <label for="order-amount">Сумма ($)</label>
                    <input type="number" id="order-amount" name="amount" readonly>
                </div>
                <button type="submit" class="btn">Оплатить</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно оплаты всех заказов -->
    <div id="payAllModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('payAllModal')">×</span>
            <h2>Оплата всех заказов</h2>
            <form id="payAllForm" action="api/process_multiple_payments.php" method="POST">
                <input type="hidden" name="order_ids" id="pay-all-order-ids">
                <div class="form-group">
                    <label for="pay-all-card-number">Номер карты</label>
                    <input type="text" id="pay-all-card-number" name="card_number" placeholder="1234 5678 9012 3456" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="pay-all-card-expiry">Срок действия</label>
                        <input type="text" id="pay-all-card-expiry" name="card_expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label for="pay-all-card-cvc">CVC</label>
                        <input type="text" id="pay-all-card-cvc" name="card_cvc" placeholder="123" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="pay-all-cardholder-name">Имя держателя карты</label>
                    <input type="text" id="pay-all-cardholder-name" name="cardholder_name" required>
                </div>
                <div class="form-group">
                    <label for="pay-all-amount">Сумма ($)</label>
                    <input type="number" id="pay-all-amount" name="amount" readonly>
                </div>
                <button type="submit" class="btn">Оплатить</button>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования профиля -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">×</span>
            <h2>Редактирование профиля</h2>
            <form id="editProfileForm" action="api/update_profile.php" method="POST">
                <div class="form-group">
                    <label for="edit-first-name">Имя</label>
                    <input type="text" id="edit-first-name" name="first_name" value="<?= htmlspecialchars($client['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-last-name">Фамилия</label>
                    <input type="text" id="edit-last-name" name="last_name" value="<?= htmlspecialchars($client['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" name="email" value="<?= htmlspecialchars($client['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-phone">Телефон</label>
                    <input type="tel" id="edit-phone" name="phone" value="<?= htmlspecialchars($client['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit-password">Новый пароль (оставьте пустым, если не хотите менять)</label>
                    <input type="password" id="edit-password" name="password">
                </div>
                <button type="submit" class="btn">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
    <script>
        // Переключение вкладок
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // Функции для работы с модальными окнами
        function openEditModal() {
            document.getElementById('editModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Обработка формы редактирования профиля
        document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Профиль успешно обновлен!');
                    closeModal('editModal');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        });

        function openOrderPaymentModal(btn) {
            const orderId = btn.getAttribute('data-order-id');
            const amount = btn.getAttribute('data-amount');
            document.getElementById('order-order-id').value = orderId;
            document.getElementById('order-amount').value = amount;
            document.getElementById('orderPaymentModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openPayAllModal(orderIds, amount) {
            document.getElementById('pay-all-order-ids').value = JSON.stringify(orderIds);
            document.getElementById('pay-all-amount').value = amount;
            document.getElementById('payAllModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

document.getElementById('orderPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(this.action, {
        method: 'POST',
        body: new FormData(this),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => ({ status: response.status, text }));
    })
    .then(({ status, text }) => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Оплата успешно проведена! Номер заказа: ' + data.order_id);
                closeModal('orderPaymentModal');
                location.reload();
            } else {
                alert('Ошибка оплаты: ' + data.message);
            }
        } catch (e) {
            console.error('Invalid JSON:', text);
            alert('Произошла ошибка при оплате: Неверный формат ответа сервера');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Произошла ошибка при оплате: ' + error.message);
    });
});

document.getElementById('payAllForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(this.action, {
        method: 'POST',
        body: new FormData(this),
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => ({ status: response.status, text }));
    })
    .then(({ status, text }) => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Все заказы успешно оплачены!');
                closeModal('payAllModal');
                setTimeout(() => location.reload(), 1000); // Delay reload for 1 second
            } else {
                alert('Ошибка оплаты: ' + data.message);
            }
        } catch (e) {
            console.error('Invalid JSON:', text);
            alert('Произошла ошибка при оплате: Неверный формат ответа сервера');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Произошла ошибка при оплате: ' + error.message);
    });
});

        // Очистка уведомлений
        function clearNotifications() {
            if (confirm('Вы уверены, что хотите очистить историю уведомлений?')) {
                fetch('api/clear_notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Уведомления очищены');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }

        // Очистка истории заказов
function clearOrderHistory() {
    if (confirm('Внимание! Это действие нельзя отменить. Вы уверены, что хотите очистить историю заказов?')) {
        fetch('api/clear_order_history.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('История заказов очищена');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Произошла ошибка при очистке истории заказов');
        });
    }
}
        // Удаление из избранного
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
                        location.reload();
                    }
                });
            });
        });
        
        // Отмена тест-драйва
        function cancelTestDrive(btn) {
            if (confirm('Вы уверены, что хотите отменить заявку на тест-драйв?')) {
                const requestId = btn.getAttribute('data-request-id');
                fetch('api/cancel_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'request_id=' + requestId + '&type=test_drive'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        // Отмена сервисной заявки
        function cancelServiceRequest(btn) {
            if (confirm('Вы уверены, что хотите отменить сервисную заявку?')) {
                const requestId = btn.getAttribute('data-request-id');
                fetch('api/cancel_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'request_id=' + requestId + '&type=service'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>