<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';

// Проверка прав менеджера
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

$employeeId = $_SESSION['employee_id'];

// Получаем информацию о менеджере
$employeeQuery = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
$employeeQuery->bind_param("i", $employeeId);
$employeeQuery->execute();
$employee = $employeeQuery->get_result()->fetch_assoc();
$employeeQuery->close();

// Получаем заявки на тест-драйв (только для отдела Sales)
$testDrives = [];
if ($employee['department'] === 'Sales') {
    $testDrivesQuery = $conn->prepare("
        SELECT t.*, c.first_name, c.last_name, c.phone, car.make, car.model 
        FROM test_drive_requests t
        JOIN clients c ON t.client_id = c.client_id
        JOIN cars car ON t.car_id = car.car_id
        WHERE t.status = 'Pending' AND (t.employee_id IS NULL OR t.employee_id = ?)
        ORDER BY t.preferred_date, t.preferred_time
    ");
    $testDrivesQuery->bind_param("i", $employeeId);
    $testDrivesQuery->execute();
    $testDrives = $testDrivesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $testDrivesQuery->close();
}

// Получаем сервисные заявки (только для отдела Service)
$serviceRequests = [];
if ($employee['department'] === 'Service') {
    $serviceQuery = $conn->prepare("
        SELECT s.*, c.first_name, c.last_name, car.make, car.model 
        FROM service_requests s
        JOIN clients c ON s.client_id = c.client_id
        JOIN cars car ON s.car_id = car.car_id
        WHERE s.status = 'Pending' AND (s.employee_id IS NULL OR s.employee_id = ?)
        ORDER BY s.request_date
    ");
    $serviceQuery->bind_param("i", $employeeId);
    $serviceQuery->execute();
    $serviceRequests = $serviceQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $serviceQuery->close();
}

// Получаем заказы (только для отдела Sales)
$orders = [];
if ($employee['department'] === 'Sales') {
    $ordersQuery = $conn->prepare("
        SELECT o.*, c.first_name, c.last_name, car.make, car.model, car.price
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        JOIN cars car ON o.car_id = car.car_id
        WHERE o.order_status = 'pending'
        ORDER BY o.order_date
    ");
    $ordersQuery->execute();
    $orders = $ordersQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $ordersQuery->close();
}

// Получаем активные заявки менеджера
$activeRequests = [];
if ($employee['department'] === 'Sales') {
    $activeTestDrivesQuery = $conn->prepare("
        SELECT t.*, c.first_name, c.last_name, car.make, car.model 
        FROM test_drive_requests t
        JOIN clients c ON t.client_id = c.client_id
        JOIN cars car ON t.car_id = car.car_id
        WHERE t.employee_id = ? AND t.status IN ('Pending', 'Confirmed')
        ORDER BY t.preferred_date, t.preferred_time
    ");
    $activeTestDrivesQuery->bind_param("i", $employeeId);
    $activeTestDrivesQuery->execute();
    $activeRequests['test_drives'] = $activeTestDrivesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $activeTestDrivesQuery->close();
} else {
    $activeRequests['test_drives'] = [];
}

if ($employee['department'] === 'Service') {
    $activeServicesQuery = $conn->prepare("
        SELECT s.*, c.first_name, c.last_name, car.make, car.model 
        FROM service_requests s
        JOIN clients c ON s.client_id = c.client_id
        JOIN cars car ON s.car_id = car.car_id
        WHERE s.employee_id = ? AND s.status = 'In Progress'
        ORDER BY s.request_date
    ");
    $activeServicesQuery->bind_param("i", $employeeId);
    $activeServicesQuery->execute();
    $activeRequests['services'] = $activeServicesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $activeServicesQuery->close();
} else {
    $activeRequests['services'] = [];
}

if ($employee['department'] === 'Sales') {
    $activeOrdersQuery = $conn->prepare("
        SELECT o.*, c.first_name, c.last_name, car.make, car.model 
        FROM orders o
        JOIN clients c ON o.client_id = c.client_id
        JOIN cars car ON o.car_id = car.car_id
        WHERE o.order_status = 'processing'
        ORDER BY o.order_date
    ");
    $activeOrdersQuery->execute();
    $activeRequests['orders'] = $activeOrdersQuery->get_result()->fetch_all(MYSQLI_ASSOC);
    $activeOrdersQuery->close();
} else {
    $activeRequests['orders'] = [];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель менеджера - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/manager.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <div class="manager-header">
                    <div class="manager-info">
                        <img src="images/employees/<?= htmlspecialchars($employee['photo'] ?? 'default.jpg') ?>" alt="Фото сотрудника" class="manager-photo">
                        <div>
                            <h1>Панель менеджера</h1>
                            <p>Добро пожаловать, <?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?></p>
                            <p class="manager-position"><?= htmlspecialchars($employee['position']) ?> (<?= htmlspecialchars($employee['department']) ?>)</p>
                        </div>
                    </div>
                    <div class="manager-stats">
                        <?php if ($employee['department'] === 'Sales'): ?>
                        <div class="stat-item">
                            <i class="fas fa-calendar-check"></i>
                            <span><?= count($activeRequests['test_drives']) ?> активных тест-драйвов</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-file-signature"></i>
                            <span><?= count($activeRequests['orders']) ?> активных заказов</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($employee['department'] === 'Service'): ?>
                        <div class="stat-item">
                            <i class="fas fa-tools"></i>
                            <span><?= count($activeRequests['services']) ?> активных сервисов</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="manager-tabs">
                    <?php if ($employee['department'] === 'Sales'): ?>
                        <button class="tab-btn active" data-tab="test-drives">Тест-драйвы</button>
                        <button class="tab-btn" data-tab="orders">Заказы</button>
                    <?php endif; ?>
                    <?php if ($employee['department'] === 'Service'): ?>
                        <button class="tab-btn active" data-tab="service">Сервисные заявки</button>
                    <?php endif; ?>
                    <button class="tab-btn" data-tab="active">Активные задачи</button>
                    <button class="tab-btn" data-tab="profile">Профиль</button>
                </div>
                
                <?php if ($employee['department'] === 'Sales'): ?>
                <div class="tab-content active" id="test-drives-tab">
                    <h2>Новые заявки на тест-драйв</h2>
                    <?php if (!empty($testDrives)): ?>
                        <table class="manager-table">
                            <thead>
                                <tr>
                                    <th>Дата/Время</th>
                                    <th>Клиент</th>
                                    <th>Телефон</th>
                                    <th>Автомобиль</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testDrives as $drive): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($drive['preferred_date'])) ?> <?= $drive['preferred_time'] ?></td>
                                    <td><?= htmlspecialchars($drive['first_name'].' '.$drive['last_name']) ?></td>
                                    <td><?= htmlspecialchars($drive['phone']) ?></td>
                                    <td><?= htmlspecialchars($drive['make'].' '.$drive['model']) ?></td>
                                    <td><span class="status-badge <?= strtolower($drive['status']) ?>"><?= $drive['status'] ?></span></td>
                                    <td>
                                        <?php if ($drive['employee_id'] === null): ?>
                                            <button class="btn-small btn-success" 
                                                    onclick="assignTestDrive(<?= $drive['request_id'] ?>)">
                                                <i class="fas fa-user-check"></i> Назначить себе
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-small btn-success" 
                                                onclick="updateTestDrive(<?= $drive['request_id'] ?>, 'Confirmed')">
                                            <i class="fas fa-check"></i> Подтвердить
                                        </button>
                                        <button class="btn-small btn-danger" 
                                                onclick="updateTestDrive(<?= $drive['request_id'] ?>, 'Rejected')">
                                            <i class="fas fa-times"></i> Отклонить
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-requests">Нет новых заявок на тест-драйв.</p>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="orders-tab">
                    <h2>Новые заказы</h2>
                    <?php if (!empty($orders)): ?>
                        <table class="manager-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Клиент</th>
                                    <th>Автомобиль</th>
                                    <th>Цена</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                                    <td><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></td>
                                    <td><?= htmlspecialchars($order['make'].' '.$order['model']) ?></td>
                                    <td>$<?= number_format($order['price'], 2) ?></td>
                                    <td><span class="status-badge <?= strtolower($order['order_status']) ?>"><?= $order['order_status'] ?></span></td>
                                    <td>
                                        <button class="btn-small btn-success" 
                                                onclick="updateOrder(<?= $order['order_id'] ?>, 'processing')">
                                            <i class="fas fa-check"></i> В работу
                                        </button>
                                        <button class="btn-small btn-danger" 
                                                onclick="updateOrder(<?= $order['order_id'] ?>, 'cancelled')">
                                            <i class="fas fa-times"></i> Отменить
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-requests">Нет новых заказов.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($employee['department'] === 'Service'): ?>
                <div class="tab-content active" id="service-tab">
                    <h2>Новые сервисные заявки</h2>
                    <?php if (!empty($serviceRequests)): ?>
                        <table class="manager-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Клиент</th>
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
                                    <td><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></td>
                                    <td><?= htmlspecialchars($request['make'].' '.$request['model']) ?></td>
                                    <td><?= htmlspecialchars($request['repair_type']) ?></td>
                                    <td><span class="status-badge <?= strtolower(str_replace(' ', '-', $request['status'])) ?>"><?= $request['status'] ?></span></td>
                                    <td>
                                        <?php if ($request['employee_id'] === null): ?>
                                            <button class="btn-small btn-success" 
                                                    onclick="assignServiceRequest(<?= $request['request_id'] ?>)">
                                                <i class="fas fa-user-check"></i> Назначить себе
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-small btn-success" 
                                                onclick="updateServiceRequest(<?= $request['request_id'] ?>, 'In Progress')">
                                            <i class="fas fa-check"></i> В работу
                                        </button>
                                        <button class="btn-small btn-danger" 
                                                onclick="updateServiceRequest(<?= $request['request_id'] ?>, 'Rejected')">
                                            <i class="fas fa-times"></i> Отклонить
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="no-requests">Нет новых сервисных заявок.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="tab-content" id="active-tab">
                    <h2>Активные задачи</h2>
                    
                    <div class="active-tasks">
                        <?php if ($employee['department'] === 'Sales'): ?>
                        <div class="task-section">
                            <h3>Тест-драйвы</h3>
                            <?php if (!empty($activeRequests['test_drives'])): ?>
                                <table class="manager-table">
                                    <thead>
                                        <tr>
                                            <th>Дата/Время</th>
                                            <th>Клиент</th>
                                            <th>Автомобиль</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeRequests['test_drives'] as $drive): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($drive['preferred_date'])) ?> <?= $drive['preferred_time'] ?></td>
                                            <td><?= htmlspecialchars($drive['first_name'].' '.$drive['last_name']) ?></td>
                                            <td><?= htmlspecialchars($drive['make'].' '.$drive['model']) ?></td>
                                            <td><span class="status-badge <?= strtolower($drive['status']) ?>"><?= $drive['status'] ?></span></td>
                                            <td>
                                                <?php if ($drive['status'] === 'Pending'): ?>
                                                    <button class="btn-small btn-success" 
                                                            onclick="updateTestDrive(<?= $drive['request_id'] ?>, 'Confirmed')">
                                                        <i class="fas fa-check"></i> Подтвердить
                                                    </button>
                                                    <button class="btn-small btn-danger" 
                                                            onclick="updateTestDrive(<?= $drive['request_id'] ?>, 'Rejected')">
                                                        <i class="fas fa-times"></i> Отклонить
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-small btn-success" 
                                                        onclick="completeTestDrive(<?= $drive['request_id'] ?>)">
                                                    <i class="fas fa-check-circle"></i> Завершить
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-tasks">Нет активных тест-драйвов.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-section">
                            <h3>Заказы</h3>
                            <?php if (!empty($activeRequests['orders'])): ?>
                                <table class="manager-table">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Клиент</th>
                                            <th>Автомобиль</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeRequests['orders'] as $order): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                                            <td><?= htmlspecialchars($order['first_name'].' '.$order['last_name']) ?></td>
                                            <td><?= htmlspecialchars($order['make'].' '.$order['model']) ?></td>
                                            <td><span class="status-badge <?= strtolower($order['order_status']) ?>"><?= $order['order_status'] ?></span></td>
                                            <td>
                                                <button class="btn-small btn-success" 
                                                        onclick="completeOrder(<?= $order['order_id'] ?>)">
                                                    <i class="fas fa-check-circle"></i> Завершить
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-tasks">Нет активных заказов.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($employee['department'] === 'Service'): ?>
                        <div class="task-section">
                            <h3>Сервисные заявки</h3>
                            <?php if (!empty($activeRequests['services'])): ?>
                                <table class="manager-table">
                                    <thead>
                                        <tr>
                                            <th>Дата</th>
                                            <th>Клиент</th>
                                            <th>Автомобиль</th>
                                            <th>Тип работ</th>
                                            <th>Статус</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeRequests['services'] as $request): ?>
                                        <tr>
                                            <td><?= date('d.m.Y', strtotime($request['request_date'])) ?></td>
                                            <td><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></td>
                                            <td><?= htmlspecialchars($request['make'].' '.$request['model']) ?></td>
                                            <td><?= htmlspecialchars($request['repair_type']) ?></td>
                                            <td><span class="status-badge <?= strtolower(str_replace(' ', '-', $request['status'])) ?>"><?= $request['status'] ?></td>
                                            <td>
                                                <button class="btn-small btn-success" 
                                                        onclick="completeServiceRequest(<?= $request['request_id'] ?>)">
                                                    <i class="fas fa-check-circle"></i> Завершить
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="no-tasks">Нет активных сервисных заявок.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                  <div class="tab-content" id="profile-tab">
                    <div class="profile-container">
                        <div class="profile-info">
                            <h2>Профиль сотрудника</h2>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Имя:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['first_name']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Фамилия:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['last_name']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Отдел:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['department']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Должность:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['position']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['email']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Телефон:</span>
                                    <span class="info-value"><?= htmlspecialchars($employee['phone']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Дата приема:</span>
                                    <span class="info-value"><?= date('d.m.Y', strtotime($employee['hire_date'])) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Зарплата:</span>
                                    <span class="info-value">$<?= number_format($employee['salary'], 2) ?></span>
                                </div>
                            </div>
                            <button class="btn" onclick="openEditProfileModal()">
                                <i class="fas fa-edit"></i> Изменить профиль
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Модальное окно редактирования профиля -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editProfileModal')">×</span>
            <h2>Редактирование профиля</h2>
            <form id="editProfileForm" action="api/manager/update_profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-first-name">Имя*</label>
                        <input type="text" id="edit-first-name" name="first_name" value="<?= htmlspecialchars($employee['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-last-name">Фамилия*</label>
                        <input type="text" id="edit-last-name" name="last_name" value="<?= htmlspecialchars($employee['last_name']) ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-email">Email*</label>
                        <input type="email" id="edit-email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-phone">Телефон*</label>
                        <input type="tel" id="edit-phone" name="phone" value="<?= htmlspecialchars($employee['phone']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-password">Новый пароль (оставьте пустым, если не хотите менять)</label>
                    <input type="password" id="edit-password" name="password">
                </div>
                
                <div class="form-group">
                    <label for="edit-photo">Фото</label>
                    <input type="file" id="edit-photo" name="photo" accept="image/*">
                    <div id="photo-preview" class="photo-preview">
                        <?php if ($employee['photo']): ?>
                            <img src="images/employees/<?= htmlspecialchars($employee['photo']) ?>" alt="Текущее фото">
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
            </form>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
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
        
        function openEditProfileModal() {
            document.getElementById('editProfileModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
        }
        
        document.getElementById('edit-photo')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photo-preview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
        
        document.getElementById('editProfileForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Профиль успешно обновлен!');
                    closeModal('editProfileModal');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        });
        
        function assignTestDrive(requestId) {
            if (confirm('Вы уверены, что хотите назначить эту заявку себе?')) {
                fetch('api/manager/update_test_drive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=Pending&assign=true`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Заявка успешно назначена вам!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        function assignServiceRequest(requestId) {
            if (confirm('Вы уверены, что хотите назначить эту заявку себе?')) {
                fetch('api/manager/update_service_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=Pending&assign=true`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Заявка успешно назначена вам!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        function updateTestDrive(requestId, status) {
            if (confirm(`Вы уверены, что хотите ${status === 'Confirmed' ? 'подтвердить' : 'отклонить'} эту заявку?`)) {
                fetch('api/manager/update_test_drive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Статус заявки успешно обновлен!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        function completeTestDrive(requestId) {
            if (confirm('Вы уверены, что хотите завершить этот тест-драйв?')) {
                fetch('api/manager/update_test_drive.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=Completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Тест-драйв успешно завершен!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        function updateServiceRequest(requestId, status) {
            if (confirm(`Вы уверены, что хотите изменить статус заявки на "${status}"?`)) {
                fetch('api/manager/update_service_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Статус заявки успешно обновлен!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
        function completeServiceRequest(requestId) {
            if (confirm('Вы уверены, что хотите завершить эту сервисную заявку?')) {
                fetch('api/manager/update_service_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `request_id=${requestId}&status=Completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Сервисная заявка успешно завершена!');
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                });
            }
        }
        
function updateOrder(orderId, status) {
    const statusText = status === 'processing' ? 'взять в работу' : 'отменить';
    if (confirm(`Вы уверены, что хотите ${statusText} этот заказ?`)) {
        fetch('api/manager/update_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${status}`,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status}, text: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(`Заказ успешно ${status === 'processing' ? 'взят в работу' : 'отменен'}!`);
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при обновлении заказа:', error);
            alert('Произошла ошибка при обновлении заказа. Проверьте консоль для подробностей.');
        });
    }
}

function completeOrder(orderId) {
    if (confirm('Вы уверены, что хотите завершить этот заказ?')) {
        fetch('api/manager/update_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=completed`,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error(`HTTP error! status: ${response.status}, text: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Заказ успешно завершен!');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка при завершении заказа:', error);
            alert('Произошла ошибка при завершении заказа. Проверьте консоль для подробностей.');
        });
    }
}
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal(e.target.id);
            }
        });
    </script>
</body>
</html>