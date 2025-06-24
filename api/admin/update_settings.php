<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $siteName = $conn->real_escape_string($_POST['site_name'] ?? 'AutoDealer');
    $adminEmail = $conn->real_escape_string($_POST['admin_email'] ?? '');
    $itemsPerPage = intval($_POST['items_per_page'] ?? 20);
    $defaultCurrency = $conn->real_escape_string($_POST['default_currency'] ?? 'USD');

    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        // Обновление режима обслуживания
        $stmt->bind_param("sss", $settingName, $settingValue, $settingValue);
        $settingName = 'maintenance_mode';
        $settingValue = $maintenanceMode;
        $stmt->execute();
        
        // Обновление других настроек
        $settingName = 'site_name';
        $settingValue = $siteName;
        $stmt->execute();
        
        $settingName = 'admin_email';
        $settingValue = $adminEmail;
        $stmt->execute();
        
        $settingName = 'items_per_page';
        $settingValue = $itemsPerPage;
        $stmt->execute();
        
        $settingName = 'default_currency';
        $settingValue = $defaultCurrency;
        $stmt->execute();
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Настройки сохранены']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неправильный метод запроса']);
}
?>