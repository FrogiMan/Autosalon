<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!is_authenticated()) {
    header('Location: login.php');
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="purchase-confirmation">
            <h2>Подтверждение покупки</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <p class="message"><?php echo htmlspecialchars($_SESSION['message']); ?></p>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            <p>Ваша заявка на покупку отправлена на рассмотрение. Вы можете проверить статус в <a href="profile.php">личном кабинете</a>.</p>
            <a href="catalog.php" class="btn">Вернуться в каталог</a>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>