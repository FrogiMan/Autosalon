<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="services">
            <h2>Наши услуги</h2>
            <div class="service-item">
                <h3>Продажа автомобилей</h3>
                <p>Широкий выбор новых и подержанных автомобилей от ведущих мировых брендов. Персональный подбор под ваши потребности.</p>
            </div>
            <div class="service-item">
                <h3>Тест-драйв</h3>
                <p>Запишитесь на тест-драйв, чтобы оценить автомобиль перед покупкой. Удобное время и профессиональное сопровождение.</p>
                <a href="catalog.php" class="btn">Выбрать автомобиль</a>
            </div>
            <div class="service-item">
                <h3>Сервисное обслуживание</h3>
                <p>Качественное техническое обслуживание и ремонт автомобилей с использованием оригинальных запчастей.</p>
            </div>
            <div class="service-item">
                <h3>Кредитование и лизинг</h3>
                <p>Гибкие условия автокредитования и лизинга с минимальными процентными ставками. Помощь в оформлении.</p>
            </div>
            <div class="service-item">
                <h3>Страхование</h3>
                <p>Оформление автострахования (ОСАГО, КАСКО) на выгодных условиях через наших партнеров.</p>
            </div>
            <div class="service-item">
                <h3>Trade-in</h3>
                <p>Обменяйте свой старый автомобиль на новый с доплатой. Быстрая оценка и прозрачные условия.</p>
            </div>
            <p>Для получения дополнительной информации свяжитесь с нами через <a href="contacts.php">форму обратной связи</a>.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>