// assets/js/admin.js
// Инициализация панели администратора
document.addEventListener('DOMContentLoaded', function() {
    initAdminTabs();
    initSearch('#car-search', '#cars-table tbody tr');
    initSearch('#employee-search', '#employees-table tbody tr');
    initSearch('#client-search', '#clients-table tbody tr');
    initSearch('#order-search', '#orders-table tbody tr');
    loadBackupList();
    initOrderFilters();
    initCarStatusFilter();
});

// Функции для работы с вкладками
function initAdminTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(tabId + '-tab').classList.add('active');
            if (tabId === 'orders') {
                refreshOrdersTable();
            }
        });
    });
}

// Функции для работы с поиском
function initSearch(inputSelector, rowSelector) {
    const searchInput = document.querySelector(inputSelector);
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll(rowSelector);
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
}

// Функции для фильтра по статусу автомобилей
function initCarStatusFilter() {
    const statusFilter = document.getElementById('car-status-filter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('#cars-table tbody tr');
            rows.forEach(row => {
                const rowStatus = status === '' || row.querySelector('.status-badge').className.includes(status);
                row.style.display = rowStatus ? '' : 'none';
            });
        });
    }
}

// Функции для работы с фильтрами заказов
function initOrderFilters() {
    const statusFilter = document.getElementById('order-status-filter');
    const dateFilter = document.getElementById('order-date-filter');
    if (statusFilter) statusFilter.addEventListener('change', applyOrderFilters);
    if (dateFilter) dateFilter.addEventListener('change', applyOrderFilters);
}

function applyOrderFilters() {
    const status = document.getElementById('order-status-filter').value;
    const date = document.getElementById('order-date-filter').value;
    const rows = document.querySelectorAll('#orders-table tbody tr');
    rows.forEach(row => {
        const rowStatus = status === '' || row.querySelector('.status-badge').className.includes(status);
        const rowDate = date === '' || row.cells[1].textContent.includes(date.split('-').reverse().join('.'));
        row.style.display = rowStatus && rowDate ? '' : 'none';
    });
}

function resetOrderFilters() {
    document.getElementById('order-status-filter').value = '';
    document.getElementById('order-date-filter').value = '';
    applyOrderFilters();
}

// Функции для работы с автомобилями
function openAddCarModal() {
    document.getElementById('car-modal-title').textContent = 'Добавить автомобиль';
    document.getElementById('car-id').value = '';
    document.getElementById('carForm').reset();
    document.getElementById('current-car-images').innerHTML = '';
    document.getElementById('car-images-preview').innerHTML = ''; // Очищаем превью
    openModal('editCarModal');
}

function viewCar(carId) {
    window.open(`car_details.php?car_id=${carId}`, '_blank');
}

