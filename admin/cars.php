<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

if (!is_authenticated() || !is_admin_or_manager($_SESSION['user_role'])) {
    $_SESSION['message'] = 'Войдите с правами администратора или менеджера.';
    header('Location: /login.php');
    exit;
}

$brands = get_brands($conn);
$categories = get_categories($conn);
$error = '';
$success = '';
$car = null;
$items_per_page = 10;

// Load car data for editing
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $sql = "SELECT c.*, cs.engine, cs.horsepower, cs.transmission, cs.fuel_type 
            FROM cars c 
            LEFT JOIN car_specifications cs ON c.id = cs.car_id 
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error = 'Ошибка подготовки запроса: ' . $conn->error;
    } else {
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $car = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $brand_id = (int)$_POST['brand_id'];
    $category_id = (int)$_POST['category_id'];
    $model = sanitize_input($_POST['model']);
    $year = (int)$_POST['year'];
    $price = (float)$_POST['price'];
    $description = sanitize_input($_POST['description']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $engine = sanitize_input($_POST['engine']);
    $horsepower = (int)$_POST['horsepower'];
    $transmission = sanitize_input($_POST['transmission']);
    $fuel_type = sanitize_input($_POST['fuel_type']);

    // Validation
    if ($brand_id && $category_id && $model && $year >= 2000 && $year <= date('Y') && $price > 0) {
        // Handle image upload
        $image = $id && $car ? ($car['image'] ?? '') : '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = upload_image($_FILES['image']);
            if (!$upload_result['success']) {
                $error = 'Ошибка загрузки изображения: ' . $upload_result['error'];
            } else {
                $image = $upload_result['filename'];
            }
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = 'Ошибка при выборе файла: код ' . $_FILES['image']['error'];
        }

        if (!$error) {
            if ($id) {
                // Update car
                $sql = "UPDATE cars SET brand_id = ?, category_id = ?, model = ?, year = ?, price = ?, description = ?, is_available = ?" . ($image ? ", image = ?" : "") . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = 'Ошибка подготовки запроса: ' . $conn->error;
                } else {
                    if ($image) {
                        $stmt->bind_param('iisidissi', $brand_id, $category_id, $model, $year, $price, $description, $is_available, $image, $id);
                    } else {
                        $stmt->bind_param('iisidisi', $brand_id, $category_id, $model, $year, $price, $description, $is_available, $id);
                    }
                    if ($stmt->execute()) {
                        // Update specifications
                        $sql_spec = "INSERT INTO car_specifications (car_id, engine, horsepower, transmission, fuel_type) 
                                     VALUES (?, ?, ?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE engine = ?, horsepower = ?, transmission = ?, fuel_type = ?";
                        $stmt_spec = $conn->prepare($sql_spec);
                        if ($stmt_spec === false) {
                            $error = 'Ошибка подготовки запроса: ' . $conn->error;
                        } else {
                            $stmt_spec->bind_param('isisisiss', $id, $engine, $horsepower, $transmission, $fuel_type, $engine, $horsepower, $transmission, $fuel_type);
                            if ($stmt_spec->execute()) {
                                $success = 'Автомобиль обновлен.';
                            } else {
                                $error = 'Ошибка при обновлении характеристик: ' . $conn->error;
                            }
                            $stmt_spec->close();
                        }
                    } else {
                        $error = 'Ошибка при обновлении автомобиля: ' . $conn->error;
                    }
                    $stmt->close();
                }
            } else {
                // Add car
                $sql = "INSERT INTO cars (brand_id, category_id, model, year, price, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $error = 'Ошибка подготовки запроса: ' . $conn->error;
                } else {
                    $stmt->bind_param('iisidisi', $brand_id, $category_id, $model, $year, $price, $description, $image, $is_available);
                    if ($stmt->execute()) {
                        $car_id = $conn->insert_id;
                        // Add specifications
                        $sql_spec = "INSERT INTO car_specifications (car_id, engine, horsepower, transmission, fuel_type) VALUES (?, ?, ?, ?, ?)";
                        $stmt_spec = $conn->prepare($sql_spec);
                        if ($stmt_spec === false) {
                            $error = 'Ошибка подготовки запроса: ' . $conn->error;
                        } else {
                            $stmt_spec->bind_param('isisi', $car_id, $engine, $horsepower, $transmission, $fuel_type);
                            if ($stmt_spec->execute()) {
                                $success = 'Автомобиль добавлен.';
                            } else {
                                $error = 'Ошибка при добавлении характеристик: ' . $conn->error;
                            }
                            $stmt_spec->close();
                        }
                    } else {
                        $error = 'Ошибка при добавлении автомобиля: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    } else {
        $error = 'Заполните все обязательные поля корректно.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $sql = "DELETE FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error = 'Ошибка подготовки запроса: ' . $conn->error;
    } else {
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $sql_spec = "DELETE FROM car_specifications WHERE car_id = ?";
            $stmt_spec = $conn->prepare($sql_spec);
            if ($stmt_spec === false) {
                $error = 'Ошибка подготовки запроса: ' . $conn->error;
            } else {
                $stmt_spec->bind_param('i', $id);
                $stmt_spec->execute();
                $stmt_spec->close();
                $success = 'Автомобиль удален.';
            }
        } else {
            $error = 'Ошибка при удалении автомобиля: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Generate pagination
$pagination = generate_pagination($conn, 'cars', 'cars.php', $items_per_page);
$offset = $pagination['offset'];

// Fetch cars with pagination
$sql = "SELECT c.id, c.model, c.year, c.price, c.image, c.is_available, b.name AS brand_name, cat.name AS category_name 
        FROM cars c 
        JOIN brands b ON c.brand_id = b.id 
        JOIN categories cat ON c.category_id = cat.id 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = 'Ошибка подготовки запроса: ' . $conn->error;
} else {
    $stmt->bind_param('ii', $items_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<?php include '../includes/header.php'; ?>

<main>
    <div class="container">
        <h2>Управление автомобилями</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        
        <h3><?php echo $car ? 'Редактировать автомобиль' : 'Добавить автомобиль'; ?></h3>
        <form method="POST" action="cars.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $car['id'] ?? 0; ?>">
            <label for="brand_id">Бренд:</label>
            <select id="brand_id" name="brand_id" required>
                <option value="">Выберите бренд</option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo $brand['id']; ?>" <?php echo ($car && $car['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="category_id">Категория:</label>
            <select id="category_id" name="category_id" required>
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($car && $car['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="model">Модель:</label>
            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($car['model'] ?? ''); ?>" required>
            <label for="year">Год:</label>
            <input type="number" id="year" name="year" min="2000" max="<?php echo date('Y'); ?>" value="<?php echo $car['year'] ?? ''; ?>" required>
            <label for="price">Цена (₽):</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo $car['price'] ?? ''; ?>" required>
            <label for="description">Описание:</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($car['description'] ?? ''); ?></textarea>
            <label for="image">Изображение:</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
            <?php if ($car && $car['image']): ?>
                <p>Текущее изображение: <img src="/uploads/<?php echo htmlspecialchars($car['image']); ?>" alt="Car" width="50"></p>
            <?php endif; ?>
            <label for="is_available">Доступен:</label>
            <input type="checkbox" id="is_available" name="is_available" <?php echo ($car && $car['is_available']) ? 'checked' : ''; ?>>
            <h4>Характеристики</h4>
            <label for="engine">Двигатель:</label>
            <input type="text" id="engine" name="engine" value="<?php echo htmlspecialchars($car['engine'] ?? ''); ?>">
            <label for="horsepower">Мощность (л.с.):</label>
            <input type="number" id="horsepower" name="horsepower" value="<?php echo $car['horsepower'] ?? ''; ?>">
            <label for="transmission">Трансмиссия:</label>
            <input type="text" id="transmission" name="transmission" value="<?php echo htmlspecialchars($car['transmission'] ?? ''); ?>">
            <label for="fuel_type">Тип топлива:</label>
            <input type="text" id="fuel_type" name="fuel_type" value="<?php echo htmlspecialchars($car['fuel_type'] ?? ''); ?>">
            <button type="submit" class="btn">Сохранить</button>
        </form>
        
        <h3>Список автомобилей</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Бренд</th>
                    <th>Модель</th>
                    <th>Категория</th>
                    <th>Год</th>
                    <th>Цена</th>
                    <th>Изображение</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['model']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><?php echo format_price($row['price']); ?></td>
                            <td><?php echo $row['image'] ? '<img src="/Uploads/' . htmlspecialchars($row['image']) . '" alt="Car" width="50">' : 'Нет'; ?></td>
                            <td><?php echo $row['is_available'] ? 'Доступен' : 'Недоступен'; ?></td>
                            <td>
                                <a href="cars.php?edit=<?php echo $row['id']; ?>" class="btn">Редактировать</a>
                                <a href="cars.php?delete=<?php echo $row['id']; ?>" class="btn" onclick="return confirm('Вы уверены?');">Удалить</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">Автомобили не найдены.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php echo $pagination['html']; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<?php $conn->close(); ?>