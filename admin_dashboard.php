<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Проверка прав администратора
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}

// Параметры пагинации
$itemsPerPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Получение текущих настроек
$settingsQuery = $conn->query("SELECT setting_name, setting_value FROM settings");
$settings = [];
while ($row = $settingsQuery->fetch_assoc()) {
    $settings[$row['setting_name']] = $row['setting_value'];
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'includes/admin_actions.php';
    handleAdminAction($_POST, $conn);
}

// Получаем статистику
$stats = getAdminStatistics($conn);

// Получаем данные для таблиц
$recentOrders = getRecentOrders($conn);
$recentCars = getRecentCars($conn);
$allCars = getAllCars($conn, $itemsPerPage, $offset);
$totalCars = getTotalCars($conn);
$employees = getAllEmployees($conn);
$clients = getAllClients($conn);
$allOrders = getAllOrders($conn, $itemsPerPage, $offset);
$totalOrders = getTotalOrders($conn);

// Функции для получения данных
function getAdminStatistics($conn) {
    $statsQuery = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM cars) as total_cars,
            (SELECT COUNT(*) FROM cars WHERE status = 'available') as available_cars,
            (SELECT COUNT(*) FROM clients) as total_clients,
            (SELECT COUNT(*) FROM employees) as total_employees,
            (SELECT COUNT(*) FROM orders WHERE order_status = 'completed') as total_sales,
            (SELECT SUM(sale_price) FROM sales_department) as total_revenue,
            (SELECT COUNT(*) FROM test_drive_requests WHERE status = 'Pending') as pending_test_drives,
            (SELECT COUNT(*) FROM service_requests WHERE status = 'Pending') as pending_service_requests
    ");
    return $statsQuery->fetch_assoc();
}