function editCar(carId) {
    fetch(`api/admin/get_car.php?car_id=${carId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check if elements exist before setting values
                const setValue = (id, value) => {
                    const el = document.getElementById(id);
                    if (el) el.value = value || '';
                };

                setValue('car-id', data.car.car_id);
                setValue('car-make', data.car.make);
                setValue('car-model', data.car.model);
                setValue('car-year', data.car.year);
                setValue('car-price', data.car.price);
                setValue('car-mileage', data.car.mileage);
                setValue('car-body-type', data.car.body_type);
                setValue('car-description', data.car.description);
                setValue('car-status', data.car.status);

                if (data.features) {
                    setValue('car-engine-type', data.features.engine_type);
                    setValue('car-engine-volume', data.features.engine_volume);
                    setValue('car-power', data.features.power);
                    setValue('car-transmission', data.features.transmission);
                    setValue('car-drive-type', data.features.drive_type);
                    setValue('car-color', data.features.color);
                    setValue('car-interior', data.features.interior);
                    setValue('car-fuel-consumption', data.features.fuel_consumption);
                }

                const imagesContainer = document.getElementById('current-car-images');
                if (imagesContainer) {
                    imagesContainer.innerHTML = '';
                    if (data.images && data.images.length > 0) {
                        data.images.forEach(image => {
                            const imgElement = document.createElement('div');
                            imgElement.className = 'car-image-thumbnail';
                            imgElement.innerHTML = `
                                <img src="images/cars/${image.image_path}">
                                <button type="button" class="btn-small btn-danger" onclick="deleteCarImage(${image.image_id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                ${image.is_main ? '<span class="main-image-badge">Main</span>' : ''}
                            `;
                            imagesContainer.appendChild(imgElement);
                        });
                    }
                }

                const previewContainer = document.getElementById('car-images-preview');
                if (previewContainer) previewContainer.innerHTML = '';
                
                document.getElementById('car-modal-title').textContent = 'Редактировать автомобиль';
                openModal('editCarModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching car data:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные автомобиля', 'error');
        });
}

function deleteCarImage(imageId) {
    showConfirmDialog(
        'Удаление изображения',
        'Вы уверены, что хотите удалить это изображение?',
        'Удалить',
        'Отмена',
        () => {
            fetch(`api/admin/delete_car_image.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `image_id=${imageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Успех', 'Изображение удалено', 'success');
                    const carId = document.getElementById('car-id').value;
                    if (carId) editCar(carId);
                } else {
                    showAlert('Ошибка', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting image:', error);
                showAlert('Ошибка', 'Не удалось удалить изображение', 'error');
            });
        }
    );
}

function confirmDeleteCar(carId) {
    showConfirmDialog(
        'Удаление автомобиля',
        'Вы уверены, что хотите удалить этот автомобиль? Это действие нельзя отменить.',
        'Удалить',
        'Отмена',
        () => deleteCar(carId)
    );
}

function deleteCar(carId) {
    fetch(`api/admin/delete_car.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `car_id=${carId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Автомобиль успешно удален', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting car:', error);
        showAlert('Ошибка', 'Не удалось удалить автомобиль', 'error');
    });
}

// Функции для работы с сотрудниками
function openAddEmployeeModal() {
    document.getElementById('employee-modal-title').textContent = 'Добавить сотрудника';
    document.getElementById('employee-id').value = '';
    document.getElementById('employeeForm').reset();
    document.getElementById('employee-photo-preview').innerHTML = '';
    openModal('editEmployeeModal');
}

function editEmployee(employeeId) {
    fetch(`api/admin/get_employee.php?employee_id=${employeeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('employee-id').value = data.employee.employee_id;
                document.getElementById('emp-first-name').value = data.employee.first_name;
                document.getElementById('emp-last-name').value = data.employee.last_name;
                document.getElementById('emp-phone').value = data.employee.phone;
                document.getElementById('emp-email').value = data.employee.email;
                document.getElementById('emp-department').value = data.employee.department;
                document.getElementById('emp-position').value = data.employee.position;
                document.getElementById('emp-salary').value = data.employee.salary;
                document.getElementById('emp-is-admin').checked = data.employee.is_admin == 1;
                const photoPreview = document.getElementById('employee-photo-preview');
                if (data.employee.photo && data.employee.photo !== 'default.jpg') {
                    photoPreview.innerHTML = `<img src="images/employees/${data.employee.photo}" alt="Employee photo" style="max-width: 200px; max-height: 200px;">`;
                    photoPreview.style.display = 'block';
                } else {
                    photoPreview.innerHTML = '';
                    photoPreview.style.display = 'none';
                }
                document.getElementById('employee-modal-title').textContent = 'Редактировать сотрудника';
                openModal('editEmployeeModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching employee:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные сотрудника', 'error');
        });
}

function confirmDeleteEmployee(employeeId) {
    showConfirmDialog(
        'Удаление сотрудника',
        'Вы уверены, что хотите удалить этого сотрудника? Это действие нельзя отменить.',
        'Удалить',
        'Отмена',
        () => deleteEmployee(employeeId)
    );
}

function deleteEmployee(employeeId) {
    fetch(`api/admin/delete_employee.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `employee_id=${employeeId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Сотрудник успешно удален', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting employee:', error);
        showAlert('Ошибка', 'Не удалось удалить сотрудника', 'error');
    });
}

function promoteToAdmin(employeeId) {
    showConfirmDialog(
        'Назначение администратором',
        'Вы уверены, что хотите назначить этого сотрудника администратором?',
        'Назначить',
        'Отмена',
        () => {
            fetch(`api/admin/promote_to_admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${employeeId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Успех', 'Сотрудник теперь администратор', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Ошибка', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error promoting employee:', error);
                showAlert('Ошибка', 'Не удалось назначить администратором', 'error');
            });
        }
    );
}

function demoteFromAdmin(employeeId) {
    showConfirmDialog(
        'Снятие прав администратора',
        'Вы уверены, что хотите снять права администратора с этого сотрудника?',
        'Снять',
        'Отмена',
        () => {
            fetch(`api/admin/demote_from_admin.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${employeeId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Успех', 'Права администратора сняты', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Ошибка', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error demoting employee:', error);
                showAlert('Ошибка', 'Не удалось снять права администратора', 'error');
            });
        }
    );
}

