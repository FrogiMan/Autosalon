<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    $_SESSION['message'] = 'Войдите, чтобы добавить автомобиль в сравнение.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $user_id = (int)$_SESSION['user_id'];

    if ($car_id) {
        // Check if car exists
        $sql = "SELECT id FROM cars WHERE id = ? AND is_available = TRUE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $car_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $_SESSION['message'] = 'Автомобиль не найден или недоступен.';
            header('Location: catalog.php');
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Check if already in comparison
        $sql = "SELECT COUNT(*) FROM comparisons WHERE user_id = ? AND car_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $car_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if ($count === 0) {
            // Add to comparisons
            $sql = "INSERT INTO comparisons (user_id, car_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $user_id, $car_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Автомобиль добавлен в сравнение.';
            } else {
                $_SESSION['message'] = 'Ошибка при добавлении в сравнение.';
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Автомобиль уже в сравнении.';
        }
    } else {
        $_SESSION['message'] = 'Неверный идентификатор автомобиля.';
    }
}

header('Location: catalog.php');
exit;
?>