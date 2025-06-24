<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
// Проверка прав администратора
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    $carId = intval($_DELETE['car_id']);
    
    try {
        $conn->begin_transaction();
        
        // Получаем пути изображений для удаления
        $images = $conn->query("SELECT image_path FROM car_images WHERE car_id = $carId");
        $imagePaths = [];
        while ($row = $images->fetch_assoc()) {
            $imagePaths[] = '../../images/cars/' . $row['image_path'];
        }
        
        // Удаляем связанные записи
        $conn->query("DELETE FROM car_features WHERE car_id = $carId");
        $conn->query("DELETE FROM car_images WHERE car_id = $carId");
        $conn->query("DELETE FROM favorites WHERE car_id = $carId");
        $conn->query("DELETE FROM reviews WHERE car_id = $carId");
        
        // Обновляем связанные заказы
        $conn->query("UPDATE orders SET car_id = NULL WHERE car_id = $carId");
        $conn->query("UPDATE test_drive_requests SET car_id = NULL WHERE car_id = $carId");
        $conn->query("UPDATE service_requests SET car_id = NULL WHERE car_id = $carId");
        $conn->query("UPDATE sales_department SET car_id = NULL WHERE car_id = $carId");
        
        // Удаляем автомобиль
        $conn->query("DELETE FROM cars WHERE car_id = $carId");
        
        $conn->commit();
        
        // Удаляем файлы изображений
        foreach ($imagePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>