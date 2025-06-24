<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Простая проверка данных
    if (!empty($name) && !empty($email) && !empty($message)) {
        // Сохранение или отправка сообщения (например, в базу данных или на email)
        echo "Спасибо, $name! Ваше сообщение успешно отправлено.";
    } else {
        echo "Пожалуйста, заполните все поля формы.";
    }
} else {
    echo "Некорректный запрос.";
}
?>