// Функции для работы с клиентами
function viewClient(clientId) {
    fetch(`api/admin/get_client.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('client-view-first-name').textContent = data.client.first_name;
                document.getElementById('client-view-last-name').textContent = data.client.last_name;
                document.getElementById('client-view-email').textContent = data.client.email;
                document.getElementById('client-view-phone').textContent = data.client.phone;
                document.getElementById('client-view-orders-count').textContent = data.orders_count;
                const carsList = document.getElementById('client-cars-list');
                if (carsList) {
                    carsList.innerHTML = '';
                    if (data.cars && data.cars.length > 0) {
                        data.cars.forEach(car => {
                            const carItem = document.createElement('div');
                            carItem.className = 'client-car-item';
                            const price = car.price ? `$${parseFloat(car.price).toFixed(2)}` : 'Цена не указана';
                            carItem.innerHTML = `
                                <div class="car-info">
                                    <strong>${car.make} ${car.model} (${car.year})</strong>
                                    <span>Цена: ${price}</span>
                                    <span>Статус: ${car.status}</span>
                                </div>
                            `;
                            carsList.appendChild(carItem);
                        });
                    } else {
                        carsList.innerHTML = '<p>У клиента нет автомобилей</p>';
                    }
                }
                
                openModal('viewClientModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching client:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные клиента', 'error');
        });
}

function editClient(clientId) {
    fetch(`api/admin/get_client.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('client-id').value = data.client.client_id;
                document.getElementById('client-first-name').value = data.client.first_name;
                document.getElementById('client-last-name').value = data.client.last_name;
                document.getElementById('client-email').value = data.client.email;
                document.getElementById('client-phone').value = data.client.phone;
                document.getElementById('client-password').value = ''; // Пароль не заполняем
                openModal('editClientModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching client:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные клиента', 'error');
        });
}

function confirmDeleteClient(clientId) {
    showConfirmDialog(
        'Удаление клиента',
        'Вы уверены, что хотите удалить этого клиента? Это действие нельзя отменить.',
        'Удалить',
        'Отмена',
        () => deleteClient(clientId)
    );
}

function deleteClient(clientId) {
    fetch(`api/admin/delete_client.php`, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `client_id=${clientId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Клиент успешно удален', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting client:', error);
        showAlert('Ошибка', 'Не удалось удалить клиента', 'error');
    });
}

function addCarToClient(clientId) {
    openModal('addCarToClientModal');
    document.getElementById('client-id-modal').value = clientId;
    
    // Загрузка доступных автомобилей
    fetch('api/admin/get_available_cars.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('car-to-add');
                select.innerHTML = '<option value="">Выберите автомобиль</option>';
                data.cars.forEach(car => {
                    const option = document.createElement('option');
                    option.value = car.car_id;
                    option.textContent = `${car.make} ${car.model} (${car.year}) - $${car.price.toFixed(2)}`;
                    option.dataset.price = car.price;
                    select.appendChild(option);
                });
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const salePriceInput = document.getElementById('sale-price');
                    if (selectedOption.value) {
                        salePriceInput.value = parseFloat(selectedOption.dataset.price).toFixed(2);
                    } else {
                        salePriceInput.value = '';
                    }
                });
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching available cars:', error);
            showAlert('Ошибка', 'Не удалось загрузить доступные автомобили', 'error');
        });
    
    // Загрузка тест-драйвов клиента
    fetch(`api/admin/get_client_test_drives.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('test-drive-id');
                select.innerHTML = '<option value="">Не связан с тест-драйвом</option>';
                data.test_drives?.forEach(td => {
                    if (td.status === 'Completed') {
                        const option = document.createElement('option');
                        option.value = td.request_id;
                        option.textContent = `Тест-драйв #${td.request_id} (${td.make} ${td.model}, ${td.preferred_date})`;
                        select.appendChild(option);
                    }
                });
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching test drives:', error);
            showAlert('Ошибка', 'Не удалось загрузить тест-драйвы клиента', 'error');
        });
}

// Функции для работы с заказами
document.getElementById('editOrderForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Заказ успешно обновлен!', 'success');
            closeModal('editOrderModal');
            refreshOrdersTable();
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating order:', error);
        showAlert('Ошибка', 'Не удалось обновить заказ', 'error');
    });
});

