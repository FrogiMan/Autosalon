<?php
// Основные настройки приложения
define('BASE_URL', 'http://localhost/autosalon/');
define('UPLOADS_DIR', __DIR__ . '/../Uploads/');
define('CAR_IMAGES_DIR', UPLOADS_DIR . 'cars/');
define('TRADE_IN_IMAGES_DIR', UPLOADS_DIR . 'trade_ins/');

// Настройки отображения ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Настройки сессии
session_start();

// Подключение автозагрузки классов
spl_autoload_register(function ($class_name) {
    $paths = [
        __DIR__ . '/../includes/',
        __DIR__ . '/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});
?>