<?php
// api/admin/edit_car.php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carId = intval($_POST['car_id']);
    
    if (!$carId) {
        echo json_encode(['success' => false, 'message' => 'ID автомобиля не указан']);
        exit;
    }

    try {
        $conn->begin_transaction();
        
        // Обновление основных данных
        $stmt = $conn->prepare(
            "UPDATE cars SET make = ?, model = ?, year = ?, price = ?, mileage = ?, body_type = ?, description = ?, status = ? WHERE car_id = ?"
        );
        $make = $_POST['make'];
        $model = $_POST['model'];
        $year = intval($_POST['year']);
        $price = floatval($_POST['price']);
        $mileage = isset($_POST['mileage']) ? intval($_POST['mileage']) : null;
        $bodyType = $_POST['body_type'] ?? 'sedan';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'available';
        
        $stmt->bind_param(
            "ssiddsssi",
            $make,
            $model,
            $year,
            $price,
            $mileage,
            $bodyType,
            $description,
            $status,
            $carId
        );
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при обновлении автомобиля");
        }
        
        // Обновление характеристик
        $featStmt = $conn->prepare(
            "INSERT INTO car_features (car_id, engine_type, engine_volume, power, transmission, drive_type, color, interior, fuel_consumption) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE 
             engine_type = VALUES(engine_type), 
             engine_volume = VALUES(engine_volume), 
             power = VALUES(power), 
             transmission = VALUES(transmission), 
             drive_type = VALUES(drive_type), 
             color = VALUES(color), 
             interior = VALUES(interior), 
             fuel_consumption = VALUES(fuel_consumption)"
        );
        $engineType = $_POST['engine_type'] ?? '';
        $engineVolume = isset($_POST['engine_volume']) ? floatval($_POST['engine_volume']) : null;
        $power = isset($_POST['power']) ? intval($_POST['power']) : null;
        $transmission = $_POST['transmission'] ?? 'automatic';
        $driveType = $_POST['drive_type'] ?? 'front';
        $color = $_POST['color'] ?? '';
        $interior = $_POST['interior'] ?? '';
        $fuelConsumption = isset($_POST['fuel_consumption']) ? floatval($_POST['fuel_consumption']) : null;

        $featStmt->bind_param(
            "ississssd",
            $carId,
            $engineType,
            $engineVolume,
            $power,
            $transmission,
            $driveType,
            $color,
            $interior,
            $fuelConsumption
        );
        if (!$featStmt->execute()) {
            throw new Exception("Ошибка при обновлении характеристик");
        }
        
        // Обработка новых изображений
if (!empty($_FILES['images'])) {
    $uploadDir = '../../images/cars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            // Check if this should be the main image
            $isMain = ($key === 0 && !hasMainImage($carId)) ? 1 : 0;
            
            $imgStmt = $conn->prepare("INSERT INTO car_images (car_id, image_path, is_main) VALUES (?, ?, ?)");
            $imgStmt->bind_param("isi", $carId, $fileName, $isMain);
            if (!$imgStmt->execute()) {
                throw new Exception("Ошибка при добавлении изображения");
            }
        }
    }
}
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Автомобиль успешно обновлен']);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in edit_car: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}

function hasMainImage($carId) {
    global $conn;
    $result = $conn->query("SELECT image_id FROM car_images WHERE car_id = $carId AND is_main = 1");
    return $result->num_rows > 0;
}
?>