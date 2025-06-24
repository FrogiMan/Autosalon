<!-- Модальное окно добавления/редактирования автомобиля -->
<div id="editCarModal" class="modal">
    <div class="modal-content large">
        <span class="close" onclick="closeModal('editCarModal')">×</span>
        <h2 id="car-modal-title">Добавить автомобиль</h2>
        <form id="carForm" action="api/admin/<?= isset($_GET['car_id']) ? 'edit_car' : 'add_car' ?>.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="car-id" name="car_id" value="">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-make">Марка*</label>
                    <input type="text" id="car-make" name="make" required>
                </div>
                <div class="form-group">
                    <label for="car-model">Модель*</label>
                    <input type="text" id="car-model" name="model" required>
                </div>
                <div class="form-group">
                    <label for="car-year">Год*</label>
                    <input type="number" id="car-year" name="year" min="1900" max="<?= date('Y')+1 ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-price">Цена ($)*</label>
                    <input type="number" id="car-price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="car-mileage">Пробег (км)</label>
                    <input type="number" id="car-mileage" name="mileage" min="0">
                </div>
                <div class="form-group">
                    <label for="car-body-type">Тип кузова</label>
                    <select id="car-body-type" name="body_type">
                        <option value="sedan">Седан</option>
                        <option value="hatchback">Хэтчбек</option>
                        <option value="suv">Внедорожник</option>
                        <option value="coupe">Купе</option>
                        <option value="convertible">Кабриолет</option>
                        <option value="minivan">Минивэн</option>
                        <option value="pickup">Пикап</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="car-description">Описание</label>
                <textarea id="car-description" name="description" rows="4"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-status">Статус*</label>
                    <select id="car-status" name="status" required>
                        <option value="available">Доступен</option>
                        <option value="sold">Продан</option>
                        <option value="reserved">Зарезервирован</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="car-images">Изображения</label>
                    <input type="file" id="car-images" name="images[]" multiple accept="image/*">
                </div>
            </div>
            
            <div id="current-car-images" class="current-images">
                <!-- Текущие изображения будут загружены здесь -->
            </div>
            
            <h3>Характеристики</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-engine-type">Тип двигателя</label>
                    <input type="text" id="car-engine-type" name="engine_type">
                </div>
                <div class="form-group">
                    <label for="car-engine-volume">Объем двигателя (л)</label>
                    <input type="number" id="car-engine-volume" name="engine_volume" step="0.1" min="0">
                </div>
                <div class="form-group">
                    <label for="car-power">Мощность (л.с.)</label>
                    <input type="number" id="car-power" name="power" min="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-transmission">Коробка передач</label>
                    <select id="car-transmission" name="transmission">
                        <option value="automatic">Автоматическая</option>
                        <option value="manual">Механическая</option>
                        <option value="robot">Роботизированная</option>
                        <option value="variator">Вариатор</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="car-drive-type">Привод</label>
                    <select id="car-drive-type" name="drive_type">
                        <option value="front">Передний</option>
                        <option value="rear">Задний</option>
                        <option value="all">Полный</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="car-color">Цвет</label>
                    <input type="text" id="car-color" name="color">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="car-interior">Салон</label>
                    <input type="text" id="car-interior" name="interior">
                </div>
                <div class="form-group">
                    <label for="car-fuel-consumption">Расход топлива (л/100км)</label>
                    <input type="number" id="car-fuel-consumption" name="fuel_consumption" step="0.1" min="0">
                </div>
            </div>
            
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
</div>

