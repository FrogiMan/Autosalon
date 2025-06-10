<?php
$host = 'localhost';
$dbname = 'car_dealership';
$username = 'root';
$password = 'root';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Ошибка подключения к базе данных: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
?>