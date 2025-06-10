<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $message = sanitize_input($_POST['message']);
    
    if ($name && validate_email($email) && $message) {
        // Здесь можно добавить отправку email или запись в базу данных
        $success = 'Ваше сообщение отправлено!';
    } else {
        $error = 'Заполните все поля корректно.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="contacts">
            <h2>Контакты</h2>
            <div class="contact-info">
                <p><strong>Телефон:</strong> +7 (495) 123-45-67</p>
                <p><strong>Email:</strong> info@autosalon.ru</p>
                <p><strong>Адрес:</strong> г. Москва, ул. Автомобильная, д. 10</p>
                <p><strong>График работы:</strong> Пн-Пт: 9:00-20:00, Сб-Вс: 10:00-18:00</p>
            </div>
            <div class="contact-form">
                <h3>Обратная связь</h3>
                <?php if ($success): ?>
                    <p class="success"><?php echo $success; ?></p>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST" action="contacts.php">
                    <label for="name">Имя:</label>
                    <input type="text" id="name" name="name" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="message">Сообщение:</label>
                    <textarea id="message" name="message" required></textarea>
                    <button type="submit" class="btn">Отправить</button>
                </form>
            </div>
            <div class="map">
                <h3>Мы на карте</h3>
                <!-- Вставьте iframe с Google Maps или Яндекс.Карты -->
                <iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>