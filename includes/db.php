<?php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Временно включить для отладки
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php_error.log'); // Укажите путь к лог-файлу

$host = 'localhost';
$dbname = 'autosalon';
$user = 'root';
$password = 'root';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    error_log("Ошибка подключения к базе данных: " . $conn->connect_error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к базе данных']);
    exit;
}

$conn->set_charset('utf8mb4');