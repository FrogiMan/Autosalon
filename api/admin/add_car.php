<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Основные данные
        $stmt = $conn->prepare(
            "INSERT INTO cars (make, model, year, price, mileage, body_type, description, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
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
            "ssiddsss",
            $make,
            $model,
            $year,
            $price,
            $mileage,
            $bodyType,
            $description,
            $status
        );
        if (!$stmt->execute()) {
            throw new Exception("Ошибка при добавлении автомобиля");
        }
        $carId = $conn->insert_id;

        // Характеристики
        $featStmt = $conn->prepare(
            "INSERT INTO car_features (car_id, engine_type, engine_volume, power, transmission, drive_type, color, interior, fuel_consumption) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
            throw new Exception("Ошибка при добавления характеристик");
        }

        // Изображения
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
        echo json_encode(['success' => true, 'car_id' => $carId]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in add_car: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>