function viewOrderDetails(orderId) {
    fetch(`api/admin/get_order.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('order-details-id').textContent = data.order.order_id;
                document.getElementById('order-details-date').textContent = data.order.order_date;
                document.getElementById('order-details-client').textContent = 
                    `${data.client.first_name} ${data.client.last_name}`;
                document.getElementById('order-details-car').textContent = 
                    `${data.car.make} ${data.car.model} (${data.car.year})`;
                document.getElementById('order-details-status').textContent = data.order.order_status;
                document.getElementById('order-details-type').textContent = data.order.order_type;
                document.getElementById('order-details-price').textContent = 
                    `$${data.sale_price ? data.sale_price.toFixed(2) : data.car.price.toFixed(2)}`;
                openModal('orderDetailsModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching order:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные заказа', 'error');
        });
}

function editOrder(orderId) {
    fetch(`api/admin/get_order.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit-order-id').value = data.order.order_id;
                document.getElementById('edit-order-client-id').value = data.order.client_id;
                document.getElementById('edit-order-car-id').value = data.order.car_id;
                document.getElementById('edit-order-date').value = data.order.order_date.split(' ')[0];
                document.getElementById('edit-order-type').value = data.order.order_type;
                document.getElementById('edit-order-status').value = data.order.order_status;
                document.getElementById('edit-sale-price').value = data.sale_price || data.car.price;
                openModal('editOrderModal');
            } else {
                showAlert('Ошибка', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching order for edit:', error);
            showAlert('Ошибка', 'Не удалось загрузить данные для редактирования заказа', 'error');
        });
}

function completeOrder(orderId) {
    updateOrderStatus(orderId, 'completed');
}

function cancelOrder(orderId) {
    updateOrderStatus(orderId, 'cancelled');
}

function updateOrderStatus(orderId, status) {
    showConfirmDialog(
        `Подтверждение ${status === 'completed' ? 'завершения' : 'отмены'} заказа`,
        `Вы уверены, что хотите ${status === 'completed' ? 'завершить' : 'отменить'} этот заказ?`,
        status === 'completed' ? 'Завершить' : 'Отменить',
        'Отмена',
        () => {
            fetch(`api/admin/update_order_status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `order_id=${orderId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Успех', `Заказ ${status === 'completed' ? 'завершен' : 'отменен'}`, 'success');
                    refreshOrdersTable();
                } else {
                    showAlert('Ошибка', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating order status:', error);
                showAlert('Ошибка', 'Не удалось обновить статус заказа', 'error');
            });
        }
    );
}

function refreshOrdersTable() {
    fetch('api/admin/get_orders.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.querySelector('#orders-table tbody');
                tbody.innerHTML = '';
                data.orders.forEach(order => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${order.order_id}</td>
                        <td>${order.order_date}</td>
                        <td>${order.first_name} ${order.last_name}</td>
                        <td>${order.make} ${order.model}</td>
                        <td>${order.order_type}</td>
                        <td><span class="status-badge ${order.order_status}">${order.order_status}</span></td>
                        <td>$${order.sale_price ? order.sale_price.toFixed(2) : order.price.toFixed(2)}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-small btn-view" onclick="viewOrderDetails(${order.order_id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-small btn-edit" onclick="editOrder(${order.order_id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                ${order.order_status === 'pending' ? `
                                <button class="btn-small btn-success" onclick="completeOrder(${order.order_id})">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-small btn-danger" onclick="cancelOrder(${order.order_id})">
                                    <i class="fas fa-times"></i>
                                </button>
                                ` : ''}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => {
            console.error('Error refreshing orders table:', error);
            showAlert('Ошибка', 'Не удалось обновить таблицу заказов', 'error');
        });
}

function printOrder() {
    const printContent = document.getElementById('orderDetailsModal').innerHTML;
    const originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    openModal('orderDetailsModal');
}

// Функции для работы с настройками
function createBackup() {
    showConfirmDialog(
        'Создание резервной копии',
        'Вы уверены, что хотите создать резервную копию базы данных?',
        'Создать',
        'Отмена',
        () => {
            fetch('api/admin/create_backup.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Успех', 'Резервная копия успешно создана', 'success');
                        loadBackupList();
                    } else {
                        showAlert('Ошибка', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error creating backup:', error);
                    showAlert('Ошибка', 'Не удалось создать резервную копию', 'error');
                });
        }
    );
}

function restoreBackup() {
    const backupSelect = document.getElementById('backup-select');
    if (!backupSelect || backupSelect.options.length === 0) {
        showAlert('Ошибка', 'Нет доступных резервных копий', 'error');
        return;
    }
    const backupFile = backupSelect.value;
    showConfirmDialog(
        'Восстановление из резервной копии',
        `Вы уверены, что хотите восстановить базу данных из копии ${backupFile}? Все текущие данные будут перезаписаны.`,
        'Восстановить',
        'Отмена',
        () => {
            fetch('api/admin/restore_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `backup_file=${encodeURIComponent(backupFile)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Успех', 'База данных успешно восстановлена', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('Ошибка', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error restoring backup:', error);
                showAlert('Ошибка', 'Не удалось восстановить базу данных', 'error');
            });
        }
    );
}

function loadBackupList() {
    fetch('api/admin/get_backups.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const backupList = document.getElementById('backup-list');
                backupList.innerHTML = '';
                if (data.backups.length > 0) {
                    const select = document.createElement('select');
                    select.id = 'backup-select';
                    select.className = 'form-control';
                    data.backups.forEach(backup => {
                        const option = document.createElement('option');
                        option.value = backup;
                        option.textContent = backup;
                        select.appendChild(option);
                    });
                    backupList.appendChild(select);
                } else {
                    backupList.innerHTML = '<p>Нет доступных резервных копий</p>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading backups:', error);
            showAlert('Ошибка', 'Не удалось загрузить список резервных копий', 'error');
        });
}

function downloadLogs() {
    window.open('api/admin/download_logs.php', '_blank');
}

function clearLogs() {
    showConfirmDialog(
        'Очистка логов',
        'Вы уверены, что хотите очистить все логи системы?',
        'Очистить',
        'Отмена',
        () => {
            fetch('api/admin/clear_logs.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Успех', 'Логи успешно очищены', 'success');
                        document.querySelector('.log-viewer pre').textContent = '';
                    } else {
                        showAlert('Ошибка', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error clearing logs:', error);
                    showAlert('Ошибка', 'Не удалось очистить логи', 'error');
                });
        }
    );
}

// Обработка формы автомобиля
document.getElementById('carForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const carId = document.getElementById('car-id').value;
    fetch(`/api/admin/${carId ? 'edit_car' : 'add_car'}.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Автомобиль успешно сохранен!', 'success');
            closeModal('editCarModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting car form:', error);
        showAlert('Ошибка', 'Не удалось сохранить автомобиль', 'error');
    });
});

// Превью загружаемых изображений автомобиля
document.getElementById('car-images')?.addEventListener('change', function(e) {
    const files = e.target.files;
    const previewContainer = document.getElementById('car-images-preview');
    previewContainer.innerHTML = '';
    if (files.length > 0) {
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgElement = document.createElement('div');
                imgElement.className = 'car-image-thumbnail';
                imgElement.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px;">`;
                previewContainer.appendChild(imgElement);
            };
            reader.readAsDataURL(file);
        });
        previewContainer.style.display = 'block';
    } else {
        previewContainer.style.display = 'none';
    }
});

