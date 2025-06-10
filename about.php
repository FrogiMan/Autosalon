<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="about">
            <h2>О нас</h2>
            <p>Наш автосалон работает с 2010 года, предоставляя клиентам лучшие автомобили от ведущих мировых брендов. Мы гордимся качеством обслуживания и индивидуальным подходом к каждому клиенту.</p>
            <h3>Наши преимущества</h3>
            <ul>
                <li>Широкий выбор автомобилей в наличии.</li>
                <li>Гарантия на все автомобили.</li>
                <li>Профессиональная консультация и поддержка.</li>
                <li>Гибкие условия покупки и кредитования.</li>
            </ul>
            <h3>Наша миссия</h3>
            <p>Сделать покупку автомобиля простой, удобной и приятной для каждого клиента.</p>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>