<!-- Модальное окно добавления/редактирования сотрудника -->
<div id="editEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editEmployeeModal')">×</span>
        <h2 id="employee-modal-title">Добавить сотрудника</h2>
        <form id="employeeForm" action="api/admin/<?= isset($_GET['employee_id']) ? 'edit_employee' : 'add_employee' ?>.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="employee-id" name="employee_id" value="">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emp-first-name">Имя*</label>
                    <input type="text" id="emp-first-name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="emp-last-name">Фамилия*</label>
                    <input type="text" id="emp-last-name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emp-phone">Телефон*</label>
                    <input type="tel" id="emp-phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="emp-email">Email*</label>
                    <input type="email" id="emp-email" name="email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="emp-password"><?= isset($_GET['employee_id']) ? 'Новый пароль' : 'Пароль*' ?></label>
                <input type="password" id="emp-password" name="password" <?= !isset($_GET['employee_id']) ? 'required' : '' ?>>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emp-department">Отдел*</label>
                    <select id="emp-department" name="department" required>
                        <option value="Sales">Продажи</option>
                        <option value="Service">Сервис</option>
                        <option value="Administration">Администрация</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="emp-position">Должность*</label>
                    <input type="text" id="emp-position" name="position" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emp-salary">Зарплата ($)*</label>
                    <input type="number" id="emp-salary" name="salary" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="emp-photo">Фото</label>
                    <input type="file" id="emp-photo" name="photo" accept="image/*">
                </div>
            </div>
            
            <div class="form-group">
                <label for="emp-is-admin">Администратор</label>
                <input type="checkbox" id="emp-is-admin" name="is_admin" value="1">
            </div>
            
            <div id="employee-photo-preview" class="photo-preview">
                <!-- Превью фото будет загружено здесь -->
            </div>
            
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
</div>

<!-- Модальное окно редактирования клиента -->
<div id="editClientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editClientModal')">×</span>
        <h2 id="client-modal-title">Редактировать клиента</h2>
        <form id="editClientForm" action="api/admin/edit_client.php" method="POST">
            <input type="hidden" id="client-id" name="client_id" value="">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="client-first-name">Имя*</label>
                    <input type="text" id="client-first-name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="client-last-name">Фамилия*</label>
                    <input type="text" id="client-last-name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="client-phone">Телефон*</label>
                    <input type="tel" id="client-phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="client-email">Email*</label>
                    <input type="email" id="client-email" name="email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="client-password">Новый пароль</label>
                <input type="password" id="client-password" name="password">
            </div>
            
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
</div>

<!-- Модальное окно просмотра клиента -->
<div id="viewClientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('viewClientModal')">×</span>
        <h2>Информация о клиенте</h2>
        
        <div class="client-info">
            <div class="info-item">
                <span class="info-label">Имя:</span>
                <span class="info-value" id="client-view-first-name"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Фамилия:</span>
                <span class="info-value" id="client-view-last-name"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value" id="client-view-email"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Телефон:</span>
                <span class="info-value" id="client-view-phone"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Количество заказов:</span>
                <span class="info-value" id="client-view-orders-count"></span>
            </div>
        </div>
        
        <h3>Автомобили клиента</h3>
        <div id="client-cars-list" class="client-cars">
            <!-- Список автомобилей будет загружен здесь -->
        </div>
    </div>
</div>

<!-- Модальное окно добавления автомобиля клиенту -->
<div class="modal" id="addCarToClientModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('addCarToClientModal')">×</span>
        <h2 id="modal-title">Добавить автомобиль клиенту</h2>
        <form id="addCarToClientForm" action="/api/admin/add_car_to_client.php" method="POST">
            <input type="hidden" id="client-id-modal" name="client_id">
            <div class="form-group">
                <label for="car-to-add">Выберите автомобиль:</label>
                <select id="car-to-add" name="car_id" required>
                    <option value="">Выберите автомобиль</option>
                </select>
            </div>
            <div class="form-group">
                <label for="sale-price">Цена продажи ($):</label>
                <input type="number" id="sale-price" name="sale_price" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="payment-method">Способ оплаты:</label>
                <select id="payment-method" name="payment_method" required>
                    <option value="cash">Наличные</option>
                    <option value="credit">Кредит</option>
                    <option value="lease">Лизинг</option>
                    <option value="online">Онлайн</option>
                </select>
            </div>
            <div class="form-group">
                <label for="order-type">Тип заказа:</label>
                <select id="order-type" name="order_type" required>
                    <option value="purchase">Покупка</option>
                    <option value="test_drive">Тест-драйв</option>
                    <option value="service">Сервис</option>
                </select>
            </div>
            <div class="form-group">
                <label for="order-status">Статус заказа:</label>
                <select id="order-status" name="order_status" required>
                    <option value="pending">Ожидание</option>
                    <option value="completed">Завершено</option>
                    <option value="cancelled">Отменено</option>
                </select>
            </div>
            <div class="form-group">
                <label for="test-drive-id">Связанный тест-драйв (если есть):</label>
                <select id="test-drive-id" name="test_drive_id">
                    <option value="">Не связан с тест-драйвом</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Добавить</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCarToClientModal')">Отмена</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for Editing Order -->
