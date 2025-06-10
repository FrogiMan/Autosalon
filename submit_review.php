<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $rating = (int)$_POST['rating'];
    $comment = sanitize_input($_POST['comment']);
    $user_id = (int)$_SESSION['user_id'];

    if ($car_id && $rating >= 1 && $rating <= 5 && $comment) {
        $sql = "INSERT INTO reviews (car_id, user_id, rating, comment, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiis', $car_id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            $success = 'Отзыв отправлен на модерацию.';
        } else {
            $error = 'Ошибка при отправке отзыва.';
        }
        $stmt->close();
    } else {
        $error = 'Заполните все поля корректно.';
    }
}
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="review-result">
            <h2>Отправка отзыва</h2>
            <?php if ($success): ?>
                <p class="success"><?php echo $success; ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <a href="reviews.php" class="btn">Вернуться к отзывам</a>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>