<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - AutoDealer</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main>
        <section class="section">
            <div class="container">
                <h1>Контакты</h1>
                
                <div class="contact-grid">
                    <div class="contact-info">
                        <h2>Наши контакты</h2>
                        <div class="contact-item">
                            <div class="contact-icon">
                            </div>
                            <div class="contact-text">
                                <h3>Адрес</h3>
                                <p>г. Москва, ул. Автозаводская, 12</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                            </div>
                            <div class="contact-text">
                                <h3>Телефон</h3>
                                <p><a href="tel:+12345678900">+1 (234) 567-89-00</a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                            </div>
                            <div class="contact-text">
                                <h3>Email</h3>
                                <p><a href="mailto:info@autodealer.com">info@autodealer.com</a></p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                            </div>
                            <div class="contact-text">
                                <h3>Часы работы</h3>
                                <p>Пн-Пт: 9:00 - 19:00</p>
                                <p>Сб: 10:00 - 17:00</p>
                                <p>Вс: выходной</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-form">
                        <h2>Напишите нам</h2>
                        <form id="contactForm" action="api/send_contact_message.php" method="POST">
                            <div class="form-group">
                                <label for="contact-name">Ваше имя</label>
                                <input type="text" id="contact-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="contact-email">Ваш Email</label>
                                <input type="email" id="contact-email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="contact-phone">Телефон</label>
                                <input type="tel" id="contact-phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="contact-subject">Тема</label>
                                <select id="contact-subject" name="subject">
                                    <option value="question">Общий вопрос</option>
                                    <option value="test-drive">Запись на тест-драйв</option>
                                    <option value="service">Сервисное обслуживание</option>
                                    <option value="other">Другое</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="contact-message">Сообщение</label>
                                <textarea id="contact-message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn">Отправить сообщение</button>
                        </form>
                    </div>
                </div>
                
                <div class="map-container">
                    <h2>Мы на карте</h2>
                    <div class="map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2245.372951435678!2d37.61711931593095!3d55.755826980553606!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54a5a738fa419%3A0x7c347d506f52311f!2z0JrRgNCw0YHQvdC-0Y_RgNGB0LosINCc0L7RgdC60LLQsA!5e0!3m2!1sru!2sru!4v1620000000000!5m2!1sru!2sru" 
                                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

 <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/scripts.js"></script>
    <script>
        // Обработка формы обратной связи
        document.getElementById('contactForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ваше сообщение успешно отправлено! Мы свяжемся с вами в ближайшее время.');
                    this.reset();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>