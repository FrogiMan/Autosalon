<?php
// Защита от XSS
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Валидация email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Валидация телефона
function validate_phone($phone) {
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phone);
}

// Валидация имени
function validate_name($name) {
    return preg_match('/^[a-zA-Zа-яА-Я\s]{2,100}$/u', $name);
}

// Проверка админа/менеджера
function is_admin_or_manager($role) {
    return in_array($role, ['admin', 'manager']);
}

// Форматирование цены
function format_price($price) {
    return number_format($price, 2, ',', ' ') . ' ₽';
}

// Загрузка изображения
function upload_image($file, $upload_dir = 'Uploads/') {
    // Log file details for debugging
    $log = "Upload attempt: " . print_r($file, true) . "\n";
    file_put_contents('upload_log.txt', $log, FILE_APPEND);

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = 'Ошибка загрузки файла: код ' . $file['error'];
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        return ['success' => false, 'error' => $error_msg];
    }

    // Validate file size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $error_msg = 'Файл слишком большой (максимум 5MB)';
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        return ['success' => false, 'error' => $error_msg];
    }

    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $error_msg = 'Не удалось создать папку ' . $upload_dir;
            file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
            return ['success' => false, 'error' => $error_msg];
        }
    }

    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        $error_msg = 'Папка ' . $upload_dir . ' недоступна для записи';
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        return ['success' => false, 'error' => $error_msg];
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime_type, $allowed_types)) {
        $error_msg = 'Неподдерживаемый формат файла: ' . $mime_type;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        return ['success' => false, 'error' => $error_msg];
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . strtolower($ext);
    $destination = $upload_dir . $filename;

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        file_put_contents('upload_log.txt', "File moved to: $destination\n", FILE_APPEND);
        return ['success' => true, 'filename' => $filename];
    } else {
        $error_msg = 'Не удалось переместить файл в ' . $destination;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        return ['success' => false, 'error' => $error_msg];
    }
}

// Получение брендов
function get_brands($conn) {
    $sql = "SELECT id, name FROM brands ORDER BY name";
    $result = $conn->query($sql);
    $brands = [];
    while ($row = $result->fetch_assoc()) {
        $brands[] = $row;
    }
    return $brands;
}

// Получение категорий
function get_categories($conn) {
    $sql = "SELECT id, name FROM categories ORDER BY name";
    $result = $conn->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

// Получение автомобилей
function get_cars($conn, $limit = null, $offset = 0) {
    $sql = "SELECT c.id, c.model, c.year, c.price, c.image, b.name AS brand_name, cat.name AS category_name 
            FROM cars c 
            JOIN brands b ON c.brand_id = b.id 
            JOIN categories cat ON c.category_id = cat.id 
            WHERE c.is_available = TRUE
            ORDER BY c.created_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    if ($limit !== null) {
        $stmt->bind_param('ii', $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $cars = [];
    while ($row = $result->fetch_assoc()) {
        $cars[] = $row;
    }
    $stmt->close();
    return $cars;
}

// Проверка авторизации
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

// Проверка уникальности пользователя
function is_user_exists($conn, $username, $email) {
    $sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count > 0;
}

// Форматирование даты
function format_datetime($datetime) {
    return (new DateTime($datetime))->format('d.m.Y H:i');
}

// Количество в сравнении
function get_comparison_count($conn) {
    $count = 0;
    if (is_authenticated()) {
        $user_id = (int)$_SESSION['user_id'];
        $sql = "SELECT COUNT(*) AS count FROM comparisons WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Ошибка подготовки запроса: " . $conn->error);
        }
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }
    return $count;
}

// Пагинация
function generate_pagination($conn, $table, $base_url, $items_per_page = 10, $conditions = '') {
    $sql = "SELECT COUNT(*) FROM $table" . ($conditions ? " WHERE $conditions" : "");
    $result = $conn->query($sql);
    if ($result === false) {
        die("Ошибка подсчета записей: " . $conn->error);
    }
    $total_items = $result->fetch_row()[0];
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $current_page = max(1, min($current_page, $total_pages));

    $pagination = '<div class="pagination">';
    if ($current_page > 1) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '">Назад</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $pagination .= '<a href="' . $base_url . '?page=' . $i . '"' . ($i === $current_page ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '">Вперед</a>';
    }
    $pagination .= '</div>';

    return [
        'html' => $pagination,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'offset' => ($current_page - 1) * $items_per_page
    ];
}

// Проверка возраста пользователя
function is_user_of_age($conn, $user_id) {
    $sql = "SELECT birth_date FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $birth_date = $stmt->get_result()->fetch_assoc()['birth_date'];
    $stmt->close();
    if (!$birth_date) {
        return false;
    }
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
    return $age >= 18;
}

// Проверка лимита покупок
function can_purchase_car($conn, $user_id) {
    $sql = "SELECT COUNT(*) FROM purchases WHERE user_id = ? AND status = 'approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count === 0;
}

// Проверка доступности автомобиля
function is_car_available($conn, $car_id) {
    $sql = "SELECT is_available FROM cars WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param('i', $car_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result && $result['is_available'];
}

// Проверка дублирующих заявок на тест-драйв
function has_active_test_drive($conn, $user_id, $car_id) {
    $sql = "SELECT COUNT(*) FROM test_drive_requests 
            WHERE user_id = ? AND car_id = ? AND status IN ('pending', 'approved')";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    $stmt->bind_param('ii', $user_id, $car_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count > 0;
}
?>