<div class="modal" id="editOrderModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal('editOrderModal')">×</span>
        <h2 id="modal-title">Редактировать заказ</h2>
        <form id="editOrderForm" action="/api/admin/update_order.php" method="POST">
            <input type="hidden" id="edit-order-id" name="order_id">
            <div class="form-group">
                <label for="edit-order-client-id">Клиент ID:</label>
                <input type="number" id="edit-order-client-id" name="client_id" readonly>
            </div>
            <div class="form-group">
                <label for="edit-order-car-id">Автомобиль ID:</label>
                <input type="number" id="edit-order-car-id" name="car_id" readonly>
            </div>
            <div class="form-group">
                <label for="edit-order-date">Дата заказа:</label>
                <input type="date" id="edit-order-date" name="order_date" required>
            </div>
            <div class="form-group">
                <label for="edit-order-type">Тип заказа:</label>
                <select id="edit-order-type" name="order_type" required>
                    <option value="purchase">Покупка</option>
                    <option value="test_drive">Тест-драйв</option>
                    <option value="service">Сервис</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-order-status">Статус:</label>
                <select id="edit-order-status" name="order_status" required>
                    <option value="pending">Ожидание</option>
                    <option value="completed">Завершено</option>
                    <option value="cancelled">Отменено</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-sale-price">Цена продажи ($):</label>
                <input type="number" id="edit-sale-price" name="sale_price" step="0.01" min="0" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editOrderModal')">Отмена</button>
            </div>
        </form>
    </div>
</div>

<!-- Модальное окно деталей заказа -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('orderDetailsModal')">×</span>
        <h2>Детали заказа #<span id="order-details-id"></span></h2>
        
        <div class="order-info">
            <div class="info-item">
                <span class="info-label">Дата:</span>
                <span class="info-value" id="order-details-date"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Клиент:</span>
                <span class="info-value" id="order-details-client"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Автомобиль:</span>
                <span class="info-value" id="order-details-car"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Статус:</span>
                <span class="info-value" id="order-details-status"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Тип:</span>
                <span class="info-value" id="order-details-type"></span>
            </div>
            <div class="info-item">
                <span class="info-label">Сумма:</span>
                <span class="info-value" id="order-details-price"></span>
            </div>
        </div>
        
        <div class="order-actions">
            <button class="btn" onclick="printOrder()">
                <i class="fas fa-print"></i> Печать
            </button>
            <button class="btn btn-outline" onclick="closeModal('orderDetailsModal')">
                Закрыть
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения действия -->
<div id="confirmDialog" class="modal">
    <div class="modal-content small">
        <h2 id="confirm-title">Подтверждение</h2>
        <p id="confirm-message">Вы уверены, что хотите выполнить это действие?</p>
        
        <div class="dialog-buttons">
            <button class="btn btn-danger" id="confirm-action">Подтвердить</button>
            <button class="btn btn-outline" onclick="closeModal('confirmDialog')">Отмена</button>
        </div>
    </div>
</div>