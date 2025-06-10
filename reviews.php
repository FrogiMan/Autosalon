<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Получение утвержденных отзывов
$sql = "SELECT r.id, r.rating, r.comment, r.created_at, c.model, b.name AS brand_name, u.username 
        FROM reviews r 
        JOIN cars c ON r.car_id = c.id 
        JOIN brands b ON c.brand_id = b.id 
        JOIN users u ON r.user_id = u.id 
        WHERE r.status = 'approved'";
$result = $conn->query($sql);

// Получение автомобилей для формы
$cars = $conn->query("SELECT c.id, b.name AS brand_name, c.model 
                      FROM cars c JOIN brands b ON c.brand_id = b.id");
?>

<?php include 'includes/header.php'; ?>

<main>
    <div class="container">
        <section class="reviews">
            <h2>Отзывы клиентов</h2>
            <div class="reviews-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="review">
                        <h3><?php echo htmlspecialchars($row['brand_name'] . ' ' . $row['model']); ?></h3>
                        <p><strong><?php echo htmlspecialchars($row['username']); ?>:</strong> <?php echo htmlspecialchars($row['comment']); ?></p>
                        <p>Рейтинг: <?php echo $row['rating']; ?>/5</p>
                        <p>Дата: <?php echo $row['created_at']; ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php if (is_authenticated()): ?>
                <h3>Оставить отзыв</h3>
                <form action="submit_review.php" method="POST">
                    <label for="car_id">Автомобиль:</label>
                    <select id="car_id" name="car_id" required>
                        <option value="">Выберите автомобиль</option>
                        <?php while ($car = $cars->fetch_assoc()): ?>
                            <option value="<?php echo $car['id']; ?>">
                                <?php echo htmlspecialchars($car['brand_name'] . ' ' . $car['model']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="rating">Рейтинг:</label>
                    <select id="rating" name="rating" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <label for="comment">Комментарий:</label>
                    <textarea id="comment" name="comment" required></textarea>
                    <button type="submit" class="btn">Отправить</button>
                </form>
            <?php else: ?>
                <p><a href="login.php?message=<?php echo urlencode('Войдите, чтобы оставить отзыв.'); ?>">Войдите</a>, чтобы оставить отзыв.</p>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<?php $conn->close(); ?>