// Обработка формы сотрудника
document.getElementById('employeeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const employeeId = document.getElementById('employee-id').value;
    fetch(`/api/admin/${employeeId ? 'edit_employee' : 'add_employee'}.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Сотрудник успешно сохранен!', 'success');
            closeModal('editEmployeeModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting employee form:', error);
        showAlert('Ошибка', 'Не удалось сохранить сотрудника', 'error');
    });
});

// Обработка формы клиента
document.getElementById('editClientForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/api/admin/edit_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Клиент успешно обновлен!', 'success');
            closeModal('editClientModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting client form:', error);
        showAlert('Ошибка', 'Не удалось обновить клиента', 'error');
    });
});

// Обработка формы добавления автомобиля клиенту
document.getElementById('addCarToClientForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/api/admin/add_car_to_client.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Автомобиль успешно добавлен клиенту!', 'success');
            closeModal('addCarToClientModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting car to client form:', error);
        showAlert('Ошибка', 'Не удалось добавить автомобиль клиенту', 'error');
    });
});

// Обработка формы настроек
document.getElementById('system-settings-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/api/admin/update_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Успех', 'Настройки успешно сохранены!', 'success');
        } else {
            showAlert('Ошибка', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting settings form:', error);
        showAlert('Ошибка', 'Не удалось сохранить настройки', 'error');
    });
});

// Превью фото сотрудника перед загрузкой
document.getElementById('emp-photo')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('employee-photo-preview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Инициализация datepicker для даты заказа
document.getElementById('edit-order-date')?.addEventListener('focus', function() {
    this.type = 'date';
});

// Закрытие модальных окон при клике вне контента
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// Общие функции
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function showAlert(title, message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <h3>${title}</h3>
        <p>${message}</p>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}

function showConfirmDialog(title, message, confirmText, cancelText, confirmCallback) {
    const dialog = document.getElementById('confirmDialog');
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    const confirmButton = document.getElementById('confirm-action');
    confirmButton.textContent = confirmText;
    confirmButton.onclick = () => {
        closeModal('confirmDialog');
        confirmCallback();
    };
    openModal('confirmDialog');
}