function getRecentOrders($conn) {
    $query = $conn->query("
        SELECT o.*, c.first_name, c.last_name, car.make, car.model, sd.sale_price
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        JOIN cars car ON o.car_id = car.car_id
        LEFT JOIN sales_department sd ON o.order_id = sd.order_id
        ORDER BY o.order_date DESC LIMIT 5
    ");
    return $query->fetch_all(MYSQLI_ASSOC);
}

function getRecentCars($conn) {
    $query = $conn->query("SELECT * FROM cars ORDER BY created_at DESC LIMIT 5");
    return $query->fetch_all(MYSQLI_ASSOC);
}

function getAllCars($conn, $limit, $offset) {
    $query = $conn->prepare("SELECT * FROM cars ORDER BY make, model LIMIT ? OFFSET ?");
    $query->bind_param("ii", $limit, $offset);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalCars($conn) {
    $query = $conn->query("SELECT COUNT(*) as total FROM cars");
    return $query->fetch_assoc()['total'];
}

function getAllEmployees($conn) {
    $query = $conn->query("SELECT * FROM employees ORDER BY department, position");
    return $query->fetch_all(MYSQLI_ASSOC);
}

function getAllClients($conn) {
    $query = $conn->query("SELECT * FROM clients ORDER BY last_name, first_name");
    return $query->fetch_all(MYSQLI_ASSOC);
}

function getAllOrders($conn, $limit, $offset) {
    $query = $conn->prepare("
        SELECT o.*, c.first_name, c.last_name, car.make, car.model, sd.sale_price, car.price as car_price
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        JOIN cars car ON o.car_id = car.car_id
        LEFT JOIN sales_department sd ON o.order_id = sd.order_id
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?
    ");
    $query->bind_param("ii", $limit, $offset);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTotalOrders($conn) {
    $query = $conn->query("SELECT COUNT(*) as total FROM orders");
    return $query->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <h1>Панель администратора</h1>
                
                <div class="admin-tabs">
                    <button class="tab-btn active" data-tab="dashboard">Дашборд</button>
                    <button class="tab-btn" data-tab="cars">Автомобили</button>
                    <button class="tab-btn" data-tab="employees">Сотрудники</button>
                    <button class="tab-btn" data-tab="clients">Клиенты</button>
                    <button class="tab-btn" data-tab="orders">Заказы</button>
                    <button class="tab-btn" data-tab="settings">Настройки</button>
                    <button class="tab-btn" onclick="window.location.href='logout.php'">Выйти</button>
                </div>
                
                <!-- Dashboard Tab -->
                <div class="tab-content active" id="dashboard-tab">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-car"></i></div>
                            <h3>Всего автомобилей</h3>
                            <p><?= $stats['total_cars'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <h3>Доступно</h3>
                            <p><?= $stats['available_cars'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <h3>Клиентов</h3>
                            <p><?= $stats['total_clients'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                            <h3>Сотрудников</h3>
                            <p><?= $stats['total_employees'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <h3>Продажи</h3>
                            <p><?= $stats['total_sales'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                            <h3>Выручка</h3>
                            <p>$<?= number_format($stats['total_revenue'], 2) ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <h3>Тест-драйвы</h3>
                            <p><?= $stats['pending_test_drives'] ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-tools"></i></div>
                            <h3>Сервисные заявки</h3>
                            <p><?= $stats['pending_service_requests'] ?></p>
                        </div>
                    </div>
                    
                    <div class="admin-section">
                        <h2>Последние заказы</h2>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Клиент</th>
                                    <th>Автомобиль</th>
                                    <th>Дата</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?= $order['order_id'] ?></td>
                                    <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                    <td><?= htmlspecialchars($order['make'] . ' ' . $order['model']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                                    <td><span class="status-badge <?= $order['order_status'] ?>"><?= $order['order_status'] ?></span></td>
                                    <td>
                                        <button class="btn-small" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="admin-section">
                        <h2>Недавно добавленные автомобили</h2>
                        <div class="cars-grid">
                            <?php foreach ($recentCars as $car): ?>
                                <div class="car-card">
                                    <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>">
                                    <div class="car-info">
                                        <h3><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?></h3>
                                        <p>Год: <?= htmlspecialchars($car['year']) ?></p>
                                        <p class="price">$<?= number_format($car['price'], 2) ?></p>
                                        <div class="car-actions">
                                            <a href="car_details.php?car_id=<?= $car['car_id'] ?>" class="btn-small">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn-small" onclick="editCar(<?= $car['car_id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Cars Tab -->
                <div class="tab-content" id="cars-tab">
                    <div class="admin-actions">
                        <button class="btn" onclick="openAddCarModal()">
                            <i class="fas fa-plus"></i> Добавить автомобиль
                        </button>
                        <div class="filters">
                            <select id="car-status-filter">
                                <option value="">Все статусы</option>
                                <option value="available">Доступен</option>
                                <option value="reserved">Зарезервирован</option>
                                <option value="sold">Продан</option>
                                <option value="maintenance">На обслуживании</option>
                            </select>
                        </div>
                        <div class="search-box">
                            <input type="text" id="car-search" placeholder="Поиск автомобилей...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <table class="admin-table" id="cars-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Марка</th>
                                <th>Модель</th>
                                <th>Год</th>
                                <th>Цена</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allCars as $car): ?>
                            <tr>
                                <td><?= $car['car_id'] ?></td>
                                <td>
                                    <img src="images/cars/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>" class="table-car-image">
                                </td>
                                <td><?= htmlspecialchars($car['make']) ?></td>
                                <td><?= htmlspecialchars($car['model']) ?></td>
                                <td><?= htmlspecialchars($car['year']) ?></td>
                                <td>$<?= number_format($car['price'], 2) ?></td>
                                <td>
                                    <span class="status-badge <?= $car['status'] ?>"><?= $car['status'] ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-view" onclick="viewCar(<?= $car['car_id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-small btn-edit" onclick="editCar(<?= $car['car_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-small btn-danger" onclick="confirmDeleteCar(<?= $car['car_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($totalCars / $itemsPerPage);
                        for ($i = 1; $i <= $totalPages; $i++):
                        ?>
                            <a href="?page=<?= $i ?>" class="pagination-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Employees Tab -->
                <div class="tab-content" id="employees-tab">
                    <div class="admin-actions">
                        <button class="btn" onclick="openAddEmployeeModal()">
                            <i class="fas fa-plus"></i> Добавить сотрудника
                        </button>
                        <div class="search-box">
                            <input type="text" id="employee-search" placeholder="Поиск сотрудников...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <table class="admin-table" id="employees-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Фото</th>
                                <th>Имя</th>
                                <th>Фамилия</th>
                                <th>Должность</th>
                                <th>Отдел</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td><?= $emp['employee_id'] ?></td>
                                <td>
                                    <img src="images/employees/<?= htmlspecialchars($emp['photo'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>" class="table-employee-photo">
                                </td>
                                <td><?= htmlspecialchars($emp['first_name']) ?></td>
                                <td><?= htmlspecialchars($emp['last_name']) ?></td>
                                <td><?= htmlspecialchars($emp['position']) ?></td>
                                <td><?= htmlspecialchars($emp['department']) ?></td>
                                <td>
                                    <span class="status-badge <?= $emp['is_admin'] ? 'admin' : 'employee' ?>">
                                        <?= $emp['is_admin'] ? 'Админ' : 'Сотрудник' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-edit" onclick="editEmployee(<?= $emp['employee_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-small btn-danger" onclick="confirmDeleteEmployee(<?= $emp['employee_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php if (!$emp['is_admin']): ?>
                                        <button class="btn-small btn-success" onclick="promoteToAdmin(<?= $emp['employee_id'] ?>)">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($emp['is_admin']): ?>
                                        <button class="btn-small btn-warning" onclick="demoteFromAdmin(<?= $emp['employee_id'] ?>)">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Clients Tab -->
                <div class="tab-content" id="clients-tab">
                    <div class="admin-actions">
                        <div class="search-box">
                            <input type="text" id="client-search" placeholder="Поиск клиентов...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <table class="admin-table" id="clients-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Фамилия</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Заказов</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client):
                                $orderCount = $conn->query("SELECT COUNT(*) as count FROM orders WHERE client_id = {$client['client_id']}")->fetch_assoc()['count'];
                            ?>
                            <tr>
                                <td><?= $client['client_id'] ?></td>
                                <td><?= htmlspecialchars($client['first_name']) ?></td>
                                <td><?= htmlspecialchars($client['last_name']) ?></td>
                                <td><?= htmlspecialchars($client['email']) ?></td>
                                <td><?= htmlspecialchars($client['phone']) ?></td>
                                <td><?= $orderCount ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-view" onclick="viewClient(<?= $client['client_id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-small btn-edit" onclick="editClient(<?= $client['client_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-small btn-danger" onclick="confirmDeleteClient(<?= $client['client_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn-small btn-success" onclick="addCarToClient(<?= $client['client_id'] ?>)">
                                            <i class="fas fa-car"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-content" id="orders-tab">
                    <div class="admin-actions">
                        <div class="filters">
                            <select id="order-status-filter">
                                <option value="">Все статусы</option>
                                <option value="pending">Ожидание</option>
                                <option value="completed">Завершено</option>
                                <option value="cancelled">Отменено</option>
                            </select>
                            <input type="date" id="order-date-filter">
                            <button class="btn-small" onclick="resetOrderFilters()">Сбросить</button>
                        </div>
                        <div class="search-box">
                            <input type="text" id="order-search" placeholder="Поиск заказов...">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    
                    <table class="admin-table" id="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата</th>
                                <th>Клиент</th>
                                <th>Автомобиль</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th>Сумма</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allOrders as $order): ?>
                            <tr>
                                <td><?= $order['order_id'] ?></td>
                                <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                                <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                <td><?= htmlspecialchars($order['make'] . ' ' . $order['model']) ?></td>
                                <td><?= htmlspecialchars($order['order_type']) ?></td>
                                <td>
                                    <span class="status-badge <?= $order['order_status'] ?>"><?= $order['order_status'] ?></span>
                                </td>
                                <td>$<?= number_format($order['sale_price'] ?? $order['car_price'], 2) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-small btn-view" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-small btn-edit" onclick="editOrder(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($order['order_status'] == 'pending'): ?>
                                        <button class="btn-small btn-success" onclick="completeOrder(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn-small btn-danger" onclick="cancelOrder(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination -->
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($totalOrders / $itemsPerPage);
                        for ($i = 1; $i <= $totalPages; $i++):
                        ?>
                            <a href="?page=<?= $i ?>" class="pagination-link <?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div class="tab-content" id="settings-tab">
                    <div class="settings-section">
                        <h2>Настройки системы</h2>
                        <form id="system-settings-form" action="api/admin/update_settings.php" method="POST">
                            <div class="form-group">
                                <label for="site-name">Название сайта</label>
                                <input type="text" id="site-name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'AutoDealer') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="admin-email">Email администратора</label>
                                <input type="email" id="admin-email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@autodealer.com') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="items-per-page">Элементов на странице</label>
                                <input type="number" id="items-per-page" name="items_per_page" value="<?= htmlspecialchars($settings['items_per_page'] ?? 20) ?>" min="5" max="100" required>
                            </div>
                            <div class="form-group">
                                <label for="default-currency">Валюта по умолчанию</label>
                                <select id="default-currency" name="default_currency" required>
                                    <option value="USD" <?= ($settings['default_currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>Доллар ($)</option>
                                    <option value="EUR" <?= ($settings['default_currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>Евро (€)</option>
                                    <option value="RUB" <?= ($settings['default_currency'] ?? '') === 'RUB' ? 'selected' : '' ?>>Рубль (₽)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="maintenance-mode">Режим обслуживания</label>
                                <input type="checkbox" id="maintenance-mode" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                            </div>
                            <button type="submit" class="btn">Сохранить настройки</button>
                        </form>
                    </div>
                    
                    <div class="settings-section">
                        <h2>Резервное копирование</h2>
                        <div class="backup-actions">
                            <button class="btn" onclick="createBackup()">
                                <i class="fas fa-database"></i> Создать резервную копию
                            </button>
                            <button class="btn btn-outline" onclick="restoreBackup()">
                                <i class="fas fa-undo"></i> Восстановить из копии
                            </button>
                        </div>
                        <div class="backup-list" id="backup-list">
                            <!-- Список резервных копий будет загружен здесь -->
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h2>Логи системы</h2>
                        <div class="log-viewer">
                            <pre><?php 
                                $logFile = 'logs/system.log';
                                if (file_exists($logFile)) {
                                    echo htmlspecialchars(file_get_contents($logFile));
                                } else {
                                    echo 'Лог-файл не найден';
                                }
                            ?></pre>
                        </div>
                        <button class="btn" onclick="downloadLogs()">
                            <i class="fas fa-download"></i> Скачать логи
                        </button>
                        <button class="btn btn-danger" onclick="clearLogs()">
                            <i class="fas fa-trash"></i> Очистить логи
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Модальные окна -->
    <?php include 'includes/admin_modals.php'; ?>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/admin.js"></script>
</body>
</html>